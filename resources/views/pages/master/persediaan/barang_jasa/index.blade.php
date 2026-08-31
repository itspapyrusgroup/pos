@extends('layouts.app')

@section('title', 'Barang dan Jasa')

@push('styles')
<style>
    .barang-jasa-modal .select2-container {
        width: 100% !important;
    }

    .barang-jasa-modal .select2-container--bootstrap4 .select2-selection--single {
        min-height: 38px;
        background-color: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #ced4da);
    }

    .barang-jasa-modal .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color, #212529);
        line-height: 36px;
        padding-left: 0.75rem;
    }

    .barang-jasa-modal .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        color: var(--bs-secondary-color, #6c757d);
    }

    .barang-jasa-modal .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    .barang-jasa-modal .select2-container--bootstrap4 .select2-dropdown {
        background-color: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #ced4da);
    }

    .barang-jasa-modal .select2-container--bootstrap4 .select2-search--dropdown .select2-search__field {
        background-color: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
        border: 1px solid var(--bs-border-color, #ced4da);
    }

    .barang-jasa-modal .select2-container--bootstrap4 .select2-results__options,
    .barang-jasa-modal .select2-container--bootstrap4 .select2-results__option {
        background-color: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
    }

    .barang-jasa-modal .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected],
    .barang-jasa-modal .select2-container--bootstrap4 .select2-results__option--highlighted[data-selected] {
        background-color: var(--bs-primary, #0d6efd);
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Barang & Jasa</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBarangJasa">
            <i class="bi bi-plus-circle"></i> Tambah Barang/Jasa
        </button>
    </div>
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
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nama Barang/Jasa</label>
                <input type="text" class="form-control" name="nama_item" value="{{ request('nama_item') }}" placeholder="Cari nama...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kode</label>
                <input type="text" class="form-control" name="kode_item" value="{{ request('kode_item') }}" placeholder="Cari kode...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Golongan</label>
                <select class="form-select" name="golongan">
                    <option value="">Semua Golongan</option>
                    @foreach($golonganList as $golongan)
                        <option value="{{ $golongan->kode }}" {{ (string) request('golongan') === (string) $golongan->kode ? 'selected' : '' }}>
                            {{ $golongan->nama }} ({{ strtoupper((string) $golongan->tipe) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non Aktif</option>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('persediaan.barang-jasa') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <div class="border rounded p-3 mb-3 bg-light">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Item Terpilih</label>
                    <div class="form-control bg-white fw-semibold" id="batch-selected-count">0 item dipilih</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Aksi Batch</label>
                    <select class="form-select" id="batch-field">
                        <option value="status">Update Status</option>
                        <option value="track_stok">Update Track Stok</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Nilai</label>
                    <select class="form-select" id="batch-value">
                        <option value="1">Aktif</option>
                        <option value="0">Non Aktif</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="button" class="btn btn-dark" id="btn-apply-batch" disabled>Terapkan Batch</button>
                </div>
            </div>
            <small class="text-muted d-block mt-2">
                Untuk update <strong>track stok</strong>, item yang sudah memiliki stok tidak akan diubah.
            </small>
        </div>

        <form id="batch-update-form" method="POST" action="{{ route('persediaan.barang-jasa.batch-update') }}">
            @csrf
            <input type="hidden" name="field" id="batch-field-input" value="status">
            <input type="hidden" name="value" id="batch-value-input" value="1">
            <div id="batch-ids-container"></div>
        </form>

        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th width="40">
                        <input type="checkbox" class="form-check-input" id="select-all-items">
                    </th>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama Barang/Jasa</th>
                    <th>Tipe</th>
                    <th>Kode Golongan</th>
                    <th>Golongan</th>
                    <th>Satuan</th>
                    <th>Track Stok</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($produk as $index => $item)
                    @php
                        $namaSatuan = optional($item->getRelation('satuan'))->nama ?: '-';
                        $tipeGolongan = strtoupper((string) ($item->kategoriProduk?->tipe ?? ''));
                        $hasStock = (bool) ($item->has_stock ?? false);
                    @endphp
                    <tr data-item-id="{{ $item->id }}" data-item-name="{{ $item->nama }}" data-has-stock="{{ $hasStock ? '1' : '0' }}">
                        <td>
                            <input type="checkbox" class="form-check-input row-checkbox" value="{{ $item->id }}" data-has-stock="{{ $hasStock ? '1' : '0' }}">
                        </td>
                        <td>{{ $produk->firstItem() + $index }}</td>
                        <td><span class="badge bg-light text-dark">{{ $item->kode }}</span></td>
                        <td>{{ $item->nama }}</td>
                        <td>
                            @if($tipeGolongan === 'JASA')
                                <span class="badge bg-info">Jasa</span>
                            @elseif($tipeGolongan === 'BARANG')
                                <span class="badge bg-primary">Barang</span>
                            @else
                                <span class="badge bg-secondary">-</span>
                            @endif
                        </td>
                        <td>{{ $item->kategori_produk_kode ?? '-' }}</td>
                        <td>{{ $item->kategoriProduk->nama ?? '-' }}</td>
                        <td>{{ $namaSatuan }}</td>
                        <td>
                            {!! $item->track_stok ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-secondary">Tidak</span>' !!}
                            @if($hasStock)
                                <span class="badge bg-warning text-dark">Stok Ada</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $templateRows = ($produkTemplateHargaMap[$item->id] ?? collect());
                                $configuredTemplates = collect($templateHargaOptions ?? collect())->filter(function ($templateOption) use ($templateRows) {
                                    return $templateRows->has((int) $templateOption->id);
                                })->values();
                                $configuredCount = $configuredTemplates->count();
                            @endphp
                            @if($configuredCount > 1)
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalHargaBarangJasa{{ $item->id }}">
                                    Multiple Harga
                                </button>
                            @elseif($configuredCount === 1)
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalHargaBarangJasa{{ $item->id }}">
                                    Lihat Harga
                                </button>
                            @else
                                <span class="badge bg-light text-dark">Belum diset</span>
                            @endif
                        </td>
                        <td>{!! $item->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                        <td class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditBarangJasa{{ $item->id }}">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('persediaan.barang-jasa.destroy', $item) }}" data-swal-message="Hapus barang/jasa ini?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted">Belum ada data barang/jasa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $produk->links() }}
    </div>
</div>

<div class="modal fade barang-jasa-modal" id="modalTambahBarangJasa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('persediaan.barang-jasa.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Barang/Jasa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kode</label>
                            <input type="text" name="kode" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Golongan</label>
                            <select name="kategori_produk_kode" class="form-select js-golongan-select" data-placeholder="Pilih golongan" required>
                                <option value="">Pilih Golongan</option>
                                @foreach($golonganList as $golongan)
                                    <option value="{{ $golongan->kode }}">{{ $golongan->nama }} ({{ strtoupper((string) $golongan->tipe) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <select name="satuan_id" class="form-select">
                                <option value="">Pilih Satuan</option>
                                @foreach($satuanList as $satuan)
                                    <option value="{{ $satuan->id }}">{{ $satuan->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Harga Default</label>
                            <input type="text" name="harga_default" class="form-control currency-input" inputmode="decimal" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Track Stok</label>
                            <select name="track_stok" class="form-select">
                                <option value="1">Ya</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
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
                                                    <input type="text" name="template_harga_prices[{{ $templateId }}]" class="form-control currency-input" inputmode="decimal" value="{{ $defaultPrice }}">
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
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($produk as $item)
    <div class="modal fade" id="modalHargaBarangJasa{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Harga Template - {{ $item->kode }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $templateRows = ($produkTemplateHargaMap[$item->id] ?? collect());
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
                        <div class="alert alert-light border text-muted mb-0">Belum ada harga barang/jasa yang diset pada template cabang akses Anda.</div>
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
@endforeach

@foreach($produk as $item)
    <div class="modal fade barang-jasa-modal" id="modalEditBarangJasa{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('persediaan.barang-jasa.update', $item) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Barang/Jasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kode</label>
                                <input type="text" name="kode" class="form-control" value="{{ $item->kode }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Nama</label>
                                <input type="text" name="nama" class="form-control" value="{{ $item->nama }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Golongan</label>
                                <select name="kategori_produk_kode" class="form-select js-golongan-select" data-placeholder="Pilih golongan" required>
                                    <option value="">Pilih Golongan</option>
                                    @foreach($golonganList as $golongan)
                                        <option value="{{ $golongan->kode }}" {{ (string) $item->kategori_produk_kode === (string) $golongan->kode ? 'selected' : '' }}>
                                            {{ $golongan->nama }} ({{ strtoupper((string) $golongan->tipe) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Satuan</label>
                                <select name="satuan_id" class="form-select">
                                    <option value="">Pilih Satuan</option>
                                    @foreach($satuanList as $satuan)
                                        <option value="{{ $satuan->id }}" {{ (string) $item->satuan_id === (string) $satuan->id ? 'selected' : '' }}>
                                            {{ $satuan->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Default</label>
                                <input type="text" name="harga_default" class="form-control currency-input" inputmode="decimal" value="{{ (float) $item->harga_default }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Track Stok</label>
                                <select name="track_stok" class="form-select">
                                    <option value="1" {{ $item->track_stok ? 'selected' : '' }}>Ya</option>
                                    <option value="0" {{ !$item->track_stok ? 'selected' : '' }}>Tidak</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="1" {{ $item->status ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$item->status ? 'selected' : '' }}>Non Aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h6 class="mb-2">Harga per Template (Akses Cabang Anda)</h6>
                            @if(($templateHargaOptions ?? collect())->isEmpty())
                                <div class="alert alert-light border text-muted mb-0">
                                    Belum ada template harga aktif yang terhubung ke cabang akses Anda.
                                </div>
                            @else
                                @php
                                    $templateRows = ($produkTemplateHargaMap[$item->id] ?? collect())->all();
                                @endphp
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
                                                    $defaultPrice = old("template_harga_prices.$templateId", $currentRow?->harga ?? $item->harga_default);
                                                    $defaultStatus = old("template_harga_status.$templateId", $currentRow?->status ?? true);
                                                @endphp
                                                <tr>
                                                    <td>
                                                        {{ $templateOption->kode }} - {{ $templateOption->nama }}
                                                        <input type="hidden" name="template_harga_ids[]" value="{{ $templateId }}">
                                                    </td>
                                                    <td>{{ $templateOption->cabang_nama ?: '-' }}</td>
                                                    <td>
                                                        <input type="text" name="template_harga_prices[{{ $templateId }}]" class="form-control currency-input" inputmode="decimal" value="{{ $defaultPrice }}">
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
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-warning">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    (function () {
        function initGolonganSelect(modalEl) {
            if (!modalEl || typeof window.jQuery === 'undefined') return;

            const $modal = window.jQuery(modalEl);
            const $selects = $modal.find('select.js-golongan-select');

            $selects.each(function () {
                const $el = window.jQuery(this);
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }

                $el.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: $el.data('placeholder') || 'Pilih golongan',
                    allowClear: false,
                });
            });
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
        function formatAllCurrencyInputs(scope) {
            const root = scope || document;
            root.querySelectorAll('input.currency-input').forEach(function (input) {
                input.value = formatCurrencyInput(input.value);
            });
        }
        function normalizeFormCurrencyInputs(form) {
            form.querySelectorAll('input.currency-input').forEach(function (input) {
                input.value = toPlainCurrencyString(input.value);
            });
        }
        function getSelectedRowCheckboxes() {
            return Array.from(document.querySelectorAll('.row-checkbox:checked'));
        }
        function updateBatchSelectionState() {
            const checked = getSelectedRowCheckboxes();
            const total = checked.length;
            const countEl = document.getElementById('batch-selected-count');
            const applyBtn = document.getElementById('btn-apply-batch');
            const selectAll = document.getElementById('select-all-items');
            const allRowCheckboxes = Array.from(document.querySelectorAll('.row-checkbox'));

            if (countEl) countEl.textContent = `${total} item dipilih`;
            if (applyBtn) applyBtn.disabled = total === 0;
            if (selectAll) {
                const selectableCount = allRowCheckboxes.length;
                selectAll.checked = selectableCount > 0 && total === selectableCount;
                selectAll.indeterminate = total > 0 && total < selectableCount;
            }
        }
        function syncBatchValueOptions() {
            const field = document.getElementById('batch-field')?.value || 'status';
            const valueSelect = document.getElementById('batch-value');
            if (!valueSelect) return;

            if (field === 'track_stok') {
                valueSelect.innerHTML = '<option value="1">Ya</option><option value="0">Tidak</option>';
            } else {
                valueSelect.innerHTML = '<option value="1">Aktif</option><option value="0">Non Aktif</option>';
            }
        }
        async function submitBatchUpdate() {
            const checked = getSelectedRowCheckboxes();
            if (checked.length === 0) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Validasi',
                        text: 'Pilih minimal satu item untuk batch update.',
                        confirmButtonText: 'OK',
                    });
                }
                return;
            }

            const field = document.getElementById('batch-field')?.value || 'status';
            const value = document.getElementById('batch-value')?.value || '1';
            const form = document.getElementById('batch-update-form');
            const container = document.getElementById('batch-ids-container');
            const fieldInput = document.getElementById('batch-field-input');
            const valueInput = document.getElementById('batch-value-input');
            if (!form || !container || !fieldInput || !valueInput) return;

            const actionLabel = field === 'track_stok'
                ? `Track Stok -> ${value === '1' ? 'Ya' : 'Tidak'}`
                : `Status -> ${value === '1' ? 'Aktif' : 'Non Aktif'}`;
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Terapkan Batch Update?',
                    html: `Terapkan batch update untuk <b>${checked.length}</b> item?<br>Aksi: <b>${actionLabel}</b>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Terapkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                });
                if (!result.isConfirmed) return;
            }
            if (typeof Swal === 'undefined') {
                return;
            }

            container.innerHTML = '';
            checked.forEach(function (cb) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });
            fieldInput.value = field;
            valueInput.value = value;
            form.submit();
        }

        document.addEventListener('focusin', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.classList.contains('currency-input')) return;
            target.value = toPlainCurrencyString(target.value);
        });
        document.addEventListener('focusout', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.classList.contains('currency-input')) return;
            target.value = formatCurrencyInput(target.value);
        });
        document.addEventListener('input', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.classList.contains('currency-input')) return;
            target.value = target.value.replace(/[^\d,.\-]/g, '');
        });
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                normalizeFormCurrencyInputs(form);
            });
        });
        document.getElementById('select-all-items')?.addEventListener('change', function (event) {
            const checked = !!event.target.checked;
            document.querySelectorAll('.row-checkbox').forEach(function (cb) {
                cb.checked = checked;
            });
            updateBatchSelectionState();
        });
        document.querySelectorAll('.row-checkbox').forEach(function (cb) {
            cb.addEventListener('change', updateBatchSelectionState);
        });
        document.getElementById('batch-field')?.addEventListener('change', syncBatchValueOptions);
        document.getElementById('btn-apply-batch')?.addEventListener('click', submitBatchUpdate);
        document.querySelectorAll('.barang-jasa-modal').forEach(function (modalEl) {
            modalEl.addEventListener('shown.bs.modal', function () {
                initGolonganSelect(modalEl);
            });
            initGolonganSelect(modalEl);
        });

        syncBatchValueOptions();
        updateBatchSelectionState();
        formatAllCurrencyInputs();
    })();
</script>
@endpush
