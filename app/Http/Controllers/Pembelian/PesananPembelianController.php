<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Controller;
use App\Models\Pemasok;
use App\Models\PenerimaanBarangItem;
use App\Models\PermintaanBarang;
use App\Models\PesananPembelian;
use App\Models\Produk;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PesananPembelianController extends Controller
{
    public function index(Request $request)
    {
        $query = PesananPembelian::query()
            ->with(['pemasok', 'cabang', 'permintaanBarang'])
            ->withCount(['penerimaan', 'faktur'])
            ->latest('id');
        $this->applyCabangScope($query);

        if ($request->filled('nomor_po')) {
            $query->where('nomor_po', 'like', '%' . $request->nomor_po . '%');
        }

        if ($request->filled('pemasok_id')) {
            $query->where('pemasok_id', $request->pemasok_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pesananList = $query->paginate(10)->withQueryString();
        $pesananList->getCollection()->transform(function ($po) {
            $po->outstanding_qty = $this->computeOutstandingQty($po);
            return $po;
        });

        return view('pages.master.pembelian.pesanan.index', [
            'pesananList' => $pesananList,
            'pemasokList' => Pemasok::query()->where('status', true)->orderBy('nama')->get(),
        ]);
    }

    public function create()
    {
        return view('pages.master.pembelian.pesanan.create', $this->formPayload([
            'nomorPo' => $this->generateNomorPo(),
        ]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'permintaan_barang_id' => ['nullable', 'exists:permintaan_barang,id'],
            'pemasok_id' => ['required', 'exists:pemasok,id'],
            'cabang_id' => ['required', 'exists:cabang,id'],
            'tanggal_po' => ['required', 'date'],
            'tanggal_kirim' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
            'produk_id' => ['nullable', 'array'],
            'produk_id.*' => ['nullable', 'exists:produk,id'],
            'qty' => ['nullable', 'array'],
            'qty.*' => ['nullable', 'numeric', 'min:0.01'],
            'harga' => ['nullable', 'array'],
            'harga.*' => ['nullable', 'numeric', 'min:0'],
            'catatan_item' => ['nullable', 'array'],
            'catatan_item.*' => ['nullable', 'string'],
        ]);

        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        DB::transaction(function () use ($validated) {
            $po = PesananPembelian::query()->create([
                'nomor_po' => $this->generateNomorPo(),
                'permintaan_barang_id' => $validated['permintaan_barang_id'] ?? null,
                'pemasok_id' => $validated['pemasok_id'],
                'cabang_id' => $validated['cabang_id'],
                'tanggal_po' => $validated['tanggal_po'],
                'tanggal_kirim' => $validated['tanggal_kirim'] ?? null,
                'status' => 'ORDERED',
                'dibuat_oleh' => auth()->id(),
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $items = $this->resolveItems($validated);
            foreach ($items as $item) {
                $po->items()->create($item);
            }

            if ($po->permintaan_barang_id) {
                PermintaanBarang::query()
                    ->where('id', $po->permintaan_barang_id)
                    ->update(['status' => 'PROCESSED']);
            }
        });

        return redirect()->route('pembelian.pesanan')->with('success', 'Pesanan pembelian berhasil dibuat.');
    }

    public function permintaanOptions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $selectedId = (int) $request->query('selected_id', 0);

        $permintaanQuery = $this->buildPermintaanQuery();
        if ($query !== '') {
            $permintaanQuery->where(function ($q) use ($query) {
                $q->where('nomor_permintaan', 'like', '%' . $query . '%')
                    ->orWhereHas('cabang', function ($cabangQuery) use ($query) {
                        $cabangQuery->where('nama', 'like', '%' . $query . '%');
                    });
            });
        }

        if ($selectedId > 0) {
            $permintaanQuery->orWhere('id', $selectedId);
        }

        $results = $permintaanQuery
            ->latest('id')
            ->limit(20)
            ->get()
            ->unique('id')
            ->values()
            ->map(function ($permintaan) {
                return [
                    'id' => (int) $permintaan->id,
                    'text' => $this->formatPermintaanLabel($permintaan),
                ];
            })->all();

        return response()->json([
            'results' => $results,
        ]);
    }

    public function permintaanShow(PermintaanBarang $permintaanBarang): JsonResponse
    {
        $this->ensureCabangAccessible((int) $permintaanBarang->cabang_id);
        $permintaanBarang->loadMissing(['cabang', 'items.produk']);

        return response()->json([
            'id' => (int) $permintaanBarang->id,
            'text' => $this->formatPermintaanLabel($permintaanBarang),
            'items' => $permintaanBarang->items->map(function ($item) {
                return [
                    'produk_id' => (int) $item->produk_id,
                    'qty' => (float) $item->qty,
                    'catatan' => $item->catatan,
                ];
            })->values()->all(),
        ]);
    }

    public function edit(PesananPembelian $pesananPembelian)
    {
        $this->ensureCabangAccessible((int) $pesananPembelian->cabang_id);

        if (!$this->canModify($pesananPembelian)) {
            return redirect()->route('pembelian.pesanan')->with('error', 'PO tidak dapat diedit karena sudah diproses.');
        }

        $pesananPembelian->load('items');

        return view('pages.master.pembelian.pesanan.edit', $this->formPayload([
            'po' => $pesananPembelian,
        ]));
    }

    public function update(Request $request, PesananPembelian $pesananPembelian)
    {
        $this->ensureCabangAccessible((int) $pesananPembelian->cabang_id);

        if (!$this->canModify($pesananPembelian)) {
            return redirect()->route('pembelian.pesanan')->with('error', 'PO tidak dapat diubah karena sudah diproses.');
        }

        $validated = $request->validate([
            'permintaan_barang_id' => ['nullable', 'exists:permintaan_barang,id'],
            'pemasok_id' => ['required', 'exists:pemasok,id'],
            'cabang_id' => ['required', 'exists:cabang,id'],
            'tanggal_po' => ['required', 'date'],
            'tanggal_kirim' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
            'produk_id' => ['nullable', 'array'],
            'produk_id.*' => ['nullable', 'exists:produk,id'],
            'qty' => ['nullable', 'array'],
            'qty.*' => ['nullable', 'numeric', 'min:0.01'],
            'harga' => ['nullable', 'array'],
            'harga.*' => ['nullable', 'numeric', 'min:0'],
            'catatan_item' => ['nullable', 'array'],
            'catatan_item.*' => ['nullable', 'string'],
        ]);

        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        DB::transaction(function () use ($validated, $pesananPembelian) {
            $oldPermintaanId = $pesananPembelian->permintaan_barang_id;

            $pesananPembelian->update([
                'permintaan_barang_id' => $validated['permintaan_barang_id'] ?? null,
                'pemasok_id' => $validated['pemasok_id'],
                'cabang_id' => $validated['cabang_id'],
                'tanggal_po' => $validated['tanggal_po'],
                'tanggal_kirim' => $validated['tanggal_kirim'] ?? null,
                'status' => 'ORDERED',
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $pesananPembelian->items()->delete();
            foreach ($this->resolveItems($validated) as $item) {
                $pesananPembelian->items()->create($item);
            }

            if ($oldPermintaanId && $oldPermintaanId !== $pesananPembelian->permintaan_barang_id) {
                if (!PesananPembelian::query()->where('permintaan_barang_id', $oldPermintaanId)->exists()) {
                    PermintaanBarang::query()->where('id', $oldPermintaanId)->update(['status' => 'APPROVED']);
                }
            }

            if ($pesananPembelian->permintaan_barang_id) {
                PermintaanBarang::query()->where('id', $pesananPembelian->permintaan_barang_id)->update(['status' => 'PROCESSED']);
            }
        });

        return redirect()->route('pembelian.pesanan')->with('success', 'Pesanan pembelian berhasil diperbarui.');
    }

    public function destroy(PesananPembelian $pesananPembelian)
    {
        $this->ensureCabangAccessible((int) $pesananPembelian->cabang_id);

        if (!$this->canModify($pesananPembelian)) {
            return redirect()->route('pembelian.pesanan')->with('error', 'PO tidak dapat dihapus karena sudah diproses.');
        }

        $permintaanId = $pesananPembelian->permintaan_barang_id;
        $pesananPembelian->delete();

        if ($permintaanId && !PesananPembelian::query()->where('permintaan_barang_id', $permintaanId)->exists()) {
            PermintaanBarang::query()->where('id', $permintaanId)->update(['status' => 'APPROVED']);
        }

        return redirect()->route('pembelian.pesanan')->with('success', 'Pesanan pembelian berhasil dihapus.');
    }

    public function show(PesananPembelian $pesananPembelian)
    {
        $this->ensureCabangAccessible((int) $pesananPembelian->cabang_id);
        $pesananPembelian->load(['pemasok', 'cabang.perusahaan', 'permintaanBarang', 'items.produk', 'pembuat']);
        $outstandingQty = $this->computeOutstandingQty($pesananPembelian);

        return view('pages.master.pembelian.pesanan.show', [
            'po' => $pesananPembelian,
            'outstandingQty' => $outstandingQty,
        ]);
    }

    public function pdf(PesananPembelian $pesananPembelian)
    {
        $this->ensureCabangAccessible((int) $pesananPembelian->cabang_id);
        $pesananPembelian->load(['pemasok', 'cabang.perusahaan', 'permintaanBarang', 'items.produk', 'pembuat']);
        $outstandingQty = $this->computeOutstandingQty($pesananPembelian);

        $pdf = Pdf::loadView('pdf.pembelian.po', [
            'po' => $pesananPembelian,
            'outstandingQty' => $outstandingQty,
        ]);

        return $pdf->download($pesananPembelian->nomor_po . '.pdf');
    }

    public function close(PesananPembelian $pesananPembelian)
    {
        $this->ensureCabangAccessible((int) $pesananPembelian->cabang_id);

        if ($pesananPembelian->status === 'CLOSED') {
            return redirect()->route('pembelian.pesanan')->with('error', 'PO sudah dalam status CLOSED.');
        }

        if ($this->computeOutstandingQty($pesananPembelian) <= 0) {
            return redirect()->route('pembelian.pesanan')->with('error', 'PO tidak memiliki outstanding, tidak perlu ditutup manual.');
        }

        $pesananPembelian->update(['status' => 'CLOSED']);
        return redirect()->route('pembelian.pesanan')->with('success', 'PO berhasil ditutup. Outstanding penerimaan untuk PO ini dihentikan.');
    }

    private function resolveItems(array $validated): array
    {
        $items = [];

        if (!empty($validated['produk_id'])) {
            foreach ($validated['produk_id'] as $index => $produkId) {
                if (!$produkId) {
                    continue;
                }

                $qty = (float) ($validated['qty'][$index] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $harga = (float) ($validated['harga'][$index] ?? 0);
                $items[] = [
                    'produk_id' => $produkId,
                    'qty' => $qty,
                    'harga' => $harga,
                    'subtotal' => $qty * $harga,
                    'catatan' => $validated['catatan_item'][$index] ?? null,
                ];
            }
        }

        if (!empty($items)) {
            return $items;
        }

        if (empty($validated['permintaan_barang_id'])) {
            abort(422, 'Item pesanan pembelian wajib diisi.');
        }

        $permintaan = PermintaanBarang::query()->with('items')->findOrFail($validated['permintaan_barang_id']);
        foreach ($permintaan->items as $item) {
            $items[] = [
                'produk_id' => $item->produk_id,
                'qty' => (float) $item->qty,
                'harga' => 0,
                'subtotal' => 0,
                'catatan' => $item->catatan,
            ];
        }

        return $items;
    }

    private function generateNomorPo(): string
    {
        $prefix = 'PO' . now()->format('Ymd');
        $last = PesananPembelian::query()
            ->where('nomor_po', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_po');

        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function formPayload(array $extra = []): array
    {
        $permintaanQuery = $this->buildPermintaanQuery();

        if (isset($extra['po']) && $extra['po']?->permintaan_barang_id) {
            $permintaanId = $extra['po']->permintaan_barang_id;
            $permintaanQuery->orWhere('id', $permintaanId);
        }

        $permintaanList = $permintaanQuery->latest('id')->get();

        $permintaanPayload = $permintaanList->mapWithKeys(function ($permintaan) {
            return [
                $permintaan->id => $permintaan->items->map(function ($item) {
                    return [
                        'produk_id' => $item->produk_id,
                        'qty' => (float) $item->qty,
                        'catatan' => $item->catatan,
                    ];
                })->values()->toArray(),
            ];
        })->toArray();

        $produkList = Produk::query()->where('status', true)->orderBy('nama')->get();
        // Encode as JSON array for safe JavaScript template literal usage
        $produkOptionsHtml = $produkList->map(function ($produk) {
            return [
                'id' => $produk->id,
                'text' => $produk->kode . ' - ' . $produk->nama,
            ];
        })->values()->toJson();

        return array_merge([
            'pemasokList' => Pemasok::query()->where('status', true)->orderBy('nama')->get(),
            'cabangList' => $this->accessibleCabangQuery()->get(),
            'produkList' => $produkList,
            'produkOptionsHtml' => $produkOptionsHtml,
            'permintaanList' => $permintaanList,
            'permintaanPayload' => $permintaanPayload,
        ], $extra);
    }

    private function buildPermintaanQuery()
    {
        $permintaanQuery = PermintaanBarang::query()
            ->with(['cabang', 'items.produk'])
            ->whereIn('status', ['APPROVED', 'DRAFT']);
        $this->applyCabangScope($permintaanQuery);

        return $permintaanQuery;
    }

    private function formatPermintaanLabel(PermintaanBarang $permintaanBarang): string
    {
        return trim($permintaanBarang->nomor_permintaan . ' - ' . ($permintaanBarang->cabang->nama ?? '-'));
    }

    private function canModify(PesananPembelian $pesananPembelian): bool
    {
        if ($pesananPembelian->status === 'CLOSED') {
            return false;
        }

        return !$pesananPembelian->penerimaan()->exists() && !$pesananPembelian->faktur()->exists();
    }

    private function computeOutstandingQty(PesananPembelian $po): float
    {
        $po->loadMissing('items');
        $poItemIds = $po->items->pluck('id');
        $totalPoQty = (float) $po->items->sum('qty');

        $totalReceivedQty = (float) PenerimaanBarangItem::query()
            ->whereIn('pesanan_pembelian_item_id', $poItemIds)
            ->sum('qty_terima');

        return max($totalPoQty - $totalReceivedQty, 0);
    }
}
