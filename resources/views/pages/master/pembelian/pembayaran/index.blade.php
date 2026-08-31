@extends('layouts.app')

@section('title', 'Pembayaran Pembelian')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Pembayaran Pembelian</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
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
        <h6 class="mb-3">Input Pembayaran</h6>
        <form method="POST" action="{{ route('pembelian.pembayaran.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">No Pembayaran</label>
                <input type="text" class="form-control" value="{{ $nomorPembayaran }}" readonly>
            </div>
            <div class="col-md-3">
                <label class="form-label">Faktur</label>
                <select class="form-select" name="faktur_pembelian_id" required>
                    <option value="">Pilih Faktur</option>
                    @foreach($outstandingFaktur as $faktur)
                        <option value="{{ $faktur->id }}">
                            {{ $faktur->nomor_faktur }} - {{ $faktur->pemasok->nama ?? '-' }} (Sisa: {{ number_format((float) ($faktur->total - $faktur->dibayar), 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tanggal</label>
                <input type="date" class="form-control" name="tanggal_bayar" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Metode</label>
                <select class="form-select" name="metode_pembayaran_id">
                    <option value="">-</option>
                    @foreach($metodeList as $metode)
                        <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Nominal</label>
                <input type="number" step="0.01" min="0.01" class="form-control" name="nominal" required>
            </div>
            <div class="col-md-12">
                <label class="form-label">Catatan</label>
                <input type="text" class="form-control" name="catatan">
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>No Pembayaran</th>
                    <th>Tanggal</th>
                    <th>No Faktur</th>
                    <th>Pemasok</th>
                    <th>Metode</th>
                    <th>Nominal</th>
                    <th width="170">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pembayaranList as $index => $item)
                    <tr>
                        <td>{{ $pembayaranList->firstItem() + $index }}</td>
                        <td>{{ $item->nomor_pembayaran }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal_bayar)->format('d-m-Y H:i') }}</td>
                        <td>{{ $item->fakturPembelian->nomor_faktur ?? '-' }}</td>
                        <td>{{ $item->fakturPembelian->pemasok->nama ?? '-' }}</td>
                        <td>{{ $item->metodePembayaran->nama ?? '-' }}</td>
                        <td>Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('pembelian.pembayaran.show', $item) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            <a href="{{ route('pembelian.pembayaran.pdf', $item) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada data pembayaran pembelian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $pembayaranList->links() }}
    </div>
</div>
@endsection
