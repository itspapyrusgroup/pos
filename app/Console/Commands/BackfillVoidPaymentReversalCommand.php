<?php

namespace App\Console\Commands;

use App\Models\PembayaranPenjualan;
use App\Models\PenjualanVoidLog;
use App\Models\PesananPenjualan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVoidPaymentReversalCommand extends Command
{
    protected $signature = 'pos:backfill-void-payment-reversal
        {--cabang_id=* : Batasi ke cabang tertentu}
        {--dry-run : Simulasi tanpa menulis data}';

    protected $description = 'Backfill pembayaran VOID (nominal negatif) untuk data lama agar kas bersih konsisten';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $cabangIds = collect((array) $this->option('cabang_id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $query = PesananPenjualan::query()
            ->select(['id', 'cabang_id', 'paid_total'])
            ->whereIn('status_pembayaran', ['PAID', 'PARTIALLY_PAID', 'VOID'])
            ->orderBy('id');

        if ($cabangIds->isNotEmpty()) {
            $query->whereIn('cabang_id', $cabangIds->all());
        }

        $orders = $query->get();

        $checked = 0;
        $patchedOrders = 0;
        $createdRows = 0;
        $reversalNominal = 0.0;
        $skippedNegativeDrift = 0;

        foreach ($orders as $order) {
            $checked++;

            $paymentGroups = PembayaranPenjualan::query()
                ->selectRaw('metode_pembayaran_id, shift_kasir_id, kasir_user_id, SUM(nominal) as net_nominal')
                ->where('pesanan_penjualan_id', (int) $order->id)
                ->groupBy('metode_pembayaran_id', 'shift_kasir_id', 'kasir_user_id')
                ->orderByDesc('net_nominal')
                ->get();

            $netPembayaran = (float) $paymentGroups->sum(fn ($row) => (float) ($row->net_nominal ?? 0));
            $targetPaid = (float) $order->paid_total;
            $selisihLebih = round($netPembayaran - $targetPaid, 2);

            if ($selisihLebih <= 0) {
                if ($selisihLebih < 0) {
                    $skippedNegativeDrift++;
                }
                continue;
            }

            $positiveGroups = $paymentGroups
                ->filter(fn ($row) => (float) ($row->net_nominal ?? 0) > 0)
                ->values();

            if ($positiveGroups->isEmpty()) {
                continue;
            }

            $latestVoidAt = PenjualanVoidLog::query()
                ->where('pesanan_penjualan_id', (int) $order->id)
                ->max('voided_at');
            $paidAt = $latestVoidAt ? now()->parse($latestVoidAt) : now();

            $remaining = $selisihLebih;
            $rowsToInsert = [];

            foreach ($positiveGroups as $group) {
                if ($remaining <= 0) {
                    break;
                }

                $groupNet = (float) ($group->net_nominal ?? 0);
                if ($groupNet <= 0) {
                    continue;
                }

                $portion = min($groupNet, $remaining);
                if ($portion <= 0) {
                    continue;
                }

                $rowsToInsert[] = [
                    'pesanan_penjualan_id' => (int) $order->id,
                    'metode_pembayaran_id' => (int) $group->metode_pembayaran_id,
                    'shift_kasir_id' => $group->shift_kasir_id ? (int) $group->shift_kasir_id : null,
                    'kasir_user_id' => $group->kasir_user_id ? (int) $group->kasir_user_id : null,
                    'nominal' => -$portion,
                    'tipe' => 'VOID',
                    'tanggal_bayar' => $paidAt,
                    'created_at' => $paidAt,
                    'updated_at' => $paidAt,
                ];

                $remaining = round($remaining - $portion, 2);
            }

            if (empty($rowsToInsert)) {
                continue;
            }

            if (!$dryRun) {
                DB::transaction(function () use ($rowsToInsert) {
                    PembayaranPenjualan::query()->insert($rowsToInsert);
                });
            }

            $patchedOrders++;
            $createdRows += count($rowsToInsert);
            $reversalNominal += (float) collect($rowsToInsert)->sum(fn ($row) => abs((float) $row['nominal']));
        }

        $this->line('Checked order   : ' . number_format($checked, 0, ',', '.'));
        $this->line('Patched order   : ' . number_format($patchedOrders, 0, ',', '.'));
        $this->line('Created rows    : ' . number_format($createdRows, 0, ',', '.'));
        $this->line('Reversal total  : Rp ' . number_format($reversalNominal, 0, ',', '.'));
        $this->line('Negative drift  : ' . number_format($skippedNegativeDrift, 0, ',', '.'));
        $this->line($dryRun ? 'Mode            : DRY RUN (tanpa perubahan)' : 'Mode            : WRITE');

        return self::SUCCESS;
    }
}

