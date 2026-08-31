@extends('layouts.app')

@section('title', $role->exists ? 'Edit Role User' : 'Tambah Role User')

@section('content')
@php
    $user = auth()->user();
    $canCreateRole = $user?->hasPermission('konfigurasi.role_user.create') ?? false;
    $canUpdateRole = $user?->hasPermission('konfigurasi.role_user.update') ?? false;
    $canSubmit = $role->exists ? $canUpdateRole : $canCreateRole;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Konfigurasi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('konfigurasi.role-karyawan') }}">Role User</a></li>
                <li class="breadcrumb-item active">{{ $role->exists ? 'Edit' : 'Tambah' }}</li>
            </ol>
        </nav>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $role->exists ? route('konfigurasi.role-karyawan.update', $role) : route('konfigurasi.role-karyawan.store') }}" method="POST">
    @csrf
    @if($role->exists)
        @method('PUT')
    @endif

    <div class="card mb-3">
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Role</label>
                <input type="text" name="nama" class="form-control" required value="{{ old('nama', $role->nama) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="1" @selected(old('status', $role->status ?? true))>Aktif</option>
                    <option value="0" @selected(!old('status', $role->status ?? true))>Nonaktif</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="2">{{ old('deskripsi', $role->deskripsi) }}</textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Permission</h5>
            <div class="row">
                @foreach($permissionsByModule as $module => $items)
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2">{{ str_replace('_', ' ', strtoupper($module)) }}</h6>
                            @foreach($items as $permission)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                           id="permission_{{ $permission->id }}"
                                           value="{{ $permission->id }}"
                                           @checked(in_array($permission->id, old('permissions', $selectedPermissions), true))>
                                    <label class="form-check-label" for="permission_{{ $permission->id }}">
                                        {{ $permission->label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-3">
        @if($canSubmit)
            <button class="btn btn-primary" type="submit">Simpan</button>
        @endif
        <a href="{{ route('konfigurasi.role-karyawan') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection
