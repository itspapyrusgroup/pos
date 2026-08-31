@extends('layouts.app')

@section('title', 'Tutup Kasir')

@php
    $canReadRiwayat = auth()->user()?->hasPermission('pos.tutup_kasir.read') ?? false;
@endphp

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Tutup Kasir</li>
            </ol>
        </nav>
    </div>
</div>

@if($openShift)
    @if($isStaleOpenShift ?? false)
        <div class="alert alert-warning">
            Shift ini dibuka pada {{ $openShift->dibuka_pada?->format('d-m-Y H:i') }} (hari sebelumnya) dan belum ditutup.
            Selesaikan proses tutup kasir terlebih dahulu sebelum lanjut transaksi hari ini.
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Shift Aktif</h5>
            <div class="row g-3">
                <div class="col-md-4"><strong>Dibuka:</strong><br>{{ $openShift->dibuka_pada?->format('d-m-Y H:i') }}</div>
                <div class="col-md-4"><strong>Modal Awal:</strong><br>Rp {{ number_format((float) $openShift->modal_awal, 0, ',', '.') }}</div>
                <div class="col-md-4"><strong>Status:</strong><br><span class="badge bg-success">OPEN</span></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Input Uang Fisik Saat Tutup Kasir</h5>
            <form method="POST" action="{{ route('pos.tutup-kasir.close') }}">
                @csrf
                <div class="row g-3">
                    @foreach($pecahan as $nominal)
                        <div class="col-md-3">
                            <label class="form-label">Jumlah Rp {{ number_format($nominal, 0, ',', '.') }}</label>
                            <input type="number" min="0" name="pecahan[{{ $nominal }}]" class="form-control" value="{{ old('pecahan.' . $nominal, 0) }}">
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-danger">Tutup Kasir</button>
                </div>
            </form>
        </div>
    </div>
@else
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Belum Ada Shift Aktif</h5>
            <form method="POST" action="{{ route('pos.tutup-kasir.open') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Modal Awal (Opsional)</label>
                    <input type="number" min="0" step="0.01" name="modal_awal" class="form-control" value="0">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Mulai Shift</button>
                </div>
            </form>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body table-responsive">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="mb-0">Riwayat Shift Hari Ini</h6>
            @if(auth()->user()?->hasPermission('laporan.tutup_kasir.view'))
            <a href="{{ route('laporan-tutup-kasir') }}" class="btn btn-sm btn-outline-primary">Lihat Laporan Tutup Kasir</a>
            @endif
        </div>
        @if($canReadRiwayat)
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Cabang</th>
                    <th>Dibuka</th>
                    <th>Ditutup</th>
                    <th class="text-end">Kas Expected</th>
                    <th class="text-end">Kas Fisik</th>
                    <th class="text-end">Selisih</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatShift as $index => $shift)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $shift->cabang?->nama ?? '-' }}</td>
                        <td>{{ $shift->dibuka_pada?->format('d-m-Y H:i') }}</td>
                        <td>{{ $shift->ditutup_pada?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float) $shift->kas_expected, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($shift->kas_fisik ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($shift->selisih ?? 0), 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $shift->status === 'OPEN' ? 'bg-success' : 'bg-secondary' }}">{{ $shift->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada data shift hari ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @else
        <div class="text-center text-muted py-4">
            <i class="bi bi-lock fs-1 d-block mb-2"></i>
            <p class="mb-0">Anda tidak memiliki izin untuk melihat riwayat shift.<br>Hubungi administrator untuk meminta akses.</p>
        </div>
        @endif
    </div>
</div>
@endsection
