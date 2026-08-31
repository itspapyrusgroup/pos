@extends('layouts.app')

@section('title', 'Master Promosi')

@push('styles')
<style>
    .promo-modal .select2-container {
        width: 100% !important;
    }

    .promo-modal .select2-container--bootstrap4 .select2-selection--multiple {
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        background-color: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #ced4da);
        color: var(--bs-body-color, #212529);
    }

    .promo-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
        color: var(--bs-body-color, #212529);
    }

    .promo-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field {
        color: var(--bs-body-color, #212529);
    }

    .promo-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
        background-color: color-mix(in srgb, var(--bs-primary, #0d6efd) 18%, var(--bs-body-bg, #fff));
        border: 1px solid color-mix(in srgb, var(--bs-primary, #0d6efd) 45%, transparent);
        color: var(--bs-body-color, #212529);
        padding-inline: 0.5rem;
    }

    .promo-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
        color: inherit;
        margin-right: 0.35rem;
    }

    .promo-modal .select2-container--bootstrap4 .select2-dropdown {
        background-color: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #ced4da);
    }

    .promo-modal .select2-container--bootstrap4 .select2-search--dropdown .select2-search__field {
        background-color: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
        border: 1px solid var(--bs-border-color, #ced4da);
    }

    .promo-modal .select2-container--bootstrap4 .select2-results__options {
        background-color: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
    }

    .promo-modal .select2-container--bootstrap4 .select2-results__option {
        color: var(--bs-body-color, #212529);
    }

    .promo-modal .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected],
    .promo-modal .select2-container--bootstrap4 .select2-results__option--highlighted[data-selected] {
        background-color: var(--bs-primary, #0d6efd);
        color: #fff;
    }

    .promo-modal .select2-container--bootstrap4 .select2-results__option[aria-selected=true],
    .promo-modal .select2-container--bootstrap4 .select2-results__option[data-selected=true] {
        background-color: color-mix(in srgb, var(--bs-primary, #0d6efd) 16%, var(--bs-body-bg, #fff));
        color: var(--bs-body-color, #212529);
    }

    .promo-modal .select2-container--bootstrap4.select2-container--focus .select2-selection--multiple,
    .promo-modal .select2-container--bootstrap4.select2-container--open .select2-selection--multiple {
        border-color: color-mix(in srgb, var(--bs-primary, #0d6efd) 55%, var(--bs-border-color, #ced4da));
        box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--bs-primary, #0d6efd) 18%, transparent);
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $canCreatePromosi = $user?->hasPermission('promosi.create') ?? false;
    $canUpdatePromosi = $user?->hasPermission('promosi.update') ?? false;
    $canDeletePromosi = $user?->hasPermission('promosi.delete') ?? false;
    $hariLabels = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Master Promosi</li>
            </ol>
        </nav>
    </div>
</div>

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
        @if($canCreatePromosi)
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVoucherModal">
                    <i class="bi bi-ticket-perforated me-1"></i> Tambah Voucher
                </button>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                    data-bs-target="#addDiskonModal">
                    <i class="bi bi-percent me-1"></i> Tambah Diskon Otomatis
                </button>
            </div>
        @endif

        <form method="GET" class="row g-2">
            <input type="hidden" name="tab" id="promo-tab-input" value="{{ $activeTab ?? 'voucher' }}">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                    placeholder="Cari nama / kode voucher">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request()->exists('status') ? (request('status') === '1' ? 'selected' : '') : 'selected' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non Aktif</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangList as $cabang)
                        <option value="{{ $cabang->id }}" {{ (string) request('cabang_id') === (string) $cabang->id ? 'selected' : '' }}>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('promosi') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header pb-0 border-0">
        <ul class="nav nav-tabs card-header-tabs" id="promo-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ ($activeTab ?? 'voucher') === 'voucher' ? 'active' : '' }}"
                    id="tab-voucher-btn" data-bs-toggle="tab" data-bs-target="#tab-voucher" type="button" role="tab"
                    aria-controls="tab-voucher"
                    aria-selected="{{ ($activeTab ?? 'voucher') === 'voucher' ? 'true' : 'false' }}">
                    Voucher
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ ($activeTab ?? '') === 'diskon' ? 'active' : '' }}" id="tab-diskon-btn"
                    data-bs-toggle="tab" data-bs-target="#tab-diskon" type="button" role="tab"
                    aria-controls="tab-diskon" aria-selected="{{ ($activeTab ?? '') === 'diskon' ? 'true' : 'false' }}">
                    Diskon Otomatis
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade {{ ($activeTab ?? 'voucher') === 'voucher' ? 'show active' : '' }}"
                id="tab-voucher" role="tabpanel" aria-labelledby="tab-voucher-btn">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Diskon</th>
                                <th>Min</th>
                                <th>Cabang</th>
                                <th>Paket</th>
                                <th>Periode Aktif</th>
                                <th>Hari Aktif</th>
                                <th>Jam Aktif</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($voucherList as $v)
                            @php($cabangNames = $v->cabangs->pluck('nama')->filter()->values())
                            <tr>
                                <td>{{ $v->kode }}</td>
                                <td>{{ $v->nama }}</td>
                                <td>{{ $v->tipe_diskon === 'PERSEN' ? $v->nilai_diskon . '%' : 'Rp ' . number_format((float) $v->nilai_diskon, 0, ',', '.') }}
                                </td>
                                <td>Rp {{ number_format((float) $v->minimum_pembelian, 0, ',', '.') }}</td>
                                <td>
                                    @if($cabangNames->isNotEmpty())
                                        {{ $cabangNames->implode(', ') }}
                                    @else
                                        {{ $v->cabang->nama ?? 'Semua Cabang' }}
                                    @endif
                                </td>
                                <td>{{ $v->aktif_mulai?->format('d-m-Y') }} s/d {{ $v->aktif_sampai?->format('d-m-Y') }}
                                </td>
                                <td>
                                    @if(empty($v->hari_aktif) || !is_array($v->hari_aktif))
                                        Setiap Hari
                                    @else
                                        {{ collect($v->hari_aktif)->map(fn($h) => $hariLabels[(int) $h] ?? $h)->implode(', ') }}
                                    @endif
                                </td>
                                <td>
                                    @if($v->aktif_24_jam)
                                        24 Jam
                                    @elseif($v->jam_mulai && $v->jam_sampai)
                                        {{ \Illuminate\Support\Str::substr((string) $v->jam_mulai, 0, 5) }} -
                                        {{ \Illuminate\Support\Str::substr((string) $v->jam_sampai, 0, 5) }}
                                    @else
                                        24 Jam
                                    @endif
                                </td>
                                <td>{{ $v->status ? 'Aktif' : 'Non Aktif' }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if($canUpdatePromosi)
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#editVoucherModal{{ $v->id }}">
                                                Edit
                                            </button>
                                        @endif
                                        @if($canDeletePromosi)
                                            <form method="POST" action="{{ route('promosi.voucher.destroy', $v) }}"
                                                data-swal-message="Hapus voucher ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        @endif
                                        @if(!$canUpdatePromosi && !$canDeletePromosi)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada voucher.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $voucherList->appends(['tab' => 'voucher'])->links() }}
            </div>

            <div class="tab-pane fade {{ ($activeTab ?? '') === 'diskon' ? 'show active' : '' }}" id="tab-diskon"
                role="tabpanel" aria-labelledby="tab-diskon-btn">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Diskon</th>
                                <th>Min</th>
                                <th>Cabang</th>
                                <th>Periode Aktif</th>
                                <th>Hari Aktif</th>
                                <th>Jam Aktif</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($diskonList as $d)
                            @php($cabangNames = $d->cabangs->pluck('nama')->filter()->values())
                            <tr>
                                <td>{{ $d->nama }}</td>
                                <td>{{ $d->tipe_diskon === 'PERSEN' ? $d->nilai_diskon . '%' : 'Rp ' . number_format((float) $d->nilai_diskon, 0, ',', '.') }}
                                </td>
                                <td>Rp {{ number_format((float) $d->minimum_pembelian, 0, ',', '.') }}</td>
                                <td>
                                    @if($cabangNames->isNotEmpty())
                                        {{ $cabangNames->implode(', ') }}
                                    @else
                                        {{ $d->cabang->nama ?? 'Semua Cabang' }}
                                    @endif
                                </td>
                                <td>{{ $d->pakets->isNotEmpty() ? $d->pakets->pluck('nama')->implode(', ') : 'Semua Paket' }}</td>
                                <td>{{ $d->aktif_mulai?->format('d-m-Y') }} s/d {{ $d->aktif_sampai?->format('d-m-Y') }}
                                </td>
                                <td>
                                    @if(empty($d->hari_aktif) || !is_array($d->hari_aktif))
                                        Setiap Hari
                                    @else
                                        {{ collect($d->hari_aktif)->map(fn($h) => $hariLabels[(int) $h] ?? $h)->implode(', ') }}
                                    @endif
                                </td>
                                <td>
                                    @if($d->aktif_24_jam)
                                        24 Jam
                                    @elseif($d->jam_mulai && $d->jam_sampai)
                                        {{ \Illuminate\Support\Str::substr((string) $d->jam_mulai, 0, 5) }} -
                                        {{ \Illuminate\Support\Str::substr((string) $d->jam_sampai, 0, 5) }}
                                    @else
                                        24 Jam
                                    @endif
                                </td>
                                <td>{{ $d->status ? 'Aktif' : 'Non Aktif' }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if($canUpdatePromosi)
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#editDiskonModal{{ $d->id }}">
                                                Edit
                                            </button>
                                        @endif
                                        @if($canDeletePromosi)
                                            <form method="POST" action="{{ route('promosi.diskon.destroy', $d) }}"
                                                data-swal-message="Hapus diskon otomatis ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        @endif
                                        @if(!$canUpdatePromosi && !$canDeletePromosi)
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada diskon otomatis.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $diskonList->appends(['tab' => 'diskon'])->links() }}
            </div>
        </div>
    </div>
</div>

@if($canCreatePromosi)
    <div class="modal fade promo-modal" id="addVoucherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('promosi.voucher.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Voucher</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Kode</label><input type="text" name="kode"
                                    class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">Nama</label><input type="text" name="nama"
                                    class="form-control" required></div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe Diskon</label>
                                <select name="tipe_diskon" class="form-select" required>
                                    <option value="NOMINAL">Nominal</option>
                                    <option value="PERSEN">Persen</option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label">Nilai Diskon</label><input type="number" min="0"
                                    step="0.01" name="nilai_diskon" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label">Min Pembelian</label><input type="number"
                                    min="0" step="0.01" name="minimum_pembelian" class="form-control"></div>
                            <div class="col-md-6">
                                <label class="form-label">Cabang Berlaku (Multiple)</label>
                                <select name="cabang_ids[]" class="form-select multiple-select w-100"
                                    data-placeholder="Pilih satu / beberapa cabang" multiple>
                                    @foreach($cabangList as $cabang)
                                        <option value="{{ $cabang->id }}">{{ $cabang->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk semua cabang.</small>
                            </div>
                            <div class="col-md-3"><label class="form-label">Aktif Mulai</label><input type="date"
                                    name="aktif_mulai" class="form-control" required></div>
                            <div class="col-md-3"><label class="form-label">Aktif Sampai</label><input type="date"
                                    name="aktif_sampai" class="form-control" required></div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input js-setiap-hari" type="checkbox" id="voucher_setiap_hari"
                                        checked>
                                    <label class="form-check-label" for="voucher_setiap_hari">Setiap Hari</label>
                                </div>
                            </div>
                            <div class="col-12 js-hari-list">
                                <label class="form-label d-block mb-2">Pilih Hari Aktif</label>
                                @foreach($hariLabels as $hariVal => $hariLabel)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input js-hari-item" type="checkbox" name="hari_aktif[]"
                                            value="{{ $hariVal }}" id="voucher_hari_{{ $hariVal }}">
                                        <label class="form-check-label"
                                            for="voucher_hari_{{ $hariVal }}">{{ $hariLabel }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input js-aktif-24jam" type="checkbox" id="voucher_24_jam"
                                        name="aktif_24_jam" value="1" checked>
                                    <label class="form-check-label" for="voucher_24_jam">Aktif 24 Jam</label>
                                </div>
                            </div>
                            <div class="col-md-3"><label class="form-label">Jam Mulai</label><input type="time"
                                    name="jam_mulai" class="form-control js-jam-mulai" disabled></div>
                            <div class="col-md-3"><label class="form-label">Jam Sampai</label><input type="time"
                                    name="jam_sampai" class="form-control js-jam-sampai" disabled></div>

                            <div class="col-md-6"><label class="form-label">Kuota (opsional)</label><input type="number"
                                    min="1" name="kuota" class="form-control"></div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Non Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Voucher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <div class="modal fade promo-modal" id="addDiskonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('promosi.diskon.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Diskon Otomatis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12"><label class="form-label">Nama</label><input type="text" name="nama"
                                    class="form-control" required></div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe Diskon</label>
                                <select name="tipe_diskon" class="form-select" required>
                                    <option value="NOMINAL">Nominal</option>
                                    <option value="PERSEN">Persen</option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label">Nilai Diskon</label><input type="number" min="0"
                                    step="0.01" name="nilai_diskon" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label">Min Pembelian</label><input type="number"
                                    min="0" step="0.01" name="minimum_pembelian" class="form-control"></div>

                            <div class="col-md-6">
                                <label class="form-label">Cabang Berlaku (Multiple)</label>
                                <select name="cabang_ids[]" class="form-select multiple-select w-100"
                                    data-placeholder="Pilih satu / beberapa cabang" multiple>
                                    @foreach($cabangList as $cabang)
                                        <option value="{{ $cabang->id }}">{{ $cabang->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk semua cabang.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Paket Berlaku (Multiple)</label>
                                <select name="paket_ids[]" class="form-select multiple-select w-100"
                                    data-placeholder="Pilih satu / beberapa paket" multiple>
                                    @foreach($paketList as $paket)
                                        <option value="{{ $paket->id }}">{{ $paket->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk semua paket.</small>
                            </div>
                            <div class="col-md-3"><label class="form-label">Aktif Mulai</label><input type="date"
                                    name="aktif_mulai" class="form-control" required></div>
                            <div class="col-md-3"><label class="form-label">Aktif Sampai</label><input type="date"
                                    name="aktif_sampai" class="form-control" required></div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input js-setiap-hari" type="checkbox" id="diskon_setiap_hari"
                                        checked>
                                    <label class="form-check-label" for="diskon_setiap_hari">Setiap Hari</label>
                                </div>
                            </div>
                            <div class="col-12 js-hari-list">
                                <label class="form-label d-block mb-2">Pilih Hari Aktif</label>
                                @foreach($hariLabels as $hariVal => $hariLabel)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input js-hari-item" type="checkbox" name="hari_aktif[]"
                                            value="{{ $hariVal }}" id="diskon_hari_{{ $hariVal }}">
                                        <label class="form-check-label"
                                            for="diskon_hari_{{ $hariVal }}">{{ $hariLabel }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input js-aktif-24jam" type="checkbox" id="diskon_24_jam"
                                        name="aktif_24_jam" value="1" checked>
                                    <label class="form-check-label" for="diskon_24_jam">Aktif 24 Jam</label>
                                </div>
                            </div>
                            <div class="col-md-3"><label class="form-label">Jam Mulai</label><input type="time"
                                    name="jam_mulai" class="form-control js-jam-mulai" disabled></div>
                            <div class="col-md-3"><label class="form-label">Jam Sampai</label><input type="time"
                                    name="jam_sampai" class="form-control js-jam-sampai" disabled></div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Non Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Diskon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@foreach($voucherList as $v)
@php($voucherCabangIds = $v->cabangs->pluck('id')->map(fn($id) => (int) $id)->all())
@if(empty($voucherCabangIds) && $v->cabang_id)
@php($voucherCabangIds = [(int) $v->cabang_id])
@endif
@php($voucherHariAktif = collect($v->hari_aktif ?? [])->map(fn($h) => (int) $h)->all())
@if($canUpdatePromosi)
    <div class="modal fade promo-modal" id="editVoucherModal{{ $v->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('promosi.voucher.update', $v) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Voucher</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Kode</label><input type="text" name="kode"
                                    class="form-control" required value="{{ $v->kode }}"></div>
                            <div class="col-md-6"><label class="form-label">Nama</label><input type="text" name="nama"
                                    class="form-control" required value="{{ $v->nama }}"></div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe Diskon</label>
                                <select name="tipe_diskon" class="form-select" required>
                                    <option value="NOMINAL" {{ $v->tipe_diskon === 'NOMINAL' ? 'selected' : '' }}>Nominal
                                    </option>
                                    <option value="PERSEN" {{ $v->tipe_diskon === 'PERSEN' ? 'selected' : '' }}>Persen
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label">Nilai Diskon</label><input type="number" min="0"
                                    step="0.01" name="nilai_diskon" class="form-control" required
                                    value="{{ (float) $v->nilai_diskon }}"></div>
                            <div class="col-md-4"><label class="form-label">Min Pembelian</label><input type="number"
                                    min="0" step="0.01" name="minimum_pembelian" class="form-control"
                                    value="{{ (float) $v->minimum_pembelian }}"></div>
                            <div class="col-md-6">
                                <label class="form-label">Cabang Berlaku (Multiple)</label>
                                <select name="cabang_ids[]" class="form-select multiple-select w-100"
                                    data-placeholder="Pilih satu / beberapa cabang" multiple>
                                    @foreach($cabangList as $cabang)
                                        <option value="{{ $cabang->id }}" {{ in_array((int) $cabang->id, $voucherCabangIds, true) ? 'selected' : '' }}>{{ $cabang->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk semua cabang.</small>
                            </div>
                            <div class="col-md-3"><label class="form-label">Aktif Mulai</label><input type="date"
                                    name="aktif_mulai" class="form-control" required
                                    value="{{ $v->aktif_mulai?->format('Y-m-d') }}"></div>
                            <div class="col-md-3"><label class="form-label">Aktif Sampai</label><input type="date"
                                    name="aktif_sampai" class="form-control" required
                                    value="{{ $v->aktif_sampai?->format('Y-m-d') }}"></div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input js-setiap-hari" type="checkbox"
                                        id="voucher_edit_setiap_hari_{{ $v->id }}" {{ empty($voucherHariAktif) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="voucher_edit_setiap_hari_{{ $v->id }}">Setiap
                                        Hari</label>
                                </div>
                            </div>
                            <div class="col-12 js-hari-list">
                                <label class="form-label d-block mb-2">Pilih Hari Aktif</label>
                                @foreach($hariLabels as $hariVal => $hariLabel)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input js-hari-item" type="checkbox" name="hari_aktif[]"
                                            value="{{ $hariVal }}" id="voucher_edit_hari_{{ $v->id }}_{{ $hariVal }}" {{ in_array((int) $hariVal, $voucherHariAktif, true) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                            for="voucher_edit_hari_{{ $v->id }}_{{ $hariVal }}">{{ $hariLabel }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input js-aktif-24jam" type="checkbox"
                                        id="voucher_edit_24_jam_{{ $v->id }}" name="aktif_24_jam" value="1" {{ $v->aktif_24_jam ? 'checked' : '' }}>
                                    <label class="form-check-label" for="voucher_edit_24_jam_{{ $v->id }}">Aktif 24
                                        Jam</label>
                                </div>
                            </div>
                            <div class="col-md-3"><label class="form-label">Jam Mulai</label><input type="time"
                                    name="jam_mulai" class="form-control js-jam-mulai"
                                    value="{{ $v->jam_mulai ? \Illuminate\Support\Str::substr((string) $v->jam_mulai, 0, 5) : '' }}"
                                    {{ $v->aktif_24_jam ? 'disabled' : '' }}></div>
                            <div class="col-md-3"><label class="form-label">Jam Sampai</label><input type="time"
                                    name="jam_sampai" class="form-control js-jam-sampai"
                                    value="{{ $v->jam_sampai ? \Illuminate\Support\Str::substr((string) $v->jam_sampai, 0, 5) : '' }}"
                                    {{ $v->aktif_24_jam ? 'disabled' : '' }}></div>

                            <div class="col-md-6"><label class="form-label">Kuota (opsional)</label><input type="number"
                                    min="1" name="kuota" class="form-control" value="{{ $v->kuota }}"></div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="1" {{ $v->status ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$v->status ? 'selected' : '' }}>Non Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Voucher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endforeach

@foreach($diskonList as $d)
@php($diskonCabangIds = $d->cabangs->pluck('id')->map(fn($id) => (int) $id)->all())
@php($diskonPaketIds = $d->pakets->pluck('id')->map(fn($id) => (int) $id)->all())
@if(empty($diskonCabangIds) && $d->cabang_id)
@php($diskonCabangIds = [(int) $d->cabang_id])
@endif
@php($diskonHariAktif = collect($d->hari_aktif ?? [])->map(fn($h) => (int) $h)->all())
@if($canUpdatePromosi)
    <div class="modal fade promo-modal" id="editDiskonModal{{ $d->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('promosi.diskon.update', $d) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Diskon Otomatis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12"><label class="form-label">Nama</label><input type="text" name="nama"
                                    class="form-control" required value="{{ $d->nama }}"></div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe Diskon</label>
                                <select name="tipe_diskon" class="form-select" required>
                                    <option value="NOMINAL" {{ $d->tipe_diskon === 'NOMINAL' ? 'selected' : '' }}>Nominal
                                    </option>
                                    <option value="PERSEN" {{ $d->tipe_diskon === 'PERSEN' ? 'selected' : '' }}>Persen
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label">Nilai Diskon</label><input type="number" min="0"
                                    step="0.01" name="nilai_diskon" class="form-control" required
                                    value="{{ (float) $d->nilai_diskon }}"></div>
                            <div class="col-md-4"><label class="form-label">Min Pembelian</label><input type="number"
                                    min="0" step="0.01" name="minimum_pembelian" class="form-control"
                                    value="{{ (float) $d->minimum_pembelian }}"></div>
                            <div class="col-md-6">
                                <label class="form-label">Cabang Berlaku (Multiple)</label>
                                <select name="cabang_ids[]" class="form-select multiple-select w-100"
                                    data-placeholder="Pilih satu / beberapa cabang" multiple>
                                    @foreach($cabangList as $cabang)
                                        <option value="{{ $cabang->id }}" {{ in_array((int) $cabang->id, $diskonCabangIds, true) ? 'selected' : '' }}>{{ $cabang->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk semua cabang.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Paket Berlaku (Multiple)</label>
                                <select name="paket_ids[]" class="form-select multiple-select w-100"
                                    data-placeholder="Pilih satu / beberapa paket" multiple>
                                    @foreach($paketList as $paket)
                                        <option value="{{ $paket->id }}" {{ in_array((int) $paket->id, $diskonPaketIds, true) ? 'selected' : '' }}>{{ $paket->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Kosongkan untuk semua paket.</small>
                            </div>
                            <div class="col-md-3"><label class="form-label">Aktif Mulai</label><input type="date"
                                    name="aktif_mulai" class="form-control" required
                                    value="{{ $d->aktif_mulai?->format('Y-m-d') }}"></div>
                            <div class="col-md-3"><label class="form-label">Aktif Sampai</label><input type="date"
                                    name="aktif_sampai" class="form-control" required
                                    value="{{ $d->aktif_sampai?->format('Y-m-d') }}"></div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input js-setiap-hari" type="checkbox"
                                        id="diskon_edit_setiap_hari_{{ $d->id }}" {{ empty($diskonHariAktif) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="diskon_edit_setiap_hari_{{ $d->id }}">Setiap
                                        Hari</label>
                                </div>
                            </div>
                            <div class="col-12 js-hari-list">
                                <label class="form-label d-block mb-2">Pilih Hari Aktif</label>
                                @foreach($hariLabels as $hariVal => $hariLabel)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input js-hari-item" type="checkbox" name="hari_aktif[]"
                                            value="{{ $hariVal }}" id="diskon_edit_hari_{{ $d->id }}_{{ $hariVal }}" {{ in_array((int) $hariVal, $diskonHariAktif, true) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                            for="diskon_edit_hari_{{ $d->id }}_{{ $hariVal }}">{{ $hariLabel }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input js-aktif-24jam" type="checkbox"
                                        id="diskon_edit_24_jam_{{ $d->id }}" name="aktif_24_jam" value="1" {{ $d->aktif_24_jam ? 'checked' : '' }}>
                                    <label class="form-check-label" for="diskon_edit_24_jam_{{ $d->id }}">Aktif 24
                                        Jam</label>
                                </div>
                            </div>
                            <div class="col-md-3"><label class="form-label">Jam Mulai</label><input type="time"
                                    name="jam_mulai" class="form-control js-jam-mulai"
                                    value="{{ $d->jam_mulai ? \Illuminate\Support\Str::substr((string) $d->jam_mulai, 0, 5) : '' }}"
                                    {{ $d->aktif_24_jam ? 'disabled' : '' }}></div>
                            <div class="col-md-3"><label class="form-label">Jam Sampai</label><input type="time"
                                    name="jam_sampai" class="form-control js-jam-sampai"
                                    value="{{ $d->jam_sampai ? \Illuminate\Support\Str::substr((string) $d->jam_sampai, 0, 5) : '' }}"
                                    {{ $d->aktif_24_jam ? 'disabled' : '' }}></div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="1" {{ $d->status ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$d->status ? 'selected' : '' }}>Non Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Diskon</button>
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
            function initModalSelect2(modalEl) {
                if (!modalEl || typeof window.jQuery === 'undefined') return;

                const $modal = window.jQuery(modalEl);
                const $selects = $modal.find('select.multiple-select');

                $selects.each(function () {
                    const $el = window.jQuery(this);
                    if ($el.data('select2')) {
                        $el.select2('destroy');
                    }
                    $el.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: $el.data('placeholder') || 'Pilih cabang',
                        allowClear: true,
                        dropdownParent: $modal,
                    });
                });
            }

            function toggleTimeInputs(container) {
                const checkbox = container.querySelector('.js-aktif-24jam');
                const startInput = container.querySelector('.js-jam-mulai');
                const endInput = container.querySelector('.js-jam-sampai');
                if (!checkbox || !startInput || !endInput) return;

                const disabled = checkbox.checked;
                startInput.disabled = disabled;
                endInput.disabled = disabled;

                if (disabled) {
                    startInput.value = '';
                    endInput.value = '';
                }
            }

            function toggleHariInputs(container) {
                const setiapHari = container.querySelector('.js-setiap-hari');
                const hariItems = container.querySelectorAll('.js-hari-item');
                if (!setiapHari || !hariItems.length) return;

                const disabled = setiapHari.checked;
                hariItems.forEach(function (el) {
                    el.disabled = disabled;
                    if (disabled) {
                        el.checked = false;
                    }
                });
            }

            document.querySelectorAll('.promo-modal form').forEach(function (form) {
                toggleTimeInputs(form);
                toggleHariInputs(form);
                const checkbox = form.querySelector('.js-aktif-24jam');
                if (checkbox) {
                    checkbox.addEventListener('change', function () {
                        toggleTimeInputs(form);
                    });
                }
                const setiapHari = form.querySelector('.js-setiap-hari');
                if (setiapHari) {
                    setiapHari.addEventListener('change', function () {
                        toggleHariInputs(form);
                    });
                }
            });

            document.querySelectorAll('.promo-modal').forEach(function (modalEl) {
                modalEl.addEventListener('shown.bs.modal', function () {
                    initModalSelect2(modalEl);
                });
                initModalSelect2(modalEl);
            });

            const tabInput = document.getElementById('promo-tab-input');
            document.querySelectorAll('#promo-tabs [data-bs-toggle="tab"]').forEach(function (tabBtn) {
                tabBtn.addEventListener('shown.bs.tab', function (event) {
                    const target = event.target.getAttribute('data-bs-target');
                    if (!tabInput || !target) return;
                    tabInput.value = target === '#tab-diskon' ? 'diskon' : 'voucher';
                });
            });
        })();
    </script>
@endpush
