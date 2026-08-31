@extends('layouts.app')

@section('title', 'Add On')

@section('content')
@php
    $user = auth()->user();
    $canCreateAddon = $user?->hasPermission('paket.addon.create') ?? false;
    $canUpdateAddon = $user?->hasPermission('paket.addon.update') ?? false;
    $canDeleteAddon = $user?->hasPermission('paket.addon.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Add On</li>
            </ol>
        </nav>
    </div>
    @if($canCreateAddon)
        <div class="ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahAddon">+ Tambah Add On</button>
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
                <input type="text" name="nama" class="form-control" placeholder="Cari nama add on..." value="{{ request('nama') }}">
            </div>
            <div class="col-md-3">
                <select name="kategori_addon_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriAddon as $kategori)
                        <option value="{{ $kategori->id }}" {{ (string) request('kategori_addon_id') === (string) $kategori->id ? 'selected' : '' }}>{{ $kategori->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non Aktif</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('paket.addon') }}" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>BOM</th>
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($addonList as $index => $item)
                    <tr>
                        <td>{{ $addonList->firstItem() + $index }}</td>
                        <td>{{ $item->kode }}</td>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->kategoriAddon->nama ?? '-' }}</td>
                        <td>{{ $item->bom_id ?? '-' }}</td>
                        <td>{!! $item->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                        <td class="d-flex gap-1">
                            @if($canUpdateAddon)
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditAddon{{ $item->id }}">Edit</button>
                            @endif
                            @if($canDeleteAddon)
                                <form method="POST" action="{{ route('paket.addon.destroy', $item) }}" data-swal-message="Hapus add on ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            @endif
                            @if(!$canUpdateAddon && !$canDeleteAddon)
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $addonList->links() }}
    </div>
</div>

@if($canCreateAddon)
    <div class="modal fade" id="modalTambahAddon" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('paket.addon.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Add On</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label">Nama Add On</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kategori Add On</label>
                            <select name="kategori_addon_id" class="form-select">
                                <option value="">-- Pilih --</option>
                                @foreach($kategoriAddon as $kategori)
                                    <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">BOM</label>
                            <select name="bom_id" class="form-select">
                                <option value="">-- Pilih --</option>
                                @foreach($bomAddon as $bom)
                                    <option value="{{ $bom->id }}">{{ $bom->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                        </div>
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

@foreach($addonList as $item)
    @if($canUpdateAddon)
        <div class="modal fade" id="modalEditAddon{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('paket.addon.update', $item) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Add On - {{ $item->kode }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label">Nama Add On</label>
                                <input type="text" name="nama" class="form-control" value="{{ $item->nama }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kategori Add On</label>
                                <select name="kategori_addon_id" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach($kategoriAddon as $kategori)
                                        <option value="{{ $kategori->id }}" {{ (int) $item->kategori_addon_id === (int) $kategori->id ? 'selected' : '' }}>{{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">BOM</label>
                                <select name="bom_id" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach($bomAddon as $bom)
                                        <option value="{{ $bom->id }}" {{ (int) $item->bom_id === (int) $bom->id ? 'selected' : '' }}>{{ $bom->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="1" {{ $item->status ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$item->status ? 'selected' : '' }}>Non Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3">{{ $item->deskripsi }}</textarea>
                            </div>
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
