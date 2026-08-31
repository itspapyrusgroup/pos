<?php

namespace App\Http\Controllers;

use App\Models\AntrianStudioTugas;
use App\Models\Cabang;
use App\Models\KantongOrder;
use App\Models\KoTrackingItemCheck;
use App\Models\MetodePembayaran;
use App\Models\Paket;
use App\Models\PembayaranPenjualan;
use App\Models\PenjualanEditLog;
use App\Models\PenjualanPaymentMethodLog;
use App\Models\PenjualanVoidLog;
use App\Models\PesananPenjualan;
use App\Models\PesananPenjualanItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KoreksiTransaksiPenjualanController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'no_ko' => ['nullable', 'string', 'max:30'],
        ]);

        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $order = null;

        if (!empty($validated['no_ko'])) {
            $order = $this->findOrderByKo($validated['no_ko'], $cabangId);
        }

        return view('pages.pos.koreksi-transaksi-penjualan', [
            'activeCabang' => $cabangId > 0 ? Cabang::query()->find($cabangId) : null,
            'cabangTersedia' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'cabangDefaultId' => $cabangId,
            'order' => $order,
            'paketOptions' => Paket::query()
                ->where('status', true)
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama', 'harga_default']),
            'metodePembayaranOptions' => $this->paymentMethodsForCabang($cabangId),
            'userOptions' => $this->activeUsersForCabang($cabangId),
        ]);
    }

    public function update(Request $request, PesananPenjualan $pesananPenjualan): RedirectResponse
    {
        $this->ensureCabangAccessible((int) $pesananPenjualan->cabang_id);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
            'customer_address' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
            'cs1_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cs2_user_id' => ['nullable', 'integer', 'exists:users,id', 'different:cs1_user_id'],
            'spv_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'kasir_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'fotografer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'alasan_koreksi' => ['required', 'string', 'min:5'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:pesanan_penjualan_item,id'],
            'items.*.paket_id' => ['nullable', 'integer', 'exists:paket,id'],
            'items.*.harga' => ['required', 'numeric', 'min:0'],
            'items.*.diskon' => ['nullable', 'numeric', 'min:0'],
            'items.*.delete' => ['nullable', 'boolean'],
            'payments' => ['nullable', 'array'],
            'payments.*.id' => ['required', 'integer', 'exists:pembayaran_penjualan,id'],
            'payments.*.metode_pembayaran_id' => ['required', 'integer', 'exists:metode_pembayaran,id'],
            'payments.*.nominal' => ['required', 'numeric', 'min:0'],
            'payments.*.delete' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $pesananPenjualan) {
            $editedAt = now();
            $order = PesananPenjualan::query()
                ->with([
                    'kantongOrder',
                    'items.paket',
                    'items.produk',
                    'pembayaran.metodePembayaran',
                    'editLogs.editedBy:id,name',
                    'fotografer:id,name',
                ])
                ->lockForUpdate()
                ->findOrFail($pesananPenjualan->id);

            if (in_array((string) $order->status_pembayaran, ['VOID', 'CANCELLED'], true)) {
                throw ValidationException::withMessages([
                    'no_ko' => ['Transaksi dengan status VOID/CANCELLED tidak bisa dikoreksi dari halaman ini.'],
                ]);
            }

            $allowedUserIds = $this->activeUsersForCabang((int) $order->cabang_id)->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach (['cs1_user_id', 'cs2_user_id', 'spv_user_id', 'kasir_user_id', 'fotografer_user_id'] as $field) {
                $userId = (int) ($data[$field] ?? 0);
                if ($userId > 0 && !in_array($userId, $allowedUserIds, true)) {
                    throw ValidationException::withMessages([
                        $field => ['User yang dipilih tidak aktif di cabang transaksi ini.'],
                    ]);
                }
            }

            $activeItems = $order->items->where('is_void', false)->values();
            $submittedItems = collect($data['items'])
                ->keyBy(fn (array $row) => (int) $row['id']);

            if ($submittedItems->count() !== $activeItems->count()
                || $activeItems->pluck('id')->diff($submittedItems->keys())->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Daftar item transaksi berubah. Muat ulang halaman koreksi lalu coba lagi.'],
                ]);
            }

            $positivePayments = $order->pembayaran
                ->filter(fn ($payment) => (float) $payment->nominal >= 0 && (string) $payment->tipe !== 'VOID')
                ->values();
            $submittedPayments = collect($data['payments'] ?? [])
                ->keyBy(fn (array $row) => (int) $row['id']);

            if ($submittedPayments->count() !== $positivePayments->count()
                || $positivePayments->pluck('id')->diff($submittedPayments->keys())->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'payments' => ['Daftar pembayaran aktif berubah. Muat ulang halaman koreksi lalu coba lagi.'],
                ]);
            }

            $allowedMethodIds = $this->paymentMethodsForCabang((int) $order->cabang_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $beforeSnapshot = $this->buildSnapshot($order->fresh([
                'kantongOrder',
                'kasir:id,name',
                'cs1:id,name',
                'cs2:id,name',
                'spv:id,name',
                'fotografer:id,name',
                'items.paket',
                'items.produk',
                'pembayaran.metodePembayaran',
            ]));

            $removeItemIds = [];
            foreach ($activeItems as $item) {
                $row = $submittedItems->get((int) $item->id);
                $isDelete = (bool) ($row['delete'] ?? false);
                $harga = round((float) ($row['harga'] ?? 0), 2);
                $diskon = round((float) ($row['diskon'] ?? 0), 2);
                $maxDiskon = round((float) $item->qty * $harga, 2);

                if ($diskon > $maxDiskon) {
                    throw ValidationException::withMessages([
                        'items' => ['Diskon item tidak boleh melebihi total harga item.'],
                    ]);
                }

                $isPackageItem = !empty($item->paket_id);
                $newPaketId = $isPackageItem ? (int) ($row['paket_id'] ?? 0) : 0;
                if ($isPackageItem && !$isDelete && $newPaketId <= 0) {
                    throw ValidationException::withMessages([
                        'items' => ['Paket pada item aktif wajib dipilih.'],
                    ]);
                }

                $packageChanged = $isPackageItem && !$isDelete && $newPaketId !== (int) $item->paket_id;
                if ($isDelete || $packageChanged) {
                    $this->assertItemWorkflowEditable($item);
                }

                if ($isDelete) {
                    $removeItemIds[] = (int) $item->id;
                    $item->update([
                        'is_void' => true,
                        'voided_at' => $editedAt,
                        'updated_at' => $editedAt,
                    ]);
                    continue;
                }

                $item->update([
                    'paket_id' => $isPackageItem ? $newPaketId : $item->paket_id,
                    'harga' => $harga,
                    'diskon' => $diskon,
                    'subtotal' => max(round(((float) $item->qty * $harga) - $diskon, 2), 0),
                    'updated_at' => $editedAt,
                ]);
            }

            if (count($removeItemIds) >= $activeItems->count()) {
                throw ValidationException::withMessages([
                    'items' => ['Minimal harus ada satu item aktif tersisa di transaksi.'],
                ]);
            }

            $removeLog = null;
            if (!empty($removeItemIds)) {
                $removeLog = PenjualanVoidLog::query()->create([
                    'pesanan_penjualan_id' => (int) $order->id,
                    'kantong_order_id' => (int) ($order->kantongOrder?->id ?? 0) ?: null,
                    'otp_id' => null,
                    'tipe_void' => 'REMOVE',
                    'tipe_transaksi' => 'CURRENT_DAY',
                    'alasan' => 'Koreksi transaksi: ' . trim((string) $data['alasan_koreksi']),
                    'nominal_void' => 0,
                    'void_effective_date' => $editedAt->toDateString(),
                    'voided_at' => $editedAt,
                    'voided_by_user_id' => (int) Auth::id(),
                    'authorized_by_user_id' => null,
                    'item_payload' => array_values($removeItemIds),
                    'created_at' => $editedAt,
                    'updated_at' => $editedAt,
                ]);

                PesananPenjualanItem::query()
                    ->whereIn('id', $removeItemIds)
                    ->update([
                        'void_log_id' => (int) $removeLog->id,
                        'updated_at' => $editedAt,
                    ]);
            }

            foreach ($positivePayments as $payment) {
                $row = $submittedPayments->get((int) $payment->id);
                $isDelete = (bool) ($row['delete'] ?? false);

                if ($isDelete) {
                    PenjualanPaymentMethodLog::query()
                        ->where('pembayaran_penjualan_id', (int) $payment->id)
                        ->delete();

                    $payment->delete();
                    continue;
                }

                $metodeId = (int) ($row['metode_pembayaran_id'] ?? 0);
                $nominal = round((float) ($row['nominal'] ?? 0), 2);

                if (!in_array($metodeId, $allowedMethodIds, true)) {
                    throw ValidationException::withMessages([
                        'payments' => ['Ada metode pembayaran yang tidak aktif untuk cabang transaksi ini.'],
                    ]);
                }

                $payment->update([
                    'metode_pembayaran_id' => $metodeId,
                    'nominal' => $nominal,
                    'updated_at' => $editedAt,
                ]);
            }

            $activeItemTotal = (float) PesananPenjualanItem::query()
                ->where('pesanan_penjualan_id', (int) $order->id)
                ->where('is_void', false)
                ->sum('subtotal');

            $paidTotal = (float) PembayaranPenjualan::query()
                ->where('pesanan_penjualan_id', (int) $order->id)
                ->sum('nominal');

            $order->update([
                'customer_name' => trim((string) $data['customer_name']),
                'customer_phone' => trim((string) $data['customer_phone']),
                'customer_address' => $data['customer_address'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'cs1_user_id' => $data['cs1_user_id'] ?? null,
                'cs2_user_id' => $data['cs2_user_id'] ?? null,
                'spv_user_id' => $data['spv_user_id'] ?? null,
                'kasir_user_id' => $data['kasir_user_id'] ?? null,
                'fotografer_user_id' => $data['fotografer_user_id'] ?? null,
                'total' => $activeItemTotal,
                'paid_total' => $paidTotal,
                'balance' => max($activeItemTotal - $paidTotal, 0),
                'status_pembayaran' => $this->resolvePaymentStatus($activeItemTotal, $paidTotal),
                'updated_at' => $editedAt,
            ]);

            $afterSnapshot = $this->buildSnapshot($order->fresh([
                'kantongOrder',
                'kasir:id,name',
                'cs1:id,name',
                'cs2:id,name',
                'spv:id,name',
                'fotografer:id,name',
                'items.paket',
                'items.produk',
                'pembayaran.metodePembayaran',
            ]));

            PenjualanEditLog::query()->create([
                'pesanan_penjualan_id' => (int) $order->id,
                'kantong_order_id' => (int) ($order->kantongOrder?->id ?? 0) ?: null,
                'edited_by_user_id' => (int) Auth::id(),
                'alasan' => trim((string) $data['alasan_koreksi']),
                'before_snapshot' => $beforeSnapshot,
                'after_snapshot' => $afterSnapshot,
                'edited_at' => $editedAt,
                'created_at' => $editedAt,
                'updated_at' => $editedAt,
            ]);
        });

        $noKo = $pesananPenjualan->kantongOrder?->nomor_ko
            ?? KantongOrder::query()->where('pesanan_penjualan_id', $pesananPenjualan->id)->value('nomor_ko');

        return redirect()
            ->route('koreksi-transaksi-penjualan', ['no_ko' => $noKo])
            ->with('success', 'Koreksi transaksi berhasil disimpan.');
    }

    private function findOrderByKo(string $noKo, int $cabangId): ?PesananPenjualan
    {
        $ko = KantongOrder::query()
            ->with([
                'pesananPenjualan.kasir:id,name',
                'pesananPenjualan.cs1:id,name',
                'pesananPenjualan.cs2:id,name',
                'pesananPenjualan.spv:id,name',
                'pesananPenjualan.fotografer:id,name',
                'pesananPenjualan.items.paket:id,nama,harga_default',
                'pesananPenjualan.items.produk:id,nama',
                'pesananPenjualan.pembayaran.metodePembayaran:id,nama',
                'pesananPenjualan.editLogs.editedBy:id,name',
            ])
            ->where('nomor_ko', trim($noKo))
            ->when($cabangId > 0, fn ($query) => $query->where('cabang_id', $cabangId))
            ->first();

        if (!$ko?->pesananPenjualan) {
            return null;
        }

        $this->ensureCabangAccessible((int) $ko->pesananPenjualan->cabang_id);

        $order = $ko->pesananPenjualan;
        $workflowUsageMap = $this->itemWorkflowUsageMap(
            $order->items->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        $order->items->each(function ($item) use ($workflowUsageMap) {
            $item->setAttribute('workflow_locked', (bool) ($workflowUsageMap[(int) $item->id] ?? false));
        });

        return $order;
    }

    private function activeUsersForCabang(int $cabangId)
    {
        return User::query()
            ->with('role:id,nama')
            ->where('status', true)
            ->when($cabangId > 0, function ($query) use ($cabangId) {
                $query->whereHas('cabang', function ($inner) use ($cabangId) {
                    $inner->where('cabang.id', $cabangId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);
    }

    private function paymentMethodsForCabang(int $cabangId)
    {
        return MetodePembayaran::query()
            ->where('status', true)
            ->when($cabangId > 0, function ($query) use ($cabangId) {
                $query->whereHas('cabang', function ($inner) use ($cabangId) {
                    $inner->where('cabang.id', $cabangId);
                });
            })
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }

    private function resolvePaymentStatus(float $total, float $paid): string
    {
        if ($paid > 0 && max($total - $paid, 0) > 0) {
            return 'PARTIALLY_PAID';
        }

        if ($paid >= $total && $total > 0) {
            return 'PAID';
        }

        return 'DRAFT';
    }

    private function assertItemWorkflowEditable(PesananPenjualanItem $item): void
    {
        if (($this->itemWorkflowUsageMap([(int) $item->id])[(int) $item->id] ?? false) === true) {
            throw ValidationException::withMessages([
                'items' => ['Ada item yang sudah dipakai di tracking/antrian studio. Paket item tersebut tidak boleh diganti atau dihapus.'],
            ]);
        }
    }

    private function itemWorkflowUsageMap(array $itemIds): array
    {
        $itemIds = collect($itemIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return [];
        }

        $trackingIds = KoTrackingItemCheck::query()
            ->whereIn('pesanan_penjualan_item_id', $itemIds->all())
            ->pluck('pesanan_penjualan_item_id')
            ->map(fn ($id) => (int) $id);

        $queueIds = AntrianStudioTugas::query()
            ->whereIn('pesanan_penjualan_item_id', $itemIds->all())
            ->pluck('pesanan_penjualan_item_id')
            ->map(fn ($id) => (int) $id);

        return $trackingIds
            ->concat($queueIds)
            ->unique()
            ->mapWithKeys(fn ($id) => [$id => true])
            ->all();
    }

    private function buildSnapshot(PesananPenjualan $order): array
    {
        return [
            'order' => [
                'id' => (int) $order->id,
                'nomor_so' => (string) $order->nomor_so,
                'nomor_ko' => (string) ($order->kantongOrder?->nomor_ko ?? '-'),
                'customer_name' => (string) ($order->customer_name ?? ''),
                'customer_phone' => (string) ($order->customer_phone ?? ''),
                'customer_address' => (string) ($order->customer_address ?? ''),
                'kasir' => $order->kasir?->name,
                'cs1' => $order->cs1?->name,
                'cs2' => $order->cs2?->name,
                'spv' => $order->spv?->name,
                'fotografer' => $order->fotografer?->name,
                'catatan' => (string) ($order->catatan ?? ''),
                'status_pembayaran' => (string) $order->status_pembayaran,
                'total' => round((float) $order->total, 2),
                'paid_total' => round((float) $order->paid_total, 2),
                'balance' => round((float) $order->balance, 2),
            ],
            'items' => $order->items
                ->sortBy('id')
                ->map(function ($item) {
                    return [
                        'id' => (int) $item->id,
                        'nama' => $item->paket?->nama ?? $item->produk?->nama ?? '-',
                        'jenis' => $item->paket_id ? 'PAKET' : 'PRODUK',
                        'qty' => round((float) $item->qty, 2),
                        'harga' => round((float) $item->harga, 2),
                        'diskon' => round((float) $item->diskon, 2),
                        'subtotal' => round((float) $item->subtotal, 2),
                        'is_void' => (bool) $item->is_void,
                    ];
                })
                ->values()
                ->all(),
            'payments' => $order->pembayaran
                ->sortBy('id')
                ->map(function ($payment) {
                    return [
                        'id' => (int) $payment->id,
                        'metode' => $payment->metodePembayaran?->nama ?? '-',
                        'tipe' => (string) $payment->tipe,
                        'nominal' => round((float) $payment->nominal, 2),
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
