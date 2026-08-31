@extends('layouts.app')

@section('title', 'Laporan Pesanan Pembelian')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Laporan Pesanan Pembelian</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" @selected((int) ($filters['cabang_id'] ?? 0) === (int) $cabang->id)>{{ $cabang->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Pemasok</label>
                <select name="pemasok_id" class="form-select">
                    <option value="">Semua Pemasok</option>
                    @foreach($pemasokList as $pemasok)
                        <option value="{{ $pemasok->id }}" @selected((int) ($filters['pemasok_id'] ?? 0) === (int) $pemasok->id)>{{ $pemasok->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">No PO</label>
                <input type="text" name="nomor" class="form-control" value="{{ $filters['nomor'] }}" placeholder="Cari nomor PO">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Terapkan Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Jumlah Dokumen PO</small><h5 class="mb-0">{{ number_format((float) $summary['jumlah'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Total Nominal PO</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_nominal'], 0, ',', '.') }}</h5></div></div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal PO</th>
                    <th>No PO</th>
                    <th>Cabang</th>
                    <th>Pemasok</th>
                    <th>Status</th>
                    <th class="text-end">Jumlah Item</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->tanggal_po)->format('d-m-Y') }}</td>
                        <td>{{ $row->nomor_po }}</td>
                        <td>{{ $row->cabang->nama ?? '-' }}</td>
                        <td>{{ $row->pemasok->nama ?? '-' }}</td>
                        <td>{{ $row->status }}</td>
                        <td class="text-end">{{ number_format((float) $row->items_count, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($row->total_po ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada data pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $rows->links() }}</div>
    </div>
</div>
@endsection
