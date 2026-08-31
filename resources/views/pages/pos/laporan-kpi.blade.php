@extends('layouts.app')

@section('title', 'Laporan KPI Omset')

@push('styles')
<style>
    .kpi-summary-card {
        border-left: 4px solid #0d6efd;
    }
    .kpi-summary-card.fotografer {
        border-left-color: #198754;
    }
</style>
@endpush

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Laporan</a></li>
                <li class="breadcrumb-item active" aria-current="page">KPI Omset</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <span class="badge bg-info">{{ $summary['jumlah_order'] }} transaksi</span>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" @selected((int) $cabang->id === (int) ($filterCabangId ?? 0))>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Dari</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filterDateFrom }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Sampai</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filterDateTo }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Karyawan</label>
                <select name="karyawan_id" class="form-select">
                    <option value="">Semua Karyawan</option>
                    @foreach($karyawanList as $karyawan)
                        <option value="{{ $karyawan->id }}" @selected((int) $karyawan->id === (int) ($filterKaryawanId ?? 0))>
                            {{ $karyawan->name }} {{ $karyawan->role ? '(' . $karyawan->role->nama . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('laporan-kpi.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card kpi-summary-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Total Omset</h6>
                <h3 class="mb-0">Rp {{ number_format($summary['total_omset'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-summary-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Jumlah Transaksi</h6>
                <h3 class="mb-0">{{ number_format($summary['jumlah_order'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-summary-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Jumlah Karyawan</h6>
                <h3 class="mb-0">{{ number_format($summary['jumlah_karyawan'], 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Bagi Hasil Info --}}
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-2">Alokasi CS + Kasir + SPV: {{ $summary['persen_cs_kasir_spv'] }}%</h6>
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $summary['persen_cs_kasir_spv'] }}%">
                        {{ $summary['persen_cs_kasir_spv'] }}%
                    </div>
                </div>
                <small class="text-muted">
                    Total: Rp {{ number_format($summary['nilai_bagi_hasil_cs_kasir_spv'], 0, ',', '.') }}
                    ({{ $summary['jumlah_peserta_cs_kasir_spv'] }} peserta)
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card kpi-summary-card fotografer">
            <div class="card-body">
                <h6 class="mb-2">Alokasi Fotografer: {{ $summary['persen_fotografer'] }}%</h6>
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $summary['persen_fotografer'] }}%">
                        {{ $summary['persen_fotografer'] }}%
                    </div>
                </div>
                <small class="text-muted">
                    Total: Rp {{ number_format($summary['nilai_bagi_hasil_fotografer'], 0, ',', '.') }}
                    ({{ $summary['jumlah_peserta_fotografer'] }} peserta)
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Data Table --}}
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Perincian Per Karyawan</h5>
            <div>
                <a href="{{ route('laporan-kpi.export', request()->except('export')) }}" class="btn btn-outline-success btn-sm">
                    Export Excel
                </a>
                <a href="{{ route('laporan-kpi.konfigurasi') }}" class="btn btn-outline-primary btn-sm">
                    Konfigurasi KPI
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Cabang</th>
                        <th class="text-center">Jumlah Order<br><small class="text-muted fw-normal">(Transaksi yang ditanganinya)</small></th>
                        <th class="text-center">Proporsi<br><small class="text-muted fw-normal">(Terhadap Total)</small></th>
                        <th class="text-end">Alokasi</th>
                        <th class="text-end">Bagi Hasil</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($karyawanStats as $index => $stat)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $stat['nama'] }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $stat['role'] === 'Fotografer' ? 'success' : 'primary' }}">
                                {{ $stat['role'] }}
                            </span>
                        </td>
                        <td>{{ $stat['cabang'] }}</td>
                        <td class="text-center">{{ number_format($stat['jumlah_order'], 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($summary['jumlah_order'] > 0)
                                {{ number_format(($stat['jumlah_order'] / $summary['jumlah_order']) * 100, 1, ',', '.') }}%
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end">
                            @if($stat['role'] === 'Fotografer')
                                Fotografer ({{ $summary['persen_fotografer'] }}%)
                            @else
                                CS+Kasir+SPV ({{ $summary['persen_cs_kasir_spv'] }}%)
                            @endif
                        </td>
                        <td class="text-end fw-bold">
                            Rp {{ number_format($stat['bagi_hasil'] ?? 0, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Tidak ada data pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Penjelasan Metode Bagi Hasil --}}
<div class="card mt-3">
    <div class="card-body">
        <h6>Metode Perhitungan Bagi Hasil</h6>
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">CS + Kasir + SPV ({{ $summary['persen_cs_kasir_spv'] }}%)</h6>
                <ul>
                    <li>Total alokasi: <strong>Rp {{ number_format($summary['nilai_bagi_hasil_cs_kasir_spv'], 0, ',', '.') }}</strong></li>
                    <li>Proporsional berdasarkan jumlah transaksi masing-masing</li>
                    <li>Dihitung: <em>(Transaksi Karyawan / Total Transaksi) × Alokasi</em></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-success">Fotografer ({{ $summary['persen_fotografer'] }}%)</h6>
                <ul>
                    <li>Total alokasi: <strong>Rp {{ number_format($summary['nilai_bagi_hasil_fotografer'], 0, ',', '.') }}</strong></li>
                    <li>Proporsional berdasarkan jumlah transaksi masing-masing</li>
                    <li>Dihitung: <em>(Transaksi Karyawan / Total Transaksi) × Alokasi</em></li>
                </ul>
            </div>
        </div>
        <div class="alert alert-info mt-2 mb-0">
            <strong>Catatan:</strong> Total Omset yang ditampilkan adalah total transaksi aktual (Rp {{ number_format($summary['total_omset'], 0, ',', '.') }}), bukan penjumlahan per karyawan.
        </div>
    </div>
</div>
@endsection