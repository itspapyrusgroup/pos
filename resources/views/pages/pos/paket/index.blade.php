@extends('layouts.app')

@section('title', 'Paket')

@section('content')
@php
    $user = auth()->user();
    $canCreatePaket = $user?->hasPermission('paket.master.create') ?? false;
    $canUpdatePaket = $user?->hasPermission('paket.master.update') ?? false;
    $canDeletePaket = $user?->hasPermission('paket.master.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Paket</li>
            </ol>
        </nav>
    </div>
    @if($canCreatePaket)
        <div class="ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPaket">+ Tambah Paket</button>
        </div>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="nama" class="form-control" placeholder="Cari nama paket..." value="{{ request('nama') }}">
            </div>
            <div class="col-md-3">
                <select name="kategori_paket_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriPaket as $kategori)
                        <option value="{{ $kategori->id }}" {{ (string) request('kategori_paket_id') === (string) $kategori->id ? 'selected' : '' }}>{{ $kategori->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non Aktif</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('paket.list') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        @if($canUpdatePaket)
            <div class="border rounded p-3 mb-3 bg-light">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1">Paket Terpilih</label>
                        <div class="form-control bg-white fw-semibold" id="batch-selected-count">0 paket dipilih</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Update Status Menjadi</label>
                        <select class="form-select" id="batch-status-value">
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="button" class="btn btn-dark" id="btn-apply-batch-status" disabled>Terapkan Batch Status</button>
                    </div>
                </div>
            </div>

            <form id="batch-status-form" method="POST" action="{{ route('paket.batch-status') }}">
                @csrf
                <input type="hidden" name="status" id="batch-status-input" value="1">
                <div id="batch-paket-ids-container"></div>
            </form>
        @endif

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    @if($canUpdatePaket)
                        <th width="40">
                            <input type="checkbox" class="form-check-input" id="select-all-paket">
                        </th>
                    @endif
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Harga</th>
                    <th>Kategori</th>
                    <th>Jumlah Item</th>
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paketList as $index => $paket)
                    <tr>
                        @if($canUpdatePaket)
                            <td>
                                <input type="checkbox" class="form-check-input row-paket-checkbox" value="{{ $paket->id }}">
                            </td>
                        @endif
                        <td>{{ $paketList->firstItem() + $index }}</td>
                        <td>{{ $paket->kode }}</td>
                        <td>{{ $paket->nama }}</td>
                        <td>
                            @php
                                $templateRows = ($paketTemplateHargaMap[$paket->id] ?? collect());
                                $configuredTemplates = collect($templateHargaOptions ?? collect())->filter(function ($templateOption) use ($templateRows) {
                                    return $templateRows->has((int) $templateOption->id);
                                })->values();
                                $configuredCount = $configuredTemplates->count();
                            @endphp
                            @if($configuredCount > 1)
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalHargaPaket{{ $paket->id }}">
                                    Multiple Harga
                                </button>
                            @elseif($configuredCount === 1)
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalHargaPaket{{ $paket->id }}">
                                    Lihat Harga
                                </button>
                            @else
                                <span class="badge bg-light text-dark">Belum diset</span>
                            @endif
                        </td>
                        <td>{{ $paket->kategoriPaket->nama ?? '-' }}</td>
                        <td>{{ $paket->items->count() }}</td>
                        <td>{!! $paket->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                        <td class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetailPaket{{ $paket->id }}">Detail</button>
                            @if($canUpdatePaket)
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditPaket{{ $paket->id }}">Edit</button>
                            @endif
                            @if($canDeletePaket)
                                @if($paket->is_dipakai_transaksi ?? false)
                                    @if($paket->status)
                                        <form method="POST" action="{{ route('paket.destroy', $paket) }}" class="js-swal-confirm-form" data-confirm-title="Nonaktifkan Paket?" data-confirm-text="Paket ini sudah dipakai transaksi. Status paket akan diubah menjadi Non Aktif.">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Nonaktifkan</button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary align-self-center">Dipakai transaksi</span>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('paket.destroy', $paket) }}" class="js-swal-confirm-form" data-confirm-title="Hapus Paket?" data-confirm-text="Paket ini akan dihapus permanen. Lanjutkan?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canUpdatePaket ? 10 : 9 }}" class="text-center text-muted">Belum ada data paket.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $paketList->links() }}
    </div>
</div>

@if($canCreatePaket)
    <div class="modal fade" id="modalTambahPaket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('paket.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Paket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Nama Paket</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Harga Default Jual</label>
                            <input type="text" name="harga_default" class="form-control paket-harga-default" inputmode="decimal" value="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kategori Paket</label>
                            <select name="kategori_paket_id" class="form-select">
                                <option value="">-- Pilih --</option>
                                @foreach($kategoriPaket as $kategori)
                                    <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" rows="2" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="mb-2">Harga per Template (Akses Cabang Anda)</h6>
                        @if(($templateHargaOptions ?? collect())->isEmpty())
                            <div class="alert alert-light border text-muted mb-0">
                                Belum ada template harga aktif yang terhubung ke cabang akses Anda.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Template</th>
                                            <th>Cabang Akses</th>
                                            <th width="190">Harga Template</th>
                                            <th width="110">Aktif</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($templateHargaOptions as $templateOption)
                                            @php
                                                $templateId = (int) $templateOption->id;
                                                $defaultPrice = old("template_harga_prices.$templateId", 0);
                                                $defaultStatus = old("template_harga_status.$templateId", 1);
                                            @endphp
                                            <tr>
                                                <td>
                                                    {{ $templateOption->kode }} - {{ $templateOption->nama }}
                                                    <input type="hidden" name="template_harga_ids[]" value="{{ $templateId }}">
                                                </td>
                                                <td>{{ $templateOption->cabang_nama ?: '-' }}</td>
                                                <td>
                                                    <input type="text" name="template_harga_prices[{{ $templateId }}]" class="form-control template-harga-price" inputmode="decimal" value="{{ $defaultPrice }}">
                                                </td>
                                                <td>
                                                    <input type="hidden" name="template_harga_status[{{ $templateId }}]" value="0">
                                                    <input type="checkbox" name="template_harga_status[{{ $templateId }}]" value="1" {{ (string) $defaultStatus === '1' ? 'checked' : '' }}>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3 position-relative">
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control paket-product-search"
                                data-target-table="tbodyCreatePaket"
                                placeholder="Cari paket / barang / jasa..."
                                autocomplete="off"
                            >
                            <button type="button" class="btn btn-primary btn-add-item-search" data-target-table="tbodyCreatePaket">Tambah Item</button>
                        </div>
                        <div class="list-group position-absolute w-100 shadow-sm d-none paket-product-results" data-target-table="tbodyCreatePaket" style="z-index: 1080; max-height: 260px; overflow-y: auto;"></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th width="180">Qty</th>
                                    <th width="80">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyCreatePaket">
                                <tr class="paket-item-row">
                                    <td>
                                        <select name="item_produk_id[]" class="form-select select-produk" required>
                                            <option value="">Cari kode / nama produk...</option>
                                        </select>
                                    </td>
                                    <td><input type="number" step="1" min="1" name="item_qty[]" class="form-control item-qty-input" inputmode="numeric" required></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@foreach($paketList as $paket)
    <div class="modal fade" id="modalHargaPaket{{ $paket->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Harga Template - {{ $paket->kode }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $templateRows = ($paketTemplateHargaMap[$paket->id] ?? collect());
                        $configuredTemplates = collect($templateHargaOptions ?? collect())->map(function ($templateOption) use ($templateRows) {
                            $templateId = (int) $templateOption->id;
                            $currentRow = $templateRows->get($templateId);
                            if (!$currentRow) {
                                return null;
                            }

                            return [
                                'kode' => $templateOption->kode,
                                'nama' => $templateOption->nama,
                                'cabang_nama' => $templateOption->cabang_nama,
                                'harga' => (float) $currentRow->harga,
                                'status' => (bool) $currentRow->status,
                            ];
                        })->filter()->values();
                    @endphp

                    @if($configuredTemplates->isEmpty())
                        <div class="alert alert-light border text-muted mb-0">Belum ada harga paket yang diset pada template cabang akses Anda.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Template</th>
                                        <th>Cabang</th>
                                        <th width="160">Harga</th>
                                        <th width="100">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($configuredTemplates as $row)
                                        <tr>
                                            <td>{{ $row['kode'] }} - {{ $row['nama'] }}</td>
                                            <td>{{ $row['cabang_nama'] ?: '-' }}</td>
                                            <td>Rp {{ number_format((float) $row['harga'], 0, ',', '.') }}</td>
                                            <td>{!! $row['status'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetailPaket{{ $paket->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Paket - {{ $paket->kode }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><strong>Nama:</strong> {{ $paket->nama }}</div>
                    <div class="mb-2"><strong>Harga Default:</strong> Rp {{ number_format((float) $paket->harga_default, 0, ',', '.') }}</div>
                    <div class="mb-2"><strong>Kategori:</strong> {{ $paket->kategoriPaket->nama ?? '-' }}</div>
                    <div class="mb-2"><strong>Status:</strong> {{ $paket->status ? 'Aktif' : 'Non Aktif' }}</div>
                    <div class="mb-3"><strong>Deskripsi:</strong><br>{{ $paket->deskripsi ?: '-' }}</div>

                    <div class="mb-3">
                        <strong>Harga Menu Template:</strong>
                        @php
                            $templateRows = ($paketTemplateHargaMap[$paket->id] ?? collect());
                            $configuredTemplates = collect($templateHargaOptions ?? collect())->map(function ($templateOption) use ($templateRows) {
                                $templateId = (int) $templateOption->id;
                                $currentRow = $templateRows->get($templateId);
                                if (!$currentRow) {
                                    return null;
                                }

                                return [
                                    'kode' => $templateOption->kode,
                                    'nama' => $templateOption->nama,
                                    'cabang_nama' => $templateOption->cabang_nama,
                                    'harga' => (float) $currentRow->harga,
                                    'status' => (bool) $currentRow->status,
                                ];
                            })->filter()->values();
                        @endphp
                        @if($configuredTemplates->isEmpty())
                            <div class="text-muted small mt-1">Belum ada harga template yang diset.</div>
                        @else
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Template</th>
                                            <th>Cabang</th>
                                            <th width="160">Harga</th>
                                            <th width="100">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($configuredTemplates as $row)
                                            <tr>
                                                <td>{{ $row['kode'] }} - {{ $row['nama'] }}</td>
                                                <td>{{ $row['cabang_nama'] ?: '-' }}</td>
                                                <td>Rp {{ number_format((float) $row['harga'], 0, ',', '.') }}</td>
                                                <td>{!! $row['status'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3 position-relative">
                        <label class="form-label fw-semibold">Cari Produk di Paket</label>
                        <input
                            type="text"
                            class="form-control paket-detail-search"
                            data-detail-search-target="detail-paket-table-{{ $paket->id }}"
                            data-detail-result-target="detail-paket-search-results-{{ $paket->id }}"
                            placeholder="Ketik kode atau nama produk..."
                            autocomplete="off"
                        >
                        <div
                            id="detail-paket-search-results-{{ $paket->id }}"
                            class="list-group position-absolute w-100 shadow-sm d-none"
                            style="z-index: 1080; max-height: 240px; overflow-y: auto;"
                        ></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="detail-paket-table-{{ $paket->id }}">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kode Produk</th>
                                    <th>Nama Produk</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paket->items as $i => $item)
                                    <tr
                                        data-row-index="{{ $i }}"
                                        data-search-text="{{ strtolower(trim(($item->produk->kode ?? '') . ' ' . ($item->produk->nama ?? '') . ' ' . rtrim(rtrim(number_format($item->qty, 2, '.', ''), '0'), '.'))) }}"
                                    >
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $item->produk->kode ?? '-' }}</td>
                                        <td>{{ $item->produk->nama ?? '-' }}</td>
                                        <td>{{ rtrim(rtrim(number_format($item->qty, 2, '.', ''), '0'), '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada item.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canUpdatePaket)
        <div class="modal fade" id="modalEditPaket{{ $paket->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('paket.update', $paket) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Paket - {{ $paket->kode }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Nama Paket</label>
                                <input type="text" name="nama" class="form-control" value="{{ $paket->nama }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Harga Default Jual</label>
                                <input type="text" name="harga_default" class="form-control paket-harga-default" inputmode="decimal" value="{{ (float) $paket->harga_default }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kategori Paket</label>
                                <select name="kategori_paket_id" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach($kategoriPaket as $kategori)
                                        <option value="{{ $kategori->id }}" {{ (int) $paket->kategori_paket_id === (int) $kategori->id ? 'selected' : '' }}>{{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="1" {{ $paket->status ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$paket->status ? 'selected' : '' }}>Non Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" rows="2" class="form-control">{{ $paket->deskripsi }}</textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="mb-2">Harga per Template (Akses Cabang Anda)</h6>
                            @php
                                $templateRows = ($paketTemplateHargaMap[$paket->id] ?? collect());
                            @endphp
                            @if(($templateHargaOptions ?? collect())->isEmpty())
                                <div class="alert alert-light border text-muted mb-0">
                                    Belum ada template harga aktif yang terhubung ke cabang akses Anda.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Template</th>
                                                <th>Cabang Akses</th>
                                                <th width="190">Harga Template</th>
                                                <th width="110">Aktif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($templateHargaOptions as $templateOption)
                                                @php
                                                    $templateId = (int) $templateOption->id;
                                                    $currentRow = $templateRows[$templateId] ?? null;
                                                    $defaultPrice = old("template_harga_prices.$templateId", $currentRow?->harga ?? $paket->harga_default);
                                                    $defaultStatus = old("template_harga_status.$templateId", $currentRow?->status ?? true);
                                                @endphp
                                                <tr>
                                                    <td>
                                                        {{ $templateOption->kode }} - {{ $templateOption->nama }}
                                                        <input type="hidden" name="template_harga_ids[]" value="{{ $templateId }}">
                                                    </td>
                                                    <td>{{ $templateOption->cabang_nama ?: '-' }}</td>
                                                    <td>
                                                        <input type="text" name="template_harga_prices[{{ $templateId }}]" class="form-control template-harga-price" inputmode="decimal" value="{{ $defaultPrice }}">
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="template_harga_status[{{ $templateId }}]" value="0">
                                                        <input type="checkbox" name="template_harga_status[{{ $templateId }}]" value="1" {{ (string) $defaultStatus === '1' || $defaultStatus === true ? 'checked' : '' }}>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3 position-relative">
                            <div class="input-group">
                                <input
                                    type="text"
                                    class="form-control paket-product-search"
                                    data-target-table="tbodyEditPaket{{ $paket->id }}"
                                    placeholder="Cari paket / barang / jasa..."
                                    autocomplete="off"
                                >
                                <button type="button" class="btn btn-primary btn-add-item-search" data-target-table="tbodyEditPaket{{ $paket->id }}">Tambah Item</button>
                            </div>
                            <div class="list-group position-absolute w-100 shadow-sm d-none paket-product-results" data-target-table="tbodyEditPaket{{ $paket->id }}" style="z-index: 1080; max-height: 260px; overflow-y: auto;"></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th width="180">Qty</th>
                                        <th width="80">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyEditPaket{{ $paket->id }}">
                                    @forelse($paket->items as $item)
                                        <tr class="paket-item-row">
                                            <td>
                                                <select name="item_produk_id[]" class="form-select select-produk" required>
                                                    <option value="">Cari kode / nama produk...</option>
                                                    <option value="{{ $item->produk_id }}" selected>{{ trim((($item->produk?->kode) ? ($item->produk?->kode . ' - ') : '') . ($item->produk?->nama ?? '')) }}</option>
                                                </select>
                                            </td>
                                            <td><input type="number" step="1" min="1" name="item_qty[]" class="form-control item-qty-input" inputmode="numeric" value="{{ (int) $item->qty }}" required></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button></td>
                                        </tr>
                                    @empty
                                        <tr class="paket-item-row">
                                            <td>
                                                <select name="item_produk_id[]" class="form-select select-produk" required>
                                                    <option value="">Cari kode / nama produk...</option>
                                                </select>
                                            </td>
                                            <td><input type="number" step="1" min="1" name="item_qty[]" class="form-control item-qty-input" inputmode="numeric" required></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-warning">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script>
    (function () {
        const URL_PAKET_PRODUK_CARI = "{{ route('paket.produk-cari') }}";
        const paketSearchState = new Map();

        function buildProdukSelectElement() {
            const select = document.createElement('select');
            select.name = 'item_produk_id[]';
            select.className = 'form-select select-produk';
            select.required = true;

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Cari kode / nama produk...';
            select.appendChild(placeholder);

            return select;
        }

        function buildQtyInputElement(value) {
            const qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.step = '1';
            qtyInput.min = '1';
            qtyInput.name = 'item_qty[]';
            qtyInput.className = 'form-control item-qty-input';
            qtyInput.inputMode = 'numeric';
            qtyInput.required = true;
            if (value !== undefined && value !== null) {
                qtyInput.value = value;
            }
            return qtyInput;
        }

        function buildPaketRow(product) {
            const tr = document.createElement('tr');
            tr.className = 'paket-item-row';

            const tdProduk = document.createElement('td');
            const select = buildProdukSelectElement();
            tdProduk.appendChild(select);

            const tdQty = document.createElement('td');
            tdQty.appendChild(buildQtyInputElement(1));

            const tdAksi = document.createElement('td');
            tdAksi.className = 'text-center';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger btn-remove-row';
            removeBtn.textContent = 'x';
            tdAksi.appendChild(removeBtn);

            tr.appendChild(tdProduk);
            tr.appendChild(tdQty);
            tr.appendChild(tdAksi);

            if (product && product.id) {
                tr.setAttribute('data-added-product-id', String(product.id));
            }

            return tr;
        }

        function addPaketProductRow(tbodyId, product) {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return;

            const existingRows = Array.from(tbody.querySelectorAll('.paket-item-row'));
            const existingProductIds = existingRows.map(function (row) {
                const select = row.querySelector('select.select-produk');
                return select ? String(select.value || '') : '';
            }).filter(Boolean);

            if (product && product.id && existingProductIds.includes(String(product.id))) {
                const row = existingRows.find(function (r) {
                    const select = r.querySelector('select.select-produk');
                    return select && String(select.value || '') === String(product.id);
                });
                if (row) {
                    const qtyInput = row.querySelector('input.item-qty-input');
                    if (qtyInput) {
                        qtyInput.focus();
                        qtyInput.select?.();
                    }
                    return row;
                }
            }

            const row = buildPaketRow(product);
            tbody.appendChild(row);
            initProdukSelect(row);

            if (product && product.id) {
                const select = row.querySelector('select.select-produk');
                if (select && window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                    const option = new Option(product.text || product.nama || 'Produk', product.id, true, true);
                    window.jQuery(select).append(option).trigger('change');
                } else if (select) {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = product.text || product.nama || 'Produk';
                    option.selected = true;
                    select.appendChild(option);
                    select.value = String(product.id);
                }
            }

            const qtyInput = row.querySelector('input.item-qty-input');
            if (qtyInput) {
                qtyInput.focus();
                qtyInput.select?.();
            }

            return row;
        }

        function escapeHtml(text) {
            return String(text ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getPaketSearchState(targetTable) {
            if (!paketSearchState.has(targetTable)) {
                paketSearchState.set(targetTable, {
                    request: null,
                    timer: null,
                    cache: new Map(),
                    results: [],
                });
            }

            return paketSearchState.get(targetTable);
        }

        function renderPaketProductDropdown(input, results) {
            const targetTable = input.getAttribute('data-target-table');
            const resultsBox = document.querySelector(`.paket-product-results[data-target-table="${targetTable}"]`);
            if (!resultsBox) return;

            if (!Array.isArray(results) || results.length === 0) {
                resultsBox.innerHTML = '<div class="list-group-item text-muted">Produk tidak ditemukan.</div>';
                resultsBox.classList.remove('d-none');
                return;
            }

            resultsBox.innerHTML = results.slice(0, 8).map(function (item) {
                const safeText = escapeHtml(item.text || '-');
                return `
                    <button type="button" class="list-group-item list-group-item-action paket-product-result-item" data-target-table="${targetTable}" data-product-id="${item.id}" data-product-text="${String(item.text || '').replace(/"/g, '&quot;')}">
                        <div class="fw-semibold">${safeText}</div>
                    </button>
                `;
            }).join('');

            resultsBox.classList.remove('d-none');
        }

        function closePaketProductDropdown(targetTable) {
            const resultsBox = document.querySelector(`.paket-product-results[data-target-table="${targetTable}"]`);
            if (!resultsBox) return;
            resultsBox.classList.add('d-none');
            resultsBox.innerHTML = '';
        }

        function searchPaketProduk(input, forceShowAll = false) {
            const targetTable = input.getAttribute('data-target-table');
            if (!targetTable) return;

            const state = getPaketSearchState(targetTable);
            const term = (input.value || '').trim();
            const q = forceShowAll ? '' : term;

            if (!forceShowAll && q.length > 0 && q.length < 2) {
                closePaketProductDropdown(targetTable);
                return;
            }

            const cacheKey = q.toLowerCase();
            if (state.cache.has(cacheKey)) {
                state.results = state.cache.get(cacheKey) || [];
                renderPaketProductDropdown(input, state.results);
                return;
            }

            if (state.timer) {
                clearTimeout(state.timer);
            }

            state.timer = setTimeout(function () {
                if (state.request && state.request.readyState !== 4) {
                    state.request.abort();
                }

                state.request = window.jQuery ? window.jQuery.ajax({
                    url: URL_PAKET_PRODUK_CARI,
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        term: q,
                    },
                }) : null;

                if (!state.request) {
                    return;
                }

                state.request
                    .done(function (res) {
                        const results = res?.results || [];
                        state.results = results;
                        state.cache.set(cacheKey, results);
                        renderPaketProductDropdown(input, results);
                    })
                    .fail(function (xhr) {
                        if (xhr?.statusText === 'abort') return;
                        state.results = [];
                        renderPaketProductDropdown(input, []);
                    });
            }, 220);
        }

        function sanitizeIntegerInput(value, minValue = 1) {
            const cleaned = String(value ?? '').replace(/[^\d]/g, '');
            if (cleaned === '') return String(minValue);
            return String(Math.max(parseInt(cleaned, 10) || 0, minValue));
        }
        function parseCurrencyInput(value) {
            const raw = String(value ?? '').trim().replace(/[^\d,.\-]/g, '');
            if (!raw || raw === '-' || raw === ',' || raw === '.') return 0;
            const negative = raw.startsWith('-');
            const unsigned = raw.replace(/-/g, '');
            const lastComma = unsigned.lastIndexOf(',');
            const lastDot = unsigned.lastIndexOf('.');
            const decimalIndex = Math.max(lastComma, lastDot);
            const hasBothSeparator = lastComma !== -1 && lastDot !== -1;
            const digitsAfterLastSeparator = decimalIndex >= 0 ? (unsigned.length - decimalIndex - 1) : 0;
            const useDecimalSeparator = decimalIndex >= 0 && (
                hasBothSeparator ||
                (digitsAfterLastSeparator > 0 && digitsAfterLastSeparator <= 2)
            );

            let normalized = '';
            if (useDecimalSeparator) {
                const intPart = unsigned.slice(0, decimalIndex).replace(/[.,]/g, '');
                const fracPart = unsigned.slice(decimalIndex + 1).replace(/[.,]/g, '');
                normalized = intPart + (fracPart ? '.' + fracPart : '');
            } else {
                normalized = unsigned.replace(/[.,]/g, '');
            }

            const parsed = parseFloat((negative ? '-' : '') + normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }
        function toPlainCurrencyString(value) {
            const num = parseCurrencyInput(value);
            if (Math.abs(num - Math.round(num)) < 0.0000001) {
                return String(Math.round(num));
            }
            return num.toFixed(2).replace(/\.?0+$/, '');
        }
        function formatCurrencyInput(value) {
            const num = parseCurrencyInput(value);
            const isInteger = Math.abs(num - Math.round(num)) < 0.0000001;
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: isInteger ? 0 : 2,
                maximumFractionDigits: 2
            }).format(num);
        }
        function formatAllPaketPriceInputs(scope) {
            const root = scope || document;
            root.querySelectorAll('input.paket-harga-default, input.template-harga-price').forEach(function (input) {
                input.value = formatCurrencyInput(input.value);
            });
        }
        function getSelectedPaketCheckboxes() {
            return Array.from(document.querySelectorAll('.row-paket-checkbox:checked'));
        }
        function updateBatchPaketState() {
            const checked = getSelectedPaketCheckboxes();
            const total = checked.length;
            const countEl = document.getElementById('batch-selected-count');
            const applyBtn = document.getElementById('btn-apply-batch-status');
            const selectAll = document.getElementById('select-all-paket');
            const allRows = Array.from(document.querySelectorAll('.row-paket-checkbox'));

            if (countEl) countEl.textContent = `${total} paket dipilih`;
            if (applyBtn) applyBtn.disabled = total === 0;
            if (selectAll) {
                const totalRows = allRows.length;
                selectAll.checked = totalRows > 0 && total === totalRows;
                selectAll.indeterminate = total > 0 && total < totalRows;
            }
        }
        function submitBatchStatus() {
            const checked = getSelectedPaketCheckboxes();
            if (checked.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Validasi', 'Pilih minimal satu paket.', 'warning');
                }
                return;
            }

            const statusValue = document.getElementById('batch-status-value')?.value || '1';
            const form = document.getElementById('batch-status-form');
            const container = document.getElementById('batch-paket-ids-container');
            const statusInput = document.getElementById('batch-status-input');
            if (!form || !container || !statusInput) return;

            const statusLabel = statusValue === '1' ? 'Aktif' : 'Non Aktif';
            const proceedSubmit = function () {
                container.innerHTML = '';
                checked.forEach(function (cb) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    container.appendChild(input);
                });
                statusInput.value = statusValue;
                form.submit();
            };

            if (typeof Swal === 'undefined') return;
            Swal.fire({
                title: 'Terapkan Batch Status?',
                html: `Ubah status <b>${checked.length}</b> paket menjadi <b>${statusLabel}</b>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Terapkan',
                cancelButtonText: 'Batal',
            }).then(function (result) {
                if (result.isConfirmed) {
                    proceedSubmit();
                }
            });
        }

        function initProdukSelect(scope) {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
            const $scope = window.jQuery(scope || document);
            $scope.find('select.select-produk').each(function () {
                const $el = window.jQuery(this);
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Cari kode / nama produk...',
                    allowClear: true,
                    dropdownParent: $el.closest('.modal'),
                    minimumInputLength: 2,
                    ajax: {
                        url: URL_PAKET_PRODUK_CARI,
                        dataType: 'json',
                        delay: 250,
                        cache: true,
                        data: function (params) {
                            return {
                                term: params.term || '',
                                page: params.page || 1,
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results || [],
                                pagination: data.pagination || { more: false },
                            };
                        },
                    },
                });
            });
        }

        function bindAddRow(btn) {
            btn.addEventListener('click', function () {
                const targetId = btn.getAttribute('data-target');
                const tbody = document.getElementById(targetId);
                if (!tbody) return;

                const tr = document.createElement('tr');
                tr.className = 'paket-item-row';

                const tdProduk = document.createElement('td');
                tdProduk.appendChild(buildProdukSelectElement());

                const tdQty = document.createElement('td');
                const qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.step = '1';
                qtyInput.min = '1';
                qtyInput.name = 'item_qty[]';
                qtyInput.className = 'form-control item-qty-input';
                qtyInput.inputMode = 'numeric';
                qtyInput.required = true;
                tdQty.appendChild(qtyInput);

                const tdAksi = document.createElement('td');
                tdAksi.className = 'text-center';
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger btn-remove-row';
                removeBtn.textContent = 'x';
                tdAksi.appendChild(removeBtn);

                tr.appendChild(tdProduk);
                tr.appendChild(tdQty);
                tr.appendChild(tdAksi);
                tbody.appendChild(tr);
                initProdukSelect(tr);
            });
        }

        document.querySelectorAll('.btn-add-row').forEach(bindAddRow);
        initProdukSelect(document);
        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                initProdukSelect(modal);
            });
        });

        document.addEventListener('focusin', function (event) {
            const input = event.target.closest('.paket-product-search');
            if (!input) return;

            searchPaketProduk(input, (input.value || '').trim() === '');
        });

        document.addEventListener('input', function (event) {
            const input = event.target.closest('.paket-product-search');
            if (!input) return;

            searchPaketProduk(input, false);
        });

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-add-item-search');
            if (!btn) return;

            const targetTable = btn.getAttribute('data-target-table');
            if (!targetTable) return;

            const input = document.querySelector(`.paket-product-search[data-target-table="${targetTable}"]`);
            if (!input) return;

            const state = getPaketSearchState(targetTable);
            const currentTerm = (input.value || '').trim().toLowerCase();
            if (state.results.length > 0) {
                addPaketProductRow(targetTable, state.results[0]);
                closePaketProductDropdown(targetTable);
                return;
            }

            if (currentTerm.length >= 2) {
                searchPaketProduk(input, false);
                return;
            }

            input.focus();
        });

        document.addEventListener('click', function (event) {
            const itemBtn = event.target.closest('.paket-product-result-item');
            if (!itemBtn) return;

            const targetTable = itemBtn.getAttribute('data-target-table');
            if (!targetTable) return;

            const state = getPaketSearchState(targetTable);
            const productId = parseInt(itemBtn.getAttribute('data-product-id') || '0', 10);
            const product = state.results.find(function (item) {
                return Number(item.id) === productId;
            });

            if (product) {
                addPaketProductRow(targetTable, product);
            }

            closePaketProductDropdown(targetTable);
        });

        document.addEventListener('keydown', function (event) {
            const input = event.target.closest('.paket-product-search');
            if (!input) return;

            if (event.key !== 'Enter') return;
            event.preventDefault();

            const targetTable = input.getAttribute('data-target-table');
            if (!targetTable) return;

            const state = getPaketSearchState(targetTable);
            if (state.results.length > 0) {
                addPaketProductRow(targetTable, state.results[0]);
                closePaketProductDropdown(targetTable);
            } else {
                searchPaketProduk(input, false);
            }
        });

        document.addEventListener('click', function (event) {
            const input = event.target.closest('.paket-product-search');
            const dropdown = event.target.closest('.paket-product-results');
            const addBtn = event.target.closest('.btn-add-item-search');
            if (input || dropdown || addBtn) return;

            document.querySelectorAll('.paket-product-results').forEach(function (box) {
                box.classList.add('d-none');
                box.innerHTML = '';
            });
        });

        function closeDetailSearchResults(input) {
            const resultTarget = input.getAttribute('data-detail-result-target');
            const resultsBox = document.getElementById(resultTarget);
            if (resultsBox) {
                resultsBox.classList.add('d-none');
                resultsBox.innerHTML = '';
            }
        }

        function highlightDetailRow(row) {
            if (!row) return;
            row.classList.add('table-primary');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.setTimeout(function () {
                row.classList.remove('table-primary');
            }, 2000);
        }

        function renderDetailSearchResults(input, keyword) {
            const targetId = input.getAttribute('data-detail-search-target');
            const resultTarget = input.getAttribute('data-detail-result-target');
            const table = document.getElementById(targetId);
            const resultsBox = document.getElementById(resultTarget);
            if (!table || !resultsBox) return;

            const rows = Array.from(table.querySelectorAll('tbody tr')).filter(function (row) {
                const text = (row.getAttribute('data-search-text') || '').toLowerCase();
                return keyword === '' || text.includes(keyword);
            });

            if (!rows.length) {
                resultsBox.innerHTML = '<div class="list-group-item text-muted">Produk tidak ditemukan.</div>';
                resultsBox.classList.remove('d-none');
                return;
            }

            resultsBox.innerHTML = rows.slice(0, 8).map(function (row) {
                const cols = row.querySelectorAll('td');
                const code = cols[1]?.textContent?.trim() || '-';
                const name = cols[2]?.textContent?.trim() || '-';
                const qty = cols[3]?.textContent?.trim() || '-';
                return `
                    <button type="button" class="list-group-item list-group-item-action paket-detail-search-item" data-row-index="${row.getAttribute('data-row-index') || ''}">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="text-start">
                                <div class="fw-semibold">${code}</div>
                                <div class="small text-muted">${name}</div>
                            </div>
                            <span class="badge bg-light text-dark">Qty ${qty}</span>
                        </div>
                    </button>
                `;
            }).join('');

            resultsBox.classList.remove('d-none');
        }

        document.addEventListener('input', function (event) {
            const input = event.target.closest('.paket-detail-search');
            if (!input) return;
            renderDetailSearchResults(input, (input.value || '').trim().toLowerCase());
        });

        document.addEventListener('focusin', function (event) {
            const input = event.target.closest('.paket-detail-search');
            if (!input) return;

            renderDetailSearchResults(input, (input.value || '').trim().toLowerCase());
        });

        document.addEventListener('click', function (event) {
            const itemBtn = event.target.closest('.paket-detail-search-item');
            if (!itemBtn) return;

            const input = itemBtn.closest('.position-relative')?.querySelector('.paket-detail-search');
            if (!input) return;

            const targetId = input.getAttribute('data-detail-search-target');
            const table = document.getElementById(targetId);
            if (!table) return;

            const keyword = (input.value || '').trim().toLowerCase();
            const matchedRow = Array.from(table.querySelectorAll('tbody tr')).find(function (row) {
                return String(row.getAttribute('data-row-index') || '') === String(itemBtn.getAttribute('data-row-index') || '');
            });

            if (matchedRow) {
                highlightDetailRow(matchedRow);
            }

            closeDetailSearchResults(input);
        });

        document.addEventListener('click', function (event) {
            const input = event.target.closest('.paket-detail-search');
            if (input) return;

            document.querySelectorAll('.paket-detail-search').forEach(function (el) {
                const resultsTarget = el.getAttribute('data-detail-result-target');
                const resultsBox = document.getElementById(resultsTarget);
                const isInsideResults = resultsBox && resultsBox.contains(event.target);
                if (!isInsideResults) {
                    closeDetailSearchResults(el);
                }
            });
        });

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-remove-row');
            if (!btn) return;
            const tbody = btn.closest('tbody');
            if (!tbody) return;
            const rows = tbody.querySelectorAll('.paket-item-row');
            if (rows.length <= 1) {
                const row = btn.closest('.paket-item-row');
                if (!row) return;
                row.querySelectorAll('input').forEach(function (el) {
                    el.value = '';
                });
                const select = row.querySelector('select.select-produk');
                if (select) {
                    if (window.jQuery && window.jQuery(select).data('select2')) {
                        window.jQuery(select).val('').trigger('change');
                    } else {
                        select.value = '';
                    }
                }
                return;
            }
            const row = btn.closest('.paket-item-row');
            if (row) row.remove();
        });

        document.addEventListener('input', function (event) {
            const input = event.target.closest('input.item-qty-input');
            if (!input) return;
            input.value = sanitizeIntegerInput(input.value, 1);
        });
        document.addEventListener('focusin', function (event) {
            const input = event.target.closest('input.paket-harga-default, input.template-harga-price');
            if (!input) return;
            input.value = toPlainCurrencyString(input.value);
        });
        document.addEventListener('focusout', function (event) {
            const input = event.target.closest('input.paket-harga-default, input.template-harga-price');
            if (!input) return;
            input.value = formatCurrencyInput(input.value);
        });
        document.addEventListener('input', function (event) {
            const input = event.target.closest('input.paket-harga-default, input.template-harga-price');
            if (!input) return;
            input.value = input.value.replace(/[^\d,.\-]/g, '');
        });
        document.querySelectorAll('form[action*="paket/list"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                form.querySelectorAll('input.paket-harga-default, input.template-harga-price').forEach(function (input) {
                    input.value = toPlainCurrencyString(input.value);
                });
            });
        });
        document.getElementById('select-all-paket')?.addEventListener('change', function (event) {
            const checked = !!event.target.checked;
            document.querySelectorAll('.row-paket-checkbox').forEach(function (cb) {
                cb.checked = checked;
            });
            updateBatchPaketState();
        });
        document.querySelectorAll('.row-paket-checkbox').forEach(function (cb) {
            cb.addEventListener('change', updateBatchPaketState);
        });
        document.getElementById('btn-apply-batch-status')?.addEventListener('click', submitBatchStatus);
        document.querySelectorAll('.js-swal-confirm-form').forEach(function (formEl) {
            formEl.addEventListener('submit', function (event) {
                event.preventDefault();
                const confirmTitle = formEl.getAttribute('data-confirm-title') || 'Konfirmasi';
                const confirmText = formEl.getAttribute('data-confirm-text') || 'Lanjutkan proses ini?';

                if (typeof Swal === 'undefined') return;
                Swal.fire({
                    title: confirmTitle,
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        formEl.submit();
                    }
                });
            });
        });

        updateBatchPaketState();
        formatAllPaketPriceInputs(document);
    })();
</script>
@endpush
