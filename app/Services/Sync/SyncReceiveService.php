<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncReceiveService
{
    public function ingest(array $datasets, string $configKey = 'sync.datasets'): array
    {
        $allowed = (array) config($configKey, []);
        $summary = [
            'applied_rows' => 0,
            'datasets' => [],
            'received_datasets' => [],
        ];

        foreach ($datasets as $datasetPayload) {
            $name = (string) ($datasetPayload['name'] ?? '');
            $rows = $datasetPayload['rows'] ?? [];
            if ($name === '' || !isset($allowed[$name])) {
                continue;
            }

            $table = (string) ($allowed[$name]['table'] ?? '');
            $primaryKey = (string) ($allowed[$name]['primary_key'] ?? 'id');
            if ($table === '' || !Schema::hasTable($table)) {
                continue;
            }

            if (!is_array($rows) || empty($rows)) {
                $summary['datasets'][$name] = 0;
                $summary['received_datasets'][$name] = is_array($rows) ? count($rows) : 0;
                continue;
            }
            $summary['received_datasets'][$name] = count($rows);

            $tableColumns = Schema::getColumnListing($table);
            if (empty($tableColumns) || !in_array($primaryKey, $tableColumns, true)) {
                continue;
            }
            $allowedKeys = array_flip($tableColumns);

            $prepared = [];
            $deleteIds = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $filtered = array_intersect_key($row, $allowedKeys);
                if (!array_key_exists($primaryKey, $filtered)) {
                    continue;
                }

                 if ($this->isSyncDeleted($row)) {
                    $deleteIds[] = $filtered[$primaryKey];
                    continue;
                }

                foreach ($filtered as $k => $v) {
                    if (is_array($v) || is_object($v)) {
                        $filtered[$k] = json_encode($v);
                    }
                }

                $normalized = $this->normalizeIncomingRow($name, $filtered);
                if ($normalized === null) {
                    continue;
                }
                $prepared[] = $normalized;
            }

            if (empty($prepared)) {
                $summary['datasets'][$name] = 0;
                continue;
            }

            $updateColumns = array_values(array_filter(
                $tableColumns,
                fn ($col) => $col !== $primaryKey
            ));

            $count = 0;
            if (!empty($deleteIds)) {
                try {
                    DB::table($table)->whereIn($primaryKey, $deleteIds)->delete();
                    // Hitung instruksi delete yang diproses, bukan hanya affected rows, agar cursor incremental tidak mudah stuck.
                    $count += count($deleteIds);
                } catch (Throwable $e) {
                    foreach ($deleteIds as $id) {
                        try {
                            DB::table($table)->where($primaryKey, $id)->delete();
                            $count++;
                        } catch (Throwable $inner) {
                            // skip baris invalid
                        }
                    }
                }
            }

            try {
                if (!empty($prepared)) {
                    DB::table($table)->upsert($prepared, [$primaryKey], $updateColumns);
                    $count += count($prepared);
                }
            } catch (Throwable $e) {
                // fallback row-by-row supaya 1 baris invalid tidak menggagalkan seluruh batch.
                foreach ($prepared as $one) {
                    try {
                        DB::table($table)->upsert([$one], [$primaryKey], $updateColumns);
                        $count++;
                    } catch (Throwable $inner) {
                        // skip baris invalid
                    }
                }
            }

            $summary['datasets'][$name] = $count;
            $summary['applied_rows'] += $count;
        }

        return $summary;
    }

    private function isSyncDeleted(array $row): bool
    {
        $raw = $row['_sync_deleted'] ?? false;
        if (is_bool($raw)) {
            return $raw;
        }

        $value = strtolower(trim((string) $raw));
        return in_array($value, ['1', 'true', 'yes', 'y'], true);
    }

    private function normalizeIncomingRow(string $dataset, array $row): ?array
    {
        if ($dataset === 'pesanan_penjualan') {
            // Untuk stabilitas lintas node, transaksi tetap bisa masuk walau master pelanggan belum sinkron.
            // Identitas customer tetap aman dari snapshot customer_name/customer_phone/customer_address.
            $row['pelanggan_id'] = null;
            $row['shift_kasir_id'] = $this->nullableIfMissing('shift_kasir', (int) ($row['shift_kasir_id'] ?? 0));
            $row['sales_mode_id'] = $this->nullableIfMissing('sales_mode', (int) ($row['sales_mode_id'] ?? 0));
            $row['template_harga_id'] = $this->nullableIfMissing('template_harga', (int) ($row['template_harga_id'] ?? 0));
            $row['kasir_user_id'] = $this->nullableIfMissing('users', (int) ($row['kasir_user_id'] ?? 0));
            $row['cs1_user_id'] = $this->nullableIfMissing('users', (int) ($row['cs1_user_id'] ?? 0));
            $row['cs2_user_id'] = $this->nullableIfMissing('users', (int) ($row['cs2_user_id'] ?? 0));
            $row['spv_user_id'] = $this->nullableIfMissing('users', (int) ($row['spv_user_id'] ?? 0));
        }

        if ($dataset === 'booking_studio') {
            if (!$this->existsId('cabang', (int) ($row['cabang_id'] ?? 0))) {
                return null;
            }
            $row['pesanan_penjualan_id'] = $this->nullableIfMissing('pesanan_penjualan', (int) ($row['pesanan_penjualan_id'] ?? 0));
            $row['pelanggan_id'] = $this->nullableIfMissing('pelanggan', (int) ($row['pelanggan_id'] ?? 0));
            $row['studio_id'] = $this->nullableIfMissing('studio', (int) ($row['studio_id'] ?? 0));
        }

        if ($dataset === 'antrian_studio') {
            if (!$this->existsId('cabang', (int) ($row['cabang_id'] ?? 0))) {
                return null;
            }
            $row['booking_studio_id'] = $this->nullableIfMissing('booking_studio', (int) ($row['booking_studio_id'] ?? 0));
            $row['studio_id'] = $this->nullableIfMissing('studio', (int) ($row['studio_id'] ?? 0));
            $row['called_by_user_id'] = $this->nullableIfMissing('users', (int) ($row['called_by_user_id'] ?? 0));
            $row['started_by_user_id'] = $this->nullableIfMissing('users', (int) ($row['started_by_user_id'] ?? 0));
            $row['ended_by_user_id'] = $this->nullableIfMissing('users', (int) ($row['ended_by_user_id'] ?? 0));
            $row['photographer_user_id'] = $this->nullableIfMissing('users', (int) ($row['photographer_user_id'] ?? 0));
        }

        if ($dataset === 'pesanan_penjualan_item') {
            if (!$this->existsId('pesanan_penjualan', (int) ($row['pesanan_penjualan_id'] ?? 0))) {
                return null;
            }
            $row['produk_id'] = $this->nullableIfMissing('produk', (int) ($row['produk_id'] ?? 0));
            $row['paket_id'] = $this->nullableIfMissing('paket', (int) ($row['paket_id'] ?? 0));
            $row['shift_kasir_id'] = $this->nullableIfMissing('shift_kasir', (int) ($row['shift_kasir_id'] ?? 0));
            $row['kasir_user_id'] = $this->nullableIfMissing('users', (int) ($row['kasir_user_id'] ?? 0));
        }

        if ($dataset === 'pembayaran_penjualan') {
            if (
                !$this->existsId('pesanan_penjualan', (int) ($row['pesanan_penjualan_id'] ?? 0)) ||
                !$this->existsId('metode_pembayaran', (int) ($row['metode_pembayaran_id'] ?? 0))
            ) {
                return null;
            }
            $row['shift_kasir_id'] = $this->nullableIfMissing('shift_kasir', (int) ($row['shift_kasir_id'] ?? 0));
            $row['kasir_user_id'] = $this->nullableIfMissing('users', (int) ($row['kasir_user_id'] ?? 0));
        }

        if ($dataset === 'kantong_order') {
            if (
                !$this->existsId('pesanan_penjualan', (int) ($row['pesanan_penjualan_id'] ?? 0)) ||
                !$this->existsId('cabang', (int) ($row['cabang_id'] ?? 0))
            ) {
                return null;
            }
            $row['designer_id'] = $this->nullableIfMissing('users', (int) ($row['designer_id'] ?? 0));
        }

        if ($dataset === 'antrian_studio_tugas') {
            if (!$this->existsId('antrian_studio', (int) ($row['antrian_studio_id'] ?? 0))) {
                return null;
            }
            $row['pesanan_penjualan_item_id'] = $this->nullableIfMissing('pesanan_penjualan_item', (int) ($row['pesanan_penjualan_item_id'] ?? 0));
            $row['produk_id'] = $this->nullableIfMissing('produk', (int) ($row['produk_id'] ?? 0));
            $row['selesai_by_user_id'] = $this->nullableIfMissing('users', (int) ($row['selesai_by_user_id'] ?? 0));
        }

        if ($dataset === 'ko_tracking_ko_checks') {
            if (!$this->existsId('pesanan_penjualan', (int) ($row['pesanan_penjualan_id'] ?? 0))) {
                return null;
            }
            $row['checked_by_user_id'] = $this->nullableIfMissing('users', (int) ($row['checked_by_user_id'] ?? 0));
        }

        if ($dataset === 'ko_tracking_item_checks') {
            if (
                !$this->existsId('pesanan_penjualan_item', (int) ($row['pesanan_penjualan_item_id'] ?? 0)) ||
                !$this->existsId('produk', (int) ($row['produk_id'] ?? 0))
            ) {
                return null;
            }
            $row['checked_by_user_id'] = $this->nullableIfMissing('users', (int) ($row['checked_by_user_id'] ?? 0));
        }

        return $row;
    }

    private function nullableIfMissing(string $table, int $id): ?int
    {
        if ($id <= 0) {
            return null;
        }

        return $this->existsId($table, $id) ? $id : null;
    }

    private function existsId(string $table, int $id): bool
    {
        if ($id <= 0 || !Schema::hasTable($table)) {
            return false;
        }

        $cacheKey = "sync_exists_{$table}_{$id}";
        return (bool) Cache::remember($cacheKey, 10, function () use ($table, $id) {
            return DB::table($table)->where('id', $id)->exists();
        });
    }
}
