<?php

namespace App\Http\Controllers;

use App\Models\KartuStok;
use App\Models\KantongOrder;
use App\Models\PembayaranPenjualan;
use App\Models\PenjualanVoidLog;
use App\Models\PenjualanVoidOtp;
use App\Models\PesananPenjualan;
use App\Models\PesananPenjualanItem;
use App\Models\Produk;
use App\Models\StokCabang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenjualanVoidController extends Controller
{
    public function index(): View
    {
        return view('pages.pos.generate-otp-void');
    }

    public function cariOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:50'],
        ]);

        $reference = trim((string) $data['reference']);
        $order = PesananPenjualan::query()
            ->with([
                'kantongOrder:id,pesanan_penjualan_id,nomor_ko,cabang_id',
                'pelanggan:id,nama,no_hp',
                'items.produk:id,nama',
                'items.paket:id,nama',
            ])
            ->where('nomor_so', $reference)
            ->orWhereHas('kantongOrder', function ($q) use ($reference) {
                $q->where('nomor_ko', $reference);
            })
            ->latest('id')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        $this->ensureCabangAccessible((int) $order->cabang_id);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'nomor_so' => $order->nomor_so,
                'nomor_ko' => $order->kantongOrder?->nomor_ko,
                'customer_name' => $order->customer_name ?: ($order->pelanggan?->nama ?? '-'),
                'status_pembayaran' => $order->status_pembayaran,
                'total' => (float) $order->total,
                'paid_total' => (float) $order->paid_total,
                'balance' => (float) $order->balance,
                'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
                'items' => $order->items
                    ->sortBy('id')
                    ->map(function ($item) {
                        return [
                            'id' => (int) $item->id,
                            'nama' => $item->produk?->nama ?? $item->paket?->nama ?? '-',
                            'jenis' => $item->paket_id ? 'PAKET' : 'PRODUK',
                            'qty' => (float) $item->qty,
                            'subtotal' => (float) $item->subtotal,
                            'is_void' => (bool) ($item->is_void ?? false),
                        ];
                    })
                    ->values(),
                'payments' => $order->pembayaran()
                    ->with('metodePembayaran:id,nama')
                    ->where('nominal', '>', 0)
                    ->where('tipe', '!=', 'VOID')
                    ->orderBy('tanggal_bayar')
                    ->get()
                    ->map(function ($payment) {
                        return [
                            'id' => (int) $payment->id,
                            'tanggal_bayar' => $payment->tanggal_bayar?->format('Y-m-d H:i:s'),
                            'metode' => (string) ($payment->metodePembayaran?->nama ?? '-'),
                            'tipe' => (string) $payment->tipe,
                            'nominal' => (float) $payment->nominal,
                        ];
                    })
                    ->values(),
            ],
        ]);
    }

    public function generateOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:50'],
            'tipe_void' => ['required', 'in:FULL,PARTIAL,REMOVE,CHANGE_METHOD'],
            'tipe_transaksi' => ['required', 'in:CURRENT_DAY,BACKDATE'],
            'item_ids' => ['nullable', 'array'],
            'item_ids.*' => ['integer', 'exists:pesanan_penjualan_item,id'],
            'payment_id' => ['nullable', 'integer', 'exists:pembayaran_penjualan,id'],
        ]);

        $reference = trim((string) $data['reference']);
        $order = PesananPenjualan::query()
            ->with(['kantongOrder', 'items'])
            ->where('nomor_so', $reference)
            ->orWhereHas('kantongOrder', function ($q) use ($reference) {
                $q->where('nomor_ko', $reference);
            })
            ->latest('id')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        $this->ensureCabangAccessible((int) $order->cabang_id);

        if (in_array((string) $order->status_pembayaran, ['VOID', 'CANCELLED'], true)) {
            throw ValidationException::withMessages([
                'tipe_void' => ['Order ini sudah berstatus ' . $order->status_pembayaran . '.'],
            ]);
        }

        $tipeVoid = (string) $data['tipe_void'];
        if ($tipeVoid === 'REMOVE' && !in_array((string) $order->status_pembayaran, ['DRAFT', 'PARTIALLY_PAID'], true)) {
            throw ValidationException::withMessages([
                'tipe_void' => ['REMOVE hanya boleh untuk transaksi yang belum lunas (DRAFT / PARTIALLY_PAID).'],
            ]);
        }

        $itemPayload = null;
        if (in_array($tipeVoid, ['PARTIAL', 'REMOVE'], true)) {
            $itemIds = collect($data['item_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            if ($itemIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'item_ids' => [$tipeVoid === 'REMOVE'
                        ? 'Pilih minimal satu item untuk remove.'
                        : 'Pilih minimal satu item untuk void sebagian.'],
                ]);
            }

            $validItems = $order->items
                ->whereIn('id', $itemIds->all())
                ->where('is_void', false)
                ->values();

            if ($validItems->isEmpty() || $validItems->count() !== $itemIds->count()) {
                throw ValidationException::withMessages([
                    'item_ids' => ['Sebagian item tidak valid atau sudah pernah di-void.'],
                ]);
            }

            $itemPayload = $validItems->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        } elseif ($tipeVoid === 'CHANGE_METHOD') {
            $paymentId = (int) ($data['payment_id'] ?? 0);
            if ($paymentId <= 0) {
                throw ValidationException::withMessages([
                    'payment_id' => ['Pilih pembayaran yang metode bayarnya akan diganti.'],
                ]);
            }

            $payment = $order->pembayaran()
                ->whereKey($paymentId)
                ->where('nominal', '>', 0)
                ->where('tipe', '!=', 'VOID')
                ->first();

            if (!$payment) {
                throw ValidationException::withMessages([
                    'payment_id' => ['Pembayaran yang dipilih tidak valid untuk koreksi metode.'],
                ]);
            }

            $itemPayload = [(int) $payment->id];
        }

        $tipeTransaksi = in_array($tipeVoid, ['REMOVE', 'CHANGE_METHOD'], true)
            ? 'CURRENT_DAY'
            : (string) $data['tipe_transaksi'];

        $otp = PenjualanVoidOtp::query()->create([
            'kode_otp' => $this->generateOtpCode(),
            'pesanan_penjualan_id' => $order->id,
            'tipe_void' => $tipeVoid,
            'tipe_transaksi' => $tipeTransaksi,
            'item_payload' => $itemPayload,
            'expired_at' => now()->addHour(),
            'generated_by_user_id' => (int) Auth::id(),
        ]);

        return response()->json([
            'message' => 'OTP berhasil dibuat.',
            'data' => [
                'kode_otp' => $otp->kode_otp,
                'expired_at' => $otp->expired_at?->format('d-m-Y H:i'),
                'tipe_void' => $otp->tipe_void,
                'tipe_transaksi' => $otp->tipe_transaksi,
                'nomor_so' => $order->nomor_so,
                'nomor_ko' => $order->kantongOrder?->nomor_ko,
            ],
        ]);
    }

    public function voidRiwayat(Request $request, PesananPenjualan $pesananPenjualan): RedirectResponse
    {
        $data = $request->validate([
            'otp' => ['required', 'string', 'max:10'],
            'alasan_void' => ['required', 'string', 'min:5'],
        ]);

        $this->ensureCabangAccessible((int) $pesananPenjualan->cabang_id);
        $throttleKey = $this->otpAttemptThrottleKey($request, (int) $pesananPenjualan->id);
        $maxAttempts = max(1, (int) config('pos.void_otp_max_attempts', 5));
        $decaySeconds = max(60, (int) config('pos.void_otp_decay_seconds', 600));

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'otp' => ["Terlalu banyak percobaan OTP. Coba lagi dalam {$seconds} detik."],
            ]);
        }

        try {
            DB::transaction(function () use ($data, $pesananPenjualan) {
                $order = PesananPenjualan::query()
                    ->with(['kantongOrder', 'items.paket.items', 'items.produk'])
                    ->lockForUpdate()
                    ->findOrFail($pesananPenjualan->id);

                $otp = PenjualanVoidOtp::query()
                    ->where('kode_otp', strtoupper(trim((string) $data['otp'])))
                    ->where('pesanan_penjualan_id', $order->id)
                    ->lockForUpdate()
                    ->first();

            if (!$otp) {
                throw ValidationException::withMessages([
                    'otp' => ['OTP tidak valid untuk transaksi ini.'],
                ]);
            }
            if ($otp->used_at) {
                throw ValidationException::withMessages([
                    'otp' => ['OTP sudah pernah digunakan.'],
                ]);
            }
            if ($otp->expired_at && $otp->expired_at->isPast()) {
                throw ValidationException::withMessages([
                    'otp' => ['OTP sudah kedaluwarsa.'],
                ]);
            }
            if ($otp->tipe_void === 'REMOVE') {
                throw ValidationException::withMessages([
                    'otp' => ['OTP ini untuk REMOVE item pada transaksi belum lunas, bukan untuk VOID riwayat transaksi.'],
                ]);
            }

            $voidedAt = now();
            $voidEffectiveDate = $otp->tipe_transaksi === 'BACKDATE'
                ? $this->getEarliestPaymentDate($order)->toDateString()
                : $voidedAt->toDateString();

            $voidNominal = 0.0;
            $voidItemIds = [];

            if ($otp->tipe_void === 'FULL') {
                if (in_array((string) $order->status_pembayaran, ['VOID', 'CANCELLED'], true)) {
                    throw ValidationException::withMessages([
                        'otp' => ['Order ini sudah ' . $order->status_pembayaran . '.'],
                    ]);
                }

                $activeItems = $order->items->where('is_void', false)->values();
                foreach ($activeItems as $item) {
                    $voidNominal += (float) $item->subtotal;
                }

                if ($voidNominal <= 0) {
                    throw ValidationException::withMessages([
                        'otp' => ['Tidak ada item aktif untuk di-void.'],
                    ]);
                }

                $log = PenjualanVoidLog::query()->create([
                    'pesanan_penjualan_id' => $order->id,
                    'kantong_order_id' => $order->kantongOrder?->id,
                    'otp_id' => $otp->id,
                    'tipe_void' => 'FULL',
                    'tipe_transaksi' => $otp->tipe_transaksi,
                    'alasan' => $data['alasan_void'],
                    'nominal_void' => $voidNominal,
                    'void_effective_date' => $voidEffectiveDate,
                    'voided_at' => $voidedAt,
                    'voided_by_user_id' => (int) Auth::id(),
                    'authorized_by_user_id' => $otp->generated_by_user_id ? (int) $otp->generated_by_user_id : null,
                    'item_payload' => $activeItems->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                ]);

                $reversedPayment = $this->createVoidPaymentReversal(
                    $order,
                    (float) $order->paid_total,
                    $voidedAt,
                    (int) Auth::id(),
                    $otp->tipe_transaksi
                );

                foreach ($activeItems as $item) {
                    $voidItemIds[] = (int) $item->id;
                    $this->reverseStockMutationForItem($item, (int) $order->cabang_id, (int) $log->id, (string) $order->status_pembayaran);
                }

                PesananPenjualanItem::query()
                    ->whereIn('id', $voidItemIds)
                    ->update([
                        'qty' => 0,
                        'harga' => 0,
                        'diskon' => 0,
                        'subtotal' => 0,
                        'is_void' => true,
                        'voided_at' => $voidedAt,
                        'void_log_id' => $log->id,
                    ]);

                $order->update([
                    'total' => 0,
                    'paid_total' => max((float) $order->paid_total - $reversedPayment, 0),
                    'balance' => 0,
                    'status_pembayaran' => 'VOID',
                    'voided_at' => $voidedAt,
                    'catatan' => $this->appendVoidNote($order->catatan, $log),
                ]);
            } else {
                $itemIds = collect($otp->item_payload ?? [])->map(fn ($id) => (int) $id)->unique()->values();
                if ($itemIds->isEmpty()) {
                    throw ValidationException::withMessages([
                        'otp' => ['OTP void sebagian tidak memiliki item valid.'],
                    ]);
                }

                $activeItems = $order->items
                    ->whereIn('id', $itemIds->all())
                    ->where('is_void', false)
                    ->values();

                if ($activeItems->isEmpty()) {
                    throw ValidationException::withMessages([
                        'otp' => ['Item pada OTP sudah tidak bisa di-void.'],
                    ]);
                }

                foreach ($activeItems as $item) {
                    $voidNominal += (float) $item->subtotal;
                    $voidItemIds[] = (int) $item->id;
                }

                $log = PenjualanVoidLog::query()->create([
                    'pesanan_penjualan_id' => $order->id,
                    'kantong_order_id' => $order->kantongOrder?->id,
                    'otp_id' => $otp->id,
                    'tipe_void' => 'PARTIAL',
                    'tipe_transaksi' => $otp->tipe_transaksi,
                    'alasan' => $data['alasan_void'],
                    'nominal_void' => $voidNominal,
                    'void_effective_date' => $voidEffectiveDate,
                    'voided_at' => $voidedAt,
                    'voided_by_user_id' => (int) Auth::id(),
                    'authorized_by_user_id' => $otp->generated_by_user_id ? (int) $otp->generated_by_user_id : null,
                    'item_payload' => $voidItemIds,
                ]);

                $reversedPayment = $this->createVoidPaymentReversal(
                    $order,
                    min((float) $order->paid_total, $voidNominal),
                    $voidedAt,
                    (int) Auth::id(),
                    $otp->tipe_transaksi
                );

                foreach ($activeItems as $item) {
                    $this->reverseStockMutationForItem($item, (int) $order->cabang_id, (int) $log->id, (string) $order->status_pembayaran);
                }

                PesananPenjualanItem::query()
                    ->whereIn('id', $voidItemIds)
                    ->update([
                        'qty' => 0,
                        'harga' => 0,
                        'diskon' => 0,
                        'subtotal' => 0,
                        'is_void' => true,
                        'voided_at' => $voidedAt,
                        'void_log_id' => $log->id,
                    ]);

                $newTotal = max((float) $order->total - $voidNominal, 0);
                $newPaid = max((float) $order->paid_total - $reversedPayment, 0);
                $newBalance = max($newTotal - $newPaid, 0);
                $isAllItemsVoided = (int) $order->items->where('is_void', false)->count() <= count($voidItemIds);

                $newStatus = 'DRAFT';
                if ($isAllItemsVoided || $newTotal <= 0) {
                    $newStatus = 'VOID';
                } elseif ($newPaid > 0 && $newBalance > 0) {
                    $newStatus = 'PARTIALLY_PAID';
                } elseif ($newPaid >= $newTotal) {
                    $newStatus = 'PAID';
                }

                $order->update([
                    'total' => $newTotal,
                    'paid_total' => $newPaid,
                    'balance' => $newBalance,
                    'status_pembayaran' => $newStatus,
                    'voided_at' => $newStatus === 'VOID' ? $voidedAt : $order->voided_at,
                    'catatan' => $this->appendVoidNote($order->catatan, $log),
                ]);
            }

                $otp->update([
                    'used_at' => $voidedAt,
                    'used_by_user_id' => (int) Auth::id(),
                ]);
            });
            RateLimiter::clear($throttleKey);
        } catch (ValidationException $e) {
            if (array_key_exists('otp', $e->errors())) {
                RateLimiter::hit($throttleKey, $decaySeconds);
            }

            throw $e;
        }

        return back()->with('success', 'Void transaksi berhasil diproses.');
    }

    private function otpAttemptThrottleKey(Request $request, int $orderId): string
    {
        return 'void_otp:' . $orderId . ':' . (int) Auth::id() . ':' . (string) $request->ip();
    }

    private function reverseStockMutationForItem(PesananPenjualanItem $item, int $cabangId, int $voidLogId, string $statusPembayaran): void
    {
        $releaseOnOrderOnly = in_array($statusPembayaran, ['DRAFT', 'PARTIALLY_PAID'], true);

        if (!$item->is_void && $item->produk_id) {
            $produk = Produk::query()->find($item->produk_id);
            if ($produk && $produk->track_stok) {
                $this->tambahStok((int) $produk->id, $cabangId, (float) $item->qty, $voidLogId, $releaseOnOrderOnly);
            }
        }

        if (!$item->is_void && $item->paket_id) {
            $paket = \App\Models\Paket::query()->with('items')->find($item->paket_id);
            if ($paket) {
                foreach ($paket->items as $paketItem) {
                    $produkBom = Produk::query()->find($paketItem->produk_id);
                    if ($produkBom && $produkBom->track_stok) {
                        $qtyMasuk = (float) $paketItem->qty * (float) $item->qty;
                        $this->tambahStok((int) $produkBom->id, $cabangId, $qtyMasuk, $voidLogId, $releaseOnOrderOnly);
                    }
                }
            }
        }
    }

    private function tambahStok(int $produkId, int $cabangId, float $qtyMasuk, int $voidLogId, bool $releaseOnOrderOnly = false): void
    {
        $stok = StokCabang::query()->firstOrCreate(
            ['produk_id' => $produkId, 'cabang_id' => $cabangId],
            ['qty' => 0, 'qty_on_order' => 0]
        );

        $qtyOnOrderSebelum = (float) ($stok->qty_on_order ?? 0);
        $qtyRilisOnOrder = min($qtyOnOrderSebelum, $qtyMasuk);
        $qtyMasukOnHand = $releaseOnOrderOnly ? 0 : max(0, $qtyMasuk - $qtyRilisOnOrder);

        $qtyOnOrderBaru = max(0, $qtyOnOrderSebelum - $qtyRilisOnOrder);
        $saldoAkhir = (float) $stok->qty + $qtyMasukOnHand;

        $stok->update([
            'qty' => $saldoAkhir,
            'qty_on_order' => $qtyOnOrderBaru,
        ]);

        $catatan = $releaseOnOrderOnly
            ? 'Rollback reservasi On-Order karena void penjualan'
            : 'Rollback stok karena void penjualan';
        if (!$releaseOnOrderOnly && $qtyRilisOnOrder > 0) {
            $catatan .= ' (termasuk rilis On-Order)';
        }

        KartuStok::query()->create([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tipe_mutasi' => 'RETUR',
            'referensi_tipe' => 'penjualan_void',
            'referensi_id' => $voidLogId,
            'qty_masuk' => $qtyMasukOnHand,
            'qty_keluar' => 0,
            'saldo_akhir' => $saldoAkhir,
            'catatan' => $catatan,
            'tanggal_mutasi' => now(),
        ]);
    }

    private function appendVoidNote(?string $catatanLama, PenjualanVoidLog $log): string
    {
        $line = sprintf(
            'VOID %s (%s) Rp %s | Alasan: %s',
            $log->tipe_void,
            $log->tipe_transaksi,
            number_format((float) $log->nominal_void, 0, ',', '.'),
            trim((string) $log->alasan)
        );

        $old = trim((string) $catatanLama);
        return $old === '' ? $line : ($old . PHP_EOL . $line);
    }

    private function createVoidPaymentReversal(PesananPenjualan $order, float $maxNominalReverse, \Carbon\CarbonInterface $voidedAt, int $voidedByUserId, string $tipeTransaksi = 'CURRENT_DAY'): float
    {
        if ($maxNominalReverse <= 0) {
            return 0.0;
        }

        // Untuk BACKDATE, tanggal_bayar void harus mengikuti tanggal pembayaran asli
        $voidPaymentDate = $tipeTransaksi === 'BACKDATE'
            ? $this->getEarliestPaymentDate($order)
            : $voidedAt;

        $paymentGroups = PembayaranPenjualan::query()
            ->selectRaw('metode_pembayaran_id, shift_kasir_id, kasir_user_id, SUM(nominal) as net_nominal')
            ->where('pesanan_penjualan_id', $order->id)
            ->groupBy('metode_pembayaran_id', 'shift_kasir_id', 'kasir_user_id')
            ->havingRaw('SUM(nominal) > 0')
            ->orderByDesc('net_nominal')
            ->lockForUpdate()
            ->get();

        if ($paymentGroups->isEmpty()) {
            return 0.0;
        }

        $remaining = min($maxNominalReverse, (float) $paymentGroups->sum('net_nominal'));
        $reversed = 0.0;

        foreach ($paymentGroups as $group) {
            if ($remaining <= 0) {
                break;
            }

            $netNominal = (float) ($group->net_nominal ?? 0);
            if ($netNominal <= 0) {
                continue;
            }

            $portion = min($netNominal, $remaining);
            if ($portion <= 0) {
                continue;
            }

            PembayaranPenjualan::query()->create([
                'pesanan_penjualan_id' => (int) $order->id,
                'metode_pembayaran_id' => (int) $group->metode_pembayaran_id,
                'shift_kasir_id' => $group->shift_kasir_id ? (int) $group->shift_kasir_id : null,
                'kasir_user_id' => $group->kasir_user_id ? (int) $group->kasir_user_id : $voidedByUserId,
                'nominal' => -$portion,
                'tipe' => 'VOID',
                'tanggal_bayar' => $voidPaymentDate,
                'created_at' => $voidPaymentDate,
                'updated_at' => $voidPaymentDate,
            ]);

            $remaining -= $portion;
            $reversed += $portion;
        }

        return $reversed;
    }

    private function getEarliestPaymentDate(PesananPenjualan $order): \Carbon\CarbonInterface
    {
        $earliestPayment = PembayaranPenjualan::query()
            ->where('pesanan_penjualan_id', $order->id)
            ->where('nominal', '>', 0)
            ->where('tipe', '!=', 'VOID')
            ->orderBy('tanggal_bayar')
            ->first();

        return $earliestPayment && $earliestPayment->tanggal_bayar
            ? $earliestPayment->tanggal_bayar
            : $order->created_at;
    }

    private function generateOtpCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (PenjualanVoidOtp::query()->where('kode_otp', $code)->where('expired_at', '>', now()->subDay())->exists());

        return $code;
    }
}
