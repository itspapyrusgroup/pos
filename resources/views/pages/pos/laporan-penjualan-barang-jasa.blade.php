@extends('layouts.app')

@section('title', 'Laporan Penjualan Barang/Jasa')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Laporan Penjualan Barang/Jasa (Non Paket)</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="barang_jasa_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse(request('date_from') ?? now()->toDateString())->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse(request('date_to') ?? request('date_from') ?? now()->toDateString())->format('d-m-Y') }}" data-date-range-picker data-date-from="#barang_jasa_date_from" data-date-to="#barang_jasa_date_to" readonly>
                <input type="hidden" id="barang_jasa_date_from" name="date_from" value="{{ request('date_from') ?? now()->toDateString() }}">
                <input type="hidden" id="barang_jasa_date_to" name="date_to" value="{{ request('date_to') ?? request('date_from') ?? now()->toDateString() }}">
            </div>
            <div class="col-md-2">
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
            <div class="col-md-2">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ request('no_ko') }}" placeholder="KO-...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Produk</label>
                <input type="text" name="produk" class="form-control" value="{{ request('produk') }}" placeholder="Nama/Kode">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
            <div class="col-12">
                <a href="{{ route('laporan-penjualan-barang-jasa') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Jumlah Item Non Paket</small>
                <h5 class="mb-0">{{ number_format((float) $summary['count'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Total Qty</small>
                <h5 class="mb-0">{{ number_format((float) $summary['qty'], 2, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Subtotal Penjualan</small>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['subtotal'], 0, ',', '.') }}</h5>
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
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>No SO</th>
                    <th>No KO</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Harga</th>
                    <th class="text-end">Diskon</th>
                    <th class="text-end">Subtotal</th>
                    <th>Customer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    <tr>
                        <td>{{ $rows->firstItem() + $index }}</td>
                        <td>{{ $row->pesananPenjualan?->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td>{{ $row->pesananPenjualan?->cabang?->nama ?? '-' }}</td>
                        <td>{{ $row->pesananPenjualan?->nomor_so ?? '-' }}</td>
                        <td>{{ $row->pesananPenjualan?->kantongOrder?->nomor_ko ?? '-' }}</td>
                        <td>{{ $row->produk?->kode ?? '-' }}</td>
                        <td>{{ $row->produk?->nama ?? '-' }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty, 2, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row->harga, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row->diskon, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row->subtotal, 0, ',', '.') }}</td>
                        <td>{{ $row->pesananPenjualan?->pelanggan?->nama ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted">Data penjualan barang/jasa non paket tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div>
</div>
@endsection
