@extends('layouts.app')

@section('title', 'Detail Retur Pembelian')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('pembelian.retur') }}">Retur Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $retur->nomor_retur }}</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('pembelian.retur.pdf', $retur) }}" class="btn btn-outline-secondary">PDF</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div>No Retur: <strong>{{ $retur->nomor_retur }}</strong></div>
        <div>Tanggal: {{ \Carbon\Carbon::parse($retur->tanggal_retur)->format('d-m-Y') }}</div>
        <div>Status: {{ $retur->status }}</div>
        <div>No Penerimaan: {{ $retur->penerimaanBarang->nomor_penerimaan ?? '-' }}</div>
        <div>No PO: {{ $retur->pesananPembelian->nomor_po ?? '-' }}</div>
        <div>Pemasok: {{ $retur->pemasok->nama ?? '-' }}</div>
        <div>Cabang: {{ $retur->cabang->nama ?? '-' }}</div>
        <div>Catatan: {{ $retur->catatan ?: '-' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th>Qty Diterima</th>
                    <th>Qty Retur</th>
                    <th>Alasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($retur->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->produk->kode ?? '-' }} - {{ $item->produk->nama ?? '-' }}</td>
                        <td>{{ (float) ($item->penerimaanBarangItem->qty_terima ?? 0) }}</td>
                        <td>{{ (float) $item->qty }}</td>
                        <td>{{ $item->alasan_retur ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Tidak ada item retur.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
