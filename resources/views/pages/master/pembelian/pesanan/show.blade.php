@extends('layouts.app')

@section('title', 'Detail Pesanan Pembelian')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5>Detail PO: {{ $po->nomor_po }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('pembelian.pesanan.pdf', $po) }}" class="btn btn-outline-secondary btn-sm">PDF</a>
        <a href="{{ route('pembelian.pesanan') }}" class="btn btn-outline-primary btn-sm">Kembali</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <div>Pemasok: {{ $po->pemasok->nama ?? '-' }}</div>
    <div>Cabang: {{ $po->cabang->nama ?? '-' }}</div>
    <div>Tanggal PO: {{ \Carbon\Carbon::parse($po->tanggal_po)->format('d-m-Y') }}</div>
    <div>Status: {{ $po->status }}</div>
    <div>Dari Permintaan: {{ $po->permintaanBarang->nomor_permintaan ?? '-' }}</div>
    <div>Outstanding Qty: {{ number_format($outstandingQty, 2) }}</div>
    <div>Catatan: {{ $po->catatan ?: '-' }}</div>
</div></div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th>Catatan</th></tr></thead>
            <tbody>
            @foreach($po->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->produk->kode ?? '-' }} - {{ $item->produk->nama ?? '-' }}</td>
                    <td>{{ (float) $item->qty }}</td>
                    <td>{{ number_format((float) $item->harga, 2) }}</td>
                    <td>{{ number_format((float) $item->subtotal, 2) }}</td>
                    <td>{{ $item->catatan ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
