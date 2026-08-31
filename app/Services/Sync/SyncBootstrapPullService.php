<?php

namespace App\Services\Sync;

use App\Models\SyncPushCursor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncBootstrapPullService
{
    public function pullMasterFromCloud(?int $cabangId = null, ?string $trigger = null, ?string $mode = null): array
    {
        if (!(bool) config('sync.enabled')) {
            return [
                'ok' => false,
                'message' => 'SYNC_ENABLED=false',
                'applied_rows' => 0,
                'datasets' => [],
            ];
        }

        $resolvedMode = strtolower(trim((string) ($mode ?: config('sync.pull_master_mode', 'incremental'))));
        if ($resolvedMode === 'incremental') {
            return $this->pullIncremental($cabangId, $trigger);
        }

        return $this->pullBootstrap($cabangId, $trigger);
    }

    private function pullBootstrap(?int $cabangId = null, ?string $trigger = null): array
    {
        $role = (string) config('sync.role');
        if (!in_array($role, ['sender', 'both'], true)) {
            return [
                'ok' => false,
                'message' => 'SYNC_ROLE bukan sender/both',
                'applied_rows' => 0,
                'datasets' => [],
            ];
        }

        $url = trim((string) config('sync.cloud_bootstrap_url'));
        if ($url === '') {
            return [
                'ok' => false,
                'message' => 'SYNC_CLOUD_BOOTSTRAP_URL belum diset',
                'applied_rows' => 0,
                'datasets' => [],
            ];
        }

        $key = (string) config('sync.api_key');
        if ($key === '') {
            return [
                'ok' => false,
                'message' => 'SYNC_API_KEY belum diset',
                'applied_rows' => 0,
                'datasets' => [],
            ];
        }

        try {
            $query = [];
            if ((int) $cabangId > 0) {
                $query['cabang_id'] = (int) $cabangId;
            }
            if ($trigger) {
                $query['trigger'] = $trigger;
            }

            $response = $this->requestWithRetry('get', $url, $key, $query);

            if (!$response->successful()) {
                $status = $response->status();
                $body = mb_substr((string) $response->body(), 0, 700);
                if ($status === 404) {
                    throw new RuntimeException(
                        'Bootstrap endpoint tidak ditemukan (404). Pastikan cloud sudah deploy versi terbaru dan route GET /api/sync/bootstrap/master tersedia. Body: ' . $body
                    );
                }
                if ($status === 401 || $status === 403) {
                    throw new RuntimeException(
                        'Bootstrap ditolak (' . $status . '). Cek SYNC_API_KEY di local & cloud, dan SYNC_ROLE cloud harus receiver/both. Body: ' . $body
                    );
                }
                throw new RuntimeException(
                    'Bootstrap pull gagal. HTTP ' . $status . ': ' . $body
                );
            }

            $payload = $response->json();
            $datasets = $payload['datasets'] ?? [];
            if (!is_array($datasets)) {
                throw new RuntimeException('Format datasets bootstrap tidak valid.');
            }

            $summary = app(SyncReceiveService::class)->ingest($datasets, 'sync.bootstrap_datasets');
            $result = [
                'ok' => true,
                'message' => 'Bootstrap master dari cloud berhasil.',
                'applied_rows' => (int) ($summary['applied_rows'] ?? 0),
                'datasets' => array_keys((array) ($summary['datasets'] ?? [])),
                'dataset_rows' => (array) ($summary['datasets'] ?? []),
            ];
            Cache::put('sync:last_bootstrap_result', array_merge($result, [
                'at' => now()->toDateTimeString(),
                'url' => $url,
                'cabang_id' => $cabangId,
                'mode' => 'bootstrap',
            ]), now()->addDay());
            return $result;
        } catch (Throwable $e) {
            $errorMessage = $this->formatTransportError($e, $url);
            Log::error('sync.bootstrap.failed', [
                'message' => $errorMessage,
                'url' => $url,
                'cabang_id' => $cabangId,
                'exception' => $e::class,
            ]);
            $result = [
                'ok' => false,
                'message' => $errorMessage,
                'applied_rows' => 0,
                'datasets' => [],
            ];
            Cache::put('sync:last_bootstrap_result', array_merge($result, [
                'at' => now()->toDateTimeString(),
                'url' => $url,
                'cabang_id' => $cabangId,
                'mode' => 'bootstrap',
            ]), now()->addDay());
            return $result;
        }
    }

    private function pullIncremental(?int $cabangId = null, ?string $trigger = null): array
    {
        $role = (string) config('sync.role');
        if (!in_array($role, ['sender', 'both'], true)) {
            return [
                'ok' => false,
                'message' => 'SYNC_ROLE bukan sender/both',
                'applied_rows' => 0,
                'datasets' => [],
            ];
        }

        $url = trim((string) config('sync.cloud_master_pull_url'));
        if ($url === '') {
            $url = trim((string) config('sync.cloud_bootstrap_url'));
        }
        if ($url === '') {
            return [
                'ok' => false,
                'message' => 'SYNC_CLOUD_MASTER_PULL_URL/SYNC_CLOUD_BOOTSTRAP_URL belum diset',
                'applied_rows' => 0,
                'datasets' => [],
            ];
        }

        $key = (string) config('sync.api_key');
        if ($key === '') {
            return [
                'ok' => false,
                'message' => 'SYNC_API_KEY belum diset',
                'applied_rows' => 0,
                'datasets' => [],
            ];
        }

        $target = (string) config('sync.pull_master_target_name', 'cloud_master');
        $datasetDefs = (array) config('sync.bootstrap_datasets', []);
        $datasetNames = array_keys($datasetDefs);

        try {
            $cursorRows = SyncPushCursor::query()
                ->where('target', $target)
                ->whereIn('dataset', $datasetNames)
                ->get()
                ->keyBy('dataset');

            $cursors = [];
            foreach ($datasetNames as $datasetName) {
                $row = $cursorRows->get($datasetName);
                if (!$row) {
                    continue;
                }

                $updatedAt = $row->last_updated_at ? $row->last_updated_at->toDateTimeString() : '';
                if ($updatedAt === '' && (int) ($row->last_pk ?? 0) <= 0) {
                    continue;
                }

                $cursors[$datasetName] = [
                    'updated_at' => $updatedAt,
                    'pk' => (int) ($row->last_pk ?? 0),
                ];
            }

            $payload = [
                'cabang_id' => (int) $cabangId > 0 ? (int) $cabangId : null,
                'trigger' => $trigger ?: 'auto',
                'cursors' => $cursors,
            ];

            $response = $this->requestWithRetry('post', $url, $key, $payload);
            if (!$response->successful()) {
                $status = $response->status();
                $body = mb_substr((string) $response->body(), 0, 700);
                if ($status === 404) {
                    throw new RuntimeException(
                        'Endpoint pull incremental tidak ditemukan (404). Pastikan cloud sudah deploy route POST /api/sync/pull/master. Body: ' . $body
                    );
                }
                if ($status === 401 || $status === 403) {
                    throw new RuntimeException(
                        'Pull incremental ditolak (' . $status . '). Cek SYNC_API_KEY di local & cloud, dan SYNC_ROLE cloud harus receiver/both. Body: ' . $body
                    );
                }
                throw new RuntimeException('Pull incremental gagal. HTTP ' . $status . ': ' . $body);
            }

            $payload = $response->json();
            $datasets = $payload['datasets'] ?? [];
            $nextCursors = (array) ($payload['next_cursors'] ?? []);
            $remoteWarnings = array_values(array_filter((array) ($payload['warnings'] ?? []), fn ($v) => is_string($v) && $v !== ''));
            if (!is_array($datasets)) {
                throw new RuntimeException('Format datasets incremental tidak valid.');
            }

            $summary = app(SyncReceiveService::class)->ingest($datasets, 'sync.bootstrap_datasets');

            $receivedRows = [];
            foreach ($datasets as $datasetPayload) {
                $name = (string) ($datasetPayload['name'] ?? '');
                $rows = $datasetPayload['rows'] ?? [];
                if ($name === '' || !is_array($rows)) {
                    continue;
                }
                $receivedRows[$name] = count($rows);
            }

            $warnings = [];
            $maxPartialRetries = max(1, (int) config('sync.pull_partial_max_retries', 3));
            DB::transaction(function () use ($nextCursors, $summary, $receivedRows, $target, $cursors, $maxPartialRetries, &$warnings) {
                foreach ($nextCursors as $dataset => $next) {
                    $received = (int) ($receivedRows[$dataset] ?? 0);
                    $applied = (int) (($summary['datasets'][$dataset] ?? 0));
                    $currentCursor = (array) ($cursors[$dataset] ?? ['updated_at' => '', 'pk' => 0]);
                    $retryKey = 'sync_pull_partial_'
                        . md5($target . '|' . $dataset . '|' . (string) ($currentCursor['updated_at'] ?? '') . '|' . (int) ($currentCursor['pk'] ?? 0));
                    if ($received > 0 && $applied < $received) {
                        $retryCount = (int) Cache::increment($retryKey);
                        if ($retryCount === 1) {
                            Cache::put($retryKey, 1, now()->addDay());
                        }

                        if ($retryCount < $maxPartialRetries) {
                            SyncPushCursor::query()->updateOrCreate(
                                ['target' => $target, 'dataset' => (string) $dataset],
                                ['last_error' => 'Partial apply (' . $applied . '/' . $received . ') retry=' . $retryCount]
                            );
                            $warnings[] = 'Cursor ' . $dataset . ' ditahan (partial apply ' . $applied . '/' . $received . ', retry ' . $retryCount . '/' . $maxPartialRetries . ').';
                            continue;
                        }

                        $warnings[] = 'Cursor ' . $dataset . ' dipaksa maju setelah partial apply berulang (' . $applied . '/' . $received . ').';
                    }

                    $updatedAt = trim((string) ($next['updated_at'] ?? ''));
                    $pk = (int) ($next['pk'] ?? 0);
                    Cache::forget($retryKey);

                    SyncPushCursor::query()->updateOrCreate(
                        ['target' => $target, 'dataset' => (string) $dataset],
                        [
                            'last_updated_at' => $updatedAt !== '' ? Carbon::parse($updatedAt) : null,
                            'last_pk' => $pk,
                            'last_sent_rows' => $received,
                            'last_success_at' => now(),
                            'last_error' => null,
                        ]
                    );
                }
            });

            $result = [
                'ok' => true,
                'message' => 'Pull incremental master dari cloud berhasil.',
                'applied_rows' => (int) ($summary['applied_rows'] ?? 0),
                'datasets' => array_keys((array) ($summary['datasets'] ?? [])),
                'dataset_rows' => (array) ($summary['datasets'] ?? []),
                'warnings' => array_values(array_merge($remoteWarnings, $warnings)),
                'has_more' => (bool) ($payload['has_more'] ?? false),
            ];
            Cache::put('sync:last_bootstrap_result', array_merge($result, [
                'at' => now()->toDateTimeString(),
                'url' => $url,
                'cabang_id' => $cabangId,
                'mode' => 'incremental',
            ]), now()->addDay());
            return $result;
        } catch (Throwable $e) {
            $errorMessage = $this->formatTransportError($e, $url);
            Log::error('sync.pull.incremental.failed', [
                'message' => $errorMessage,
                'url' => $url,
                'cabang_id' => $cabangId,
                'exception' => $e::class,
            ]);
            $result = [
                'ok' => false,
                'message' => $errorMessage,
                'applied_rows' => 0,
                'datasets' => [],
            ];
            Cache::put('sync:last_bootstrap_result', array_merge($result, [
                'at' => now()->toDateTimeString(),
                'url' => $url,
                'cabang_id' => $cabangId,
                'mode' => 'incremental',
            ]), now()->addDay());
            return $result;
        }
    }

    private function requestWithRetry(string $method, string $url, string $apiKey, array $payload)
    {
        $attempts = max(1, (int) config('sync.http_retry_attempts', 3));
        $sleepMs = max(100, (int) config('sync.http_retry_sleep_ms', 800));
        $timeout = max(5, (int) config('sync.http_timeout_seconds', 20));

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

        throw new RuntimeException('Request bootstrap gagal setelah retry.');
    }

    private function formatTransportError(Throwable $e, string $url): string
    {
        $raw = (string) $e->getMessage();
        $lower = strtolower($raw);

        if (str_contains($lower, 'could not resolve host')) {
            return 'DNS resolve gagal untuk host endpoint bootstrap: ' . $url . ' | ' . $raw;
        }
        if (str_contains($lower, 'ssl') || str_contains($lower, 'certificate') || str_contains($lower, 'tls')) {
            return 'SSL/TLS endpoint bootstrap bermasalah: ' . $url . ' | ' . $raw;
        }
        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout')) {
            return 'Request bootstrap timeout ke endpoint: ' . $url . ' | ' . $raw;
        }

        return $raw;
    }
}
