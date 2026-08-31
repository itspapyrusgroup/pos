@extends('layouts.app')

@section('title', 'Jabatan')

@section('content')
@php
    $user = auth()->user();
    $canCreateJabatan = $user?->hasPermission('konfigurasi.jabatan.create') ?? false;
    $canUpdateJabatan = $user?->hasPermission('konfigurasi.jabatan.update') ?? false;
    $canDeleteJabatan = $user?->hasPermission('konfigurasi.jabatan.delete') ?? false;
    $levelOptions = !empty($levelOptions ?? null)
        ? $levelOptions
        : ['STAFF', 'SPV', 'MANAGER', 'GM', 'DIREKTUR', 'KOMISARIS'];
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Jabatan</li>
            </ol>
        </nav>
    </div>
    @if($canCreateJabatan)
        <div class="ms-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Tambah Jabatan
            </button>
        </div>
    @endif
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

<div class="card">
    <div class="card-body">
        <form class="row g-3 mb-3" method="GET" action="{{ route('konfigurasi.jabatan') }}">
            <div class="col-md-4">
                <label class="form-label">Cari</label>
                <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Kode / Nama jabatan">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="1" @selected(request('status') === '1')>Aktif</option>
                    <option value="0" @selected(request('status') === '0')>Non Aktif</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('konfigurasi.jabatan') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jabatan as $index => $item)
                        <tr>
                            <td>{{ $jabatan->firstItem() + $index }}</td>
                            <td>{{ $item->kode }}</td>
                            <td>{{ $item->nama }}</td>
                            <td><span class="badge bg-info text-dark">{{ $item->level ?? 'STAFF' }}</span></td>
                            <td>
                                <span class="badge {{ $item->status ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->status ? 'Aktif' : 'Non Aktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($canUpdateJabatan)
                                    <a href="{{ route('konfigurasi.jabatan.tracking-ko', $item) }}" class="btn btn-sm btn-outline-primary" title="Atur Hak Akses Tracking">
                                        <i class="bi bi-list-check"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-warning edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                            data-id="{{ $item->id }}"
                                            data-kode="{{ $item->kode }}"
                                            data-nama="{{ $item->nama }}"
                                            data-level="{{ $item->level ?? 'STAFF' }}"
                                            data-status="{{ $item->status ? 1 : 0 }}">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                @endif
                                @if($canDeleteJabatan)
                                    <form method="POST" action="{{ route('konfigurasi.jabatan.destroy', $item) }}" class="d-inline" data-swal-message="Yakin hapus jabatan ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endif
                                @if(!$canUpdateJabatan && !$canDeleteJabatan)
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada data jabatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $jabatan->links() }}
        </div>
    </div>
</div>

@if($canCreateJabatan)
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jabatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('konfigurasi.jabatan.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kode</label>
                            <input type="text" class="form-control" name="kode" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Level Jabatan</label>
                            <select class="form-select" name="level" required>
                                @foreach($levelOptions as $level)
                                    <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="add_status" value="1" checked>
                            <label class="form-check-label" for="add_status">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($canUpdateJabatan)
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Jabatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kode</label>
                            <input type="text" class="form-control" name="kode" id="edit_kode" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="nama" id="edit_nama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Level Jabatan</label>
                            <select class="form-select" name="level" id="edit_level" required>
                                @foreach($levelOptions as $level)
                                    <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="edit_status" value="1">
                            <label class="form-check-label" for="edit_status">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editForm = document.getElementById('editForm');
        const editKode = document.getElementById('edit_kode');
        const editNama = document.getElementById('edit_nama');
        const editLevel = document.getElementById('edit_level');
        const editStatus = document.getElementById('edit_status');

        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = btn.getAttribute('data-id');
                editForm.setAttribute('action', `{{ url('konfigurasi/jabatan') }}/${id}`);
                editKode.value = btn.getAttribute('data-kode') || '';
                editNama.value = btn.getAttribute('data-nama') || '';
                if (editLevel) {
                    editLevel.value = btn.getAttribute('data-level') || 'STAFF';
                }
                editStatus.checked = btn.getAttribute('data-status') === '1';
            });
        });
    });
</script>
@endpush
