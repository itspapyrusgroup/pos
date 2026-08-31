@extends('layouts.app')

@section('title', 'Permintaan Barang')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Transaksi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Permintaan Barang</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('permintaan-barang.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Permintaan
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">No. Permintaan</label>
                <input type="text" class="form-control" name="nomor_permintaan" value="{{ request('nomor_permintaan') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select class="form-select" name="cabang_id">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangList as $cabang)
                        <option value="{{ $cabang->id }}" {{ (string) request('cabang_id') === (string) $cabang->id ? 'selected' : '' }}>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="PROCESSED" {{ request('status') === 'PROCESSED' ? 'selected' : '' }}>Processed</option>
                    <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" class="form-control" name="tanggal" value="{{ request('tanggal') }}">
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('permintaan-barang.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>No Permintaan</th>
                    <th>Tanggal</th>
                    <th>Tanggal Butuh</th>
                    <th>Cabang</th>
                    <th>Status</th>
                    <th width="280">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permintaanBarang as $index => $item)
                    <tr>
                        <td>{{ $permintaanBarang->firstItem() + $index }}</td>
                        <td>{{ $item->nomor_permintaan }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal_permintaan)->format('d-m-Y') }}</td>
                        <td>{{ $item->tanggal_dibutuhkan ? \Carbon\Carbon::parse($item->tanggal_dibutuhkan)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $item->cabang->nama ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $item->status }}</span></td>
                        <td class="d-flex gap-1 align-items-center">
                            <a href="{{ route('permintaan-barang.show', $item) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            <a href="{{ route('permintaan-barang.pdf', $item) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                            @if($item->pesanan_pembelian_count === 0 && $item->status !== 'PROCESSED')
                                <a href="{{ route('permintaan-barang.edit', $item) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                <form method="POST" action="{{ route('permintaan-barang.destroy', $item) }}" data-swal-message="Hapus permintaan ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            @else
                                <span class="badge bg-light text-dark">Terkunci (sudah diproses)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data permintaan barang.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $permintaanBarang->links() }}
    </div>
</div>
@endsection
