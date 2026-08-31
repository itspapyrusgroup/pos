<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncBootstrapController extends Controller
{
    public function master(Request $request): JsonResponse
    {
        if (!(bool) config('sync.enabled')) {
            return response()->json(['message' => 'Sync disabled'], 503);
        }

        $role = (string) config('sync.role');
        if (!in_array($role, ['receiver', 'both'], true)) {
            return response()->json(['message' => 'Node ini bukan source bootstrap'], 403);
        }

        $expectedKey = (string) config('sync.api_key');
        $providedKey = (string) $request->header('X-Sync-Key', '');
        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            return response()->json(['message' => 'Unauthorized sync key'], 401);
        }

        $cabangId = (int) $request->query('cabang_id', 0);
        $batchSize = max(100, (int) config('sync.bootstrap_batch_size_per_dataset', 2000));
        $datasets = (array) config('sync.bootstrap_datasets', []);

        $result = [];
        $warnings = [];
        foreach ($datasets as $name => $def) {
            $table = (string) ($def['table'] ?? '');
            $primaryKey = (string) ($def['primary_key'] ?? 'id');
            if ($table === '' || !Schema::hasTable($table)) {
                $warnings[] = 'Dataset ' . $name . ' dilewati: tabel tidak ditemukan.';
                continue;
            }

            $columns = Schema::getColumnListing($table);
            if (!in_array($primaryKey, $columns, true)) {
                $warnings[] = 'Dataset ' . $name . ' dilewati: primary key tidak valid.';
                continue;
            }

            $query = DB::table($table)->orderBy($primaryKey)->limit($batchSize);
            $isBranchScoped = (bool) ($def['branch_scoped'] ?? false);
            $cabangColumn = (string) ($def['cabang_column'] ?? 'cabang_id');
            if ($isBranchScoped && $cabangId > 0 && in_array($cabangColumn, $columns, true)) {
                $query->where($cabangColumn, $cabangId);
            }

            $result[] = [
                'name' => (string) $name,
                'rows' => $query->get()->map(fn ($row) => $this->mapSyncRow((string) $name, (array) $row))->values()->all(),
            ];
        }

        return response()->json([
            'message' => 'Bootstrap master data',
            'cabang_id' => $cabangId,
            'datasets' => $result,
            'warnings' => $warnings,
        ]);
    }

    public function masterIncremental(Request $request): JsonResponse
    {
        if (!(bool) config('sync.enabled')) {
            return response()->json(['message' => 'Sync disabled'], 503);
        }

        $role = (string) config('sync.role');
        if (!in_array($role, ['receiver', 'both'], true)) {
            return response()->json(['message' => 'Node ini bukan source bootstrap'], 403);
        }

        $expectedKey = (string) config('sync.api_key');
        $providedKey = (string) $request->header('X-Sync-Key', '');
        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            return response()->json(['message' => 'Unauthorized sync key'], 401);
        }

        $validated = $request->validate([
            'cabang_id' => ['nullable', 'integer', 'min:1'],
            'trigger' => ['nullable', 'string', 'max:20'],
            'cursors' => ['nullable', 'array'],
        ]);

        $cabangId = (int) ($validated['cabang_id'] ?? 0);
        $batchSize = max(50, (int) config('sync.pull_batch_size_per_dataset', 300));
        $datasets = (array) config('sync.bootstrap_datasets', []);
        $incomingCursors = (array) ($validated['cursors'] ?? []);

        $result = [];
        $nextCursors = [];
        $hasMoreAny = false;
        $totalRows = 0;
        $warnings = [];

        foreach ($datasets as $name => $def) {
            $table = (string) ($def['table'] ?? '');
            $primaryKey = (string) ($def['primary_key'] ?? 'id');
            if ($table === '' || !Schema::hasTable($table)) {
                $warnings[] = 'Dataset ' . $name . ' dilewati: tabel tidak ditemukan.';
                continue;
            }

            $columns = Schema::getColumnListing($table);
            if (!in_array($primaryKey, $columns, true) || !in_array('updated_at', $columns, true)) {
                $warnings[] = 'Dataset ' . $name . ' dilewati: wajib punya kolom primary key dan updated_at untuk incremental pull.';
                continue;
            }

            $query = DB::table($table)->orderBy('updated_at')->orderBy($primaryKey);
            $isBranchScoped = (bool) ($def['branch_scoped'] ?? false);
            $cabangColumn = (string) ($def['cabang_column'] ?? 'cabang_id');
            if ($isBranchScoped && $cabangId > 0 && in_array($cabangColumn, $columns, true)) {
                $query->where($cabangColumn, $cabangId);
            }

            $cursor = (array) ($incomingCursors[$name] ?? []);
            $cursorUpdatedAt = trim((string) ($cursor['updated_at'] ?? ''));
            $cursorPk = (int) ($cursor['pk'] ?? 0);
            if ($cursorUpdatedAt !== '') {
                $query->where(function ($q) use ($cursorUpdatedAt, $cursorPk, $primaryKey) {
                    $q->where('updated_at', '>', $cursorUpdatedAt)
                        ->orWhere(function ($inner) use ($cursorUpdatedAt, $cursorPk, $primaryKey) {
                            $inner->where('updated_at', '=', $cursorUpdatedAt)
                                ->where($primaryKey, '>', $cursorPk);
                        });
                });
            }

            $rows = $query->limit($batchSize + 1)->get();
            $hasMore = $rows->count() > $batchSize;
            if ($hasMore) {
                $rows = $rows->take($batchSize)->values();
            }

            $rowsArray = $rows->map(fn ($row) => $this->mapSyncRow((string) $name, (array) $row))->values()->all();
            $rowCount = count($rowsArray);
            $totalRows += $rowCount;
            $hasMoreAny = $hasMoreAny || $hasMore;

            $nextUpdatedAt = $cursorUpdatedAt;
            $nextPk = $cursorPk;
            if ($rowCount > 0) {
                $last = (array) end($rowsArray);
                $nextUpdatedAt = $this->normalizeDateTimeString($last['updated_at'] ?? $cursorUpdatedAt);
                $nextPk = (int) ($last[$primaryKey] ?? $cursorPk);
            }

            $result[] = [
                'name' => (string) $name,
                'rows' => $rowsArray,
            ];
            $nextCursors[$name] = [
                'updated_at' => $nextUpdatedAt,
                'pk' => $nextPk,
                'has_more' => $hasMore,
            ];
        }

        return response()->json([
            'message' => 'Incremental master pull data',
            'cabang_id' => $cabangId,
            'datasets' => $result,
            'next_cursors' => $nextCursors,
            'has_more' => $hasMoreAny,
            'total_rows' => $totalRows,
            'warnings' => $warnings,
        ]);
    }

    private function mapSyncRow(string $dataset, array $row): array
    {
        $row = $this->sanitizeDatasetRow($dataset, $row);

        if (!empty($row['deleted_at'])) {
            $row['_sync_deleted'] = true;
        }

        return $row;
    }

    private function normalizeDateTimeString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string) $value);
    }

    private function sanitizeDatasetRow(string $dataset, array $row): array
    {
        $allowlist = (array) config("sync.bootstrap_column_allowlist.{$dataset}", []);
        if (empty($allowlist)) {
            return $row;
        }

        $allowed = array_fill_keys($allowlist, true);
        return array_intersect_key($row, $allowed);
    }
}
