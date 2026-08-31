@extends('layouts.app')

@section('title', 'Detail Penyesuaian Stok')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('persediaan.stok.penyesuaian') }}">Penyesuaian Stok</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail #{{ $penyesuaian->id }}</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto d-flex gap-2">
        @if(auth()->user()?->hasPermission('persediaan.stok.penyesuaian.update'))
            <a href="{{ route('persediaan.stok.penyesuaian.edit', $penyesuaian) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
        @endif
        <a href="{{ route('persediaan.stok.penyesuaian') }}" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">Nomor</div>
                <div class="fw-semibold">#{{ $penyesuaian->id }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Tanggal</div>
                <div class="fw-semibold">{{ optional($penyesuaian->tanggal_penyesuaian)->format('d-m-Y') }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Cabang</div>
                <div class="fw-semibold">{{ $penyesuaian->cabang?->kode }} - {{ $penyesuaian->cabang?->nama }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Dibuat Oleh</div>
                <div class="fw-semibold">{{ $penyesuaian->dibuatOleh?->name ?? '-' }}</div>
            </div>
            <div class="col-12">
                <div class="text-muted small">Catatan</div>
                <div class="fw-semibold">{{ $penyesuaian->catatan ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th class="text-end">Stok Sebelum</th>
                    <th class="text-end">Stok Setelah</th>
                    <th class="text-end">Selisih</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penyesuaian->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->produk?->kode ?? '-' }}</td>
                        <td>{{ $item->produk?->nama ?? '-' }}</td>
                        <td class="text-end">{{ number_format((float) $item->stok_sebelum, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $item->stok_setelah, 2, ',', '.') }}</td>
                        <td class="text-end fw-semibold {{ ((float) $item->qty_selisih) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float) $item->qty_selisih, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Tidak ada item penyesuaian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
