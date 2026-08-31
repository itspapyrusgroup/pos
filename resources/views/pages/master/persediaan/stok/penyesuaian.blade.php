@extends('layouts.app')

@section('title', 'Penyesuaian Stok')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('persediaan.stok') }}">Stok Barang</a></li>
                <li class="breadcrumb-item active" aria-current="page">Penyesuaian Stok</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('persediaan.stok.penyesuaian.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Penyesuaian
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select class="form-select" name="cabang_id">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangList as $cabang)
                        <option value="{{ $cabang->id }}" {{ (string) $selectedCabangId === (string) $cabang->id ? 'selected' : '' }}>
                            {{ $cabang->kode }} - {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" name="tanggal_dari" value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Keyword</label>
                <input type="text" class="form-control" name="keyword" value="{{ request('keyword') }}" placeholder="No penyesuaian / catatan / user">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-12">
                <a href="{{ route('persediaan.stok.penyesuaian') }}" class="btn btn-outline-secondary">Reset</a>
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
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>Catatan</th>
                    <th>Dibuat Oleh</th>
                    <th width="230">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $index => $adjustment)
                    <tr>
                        <td>{{ $riwayat->firstItem() + $index }}</td>
                        <td>#{{ $adjustment->id }}</td>
                        <td>{{ optional($adjustment->tanggal_penyesuaian)->format('d-m-Y') }}</td>
                        <td>{{ $adjustment->cabang?->kode }} - {{ $adjustment->cabang?->nama }}</td>
                        <td>{{ $adjustment->catatan ?: '-' }}</td>
                        <td>{{ $adjustment->dibuatOleh?->name ?? '-' }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('persediaan.stok.penyesuaian.show', $adjustment) }}" class="btn btn-sm btn-outline-secondary">Lihat</a>
                                @if(auth()->user()?->hasPermission('persediaan.stok.penyesuaian.update'))
                                    <a href="{{ route('persediaan.stok.penyesuaian.edit', $adjustment) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @endif
                                <form method="POST" action="{{ route('persediaan.stok.penyesuaian.destroy', $adjustment) }}" data-swal-message="Hapus penyesuaian ini? Saldo stok akan dikembalikan.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada riwayat penyesuaian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $riwayat->links() }}
    </div>
</div>
@endsection
