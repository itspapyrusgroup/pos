<?php

namespace App\Http\Controllers;

use App\Models\BookingStudio;
use App\Models\Cabang;
use App\Models\CabangSalesMode;
use App\Models\DiskonOtomatis;
use App\Models\KantongOrder;
use App\Models\KartuStok;
use App\Models\Pelanggan;
use App\Models\PenjualanRequestLog;
use App\Models\PesananPenjualan;
use App\Models\PesananPenjualanItem;
use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\TemplateHargaItem;
use App\Models\User;
use App\Models\VoucherPromosi;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InputOrderController extends Controller
{
    private array $allowMinusStockCabangCache = [];

    public function index()
    {
        $cabangDefaultId = $this->activeCabangId();
        $this->ensureCabangAccessible((int) $cabangDefaultId);

        $salesModeCabang = CabangSalesMode::query()
            ->with('salesMode')
            ->where('cabang_id', $cabangDefaultId)
            ->where('status', true)
            ->get();

        $activeCabang = Cabang::query()->find($cabangDefaultId);
        $cabangTersedia = $this->accessibleCabangQuery()->get(['id', 'nama']);

        $userAktif = User::query()
            ->with([
                'role:id,nama',
                'cabang:id,nama',
                'karyawan:id,user_id,divisi_id,jabatan_id',
                'karyawan.divisi:id,nama',
                'karyawan.jabatan:id,nama,level',
            ])
            ->where('status', true)
            ->when($cabangDefaultId > 0, function ($query) use ($cabangDefaultId) {
                $query->whereHas('cabang', function ($inner) use ($cabangDefaultId) {
                    $inner->where('cabang.id', $cabangDefaultId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        $studioUsers = $userAktif->filter(function ($user) {
            $divisiNama = strtoupper(trim((string) ($user->karyawan?->divisi?->nama ?? '')));
            return $divisiNama === 'STUDIO';
        })->values();

        $csCandidates = $userAktif->values();
        $spvCandidates = $userAktif->values();
        $fotograferCandidates = $studioUsers;

        return view('pages.pos.input-order', [
            'cabangDefaultId' => $cabangDefaultId,
            'activeCabang' => $activeCabang,
            'cabangTersedia' => $cabangTersedia,
            'csCandidates' => $csCandidates,
            'spvCandidates' => $spvCandidates,
            'fotograferCandidates' => $fotograferCandidates,
            'canTransaksiBackdate' => (bool) auth()->user()?->hasPermission('pos.transaksi.backdate'),
            'salesModesCabang' => $salesModeCabang->map(function ($item) {
                return [
                    'sales_mode_id' => $item->sales_mode_id,
                    'template_harga_id' => $item->template_harga_id,
                    'nama' => $item->salesMode?->nama ?? 'Sales Mode',
                ];
            })->values(),
        ]);
    }

    public function cariProduk(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $cabangId = (int) $request->query('cabang_id');
        $this->ensureCabangAccessible($cabangId);
        $salesModeId = (int) $request->query('sales_mode_id');
        $limit = 15;

        $templateHargaId = CabangSalesMode::query()
            ->where('cabang_id', $cabangId)
            ->where('sales_mode_id', $salesModeId)
            ->where('status', true)
            ->value('template_harga_id');

        $produkRows = Produk::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', '%' . $q . '%')
                        ->orWhere('kode', 'like', '%' . $q . '%');
                });
            })
            ->where('status', true)
            ->limit($limit)
            ->get(['id', 'kode', 'nama', 'harga_default']);

        $paketRows = \App\Models\Paket::query()
            ->with(['items.produk:id,kode,nama,harga_default'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', '%' . $q . '%')
                        ->orWhere('kode', 'like', '%' . $q . '%');
                });
            })
            ->where('status', true)
            ->limit($limit)
            ->get(['id', 'kode', 'nama', 'harga_default']);

        $produkHargaMap = [];
        $paketHargaMap = [];
        if ($templateHargaId) {
            $produkIds = $produkRows->pluck('id')->all();
            $paketIds = $paketRows->pluck('id')->all();

            if (!empty($produkIds)) {
                $produkHargaMap = TemplateHargaItem::query()
                    ->where('template_harga_id', $templateHargaId)
                    ->where('jenis_item', 'PRODUK')
                    ->where('status', true)
                    ->whereIn('item_id', $produkIds)
                    ->pluck('harga', 'item_id')
                    ->all();
            }

            if (!empty($paketIds)) {
                $paketHargaMap = TemplateHargaItem::query()
                    ->where('template_harga_id', $templateHargaId)
                    ->where('jenis_item', 'PAKET')
                    ->where('status', true)
                    ->whereIn('item_id', $paketIds)
                    ->pluck('harga', 'item_id')
                    ->all();
            }
        }

        $produk = $produkRows->map(function ($item) use ($produkHargaMap) {
            $harga = $produkHargaMap[$item->id] ?? $item->harga_default;

            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'tipe' => 'PRODUK',
                'harga_default' => $harga,
            ];
        })->values();

        $paket = $paketRows->map(function ($item) use ($paketHargaMap) {
            $harga = $paketHargaMap[$item->id] ?? $item->harga_default;

            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'tipe' => 'PAKET',
                'harga_default' => $harga,
                'items' => $item->items->map(function ($pi) {
                    return [
                        'produk_id' => (int) $pi->produk_id,
                        'kode' => $pi->produk?->kode ?? '',
                        'nama' => $pi->produk?->nama ?? 'Produk',
                        'qty' => (float) $pi->qty,
                        'harga_default' => (float) ($pi->produk?->harga_default ?? 0),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($produk->concat($paket)->values());
    }

    public function cekKo(Request $request): JsonResponse
    {
        $request->validate([
            'no_ko' => ['required', 'string', 'max:30'],
        ]);

        $noKo = trim((string) $request->query('no_ko'));
        if ($noKo === '') {
            return response()->json([
                'exists' => false,
                'can_add' => false,
            ]);
        }

        $activeCabangId = (int) ($this->activeCabangId() ?? 0);

        $ko = KantongOrder::query()
            ->with([
                'pesananPenjualan.pelanggan',
                'pesananPenjualan.kasir:id,name',
                'pesananPenjualan.cs1:id,name',
                'pesananPenjualan.cs:id,name',
                'pesananPenjualan.cs2:id,name',
                'pesananPenjualan.spv:id,name',
                'pesananPenjualan.fotografer:id,name',
                'pesananPenjualan.items.produk',
                'pesananPenjualan.items.paket',
            ])
            ->where('nomor_ko', $noKo)
            ->when($activeCabangId > 0, fn($q) => $q->where('cabang_id', $activeCabangId))
            ->first();

        if (!$ko || !$ko->pesananPenjualan) {
            return response()->json([
                'exists' => false,
                'can_add' => false,
            ]);
        }

        $order = $ko->pesananPenjualan;
        $isReusable = in_array((string) $order->status_pembayaran, ['CANCELLED', 'VOID'], true);
        $canAdd = !$isReusable;

        if ($isReusable) {
            return response()->json([
                'exists' => false,
                'can_add' => true,
                'reusable' => true,
                'message' => 'KO lama berstatus ' . $order->status_pembayaran . '. Nomor KO bisa dipakai ulang untuk order baru.',
            ]);
        }

        return response()->json([
            'exists' => true,
            'can_add' => $canAdd,
            'message' => 'KO ditemukan. Order akan ditambahkan ke KO ini.',
            'order' => [
                'id' => $order->id,
                'nomor_ko' => $ko->nomor_ko,
                'tanggal_selesai' => $ko->tanggal_selesai?->format('Y-m-d'),
                'nomor_so' => $order->nomor_so,
                'status_pembayaran' => $order->status_pembayaran,
                'total' => (float) $order->total,
                'diskon_otomatis' => (float) ($order->diskon_otomatis ?? 0),
                'paid_total' => (float) $order->paid_total,
                'balance' => (float) $order->balance,
                'sales_mode_id' => $order->sales_mode_id,
                'cs' => $order->cs ? ['id' => (int) $order->cs->id, 'name' => $order->cs->name] : null,
                'cs1' => $order->cs1 ? ['id' => (int) $order->cs1->id, 'name' => $order->cs1->name] : null,
                'cs2' => $order->cs2 ? ['id' => (int) $order->cs2->id, 'name' => $order->cs2->name] : null,
                'spv' => $order->spv ? ['id' => (int) $order->spv->id, 'name' => $order->spv->name] : null,
                'fotografer' => $order->fotografer ? ['id' => (int) $order->fotografer->id, 'name' => $order->fotografer->name] : null,
                'pelanggan' => [
                    'nama' => $order->customer_name ?? $order->pelanggan?->nama,
                    'no_hp' => $order->customer_phone ?? $order->pelanggan?->no_hp,
                    'alamat' => $order->customer_address ?? $order->pelanggan?->alamat,
                ],
                'items' => $order->items->where('is_void', false)->values()->map(function ($item) {
                    return [
                        'id' => (int) $item->id,
                        'jenis_item' => $item->paket_id ? 'PAKET' : 'PRODUK',
                        'produk_id' => $item->produk_id ? (int) $item->produk_id : null,
                        'paket_id' => $item->paket_id ? (int) $item->paket_id : null,
                        'kode' => $item->produk?->kode ?? $item->paket?->kode ?? null,
                        'nama' => $item->produk?->nama ?? $item->paket?->nama ?? '-',
                        'harga' => (float) $item->harga,
                        'diskon' => (float) $item->diskon,
                        'qty' => (float) $item->qty,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })->values(),
            ],
        ]);
    }

    public function promosiTersedia(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cabang_id' => ['required', 'exists:cabang,id'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tanggal' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.jenis_item' => ['required_with:items', 'in:PRODUK,PAKET'],
            'items.*.paket_id' => ['nullable', 'exists:paket,id'],
            'items.*.qty' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.harga' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.diskon' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cabangId = (int) $validated['cabang_id'];
        $this->ensureCabangAccessible($cabangId);
        $subtotal = (float) $validated['subtotal'];
        $tanggal = now();
        if (isset($validated['tanggal'])) {
            $tanggalInput = trim((string) $validated['tanggal']);
            $parsed = Carbon::parse($tanggalInput);
            if (strlen($tanggalInput) <= 10) {
                $parsed->setTimeFrom(now());
            }
            $tanggal = $parsed;
        }
        $hariKe = (int) $tanggal->dayOfWeekIso;
        $itemPayload = $this->buildItemPayload($validated['items'] ?? []);

        $voucher = VoucherPromosi::query()
            ->with('cabangs:id')
            ->where('status', true)
            ->whereDate('aktif_mulai', '<=', $tanggal->toDateString())
            ->whereDate('aktif_sampai', '>=', $tanggal->toDateString())
            ->where('minimum_pembelian', '<=', $subtotal)
            ->where(function ($q) use ($cabangId) {
                $q->whereHas('cabangs', function ($inner) use ($cabangId) {
                    $inner->where('cabang.id', $cabangId);
                })->orWhere(function ($inner) use ($cabangId) {
                    $inner->whereDoesntHave('cabangs')
                        ->where(function ($legacy) use ($cabangId) {
                            $legacy->whereNull('cabang_id')->orWhere('cabang_id', $cabangId);
                        });
                });
            })
            ->where(function ($q) {
                $q->whereNull('kuota')->orWhereColumn('terpakai', '<', 'kuota');
            })
            ->get()
            ->filter(function ($item) use ($hariKe, $tanggal) {
                if (empty($item->hari_aktif) || !is_array($item->hari_aktif)) {
                    return $this->isPromoAktifPadaJam($item, $tanggal);
                }
                $hariSesuai = in_array($hariKe, array_map('intval', $item->hari_aktif), true);
                return $hariSesuai && $this->isPromoAktifPadaJam($item, $tanggal);
            })
            ->map(function ($item) use ($subtotal) {
                $diskon = $item->tipe_diskon === 'PERSEN'
                    ? ($subtotal * ((float) $item->nilai_diskon / 100))
                    : (float) $item->nilai_diskon;
                $diskon = min($diskon, $subtotal);

                return [
                    'kode' => $item->kode,
                    'nama' => $item->nama,
                    'sumber' => 'VOUCHER',
                    'tipe_diskon' => $item->tipe_diskon,
                    'nilai_diskon' => (float) $item->nilai_diskon,
                    'diskon_hitung' => $diskon,
                    'minimum_pembelian' => (float) $item->minimum_pembelian,
                ];
            })
            ->values();

        $diskonOtomatis = DiskonOtomatis::query()
            ->with(['cabangs:id', 'pakets:id'])
            ->where('status', true)
            ->whereDate('aktif_mulai', '<=', $tanggal->toDateString())
            ->whereDate('aktif_sampai', '>=', $tanggal->toDateString())
            ->where('minimum_pembelian', '<=', $subtotal)
            ->where(function ($q) use ($cabangId) {
                $q->whereHas('cabangs', function ($inner) use ($cabangId) {
                    $inner->where('cabang.id', $cabangId);
                })->orWhere(function ($inner) use ($cabangId) {
                    $inner->whereDoesntHave('cabangs')
                        ->where(function ($legacy) use ($cabangId) {
                            $legacy->whereNull('cabang_id')->orWhere('cabang_id', $cabangId);
                        });
                });
            })
            ->get()
            ->filter(function ($item) use ($hariKe, $tanggal) {
                if (empty($item->hari_aktif) || !is_array($item->hari_aktif)) {
                    return $this->isPromoAktifPadaJam($item, $tanggal);
                }
                $hariSesuai = in_array($hariKe, array_map('intval', $item->hari_aktif), true);
                return $hariSesuai && $this->isPromoAktifPadaJam($item, $tanggal);
            })
            ->map(function ($item) use ($subtotal, $itemPayload) {
                $eligiblePaketIds = $item->pakets->pluck('id')->map(fn($id) => (int) $id)->all();
                $subtotalAcuan = $this->resolvePromoEligibleSubtotal($itemPayload, $eligiblePaketIds, $subtotal);
                $diskon = $item->tipe_diskon === 'PERSEN'
                    ? ($subtotalAcuan * ((float) $item->nilai_diskon / 100))
                    : (float) $item->nilai_diskon;
                $diskon = min($diskon, $subtotalAcuan);

                return [
                    'kode' => 'AUTO-' . $item->id,
                    'nama' => $item->nama,
                    'sumber' => 'OTOMATIS',
                    'tipe_diskon' => $item->tipe_diskon,
                    'nilai_diskon' => (float) $item->nilai_diskon,
                    'diskon_hitung' => $diskon,
                    'minimum_pembelian' => (float) $item->minimum_pembelian,
                    'paket_ids' => $eligiblePaketIds,
                ];
            })
            ->filter(fn($item) => (float) ($item['diskon_hitung'] ?? 0) > 0)
            ->values();

        return response()->json($voucher->concat($diskonOtomatis)->values());
    }

    public function simpan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_request_id' => ['required', 'string', 'max:100'],
            'cabang_id' => ['required', 'exists:cabang,id'],
            'sales_mode_id' => ['required', 'exists:sales_mode,id'],
            'tanggal' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
            'address' => ['nullable', 'string'],
            'order_note' => ['nullable', 'string'],
            'cs_user_id' => ['nullable', 'exists:users,id'],
            'cs1_user_id' => ['nullable', 'exists:users,id'],
            'cs2_user_id' => ['nullable', 'exists:users,id'],
            'spv_user_id' => ['nullable', 'exists:users,id'],
            'fotografer_user_id' => ['nullable', 'exists:users,id'],
            'is_booking' => ['nullable', 'boolean'],
            'booking_date' => ['nullable', 'date', 'required_if:is_booking,1'],
            'booking_time' => ['nullable', 'date_format:H:i', 'required_if:is_booking,1'],
            'tanggal_selesai' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.jenis_item' => ['required', 'in:PRODUK,PAKET'],
            'items.*.produk_id' => ['nullable', 'exists:produk,id'],
            'items.*.paket_id' => ['nullable', 'exists:paket,id'],
            'items.*.custom_paket_items' => ['nullable', 'array'],
            'items.*.custom_paket_items.*.produk_id' => ['nullable', 'exists:produk,id'],
            'items.*.custom_paket_items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga' => ['required', 'numeric', 'min:0'],
            'items.*.diskon' => ['nullable', 'integer', 'min:0'],
            'allow_minus_stock' => ['nullable', 'boolean'],
            'has_price_override' => ['nullable', 'boolean'],
            'authorizer_user_id' => ['nullable', 'exists:users,id'],
            'promo_kode' => ['nullable', 'string', 'max:30'],
            'promo_sumber' => ['nullable', 'in:VOUCHER,OTOMATIS'],
            'promo_diskon' => ['nullable', 'numeric', 'min:0'],
            'no_ko' => ['nullable', 'string', 'max:30'],
        ]);

        $today = now()->toDateString();
        $canBackdate = (bool) auth()->user()?->hasPermission('pos.transaksi.backdate');
        $transactionAt = Carbon::parse((string) $validated['tanggal'])->setTimeFrom(now());
        if (!$canBackdate && $transactionAt->toDateString() !== $today) {
            throw ValidationException::withMessages([
                'tanggal' => ['Anda tidak memiliki akses input transaksi backdate. Tanggal transaksi harus hari ini.'],
            ]);
        }

        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        foreach ($validated['items'] as $idx => $item) {
            if ($item['jenis_item'] === 'PRODUK' && empty($item['produk_id'])) {
                throw ValidationException::withMessages([
                    "items.$idx.produk_id" => ['Produk wajib dipilih untuk item jenis PRODUK.'],
                ]);
            }
            if ($item['jenis_item'] === 'PAKET' && empty($item['paket_id'])) {
                throw ValidationException::withMessages([
                    "items.$idx.paket_id" => ['Paket wajib dipilih untuk item jenis PAKET.'],
                ]);
            }
        }

        // Pengecekan Otorisasi Perubahan Harga
        $hasPriceOverride = (bool) ($validated['has_price_override'] ?? false);
        if ($hasPriceOverride) {
            $currentUser = auth()->user();
            $hasOverridePerm = $currentUser && $currentUser->hasPermission('pos.transaksi.override_price');
            if (!$hasOverridePerm) {
                $authorizerId = (int) ($validated['authorizer_user_id'] ?? 0);
                if ($authorizerId <= 0) {
                    throw ValidationException::withMessages([
                        'price_override' => ['Perubahan harga memerlukan otorisasi dari SPV / Manager.'],
                    ]);
                }
                $authorizer = User::query()->find($authorizerId);
                if (!$authorizer || !$authorizer->status || !$authorizer->hasPermission('pos.transaksi.override_price')) {
                    throw ValidationException::withMessages([
                        'price_override' => ['User otorisator tidak valid atau tidak memiliki izin otorisasi harga.'],
                    ]);
                }
            }
        }

        // Pengecekan Stok Kosong / Kurang sebelum transaksi
        $allowMinusStock = (bool) ($validated['allow_minus_stock'] ?? false);
        if (!$allowMinusStock && !empty($validated['items'])) {
            $requiredStockByProduct = [];
            foreach ($validated['items'] as $item) {
                if (($item['jenis_item'] ?? '') === 'PRODUK' && !empty($item['produk_id'])) {
                    $pid = (int) $item['produk_id'];
                    $requiredStockByProduct[$pid] = ($requiredStockByProduct[$pid] ?? 0) + (float) $item['qty'];
                } elseif (($item['jenis_item'] ?? '') === 'PAKET' && !empty($item['paket_id'])) {
                    if (!empty($item['custom_paket_items']) && is_array($item['custom_paket_items'])) {
                        foreach ($item['custom_paket_items'] as $cItem) {
                            $pid = (int) ($cItem['produk_id'] ?? 0);
                            if ($pid > 0) {
                                $requiredStockByProduct[$pid] = ($requiredStockByProduct[$pid] ?? 0) + ((float) ($cItem['qty'] ?? 1) * (float) $item['qty']);
                            }
                        }
                    } else {
                        $paket = \App\Models\Paket::query()->with('items')->find($item['paket_id']);
                        if ($paket) {
                            foreach ($paket->items as $paketItem) {
                                $pid = (int) $paketItem->produk_id;
                                $requiredStockByProduct[$pid] = ($requiredStockByProduct[$pid] ?? 0) + ((float) $paketItem->qty * (float) $item['qty']);
                            }
                        }
                    }
                }
            }

            if (!empty($requiredStockByProduct)) {
                $insufficientItems = [];
                $cabangId = (int) $validated['cabang_id'];
                foreach ($requiredStockByProduct as $productId => $qtyNeeded) {
                    $produk = Produk::query()->find($productId);
                    if ($produk && $produk->track_stok) {
                        $stok = StokCabang::query()->firstOrCreate(
                            ['produk_id' => $productId, 'cabang_id' => $cabangId],
                            ['qty' => 0, 'qty_on_order' => 0]
                        );
                        $stokTersedia = (float) $stok->qty - (float) $stok->qty_on_order;
                        if ($stokTersedia < $qtyNeeded) {
                            $insufficientItems[] = [
                                'produk_id' => (int) $produk->id,
                                'kode' => $produk->kode ?? '',
                                'nama' => $produk->nama,
                                'stok_tersedia' => $stokTersedia,
                                'qty_diminta' => $qtyNeeded,
                                'defisit' => max(0, $qtyNeeded - $stokTersedia),
                            ];
                        }
                    }
                }

                if (!empty($insufficientItems)) {
                    return response()->json([
                        'status' => 'INSUFFICIENT_STOCK',
                        'message' => 'Sebagian stok barang tidak mencukupi atau kosong di cabang ini.',
                        'insufficient_items' => $insufficientItems,
                    ], 422);
                }
            }
        }

        try {
            $result = DB::transaction(function () use ($validated, $transactionAt) {
                $currentUserId = Auth::id();
                $requestLog = $this->reservePenjualanRequestLog(
                    (string) $validated['client_request_id'],
                    (int) $validated['cabang_id'],
                    $currentUserId ? (int) $currentUserId : null
                );

                $mapping = CabangSalesMode::query()
                    ->where('cabang_id', $validated['cabang_id'])
                    ->where('sales_mode_id', $validated['sales_mode_id'])
                    ->where('status', true)
                    ->first();

                if (!$mapping) {
                    throw ValidationException::withMessages([
                        'sales_mode_id' => ['Sales mode tidak aktif untuk cabang ini.'],
                    ]);
                }

                $templateHargaId = $mapping->template_harga_id;
                $itemPayload = $this->buildItemPayload($validated['items'] ?? []);
                [$itemPayload, $promoDiskonTervalidasi] = $this->applyPromoToItemPayload(
                    $itemPayload,
                    (string) ($validated['promo_sumber'] ?? ''),
                    (string) ($validated['promo_kode'] ?? ''),
                    $transactionAt,
                    (int) $validated['cabang_id']
                );
                $subtotalItem = (float) collect($itemPayload)->sum('subtotal');
                $promoDiskon = min((float) $promoDiskonTervalidasi, $subtotalItem);
                $koInput = trim((string) ($validated['no_ko'] ?? ''));

                if ($koInput !== '') {
                    $ko = KantongOrder::query()
                        ->where('nomor_ko', $koInput)
                        ->where('cabang_id', $validated['cabang_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($ko && $ko->pesanan_penjualan_id) {
                        $pesanan = PesananPenjualan::query()->lockForUpdate()->findOrFail($ko->pesanan_penjualan_id);
                        if (in_array((string) $pesanan->status_pembayaran, ['CANCELLED', 'VOID'], true)) {
                            $this->archiveKoForReuse($ko, $transactionAt);
                        } else {
                            if (!empty($validated['tanggal_selesai'])) {
                                $ko->update([
                                    'tanggal_selesai' => $validated['tanggal_selesai'],
                                ]);
                            }

                            $this->appendToExistingOrder($pesanan, $validated, $itemPayload, $promoDiskon, $currentUserId, $transactionAt);
                            $this->markPromoAsUsed($validated['promo_sumber'] ?? null, $validated['promo_kode'] ?? null);

                            $freshOrder = $pesanan->fresh(['kantongOrder']);
                            $nomorKoResult = $freshOrder->kantongOrder?->nomor_ko ?? $koInput;

                            $response = [
                                'success' => true,
                                'message' => 'Item order berhasil ditambahkan ke KO existing ' . $nomorKoResult,
                                'nomor_ko' => $nomorKoResult,
                                'nomor_so' => $freshOrder->nomor_so,
                                'customer_name' => $freshOrder->customer_name,
                                'total' => (float) $freshOrder->total,
                                'order_id' => (int) $freshOrder->id,
                                'queue_url' => route('input-antrian', ['no_ko' => $nomorKoResult]),
                                'mode' => 'APPEND',
                            ];

                            $this->completePenjualanRequestLog($requestLog, $response, $transactionAt);

                            return $response;
                        }
                    }
                }

                $pesananBaru = $this->createNewOrder($validated, $templateHargaId, $itemPayload, $koInput, $promoDiskon, $currentUserId, $transactionAt);
                $this->markPromoAsUsed($validated['promo_sumber'] ?? null, $validated['promo_kode'] ?? null);

                $freshOrder = $pesananBaru->fresh(['kantongOrder']);
                $nomorKoResult = $freshOrder->kantongOrder?->nomor_ko ?? '';

                $response = [
                    'success' => true,
                    'message' => 'Order berhasil disimpan dengan No KO ' . $nomorKoResult,
                    'nomor_ko' => $nomorKoResult,
                    'nomor_so' => $freshOrder->nomor_so,
                    'customer_name' => $freshOrder->customer_name,
                    'total' => (float) $freshOrder->total,
                    'order_id' => (int) $freshOrder->id,
                    'queue_url' => route('input-antrian', ['no_ko' => $nomorKoResult]),
                    'mode' => 'CREATE',
                ];

                $this->completePenjualanRequestLog($requestLog, $response, $transactionAt);

                return $response;
            }, 3);
        } catch (QueryException $e) {
            if ($this->isDuplicatePenjualanRequestException($e)) {
                $duplicateResponse = $this->resolveDuplicatePenjualanResponse((string) $validated['client_request_id']);
                if ($duplicateResponse !== null) {
                    return response()->json($duplicateResponse);
                }

                throw ValidationException::withMessages([
                    'client_request_id' => ['Order yang sama sedang diproses. Cek antrian atau KO sebelum coba lagi.'],
                ]);
            }

            throw $e;
        }

        return response()->json($result);
    }

    private function appendToExistingOrder(PesananPenjualan $pesanan, array $validated, array $itemPayload, float $promoDiskon, ?int $currentUserId, Carbon $transactionAt): void
    {
        if ((int) $pesanan->cabang_id !== (int) $validated['cabang_id']) {
            throw ValidationException::withMessages([
                'no_ko' => ['Cabang transaksi tidak sama dengan cabang KO.'],
            ]);
        }

        $allowMinusStock = (bool) ($validated['allow_minus_stock'] ?? false);
        $totalTambahan = 0;
        if ((int) $pesanan->sales_mode_id !== (int) $validated['sales_mode_id']) {
            throw ValidationException::withMessages([
                'sales_mode_id' => ['Sales mode harus sama dengan transaksi KO sebelumnya.'],
            ]);
        }

        $pelanggan = $this->upsertPelanggan($validated);
        if ($pelanggan && (int) $pesanan->pelanggan_id !== (int) $pelanggan->id) {
            $pesanan->pelanggan_id = $pelanggan->id;
        }

        $totalTambahan = 0;
        $diskonOtomatis = $promoDiskon > 0 ? max($promoDiskon, 0) : (float) $pesanan->diskon_otomatis;

        foreach ($itemPayload as $item) {
            $totalTambahan += (float) $item['subtotal'];
            $orderItem = PesananPenjualanItem::query()->create([
                'pesanan_penjualan_id' => $pesanan->id,
                'produk_id' => $item['produk_id'],
                'paket_id' => $item['paket_id'],
                'custom_paket_items' => !empty($item['custom_paket_items']) ? $item['custom_paket_items'] : null,
                'shift_kasir_id' => null,
                'kasir_user_id' => $currentUserId,
                'qty' => $item['qty'],
                'harga' => $item['harga'],
                'diskon' => $item['diskon'],
                'subtotal' => $item['subtotal'],
                'created_at' => $transactionAt,
                'updated_at' => $transactionAt,
            ]);
            $this->forceSetTimestamps('pesanan_penjualan_item', (int) $orderItem->id, $transactionAt);

            $this->applyStockMutationForItem($item, (int) $validated['cabang_id'], (int) $pesanan->id, $transactionAt, 'DRAFT', $allowMinusStock);
        }

        $netPerubahanTotal = (float) $totalTambahan - $diskonOtomatis;
        $totalBaru = max((float) $pesanan->total + $netPerubahanTotal, 0);
        $paidBaru = (float) $pesanan->paid_total;
        $balanceBaru = max(0, $totalBaru - $paidBaru);

        $statusBaru = (string) $pesanan->status_pembayaran;
        if ($paidBaru <= 0) {
            $statusBaru = 'DRAFT';
        } elseif ($paidBaru < $totalBaru) {
            $statusBaru = 'PARTIALLY_PAID';
        } elseif ($paidBaru >= $totalBaru && $totalBaru > 0) {
            $statusBaru = 'PAID';
        }

        $catatanLama = trim((string) $pesanan->catatan);
        $catatanBaru = trim((string) ($validated['order_note'] ?? ''));
        $gabungCatatan = $catatanLama;
        if ($catatanBaru !== '') {
            $gabungCatatan = $catatanLama === '' ? $catatanBaru : ($catatanLama . PHP_EOL . $catatanBaru);
        }
        if ($promoDiskon > 0) {
            $promoInfo = 'Promo dipakai: ' . ($validated['promo_kode'] ?? '-') . ' (diskon Rp ' . number_format($promoDiskon, 0, ',', '.') . ')';
            $gabungCatatan = $gabungCatatan === '' ? $promoInfo : ($gabungCatatan . PHP_EOL . $promoInfo);
        }

        $pesanan->update([
            'cs_user_id' => $validated['cs_user_id'] ?? $pesanan->cs_user_id,
            'cs1_user_id' => $validated['cs1_user_id'] ?? $pesanan->cs1_user_id,
            'cs2_user_id' => $validated['cs2_user_id'] ?? $pesanan->cs2_user_id,
            'spv_user_id' => $validated['spv_user_id'] ?? $pesanan->spv_user_id,
            'fotografer_user_id' => $validated['fotografer_user_id'] ?? $pesanan->fotografer_user_id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['phone'],
            'customer_address' => $validated['address'] ?? null,
            'total' => $totalBaru,
            'diskon_otomatis' => $diskonOtomatis,
            'paid_total' => $paidBaru,
            'balance' => $balanceBaru,
            'status_pembayaran' => $statusBaru,
            'catatan' => $gabungCatatan !== '' ? $gabungCatatan : null,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('pesanan_penjualan', (int) $pesanan->id, $transactionAt, false);
    }

    private function createNewOrder(array $validated, ?int $templateHargaId, array $itemPayload, string $koInput, float $promoDiskon, ?int $currentUserId, Carbon $transactionAt): PesananPenjualan
    {
        $pelanggan = $this->upsertPelanggan($validated);
        $allowMinusStock = (bool) ($validated['allow_minus_stock'] ?? false);
        $subtotal = (float) collect($itemPayload)->sum('subtotal');
        $diskonOtomatis = max($promoDiskon, 0);
        $total = max($subtotal - $diskonOtomatis, 0);

        $catatanOrder = trim((string) ($validated['order_note'] ?? ''));
        if ($diskonOtomatis > 0) {
            $promoInfo = 'Promo dipakai: ' . ($validated['promo_kode'] ?? '-') . ' (diskon Rp ' . number_format($diskonOtomatis, 0, ',', '.') . ')';
            $catatanOrder = $catatanOrder === '' ? $promoInfo : ($catatanOrder . PHP_EOL . $promoInfo);
        }

        $pesanan = PesananPenjualan::query()->create([
            'nomor_so' => $this->generateNomorSo(),
            'pelanggan_id' => $pelanggan?->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['phone'],
            'customer_address' => $validated['address'] ?? null,
            'cabang_id' => $validated['cabang_id'],
            'sales_mode_id' => $validated['sales_mode_id'],
            'template_harga_id' => $templateHargaId,
            'shift_kasir_id' => null,
            'kasir_user_id' => $currentUserId,
            'cs_user_id' => $validated['cs_user_id'] ?? null,
            'cs1_user_id' => $validated['cs1_user_id'] ?? null,
            'cs2_user_id' => $validated['cs2_user_id'] ?? null,
            'spv_user_id' => $validated['spv_user_id'] ?? null,
            'fotografer_user_id' => $validated['fotografer_user_id'] ?? null,
            'total' => $total,
            'diskon_otomatis' => $diskonOtomatis,
            'paid_total' => 0,
            'balance' => $total,
            'status_pembayaran' => 'DRAFT',
            'catatan' => $catatanOrder !== '' ? $catatanOrder : null,
            'created_at' => $transactionAt,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('pesanan_penjualan', (int) $pesanan->id, $transactionAt);

        foreach ($itemPayload as $item) {
            $orderItem = PesananPenjualanItem::query()->create([
                'pesanan_penjualan_id' => $pesanan->id,
                'produk_id' => $item['produk_id'],
                'paket_id' => $item['paket_id'],
                'custom_paket_items' => !empty($item['custom_paket_items']) ? $item['custom_paket_items'] : null,
                'shift_kasir_id' => null,
                'kasir_user_id' => $currentUserId,
                'qty' => $item['qty'],
                'harga' => $item['harga'],
                'diskon' => $item['diskon'],
                'subtotal' => $item['subtotal'],
                'created_at' => $transactionAt,
                'updated_at' => $transactionAt,
            ]);
            $this->forceSetTimestamps('pesanan_penjualan_item', (int) $orderItem->id, $transactionAt);

            $this->applyStockMutationForItem($item, (int) $validated['cabang_id'], (int) $pesanan->id, $transactionAt, 'DRAFT', $allowMinusStock);
        }

        if ($koInput !== '') {
            $existingKoGlobal = KantongOrder::query()
                ->where('nomor_ko', $koInput)
                ->first();
            if ($existingKoGlobal && (int) $existingKoGlobal->cabang_id !== (int) $validated['cabang_id']) {
                throw ValidationException::withMessages([
                    'no_ko' => ['Nomor KO sudah dipakai di cabang lain. Silakan gunakan nomor KO lain.'],
                ]);
            }
        }

        $ko = KantongOrder::query()->create([
            'nomor_ko' => $koInput !== '' ? $koInput : $this->generateNomorKo(),
            'pesanan_penjualan_id' => $pesanan->id,
            'cabang_id' => $validated['cabang_id'],
            'designer_id' => Auth::id(),
            'status' => 'CREATED',
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'catatan' => $validated['order_note'] ?? null,
            'created_at' => $transactionAt,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('kantong_order', (int) $ko->id, $transactionAt);

        if ((bool) ($validated['is_booking'] ?? false)) {
            $this->createBookingFromOrder($pesanan, $validated);
        }

        return $pesanan->fresh();
    }

    private function buildItemPayload(array $items): array
    {
        $payload = [];
        foreach ($items as $item) {
            $subtotal = ((float) $item['qty'] * (float) $item['harga']) - (float) ($item['diskon'] ?? 0);
            $payload[] = [
                'jenis_item' => $item['jenis_item'],
                'produk_id' => isset($item['produk_id']) ? (int) $item['produk_id'] : null,
                'paket_id' => isset($item['paket_id']) ? (int) $item['paket_id'] : null,
                'custom_paket_items' => !empty($item['custom_paket_items']) && is_array($item['custom_paket_items']) ? $item['custom_paket_items'] : null,
                'qty' => (float) $item['qty'],
                'harga' => (float) $item['harga'],
                'diskon' => (float) ($item['diskon'] ?? 0),
                'subtotal' => $subtotal,
            ];
        }
        return $payload;
    }

    private function resolvePromoEligibleSubtotal(array $items, array $eligiblePaketIds = [], ?float $fallbackSubtotal = null): float
    {
        if (empty($items)) {
            return max((float) ($fallbackSubtotal ?? 0), 0);
        }

        if (empty($eligiblePaketIds)) {
            return max((float) collect($items)->sum('subtotal'), 0);
        }

        $eligibleMap = collect($eligiblePaketIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->flip();

        return max((float) collect($items)
            ->filter(function ($item) use ($eligibleMap) {
                return ($item['jenis_item'] ?? null) === 'PAKET'
                    && isset($item['paket_id'])
                    && $eligibleMap->has((int) $item['paket_id']);
            })
            ->sum('subtotal'), 0);
    }

    private function applyPromoToItemPayload(array $items, string $promoSumber, string $promoKode, Carbon $tanggal, int $cabangId): array
    {
        $promoSumber = strtoupper(trim($promoSumber));
        $promoKode = strtoupper(trim($promoKode));
        if (empty($items) || $promoSumber === '' || $promoKode === '') {
            return [$items, 0.0];
        }

        $subtotalOrder = max((float) collect($items)->sum('subtotal'), 0);
        if ($subtotalOrder <= 0) {
            return [$items, 0.0];
        }

        $promo = null;
        $eligiblePaketIds = [];
        if ($promoSumber === 'OTOMATIS') {
            if (!str_starts_with($promoKode, 'AUTO-')) {
                return [$items, 0.0];
            }
            $diskonId = (int) str_replace('AUTO-', '', $promoKode);
            if ($diskonId <= 0) {
                return [$items, 0.0];
            }

            $promo = DiskonOtomatis::query()
                ->with(['cabangs:id', 'pakets:id'])
                ->whereKey($diskonId)
                ->where('status', true)
                ->first();
            if (!$promo) {
                return [$items, 0.0];
            }

            $eligiblePaketIds = $promo->pakets->pluck('id')->map(fn($id) => (int) $id)->all();
        } elseif ($promoSumber === 'VOUCHER') {
            $promo = VoucherPromosi::query()
                ->with('cabangs:id')
                ->where('kode', $promoKode)
                ->where('status', true)
                ->first();
            if (!$promo) {
                return [$items, 0.0];
            }
        } else {
            return [$items, 0.0];
        }

        if (!$this->isPromoAktifPadaJam($promo, $tanggal)) {
            return [$items, 0.0];
        }
        if ($tanggal->toDateString() < $promo->aktif_mulai->toDateString() || $tanggal->toDateString() > $promo->aktif_sampai->toDateString()) {
            return [$items, 0.0];
        }

        if (!empty($promo->hari_aktif) && is_array($promo->hari_aktif)) {
            $hariKe = (int) $tanggal->dayOfWeekIso;
            $hariSesuai = in_array($hariKe, array_map('intval', $promo->hari_aktif), true);
            if (!$hariSesuai) {
                return [$items, 0.0];
            }
        }

        $cabangValid = $promo->cabangs->pluck('id')->map(fn($id) => (int) $id)->all();
        if (!empty($cabangValid) && !in_array($cabangId, $cabangValid, true)) {
            return [$items, 0.0];
        }
        if (empty($cabangValid) && !empty($promo->cabang_id) && (int) $promo->cabang_id !== $cabangId) {
            return [$items, 0.0];
        }

        if ($promoSumber === 'VOUCHER' && $promo->kuota !== null && (int) $promo->terpakai >= (int) $promo->kuota) {
            return [$items, 0.0];
        }

        if ($subtotalOrder < (float) ($promo->minimum_pembelian ?? 0)) {
            return [$items, 0.0];
        }

        $subtotalEligible = $this->resolvePromoEligibleSubtotal($items, $eligiblePaketIds, $subtotalOrder);
        if ($subtotalEligible <= 0) {
            return [$items, 0.0];
        }

        $diskonTotal = $promo->tipe_diskon === 'PERSEN'
            ? ($subtotalEligible * ((float) $promo->nilai_diskon / 100))
            : (float) $promo->nilai_diskon;
        $diskonTotal = min($diskonTotal, $subtotalEligible);
        if ($diskonTotal <= 0) {
            return [$items, 0.0];
        }

        if (empty($eligiblePaketIds)) {
            return [$items, max($diskonTotal, 0)];
        }

        $eligibleIndexes = [];
        foreach ($items as $idx => $item) {
            if (($item['jenis_item'] ?? null) === 'PAKET'
                && in_array((int) ($item['paket_id'] ?? 0), $eligiblePaketIds, true)
                && (float) ($item['subtotal'] ?? 0) > 0
            ) {
                $eligibleIndexes[] = $idx;
            }
        }

        if (empty($eligibleIndexes)) {
            return [$items, 0.0];
        }

        $remaining = $diskonTotal;
        $totalEligibleSubtotal = (float) collect($eligibleIndexes)->sum(fn($idx) => (float) ($items[$idx]['subtotal'] ?? 0));
        foreach ($eligibleIndexes as $n => $idx) {
            $itemSubtotal = (float) ($items[$idx]['subtotal'] ?? 0);
            if ($itemSubtotal <= 0) {
                continue;
            }

            if ($n === (count($eligibleIndexes) - 1)) {
                $alokasi = $remaining;
            } else {
                $alokasi = $totalEligibleSubtotal > 0 ? ($diskonTotal * ($itemSubtotal / $totalEligibleSubtotal)) : 0;
                $alokasi = min($alokasi, $remaining);
            }
            $alokasi = min($alokasi, $itemSubtotal);

            $items[$idx]['diskon'] = (float) ($items[$idx]['diskon'] ?? 0) + $alokasi;
            $items[$idx]['subtotal'] = max($itemSubtotal - $alokasi, 0);
            $remaining -= $alokasi;
        }

        return [$items, max($diskonTotal - max($remaining, 0), 0)];
    }

    private function isPromoAktifPadaJam(object $promo, Carbon $tanggal): bool
    {
        if ((bool) ($promo->aktif_24_jam ?? false)) {
            return true;
        }

        $jamMulai = (string) ($promo->jam_mulai ?? '');
        $jamSampai = (string) ($promo->jam_sampai ?? '');
        if ($jamMulai === '' || $jamSampai === '') {
            return true;
        }

        $current = $tanggal->format('H:i:s');

        if ($jamMulai <= $jamSampai) {
            return $current >= $jamMulai && $current <= $jamSampai;
        }

        return $current >= $jamMulai || $current <= $jamSampai;
    }

    private function upsertPelanggan(array $validated): ?Pelanggan
    {
        $phone = trim((string) ($validated['phone'] ?? ''));
        $name = trim((string) ($validated['customer_name'] ?? ''));
        $address = $validated['address'] ?? null;

        $pelanggan = Pelanggan::query()
            ->where('no_hp', $phone)
            ->where('nama', $name)
            ->first();

        if ($pelanggan) {
            return $pelanggan;
        }

        return Pelanggan::query()->create([
            'no_hp' => $phone,
            'nama' => $name,
            'alamat' => $address,
            'catatan' => null,
        ]);
    }

    private function applyStockMutationForItem(array $item, int $cabangId, int $pesananId, Carbon $transactionAt, string $statusPembayaran = 'DRAFT', bool $allowMinusStock = false): void
    {
        if ($item['jenis_item'] === 'PRODUK' && $item['produk_id']) {
            $produk = Produk::query()->find($item['produk_id']);
            if ($produk && $produk->track_stok) {
                $this->kurangiStok($produk->id, $cabangId, (float) $item['qty'], $pesananId, $transactionAt, $statusPembayaran, $allowMinusStock);
            }
        }

        if ($item['jenis_item'] === 'PAKET' && $item['paket_id']) {
            if (!empty($item['custom_paket_items']) && is_array($item['custom_paket_items'])) {
                foreach ($item['custom_paket_items'] as $customItem) {
                    $produkBom = Produk::query()->find($customItem['produk_id'] ?? null);
                    if ($produkBom && $produkBom->track_stok) {
                        $qtyKeluar = (float) ($customItem['qty'] ?? 1) * (float) $item['qty'];
                        $this->kurangiStok($produkBom->id, $cabangId, $qtyKeluar, $pesananId, $transactionAt, $statusPembayaran, $allowMinusStock);
                    }
                }
            } else {
                $paket = \App\Models\Paket::query()->with('items')->find($item['paket_id']);
                if ($paket) {
                    foreach ($paket->items as $paketItem) {
                        $produkBom = Produk::query()->find($paketItem->produk_id);
                        if ($produkBom && $produkBom->track_stok) {
                            $qtyKeluar = (float) $paketItem->qty * (float) $item['qty'];
                            $this->kurangiStok($produkBom->id, $cabangId, $qtyKeluar, $pesananId, $transactionAt, $statusPembayaran, $allowMinusStock);
                        }
                    }
                }
            }
        }
    }

    private function kurangiStok(int $produkId, int $cabangId, float $qtyKeluar, int $referensiId, Carbon $transactionAt, string $statusPembayaran = 'DRAFT', bool $forceAllowNegative = false): void
    {
        $stok = StokCabang::query()->firstOrCreate(
            ['produk_id' => $produkId, 'cabang_id' => $cabangId],
            ['qty' => 0, 'qty_on_order' => 0]
        );

        $allowNegative = $forceAllowNegative || $this->allowMinusStockByCabang($cabangId);
        $isBooking = in_array($statusPembayaran, ['DRAFT', 'PARTIALLY_PAID'], true);

        if ($isBooking) {
            $stokTersedia = (float) $stok->qty - (float) $stok->qty_on_order;
            if (!$allowNegative && ($stokTersedia - $qtyKeluar) < 0) {
                throw ValidationException::withMessages([
                    'items' => ['Stok tersedia tidak mencukupi untuk salah satu produk.'],
                ]);
            }

            $stok->update([
                'qty_on_order' => (float) $stok->qty_on_order + $qtyKeluar
            ]);
            $saldoAkhir = (float) $stok->qty;
        } else {
            $saldoAkhir = (float) $stok->qty - $qtyKeluar;
            if (!$allowNegative && $saldoAkhir < 0) {
                throw ValidationException::withMessages([
                    'items' => ['Stok tidak mencukupi untuk salah satu produk.'],
                ]);
            }

            $stok->update(['qty' => $saldoAkhir]);
        }

        $kartuStok = KartuStok::query()->create([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tipe_mutasi' => 'PENJUALAN',
            'referensi_tipe' => 'pesanan_penjualan',
            'referensi_id' => $referensiId,
            'qty_masuk' => 0,
            'qty_keluar' => $isBooking ? 0 : $qtyKeluar,
            'saldo_akhir' => $saldoAkhir,
            'catatan' => 'Reservasi stok On-Order dari Input Order POS (status draft/belum bayar)',
            'tanggal_mutasi' => $transactionAt,
            'created_at' => $transactionAt,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('kartu_stok', (int) $kartuStok->id, $transactionAt);
    }

    private function allowMinusStockByCabang(int $cabangId): bool
    {
        if (isset($this->allowMinusStockCabangCache[$cabangId])) {
            return $this->allowMinusStockCabangCache[$cabangId];
        }

        $cabangAllow = Cabang::query()->where('id', $cabangId)->value('allow_minus_stock');
        $allow = is_null($cabangAllow)
            ? (bool) config('pos.izinkan_stok_negatif', false)
            : (bool) $cabangAllow;

        $this->allowMinusStockCabangCache[$cabangId] = $allow;
        return $allow;
    }

    private function markPromoAsUsed(?string $sumber, ?string $kode): void
    {
        if (!$sumber || !$kode) {
            return;
        }

        if ($sumber === 'VOUCHER') {
            $voucher = VoucherPromosi::query()->where('kode', $kode)->lockForUpdate()->first();
            if ($voucher) {
                $voucher->increment('terpakai');
            }
        }
    }

    private function createBookingFromOrder(PesananPenjualan $pesanan, array $validated): void
    {
        $bookingDate = trim((string) ($validated['booking_date'] ?? ''));
        $bookingTime = trim((string) ($validated['booking_time'] ?? ''));
        if ($bookingTime === '') {
            $bookingTime = '23:59';
        }
        $tanggalBooking = $bookingDate !== ''
            ? Carbon::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $bookingTime)
            : now();

        BookingStudio::query()->create([
            'nomor_booking' => $this->generateNomorBooking(),
            'pesanan_penjualan_id' => $pesanan->id,
            'pelanggan_id' => $pesanan->pelanggan_id,
            'cabang_id' => $pesanan->cabang_id,
            'studio_id' => null,
            'tanggal_booking' => $tanggalBooking,
            'status' => 'BOOKED_UNPAID',
        ]);
    }

    private function generateNomorBooking(): string
    {
        $prefix = 'BK-' . now()->format('Ymd') . '-';
        $last = BookingStudio::query()
            ->where('nomor_booking', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_booking');

        $next = 1;
        if ($last) {
            $tail = (int) substr($last, -4);
            $next = $tail + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateNomorSo(): string
    {
        $prefix = 'SO-' . now()->format('Ymd') . '-';
        $last = PesananPenjualan::query()
            ->where('nomor_so', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_so');

        $next = 1;
        if ($last) {
            $tail = (int) substr($last, -4);
            $next = $tail + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateNomorKo(): string
    {
        $prefix = 'KO-' . now()->format('Ymd') . '-';
        $last = KantongOrder::query()
            ->where('nomor_ko', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_ko');

        $next = 1;
        if ($last) {
            $tail = (int) substr($last, -4);
            $next = $tail + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function archiveKoForReuse(KantongOrder $ko, Carbon $transactionAt): void
    {
        $nomorAsli = trim((string) $ko->nomor_ko);
        if ($nomorAsli === '') {
            return;
        }

        $suffix = '-V' . (int) $ko->id;
        $baseLength = max(1, 30 - strlen($suffix));
        $nomorArsip = substr($nomorAsli, 0, $baseLength) . $suffix;
        $catatanArsip = 'Nomor asli ' . $nomorAsli . ' diarsipkan agar bisa dipakai ulang pada ' . $transactionAt->format('Y-m-d H:i:s') . '.';
        $catatanLama = trim((string) $ko->catatan);

        $ko->update([
            'nomor_ko' => $nomorArsip,
            'catatan' => $catatanLama === '' ? $catatanArsip : ($catatanLama . PHP_EOL . $catatanArsip),
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('kantong_order', (int) $ko->id, $transactionAt, false);
    }

    private function forceSetTimestamps(string $table, int $id, Carbon $timestamp, bool $withCreatedAt = true): void
    {
        $payload = ['updated_at' => $timestamp];
        if ($withCreatedAt) {
            $payload['created_at'] = $timestamp;
        }

        DB::table($table)->where('id', $id)->update($payload);
    }

    private function reservePenjualanRequestLog(string $clientRequestId, int $cabangId, ?int $userId): PenjualanRequestLog
    {
        return PenjualanRequestLog::query()->create([
            'client_request_id' => $clientRequestId,
            'user_id' => $userId,
            'cabang_id' => $cabangId,
            'status' => 'PROCESSING',
        ]);
    }

    private function completePenjualanRequestLog(PenjualanRequestLog $requestLog, array $response, Carbon $completedAt): void
    {
        $requestLog->update([
            'pesanan_penjualan_id' => (int) ($response['order_id'] ?? 0) ?: null,
            'status' => 'COMPLETED',
            'mode' => (string) ($response['mode'] ?? ''),
            'message' => (string) ($response['message'] ?? ''),
            'completed_at' => $completedAt,
            'updated_at' => $completedAt,
        ]);
    }

    private function isDuplicatePenjualanRequestException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        if (!in_array($sqlState, ['23000', '23505'], true) && $driverCode !== 1062) {
            return false;
        }

        return str_contains($message, 'penjualan_request_logs')
            || str_contains($message, 'client_request_id');
    }

    private function resolveDuplicatePenjualanResponse(string $clientRequestId): ?array
    {
        $requestLog = PenjualanRequestLog::query()
            ->with(['pesananPenjualan.kantongOrder'])
            ->where('client_request_id', $clientRequestId)
            ->first();

        if (!$requestLog || $requestLog->status !== 'COMPLETED' || !$requestLog->pesananPenjualan) {
            return null;
        }

        $order = $requestLog->pesananPenjualan;
        $nomorKo = $order->kantongOrder?->nomor_ko ?? '';

        return [
            'success' => true,
            'message' => $requestLog->message ?: 'Order berhasil disimpan',
            'nomor_ko' => $nomorKo,
            'nomor_so' => $order->nomor_so,
            'customer_name' => $order->customer_name,
            'total' => (float) $order->total,
            'order_id' => (int) $order->id,
            'queue_url' => route('input-antrian', ['no_ko' => $nomorKo]),
            'mode' => $requestLog->mode ?: 'CREATE',
        ];
    }
}
