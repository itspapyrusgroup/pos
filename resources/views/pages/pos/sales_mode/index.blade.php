@extends('layouts.app')

@section('title', 'Sales Mode')

@section('content')
@php
    $user = auth()->user();
    $canCreateSalesMode = $user?->hasPermission('sales_mode.create') ?? false;
    $canUpdateSalesMode = $user?->hasPermission('sales_mode.update') ?? false;
    $canDeleteSalesMode = $user?->hasPermission('sales_mode.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Sales Mode</li>
            </ol>
        </nav>
    </div>
    @if($canCreateSalesMode)
        <div class="ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSalesMode">+ Tambah Sales Mode</button>
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
                <input type="text" name="nama" class="form-control" placeholder="Cari sales mode..." value="{{ request('nama') }}">
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
                <a href="{{ route('sales-mode') }}" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesMode as $index => $mode)
                    <tr>
                        <td>{{ $salesMode->firstItem() + $index }}</td>
                        <td>{{ $mode->kode }}</td>
                        <td>{{ $mode->nama }}</td>
                        <td>{!! $mode->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                        <td class="d-flex gap-1">
                            @if($canUpdateSalesMode)
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditSalesMode{{ $mode->id }}">Edit</button>
                            @endif
                            @if($canDeleteSalesMode)
                                <form method="POST" action="{{ route('sales-mode.destroy', $mode) }}" data-swal-message="Hapus sales mode ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            @endif
                            @if(!$canUpdateSalesMode && !$canDeleteSalesMode)
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $salesMode->links() }}
    </div>
</div>

@if($canCreateSalesMode)
    <div class="modal fade" id="modalTambahSalesMode" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('sales-mode.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Sales Mode</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Nama Sales Mode</label>
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

@foreach($salesMode as $mode)
    @if($canUpdateSalesMode)
        <div class="modal fade" id="modalEditSalesMode{{ $mode->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('sales-mode.update', $mode) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Sales Mode</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Sales Mode</label>
                            <input type="text" name="nama" class="form-control" value="{{ $mode->nama }}" required>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ $mode->status ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ !$mode->status ? 'selected' : '' }}>Non Aktif</option>
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
