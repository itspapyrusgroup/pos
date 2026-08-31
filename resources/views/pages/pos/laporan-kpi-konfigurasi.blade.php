@extends('layouts.app')

@section('title', 'Konfigurasi KPI')

@push('styles')
<style>
    .kpi-config-card {
        border-left: 4px solid #0d6efd;
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
                <li class="breadcrumb-item"><a href="{{ route('laporan-kpi.index') }}">Laporan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Konfigurasi KPI</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tambah / Edit Konfigurasi</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('laporan-kpi.konfigurasi.simpan') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Cabang</label>
                        <select name="cabang_id" class="form-select" required>
                            <option value="">Pilih Cabang</option>
                            @foreach($cabangs as $cabang)
                                <option value="{{ $cabang->id }}">{{ $cabang->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Konfigurasi</label>
                        <input type="text" name="nama_konfigurasi" class="form-control" value="Default" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Persentase CS + Kasir + SPV (%)</label>
                        <input type="number" name="persen_cs_kasir_spv" class="form-control" value="60" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Persentase Fotografer (%)</label>
                        <input type="number" name="persen_fotografer" class="form-control" value="40" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_kasir" id="include_kasir" value="1" checked>
                            <label class="form-check-label" for="include_kasir">
                                Include Kasir dalam bagi hasil
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_spv" id="include_spv" value="1" checked>
                            <label class="form-check-label" for="include_spv">
                                Include SPV dalam bagi hasil
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-info small">
                        <strong>Catatan:</strong> Total persentase CS+Kasir+SPV dan Fotografer harus sama dengan 100%.
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Konfigurasi</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Konfigurasi</h5>
                <a href="{{ route('laporan-kpi.index') }}" class="btn btn-outline-primary btn-sm">Lihat Laporan KPI</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Cabang</th>
                                <th>Nama Konfigurasi</th>
                                <th class="text-center">% CS+Kasir+SPV</th>
                                <th class="text-center">% Fotografer</th>
                                <th>Include Kasir</th>
                                <th>Include SPV</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($configs as $config)
                            <tr class="kpi-config-card">
                                <td>{{ $config->cabang?->nama ?? '-' }}</td>
                                <td>{{ $config->nama_konfigurasi }}</td>
                                <td class="text-center">{{ number_format($config->persen_cs_kasir_spv, 2) }}%</td>
                                <td class="text-center">{{ number_format($config->persen_fotografer, 2) }}%</td>
                                <td>
                                    @if($config->include_kasir)
                                        <span class="badge bg-success">Ya</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak</span>
                                    @endif
                                </td>
                                <td>
                                    @if($config->include_spv)
                                        <span class="badge bg-success">Ya</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak</span>
                                    @endif
                                </td>
                                <td>
                                    @if($config->status)
                                        <span class="badge bg-primary">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada konfigurasi. Silakan tambah di form sebelah.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($configs->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $configs->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection