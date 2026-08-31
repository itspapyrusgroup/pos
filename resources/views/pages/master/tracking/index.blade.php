@extends('layouts.app')

@section('title', 'Master Tracking')

@section('content')
@php
    $user = auth()->user();
    $canCreateTracking = $user?->hasPermission('konfigurasi.tracking.create') ?? false;
    $canUpdateTracking = $user?->hasPermission('konfigurasi.tracking.update') ?? false;
    $canDeleteTracking = $user?->hasPermission('konfigurasi.tracking.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Tracking</li>
            </ol>
        </nav>
    </div>
    @if($canCreateTracking)
        <div class="ms-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Tambah Tracking
            </button>
        </div>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-3" method="GET">
            <div class="col-md-4">
                <label class="form-label">Cari</label>
                <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Kode / Nama tracking">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipe</label>
                <select class="form-select" name="tipe">
                    <option value="">Semua</option>
                    <option value="ITEM" @selected(request('tipe') === 'ITEM')>ITEM</option>
                    <option value="KO" @selected(request('tipe') === 'KO')>KO</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('status') === '1')>Aktif</option>
                    <option value="0" @selected(request('status') === '0')>Non Aktif</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a href="{{ route('konfigurasi.tracking') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tracking as $index => $item)
                    <tr>
                        <td>{{ $tracking->firstItem() + $index }}</td>
                        <td><span class="badge bg-light text-dark">{{ $item->kode }}</span></td>
                        <td>{{ $item->nama }}</td>
                        <td><span class="badge {{ $item->tipe === 'KO' ? 'bg-warning text-dark' : 'bg-primary' }}">{{ $item->tipe }}</span></td>
                        <td>{{ (int) $item->urutan }}</td>
                        <td><span class="badge {{ $item->status ? 'bg-success' : 'bg-secondary' }}">{{ $item->status ? 'Aktif' : 'Non Aktif' }}</span></td>
                        <td class="text-end">
                            @if($canUpdateTracking)
                                <button type="button" class="btn btn-sm btn-outline-warning edit-btn"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        data-id="{{ $item->id }}"
                                        data-kode="{{ $item->kode }}"
                                        data-nama="{{ $item->nama }}"
                                        data-tipe="{{ $item->tipe }}"
                                        data-urutan="{{ (int) $item->urutan }}"
                                        data-status="{{ $item->status ? 1 : 0 }}">
                                    Edit
                                </button>
                            @endif
                            @if($canDeleteTracking)
                                <form method="POST" action="{{ route('konfigurasi.tracking.destroy', $item) }}" class="d-inline" data-swal-message="Hapus tracking ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            @endif
                            @if(!$canUpdateTracking && !$canDeleteTracking)
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data tracking.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $tracking->links() }}
    </div>
</div>

@if($canCreateTracking)
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('konfigurasi.tracking.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Tracking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Kode</label>
                            <input type="text" name="kode" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tipe</label>
                            <select name="tipe" class="form-select" required>
                                <option value="ITEM">ITEM</option>
                                <option value="KO">KO</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Urutan</label>
                            <input type="number" min="0" name="urutan" class="form-control" value="0">
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

@if($canUpdateTracking)
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="editForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Tracking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Kode</label>
                            <input type="text" name="kode" id="edit_kode" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tipe</label>
                            <select name="tipe" id="edit_tipe" class="form-select" required>
                                <option value="ITEM">ITEM</option>
                                <option value="KO">KO</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Urutan</label>
                            <input type="number" min="0" name="urutan" id="edit_urutan" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editForm = document.getElementById('editForm');
        const editKode = document.getElementById('edit_kode');
        const editNama = document.getElementById('edit_nama');
        const editTipe = document.getElementById('edit_tipe');
        const editUrutan = document.getElementById('edit_urutan');
        const editStatus = document.getElementById('edit_status');

        document.querySelectorAll('.edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = btn.getAttribute('data-id');
                editForm.setAttribute('action', `{{ url('konfigurasi/tracking') }}/${id}`);
                editKode.value = btn.getAttribute('data-kode') || '';
                editNama.value = btn.getAttribute('data-nama') || '';
                editTipe.value = btn.getAttribute('data-tipe') || 'ITEM';
                editUrutan.value = btn.getAttribute('data-urutan') || '0';
                editStatus.value = btn.getAttribute('data-status') === '1' ? '1' : '0';
            });
        });
    });
</script>
@endpush
