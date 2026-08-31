@extends('layouts.app')

@section('title', 'Detail Penerimaan Barang')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5>Detail Penerimaan: {{ $penerimaan->nomor_penerimaan }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('pembelian.penerimaan.pdf', $penerimaan) }}" class="btn btn-outline-secondary btn-sm">PDF</a>
        <a href="{{ route('pembelian.penerimaan') }}" class="btn btn-outline-primary btn-sm">Kembali</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <div>No PO: {{ $penerimaan->pesananPembelian->nomor_po ?? '-' }}</div>
    <div>Pemasok: {{ $penerimaan->pesananPembelian->pemasok->nama ?? '-' }}</div>
    <div>Cabang: {{ $penerimaan->cabang->nama ?? '-' }}</div>
    <div>Tanggal: {{ \Carbon\Carbon::parse($penerimaan->tanggal_penerimaan)->format('d-m-Y') }}</div>
    @if($penerimaan->nomor_surat_jalan)
    <div>No Surat Jalan: {{ $penerimaan->nomor_surat_jalan }}</div>
    @endif
    <div>Status: {{ $penerimaan->status }}</div>
    <div>Catatan: {{ $penerimaan->catatan ?: '-' }}</div>
</div></div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Produk</th><th>Qty PO</th><th>Qty Diterima</th><th>Catatan</th></tr></thead>
            <tbody>
            @foreach($penerimaan->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->produk->kode ?? '-' }} - {{ $item->produk->nama ?? '-' }}</td>
                    <td>{{ (float) ($item->pesananPembelianItem->qty ?? 0) }}</td>
                    <td>{{ (float) $item->qty_terima }}</td>
                    <td>{{ $item->catatan ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
