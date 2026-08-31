@extends('layouts.app')

@section('title', 'Faktur Pembelian')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Faktur Pembelian</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('pembelian.faktur.create') }}" class="btn btn-primary">+ Buat Faktur</a>
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
                    <th>No Faktur</th>
                    <th>Tanggal</th>
                    <th>PO</th>
                    <th>Pemasok</th>
                    <th>Total</th>
                    <th>Dibayar</th>
                    <th>Status</th>
                    <th width="170">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fakturList as $index => $faktur)
                    <tr>
                        <td>{{ $fakturList->firstItem() + $index }}</td>
                        <td>{{ $faktur->nomor_faktur }}</td>
                        <td>{{ \Carbon\Carbon::parse($faktur->tanggal_faktur)->format('d-m-Y') }}</td>
                        <td>{{ $faktur->pesananPembelian->nomor_po ?? '-' }}</td>
                        <td>{{ $faktur->pemasok->nama ?? '-' }}</td>
                        <td>Rp {{ number_format((float) $faktur->total, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $faktur->dibayar, 0, ',', '.') }}</td>
                        <td><span class="badge bg-secondary">{{ $faktur->status }}</span></td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('pembelian.faktur.show', $faktur) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            <a href="{{ route('pembelian.faktur.pdf', $faktur) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">Belum ada data faktur pembelian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $fakturList->links() }}
    </div>
</div>
@endsection
