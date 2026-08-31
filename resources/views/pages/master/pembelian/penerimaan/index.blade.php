@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Penerimaan Barang</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('pembelian.penerimaan.create') }}" class="btn btn-primary">+ Buat Penerimaan</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>No Penerimaan</th>
                    <th>Tanggal</th>
                    <th>No PO</th>
                    <th>Pemasok</th>
                    <th>Status</th>
                    <th width="170">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penerimaanList as $index => $item)
                    <tr>
                        <td>{{ $penerimaanList->firstItem() + $index }}</td>
                        <td>{{ $item->nomor_penerimaan }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal_penerimaan)->format('d-m-Y') }}</td>
                        <td>{{ $item->pesananPembelian->nomor_po ?? '-' }}</td>
                        <td>{{ $item->pesananPembelian->pemasok->nama ?? '-' }}</td>
                        <td><span class="badge {{ $item->status === 'POSTED' ? 'bg-success' : 'bg-secondary' }}">{{ $item->status }}</span></td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('pembelian.penerimaan.show', $item) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            <a href="{{ route('pembelian.penerimaan.pdf', $item) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data penerimaan barang.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $penerimaanList->links() }}
    </div>
</div>
@endsection
