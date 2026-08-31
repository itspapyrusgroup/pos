<?php

namespace App\Services\Sync;

use App\Models\SyncPushCursor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class SyncPushService
{
    public function pushToCloud(?string $trigger = null): array
    {
        if (!(bool) config('sync.enabled')) {
            return [
                'ok' => false,
                'message' => 'SYNC_ENABLED=false',
                'sent_rows' => 0,
                'datasets' => [],
            ];
        }

        $role = (string) config('sync.role');
        if (!in_array($role, ['sender', 'both'], true)) {
            return [
                'ok' => false,
                'message' => 'SYNC_ROLE bukan sender/both',
                'sent_rows' => 0,
                'datasets' => [],
            ];
        }

        $url = trim((string) config('sync.cloud_push_url'));
        $key = (string) config('sync.api_key');
        if ($url === '' || $key === '') {
            return [
                'ok' => false,
                'message' => 'SYNC_CLOUD_PUSH_URL atau SYNC_API_KEY belum diset',
                'sent_rows' => 0,
                'datasets' => [],
            ];
        }

        $target = (string) config('sync.target_name', 'cloud');
        $batchSize = max(1, (int) config('sync.batch_size_per_dataset', 300));
        $datasets = (array) config('sync.datasets', []);

        $payloadDatasets = [];
        $cursorUpdates = [];
        $sentRows = 0;

        foreach ($datasets as $name => $def) {
            $table = (string) ($def['table'] ?? '');
            $primaryKey = (string) ($def['primary_key'] ?? 'id');
            if ($table === '') {
                continue;
            }

            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            if (!in_array($primaryKey, $columns, true) || !in_array('updated_at', $columns, true)) {
                continue;
            }

            $cursor = SyncPushCursor::query()->firstOrCreate(
                ['target' => $target, 'dataset' => (string) $name],
                ['last_pk' => 0]
            );

            $query = DB::table($table)
                ->orderBy('updated_at')
                ->orderBy($primaryKey);

            if ($cursor->last_updated_at) {
                $cursorTime = $cursor->last_updated_at->toDateTimeString();
                $cursorPk = (int) ($cursor->last_pk ?? 0);

                $query->where(function ($q) use ($cursorTime, $cursorPk, $primaryKey) {
                    $q->where('updated_at', '>', $cursorTime)
                        ->orWhere(function ($inner) use ($cursorTime, $cursorPk, $primaryKey) {
                            $inner->where('updated_at', '=', $cursorTime)
                                ->where($primaryKey, '>', $cursorPk);
                        });
                });
            }

            $rows = $query->limit($batchSize)->get();
            if ($rows->isEmpty()) {
                continue;
            }

            $normalizedRows = $rows->map(function ($row) {
                $arr = (array) $row;
                foreach ($arr as $key => $value) {
                    if ($value instanceof Carbon) {
                        $arr[$key] = $value->toDateTimeString();
                    }
                }
                return $arr;
            })->values()->all();

            $lastRow = (array) $rows->last();
            $lastUpdatedAt = $lastRow['updated_at'] ?? null;
            $lastPk = (int) ($lastRow[$primaryKey] ?? 0);

            $payloadDatasets[] = [
                'name' => (string) $name,
                'rows' => $normalizedRows,
            ];
            $cursorUpdates[(string) $name] = [
                'last_updated_at' => $lastUpdatedAt ? Carbon::parse((string) $lastUpdatedAt) : null,
                'last_pk' => $lastPk,
                'last_sent_rows' => count($normalizedRows),
            ];
            $sentRows += count($normalizedRows);
        }

        if (empty($payloadDatasets)) {
            SyncPushCursor::query()
                ->where('target', $target)
                ->whereIn('dataset', array_keys($datasets))
                ->update([
                    'last_error' => null,
                    'last_success_at' => now(),
                ]);

            $result = [
                'ok' => true,
                'message' => 'Tidak ada perubahan baru',
                'sent_rows' => 0,
                'datasets' => [],
            ];
            Cache::put('sync:last_push_result', array_merge($result, [
                'at' => now()->toDateTimeString(),
                'url' => $url,
            ]), now()->addDay());
            return [
                ...$result,
            ];
        }

        $payload = [
            'sender' => [
                'app_name' => (string) config('app.name'),
                'app_url' => (string) config('app.url'),
                'mode' => (string) config('sync.app_mode'),
            ],
            'trigger' => $trigger ?: 'auto',
            'sent_at' => now()->toIso8601String(),
            'datasets' => $payloadDatasets,
        ];

        try {
            $response = $this->requestWithRetry('post', $url, $key, $payload);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'Cloud sync gagal. HTTP ' . $response->status() . ': ' . mb_substr((string) $response->body(), 0, 700)
                );
            }

            DB::transaction(function () use ($cursorUpdates, $target) {
                foreach ($cursorUpdates as $dataset => $next) {
                    SyncPushCursor::query()->updateOrCreate(
                        ['target' => $target, 'dataset' => $dataset],
                        [
                            'last_updated_at' => $next['last_updated_at'],
                            'last_pk' => (int) ($next['last_pk'] ?? 0),
                            'last_sent_rows' => (int) ($next['last_sent_rows'] ?? 0),
                            'last_success_at' => now(),
                            'last_error' => null,
                        ]
                    );
                }
            });

            $result = [
                'ok' => true,
                'message' => 'Sinkronisasi ke cloud berhasil',
                'sent_rows' => $sentRows,
                'datasets' => array_keys($cursorUpdates),
                'response' => $response->json(),
            ];
            Cache::put('sync:last_push_result', array_merge($result, [
                'at' => now()->toDateTimeString(),
                'url' => $url,
            ]), now()->addDay());

            return $result;
        } catch (Throwable $e) {
            $errorMessage = $this->formatTransportError($e, $url);
            $affectedDatasets = array_keys($cursorUpdates);
            foreach ($affectedDatasets as $datasetName) {
                SyncPushCursor::query()->updateOrCreate(
                    ['target' => $target, 'dataset' => (string) $datasetName],
                    ['last_error' => mb_substr($errorMessage, 0, 500)]
                );
            }

            Log::error('sync.push.failed', [
                'message' => $errorMessage,
                'url' => $url,
                'datasets' => $affectedDatasets,
                'exception' => $e::class,
            ]);

            $result = [
                'ok' => false,
                'message' => $errorMessage,
                'sent_rows' => $sentRows,
                'datasets' => $affectedDatasets,
            ];
            Cache::put('sync:last_push_result', array_merge($result, [
                'at' => now()->toDateTimeString(),
                'url' => $url,
            ]), now()->addDay());
            return $result;
        }
    }

    private function requestWithRetry(string $method, string $url, string $apiKey, array $payload)
    {
        $attempts = max(1, (int) config('sync.http_retry_attempts', 3));
        $sleepMs = max(100, (int) config('sync.http_retry_sleep_ms', 800));
        $timeout = max(3, (int) config('sync.http_timeout_seconds', 20));

        $lastException = null;
        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $request = Http::acceptJson()
                    ->withHeaders(['X-Sync-Key' => $apiKey])
                    ->timeout($timeout);

                $response = $method === 'post'
                    ? $request->post($url, $payload)
                    : $request->get($url, $payload);

                if ($response->successful()) {
                    return $response;
                }

                if ($response->status() >= 400 && $response->status() < 500) {
                    // 4xx biasanya tidak akan membaik dengan retry.
                    return $response;
                }

                if ($i < $attempts) {
                    usleep($sleepMs * 1000);
                }
            } catch (Throwable $e) {
                $lastException = $e;
                if ($i < $attempts) {
                    usleep($sleepMs * 1000);
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        throw new RuntimeException('Request sync gagal setelah retry.');
    }

    private function formatTransportError(Throwable $e, string $url): string
    {
        $raw = (string) $e->getMessage();
        $lower = strtolower($raw);

        if (str_contains($lower, 'could not resolve host')) {
            return 'DNS resolve gagal untuk host endpoint sync: ' . $url . ' | ' . $raw;
        }
        if (str_contains($lower, 'ssl') || str_contains($lower, 'certificate') || str_contains($lower, 'tls')) {
            return 'SSL/TLS endpoint sync bermasalah: ' . $url . ' | ' . $raw;
        }
        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout')) {
            return 'Request sync timeout ke endpoint: ' . $url . ' | ' . $raw;
        }

        return $raw;
    }
}
