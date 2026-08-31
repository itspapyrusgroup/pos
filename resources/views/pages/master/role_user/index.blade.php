@extends('layouts.app')

@section('title', 'Role User')

@section('content')
@php
    $user = auth()->user();
    $canCreateRole = $user?->hasPermission('konfigurasi.role_user.create') ?? false;
    $canUpdateRole = $user?->hasPermission('konfigurasi.role_user.update') ?? false;
    $canDeleteRole = $user?->hasPermission('konfigurasi.role_user.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Konfigurasi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Role User</li>
            </ol>
        </nav>
    </div>
    @if($canCreateRole)
        <div class="ms-auto">
            <a href="{{ route('konfigurasi.role-karyawan.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Role
            </a>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama Role</th>
                        <th>Status</th>
                        <th>Jumlah User</th>
                        <th>Permission</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->nama }}</strong>
                                @if($item->deskripsi)
                                    <div class="text-muted small">{{ $item->deskripsi }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $item->status ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->status ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>{{ $item->users_count }}</td>
                            <td>{{ $item->permissions->count() }} permission</td>
                            <td class="text-end">
                                @if($canUpdateRole)
                                    <a href="{{ route('konfigurasi.role-karyawan.edit', $item) }}" class="btn btn-sm btn-outline-warning">
                                        Edit
                                    </a>
                                @endif
                                @if($canDeleteRole)
                                    <form action="{{ route('konfigurasi.role-karyawan.destroy', $item) }}" method="POST" class="d-inline" data-swal-message="Yakin hapus role ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endif
                                @if(!$canUpdateRole && !$canDeleteRole)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada data role.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
