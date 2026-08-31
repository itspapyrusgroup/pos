@extends('layouts.app')

@section('title', 'Detail Pembayaran Pembelian')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5>Detail Pembayaran: {{ $pembayaran->nomor_pembayaran }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('pembelian.pembayaran.pdf', $pembayaran) }}" class="btn btn-outline-secondary btn-sm">PDF</a>
        <a href="{{ route('pembelian.pembayaran') }}" class="btn btn-outline-primary btn-sm">Kembali</a>
    </div>
</div>

<div class="card"><div class="card-body">
    <div>No Pembayaran: {{ $pembayaran->nomor_pembayaran }}</div>
    <div>Tanggal Bayar: {{ \Carbon\Carbon::parse($pembayaran->tanggal_bayar)->format('d-m-Y H:i') }}</div>
    <div>Faktur: {{ $pembayaran->fakturPembelian->nomor_faktur ?? '-' }}</div>
    <div>Pemasok: {{ $pembayaran->fakturPembelian->pemasok->nama ?? '-' }}</div>
    <div>Cabang: {{ $pembayaran->fakturPembelian->cabang->nama ?? '-' }}</div>
    <div>Metode: {{ $pembayaran->metodePembayaran->nama ?? '-' }}</div>
    <div>Nominal: Rp {{ number_format((float) $pembayaran->nominal, 0, ',', '.') }}</div>
    <div>Catatan: {{ $pembayaran->catatan ?: '-' }}</div>
</div></div>
@endsection
