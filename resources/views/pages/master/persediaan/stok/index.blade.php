@extends('layouts.app')

@section('title', 'Stok Barang')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Stok Barang</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('persediaan.stok.penyesuaian') }}" class="btn btn-outline-primary">
            <i class="bi bi-sliders"></i> Penyesuaian Stok
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select class="form-select" name="cabang_id" required>
                    @foreach($cabangList as $cabang)
                        <option value="{{ $cabang->id }}" {{ (string) $selectedCabangId === (string) $cabang->id ? 'selected' : '' }}>
                            {{ $cabang->kode }} - {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Per Tanggal</label>
                <input type="date" class="form-control" name="tanggal" value="{{ $selectedTanggal }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" class="form-control" name="nama_barang" value="{{ request('nama_barang') }}" placeholder="Cari nama barang...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Golongan</label>
                <select class="form-select" name="golongan_id">
                    <option value="">Semua Golongan</option>
                    @foreach($golonganList as $golongan)
                        <option value="{{ $golongan->kode }}" {{ (string) request('golongan_id') === (string) $golongan->kode ? 'selected' : '' }}>
                            {{ $golongan->kode ? $golongan->kode . ' - ' : '' }}{{ $golongan->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-12">
                <a href="{{ route('persediaan.stok') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Golongan</th>
                    <th class="text-end">On Hand</th>
                    <th class="text-end">On Order</th>
                    <th class="text-end">Tersedia</th>
                    <th class="text-end">Stok per {{ \Carbon\Carbon::parse($selectedTanggal)->format('d-m-Y') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stokProduk as $index => $item)
                    <tr>
                        <td>{{ $stokProduk->firstItem() + $index }}</td>
                        <td><span class="badge bg-light text-dark">{{ $item->kode }}</span></td>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->kategoriProduk->nama ?? '-' }}</td>
                        <td class="text-end">{{ number_format((float) $item->stok_on_hand, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $item->stok_on_order, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $item->stok_tersedia, 2, ',', '.') }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $item->stok_per_tanggal, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Data stok tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $stokProduk->links() }}
    </div>
</div>
@endsection
