@extends('layouts.app')

@section('title', 'Detail Faktur Pembelian')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5>Detail Faktur: {{ $faktur->nomor_faktur }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('pembelian.faktur.pdf', $faktur) }}" class="btn btn-outline-secondary btn-sm">PDF</a>
        <a href="{{ route('pembelian.faktur') }}" class="btn btn-outline-primary btn-sm">Kembali</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <div>No PO: {{ $faktur->pesananPembelian->nomor_po ?? '-' }}</div>
    <div>Pemasok: {{ $faktur->pemasok->nama ?? '-' }}</div>
    <div>Cabang: {{ $faktur->cabang->nama ?? '-' }}</div>
    <div>Tanggal Faktur: {{ \Carbon\Carbon::parse($faktur->tanggal_faktur)->format('d-m-Y') }}</div>
    <div>Jatuh Tempo: {{ $faktur->jatuh_tempo ? \Carbon\Carbon::parse($faktur->jatuh_tempo)->format('d-m-Y') : '-' }}</div>
    <div>Status: {{ $faktur->status }}</div>
    <div>Total: Rp {{ number_format((float) $faktur->total, 0, ',', '.') }}</div>
    <div>Dibayar: Rp {{ number_format((float) $faktur->dibayar, 0, ',', '.') }}</div>
    <div>Catatan: {{ $faktur->catatan ?: '-' }}</div>
</div></div>

<div class="card mb-3">
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
            <tbody>
            @foreach($faktur->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->produk->kode ?? '-' }} - {{ $item->produk->nama ?? '-' }}</td>
                    <td>{{ (float) $item->qty }}</td>
                    <td>{{ number_format((float) $item->harga, 2) }}</td>
                    <td>{{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Riwayat Pembayaran</strong></div>
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>Tanggal</th><th>No Pembayaran</th><th>Metode</th><th>Nominal</th></tr></thead>
            <tbody>
            @forelse($faktur->pembayaran as $bayar)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($bayar->tanggal_bayar)->format('d-m-Y H:i') }}</td>
                    <td>{{ $bayar->nomor_pembayaran }}</td>
                    <td>{{ $bayar->metodePembayaran->nama ?? '-' }}</td>
                    <td>{{ number_format((float) $bayar->nominal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Belum ada pembayaran.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
