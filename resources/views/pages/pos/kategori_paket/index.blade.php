@extends('layouts.app')

@section('title', 'Kategori Paket')

@section('content')
@php
    $user = auth()->user();
    $canCreateKategoriPaket = $user?->hasPermission('paket.kategori.create') ?? false;
    $canUpdateKategoriPaket = $user?->hasPermission('paket.kategori.update') ?? false;
    $canDeleteKategoriPaket = $user?->hasPermission('paket.kategori.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Kategori Paket</li>
            </ol>
        </nav>
    </div>
    @if($canCreateKategoriPaket)
        <div class="ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKategoriPaket">+ Tambah Kategori</button>
        </div>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="nama" class="form-control" placeholder="Cari nama kategori..." value="{{ request('nama') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non Aktif</option>
                </select>
            </div>
            <div class="col-md-5">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('paket.kategori') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Kategori</th>
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kategoriPaket as $index => $item)
                    <tr>
                        <td>{{ $kategoriPaket->firstItem() + $index }}</td>
                        <td>{{ $item->nama }}</td>
                        <td>{!! $item->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                        <td class="d-flex gap-1">
                            @if($canUpdateKategoriPaket)
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditKategoriPaket{{ $item->id }}">Edit</button>
                            @endif
                            @if($canDeleteKategoriPaket)
                                <form method="POST" action="{{ route('paket.kategori.destroy', $item) }}" data-swal-message="Hapus kategori ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            @endif
                            @if(!$canUpdateKategoriPaket && !$canDeleteKategoriPaket)
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $kategoriPaket->links() }}
    </div>
</div>

@if($canCreateKategoriPaket)
    <div class="modal fade" id="modalTambahKategoriPaket" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('paket.kategori.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Kategori Paket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@foreach($kategoriPaket as $item)
    @if($canUpdateKategoriPaket)
        <div class="modal fade" id="modalEditKategoriPaket{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('paket.kategori.update', $item) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Kategori Paket</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama" class="form-control" value="{{ $item->nama }}" required>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ $item->status ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ !$item->status ? 'selected' : '' }}>Non Aktif</option>
                            </select>
                        </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-warning">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
