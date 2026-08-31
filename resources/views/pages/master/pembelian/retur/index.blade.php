@extends('layouts.app')

@section('title', 'Retur Pembelian')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Retur Pembelian</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('pembelian.retur.create') }}" class="btn btn-primary">+ Buat Retur</a>
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
                    <th>No Retur</th>
                    <th>Tanggal</th>
                    <th>No Penerimaan</th>
                    <th>No PO</th>
                    <th>Pemasok</th>
                    <th>Status</th>
                    <th width="170">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returList as $index => $item)
                    <tr>
                        <td>{{ $returList->firstItem() + $index }}</td>
                        <td>{{ $item->nomor_retur }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal_retur)->format('d-m-Y') }}</td>
                        <td>{{ $item->penerimaanBarang->nomor_penerimaan ?? '-' }}</td>
                        <td>{{ $item->pesananPembelian->nomor_po ?? '-' }}</td>
                        <td>{{ $item->pemasok->nama ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $item->status }}</span></td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('pembelian.retur.show', $item) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            <a href="{{ route('pembelian.retur.pdf', $item) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada data retur pembelian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $returList->links() }}
    </div>
</div>
@endsection
