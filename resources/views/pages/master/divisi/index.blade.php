@extends('layouts.app')

@section('title', 'Divisi')

@section('content')
@php
    $user = auth()->user();
    $canCreateDivisi = $user?->hasPermission('konfigurasi.divisi.create') ?? false;
    $canUpdateDivisi = $user?->hasPermission('konfigurasi.divisi.update') ?? false;
    $canDeleteDivisi = $user?->hasPermission('konfigurasi.divisi.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Divisi</li>
            </ol>
        </nav>
    </div>
    @if($canCreateDivisi)
        <div class="ms-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Tambah Divisi
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
        <div class="d-flex align-items-center">
            <h5 class="mb-0">Daftar Divisi</h5>
            <form class="ms-auto position-relative" method="GET" action="{{ route('konfigurasi.divisi') }}">
                <div class="position-absolute top-50 translate-middle-y search-icon px-3"><i class="bi bi-search"></i></div>
                <input class="form-control ps-5" name="q" value="{{ request('q') }}" type="text" placeholder="Cari nama divisi...">
            </form>
        </div>

        <div class="row mt-3 mb-3">
            <div class="col-md-12">
                <form class="row g-3" method="GET" action="{{ route('konfigurasi.divisi') }}">
                    <div class="col-md-6">
                        <label for="nama_divisi" class="form-label">Nama Divisi</label>
                        <input type="text" class="form-control" id="nama_divisi" name="nama" value="{{ request('nama') }}" placeholder="Nama divisi...">
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="1" @selected(request('status') === '1')>Aktif</option>
                            <option value="0" @selected(request('status') === '0')>Non Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filter</button>
                        <a href="{{ route('konfigurasi.divisi') }}" class="btn btn-outline-secondary ms-2"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Nama Divisi</th>
                        <th>Status</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($divisi as $index => $item)
                        <tr>
                            <td>{{ $divisi->firstItem() + $index }}</td>
                            <td>{{ $item->nama }}</td>
                            <td>
                                <span class="badge {{ $item->status ? 'bg-success' : 'bg-danger' }}">
                                    {{ $item->status ? 'Aktif' : 'Non Aktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @if($canUpdateDivisi)
                                        <button type="button" class="btn btn-sm btn-outline-warning edit-btn"
                                                data-bs-toggle="modal" data-bs-target="#editModal"
                                                data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-status="{{ $item->status ? 1 : 0 }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                    @endif
                                    @if($canDeleteDivisi)
                                        <form action="{{ route('konfigurasi.divisi.destroy', $item) }}" method="POST" data-swal-message="Yakin hapus divisi ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(!$canUpdateDivisi && !$canDeleteDivisi)
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Belum ada data divisi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $divisi->links() }}
        </div>
    </div>
</div>

@if($canCreateDivisi)
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('konfigurasi.divisi.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Nama Divisi</label>
                                <input type="text" class="form-control" name="nama" placeholder="Contoh: CETAK" required>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="add_status" name="status" value="1" checked>
                                    <label class="form-check-label" for="add_status">Aktif</label>
                                </div>
                            </div>
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

@if($canUpdateDivisi)
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Divisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Nama Divisi</label>
                                <input type="text" class="form-control" id="edit_nama" name="nama" required>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_status" name="status" value="1">
                                    <label class="form-check-label" for="edit_status">Aktif</label>
                                </div>
                            </div>
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
    const editNama = document.getElementById('edit_nama');
    const editStatus = document.getElementById('edit_status');

    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.getAttribute('data-id');
            editForm.setAttribute('action', `{{ url('konfigurasi/divisi') }}/${id}`);
            editNama.value = btn.getAttribute('data-nama') || '';
            editStatus.checked = btn.getAttribute('data-status') === '1';
        });
    });
});
</script>
@endpush
