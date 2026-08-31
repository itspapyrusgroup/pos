@extends('layouts.app')

@section('title', 'Laporan Booking')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Laporan Booking</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="booking_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse(request('date_from') ?? now()->toDateString())->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse(request('date_to') ?? request('date_from') ?? now()->toDateString())->format('d-m-Y') }}" data-date-range-picker data-date-from="#booking_date_from" data-date-to="#booking_date_to" readonly>
                <input type="hidden" id="booking_date_from" name="date_from" value="{{ request('date_from') ?? now()->toDateString() }}">
                <input type="hidden" id="booking_date_to" name="date_to" value="{{ request('date_to') ?? request('date_from') ?? now()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" {{ (string) request('cabang_id') === (string) $cabang->id ? 'selected' : '' }}>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ request('no_ko') }}" placeholder="KO-...">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
            <div class="col-12">
                <a href="{{ route('laporan-booking') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Total Booking</small>
                <h5 class="mb-0">{{ number_format((float) $summary['count'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>No KO</th>
                    <th>Nama</th>
                    <th>Paket / Produk</th>
                    <th>Tanggal Booking</th>
                    <th>Jam Booking</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    @php
                        $items = $row->pesananPenjualan?->items ?? collect();
                        $namaItems = $items
                            ->map(fn ($item) => $item->produk?->nama ?? $item->paket?->nama)
                            ->filter()
                            ->unique()
                            ->values();
                    @endphp
                    <tr>
                        <td>{{ $rows->firstItem() + $index }}</td>
                        <td>{{ $row->pesananPenjualan?->kantongOrder?->nomor_ko ?? '-' }}</td>
                        <td>{{ $row->pelanggan?->nama ?? $row->pesananPenjualan?->pelanggan?->nama ?? '-' }}</td>
                        <td>{{ $namaItems->isNotEmpty() ? $namaItems->implode(', ') : '-' }}</td>
                        <td>{{ $row->tanggal_booking?->format('d-m-Y') ?? '-' }}</td>
                        <td>{{ $row->tanggal_booking?->format('H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Data booking tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
