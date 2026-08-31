@extends('layouts.app')

@section('title', 'Karyawan')

@section('content')
@php
    $user = auth()->user();
    $canCreateKaryawan = $user?->hasPermission('konfigurasi.karyawan.create') ?? false;
    $canUpdateKaryawan = $user?->hasPermission('konfigurasi.karyawan.update') ?? false;
    $canDeleteKaryawan = $user?->hasPermission('konfigurasi.karyawan.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Konfigurasi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Karyawan</li>
            </ol>
        </nav>
    </div>
    @if($canCreateKaryawan)
        <div class="ms-auto">
            <a href="{{ route('konfigurasi.karyawan.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Karyawan
            </a>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-body">
        {{-- Filter & Search Form --}}
        <form method="GET" action="{{ route('konfigurasi.karyawan') }}" class="mb-4">
            <div class="row g-3 align-items-end">
                {{-- Search --}}
                <div class="col-12 col-md-3">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" name="search" id="search" class="form-control"
                           placeholder="Nama, Email, Username..." value="{{ $filters['search'] }}">
                </div>

                {{-- Filter Role --}}
                <div class="col-6 col-md-2">
                    <label for="role_id" class="form-label">Role</label>
                    <select name="role_id" id="role_id" class="form-select">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $filters['role_id'] == $role->id ? 'selected' : '' }}>
                                {{ $role->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Divisi --}}
                <div class="col-6 col-md-2">
                    <label for="divisi_id" class="form-label">Divisi</label>
                    <select name="divisi_id" id="divisi_id" class="form-select">
                        <option value="">Semua Divisi</option>
                        @foreach($divisiList as $divisi)
                            <option value="{{ $divisi->id }}" {{ $filters['divisi_id'] == $divisi->id ? 'selected' : '' }}>
                                {{ $divisi->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Jabatan --}}
                <div class="col-6 col-md-2">
                    <label for="jabatan_id" class="form-label">Jabatan</label>
                    <select name="jabatan_id" id="jabatan_id" class="form-select">
                        <option value="">Semua Jabatan</option>
                        @foreach($jabatanList as $jabatan)
                            <option value="{{ $jabatan->id }}" {{ $filters['jabatan_id'] == $jabatan->id ? 'selected' : '' }}>
                                {{ $jabatan->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Cabang --}}
                <div class="col-6 col-md-2">
                    <label for="cabang_id" class="form-label">Cabang</label>
                    <select name="cabang_id" id="cabang_id" class="form-select">
                        <option value="">Semua Cabang</option>
                        @foreach($cabangList as $cabang)
                            <option value="{{ $cabang->id }}" {{ $filters['cabang_id'] == $cabang->id ? 'selected' : '' }}>
                                {{ $cabang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Status --}}
                <div class="col-6 col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="1" {{ $filters['status'] === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $filters['status'] === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                {{-- Filter Aksi --}}
                <div class="col-12 col-md-3">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary grow">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('konfigurasi.karyawan') }}" class="btn btn-outline-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Divisi</th>
                        <th>Jabatan</th>
                        <th>Cabang</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($karyawan as $item)
                        <tr>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->user?->username }}</td>
                            <td>{{ $item->user?->role?->nama ?? '-' }}</td>
                            <td>{{ $item->divisi?->nama ?? '-' }}</td>
                            <td>{{ $item->jabatan?->nama ?? '-' }}</td>
                            <td>
                                {{ $item->user?->cabang?->pluck('nama')->implode(', ') ?: '-' }}
                            </td>
                            <td>
                                <span class="badge {{ $item->status ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->status ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($canUpdateKaryawan)
                                    <a href="{{ route('konfigurasi.karyawan.edit', $item) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                @endif
                                @if($canDeleteKaryawan)
                                    <form action="{{ route('konfigurasi.karyawan.destroy', $item) }}" method="POST" class="d-inline" data-swal-message="Yakin hapus data karyawan ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endif
                                @if(!$canUpdateKaryawan && !$canDeleteKaryawan)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">Belum ada data karyawan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $karyawan->links() }}
        </div>
    </div>
</div>
@endsection
