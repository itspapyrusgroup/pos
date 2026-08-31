@extends('layouts.app')

@section('title', 'Laporan Promosi')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Laporan Promosi</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="promosi_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}" data-date-range-picker data-date-from="#promosi_date_from" data-date-to="#promosi_date_to" readonly>
                <input type="hidden" id="promosi_date_from" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="promosi_date_to" name="date_to" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" @selected((int) ($filters['cabang_id'] ?? 0) === (int) $cabang->id)>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sumber Promo</label>
                <select name="sumber" class="form-select">
                    <option value="SEMUA" @selected(($filters['sumber'] ?? 'SEMUA') === 'SEMUA')>Semua</option>
                    <option value="VOUCHER" @selected(($filters['sumber'] ?? '') === 'VOUCHER')>Voucher</option>
                    <option value="OTOMATIS" @selected(($filters['sumber'] ?? '') === 'OTOMATIS')>Otomatis</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Kode Promo</label>
                <input type="text" name="kode" class="form-control" value="{{ $filters['kode'] }}" placeholder="Kode promo">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Transaksi Pakai Promo</small>
                <h5 class="mb-0">{{ number_format((float) $summary['jumlah_transaksi_promo'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Total Pemakaian Promo</small>
                <h5 class="mb-0">{{ number_format((float) $summary['jumlah_pemakaian_promo'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Total Diskon Promo</small>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['total_diskon'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body table-responsive">
        <h6 class="mb-3">Rekap Promo</h6>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sumber</th>
                    <th>Kode Promo</th>
                    <th class="text-end">Jumlah Pemakaian</th>
                    <th class="text-end">Total Diskon</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekapPromo as $row)
                    <tr>
                        <td>{{ $row['sumber'] }}</td>
                        <td>{{ $row['kode'] }}</td>
                        <td class="text-end">{{ number_format((float) $row['jumlah_pemakaian'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['total_diskon'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada data promo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <h6 class="mb-3">Detail Pemakaian Promo</h6>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>Kasir</th>
                    <th>No SO</th>
                    <th>No KO</th>
                    <th>Sumber</th>
                    <th>Kode Promo</th>
                    <th class="text-end">Diskon</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detailRows as $row)
                    <tr>
                        <td>{{ $row['tanggal']?->format('d-m-Y H:i') }}</td>
                        <td>{{ $row['cabang_nama'] }}</td>
                        <td>{{ $row['kasir_nama'] }}</td>
                        <td>{{ $row['nomor_so'] }}</td>
                        <td>{{ $row['nomor_ko'] }}</td>
                        <td>{{ $row['sumber'] }}</td>
                        <td>{{ $row['kode'] }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['diskon'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada pemakaian promo pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
