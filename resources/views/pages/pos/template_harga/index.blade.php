@extends('layouts.app')

@section('title', 'Template Harga')

@section('content')
@php
    $user = auth()->user();
    $canCreateTemplateHarga = $user?->hasPermission('template.harga.create') ?? false;
    $canUpdateTemplateHarga = $user?->hasPermission('template.harga.update') ?? false;
    $canDeleteTemplateHarga = $user?->hasPermission('template.harga.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Template Harga</li>
            </ol>
        </nav>
    </div>
    @if($canCreateTemplateHarga)
        <div class="ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahTemplate">+ Tambah Template</button>
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
                <input type="text" name="nama" class="form-control" placeholder="Cari template..." value="{{ request('nama') }}">
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
                <a href="{{ route('template.harga') }}" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Keterangan</th>
                    <th>Status</th>
                    <th width="260">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templateHarga as $index => $template)
                    <tr>
                        <td>{{ $templateHarga->firstItem() + $index }}</td>
                        <td>{{ $template->kode }}</td>
                        <td>{{ $template->nama }}</td>
                        <td>{{ $template->keterangan ?? '-' }}</td>
                        <td>{!! $template->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('template.harga.detail', $template) }}" class="btn btn-sm btn-outline-primary">Detail Harga</a>
                            @if($canUpdateTemplateHarga)
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditTemplate{{ $template->id }}">Edit</button>
                            @endif
                            @if($canDeleteTemplateHarga)
                                <form method="POST" action="{{ route('template.harga.destroy', $template) }}" data-swal-message="Hapus template ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada template harga.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $templateHarga->links() }}
    </div>
</div>

@if($canCreateTemplateHarga)
    <div class="modal fade" id="modalTambahTemplate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('template.harga.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Template Harga</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Nama Template</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
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

@foreach($templateHarga as $template)
    @if($canUpdateTemplateHarga)
        <div class="modal fade" id="modalEditTemplate{{ $template->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('template.harga.update', $template) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Template Harga</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Template</label>
                            <input type="text" name="nama" class="form-control" value="{{ $template->nama }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3">{{ $template->keterangan }}</textarea>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ $template->status ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ !$template->status ? 'selected' : '' }}>Non Aktif</option>
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
