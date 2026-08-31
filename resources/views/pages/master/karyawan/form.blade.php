@extends('layouts.app')

@section('title', $karyawan->exists ? 'Edit Karyawan' : 'Tambah Karyawan')

@section('content')
@php
    $user = auth()->user();
    $canCreateKaryawan = $user?->hasPermission('konfigurasi.karyawan.create') ?? false;
    $canUpdateKaryawan = $user?->hasPermission('konfigurasi.karyawan.update') ?? false;
    $canSubmit = $karyawan->exists ? $canUpdateKaryawan : $canCreateKaryawan;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Konfigurasi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('konfigurasi.karyawan') }}">Karyawan</a></li>
                <li class="breadcrumb-item active">{{ $karyawan->exists ? 'Edit' : 'Tambah' }}</li>
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

<form method="POST"
      enctype="multipart/form-data"
      action="{{ $karyawan->exists ? route('konfigurasi.karyawan.update', $karyawan) : route('konfigurasi.karyawan.store') }}">
    @csrf
    @if($karyawan->exists)
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="nama" class="form-control" required value="{{ old('nama', $karyawan->nama) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email', $karyawan->user?->email ?? $karyawan->email) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required value="{{ old('username', $karyawan->user?->username) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">No. WA</label>
                    <input type="text" name="no_wa" class="form-control" value="{{ old('no_wa', $karyawan->user?->no_wa ?? $karyawan->no_hp) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password {{ $karyawan->exists ? '(kosongkan jika tidak diubah)' : '' }}</label>
                    <input type="password" name="password" class="form-control" {{ $karyawan->exists ? '' : 'required' }} minlength="8">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="form-control" {{ $karyawan->exists ? '' : 'required' }} minlength="8">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role User</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Pilih Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $karyawan->user?->role_id) == $role->id)>
                                {{ $role->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Divisi</label>
                    <select name="divisi_id" class="form-select">
                        <option value="">-</option>
                        @foreach($divisi as $item)
                            <option value="{{ $item->id }}" @selected(old('divisi_id', $karyawan->divisi_id) == $item->id)>
                                {{ $item->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jabatan</label>
                    <select name="jabatan_id" class="form-select">
                        <option value="">-</option>
                        @foreach($jabatan as $item)
                            <option value="{{ $item->id }}" @selected(old('jabatan_id', $karyawan->jabatan_id) == $item->id)>
                                {{ $item->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cabang (bisa lebih dari satu)</label>
                    <select id="karyawan-cabang-ids" name="cabang_ids[]" class="form-select w-100" data-placeholder="Pilih cabang" multiple required>
                        @php($defaultCabang = $selectedCabang ?: ($karyawan->cabang_id ? [$karyawan->cabang_id] : []))
                        @foreach($cabang as $item)
                            <option value="{{ $item->id }}" @selected(in_array($item->id, old('cabang_ids', $defaultCabang), true))>
                                {{ $item->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Foto Profil</label>
                    <input type="file" name="foto_profil" class="form-control" accept="image/*">
                    @if($karyawan->user?->foto_profil)
                        <small class="text-muted">File saat ini: {{ $karyawan->user->foto_profil }}</small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="1" @selected(old('status', $karyawan->status ?? true))>Aktif</option>
                        <option value="0" @selected(!old('status', $karyawan->status ?? true))>Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        @if($canSubmit)
            <button type="submit" class="btn btn-primary">Simpan</button>
        @endif
        <a href="{{ route('konfigurasi.karyawan') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    const $cabang = $('#karyawan-cabang-ids');
    if (!$cabang.length || typeof $.fn.select2 === 'undefined') {
        return;
    }

    if ($cabang.hasClass('select2-hidden-accessible')) {
        $cabang.select2('destroy');
    }

    $cabang.select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: $cabang.data('placeholder') || 'Pilih cabang',
        closeOnSelect: false
    });
});
</script>
@endpush
