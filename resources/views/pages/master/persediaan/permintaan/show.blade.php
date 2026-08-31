@extends('layouts.app')

@section('title', 'Detail Permintaan Barang')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5>Detail Permintaan: {{ $permintaan->nomor_permintaan }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('permintaan-barang.pdf', $permintaan) }}" class="btn btn-outline-secondary btn-sm">PDF</a>
        <a href="{{ route('permintaan-barang.index') }}" class="btn btn-outline-primary btn-sm">Kembali</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <div>Cabang: {{ $permintaan->cabang->nama ?? '-' }}</div>
    <div>Tanggal: {{ \Carbon\Carbon::parse($permintaan->tanggal_permintaan)->format('d-m-Y') }}</div>
    <div>Tanggal Dibutuhkan: {{ $permintaan->tanggal_dibutuhkan ? \Carbon\Carbon::parse($permintaan->tanggal_dibutuhkan)->format('d-m-Y') : '-' }}</div>
    <div>Status: {{ $permintaan->status }}</div>
    <div>Catatan: {{ $permintaan->catatan ?: '-' }}</div>
</div></div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Produk</th><th>Qty</th><th>Catatan</th></tr></thead>
            <tbody>
            @foreach($permintaan->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->produk->kode ?? '-' }} - {{ $item->produk->nama ?? '-' }}</td>
                    <td>{{ (float) $item->qty }}</td>
                    <td>{{ $item->catatan ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
