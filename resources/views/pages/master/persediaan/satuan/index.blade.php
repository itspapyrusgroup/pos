@extends('layouts.app')

@section('title', 'Satuan Barang/Jasa')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Satuan</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSatuan">
            <i class="bi bi-plus-circle"></i> Tambah Satuan
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
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
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nama Satuan</label>
                <input type="text" name="nama" class="form-control" value="{{ request('nama') }}" placeholder="Cari nama satuan...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kode</label>
                <input type="text" name="kode" class="form-control" value="{{ request('kode') }}" placeholder="Cari kode...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non Aktif</option>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('persediaan.satuan') }}" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Nama Satuan</th>
                    <th>Keterangan</th>
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($satuan as $index => $item)
                    <tr>
                        <td>{{ $satuan->firstItem() + $index }}</td>
                        <td><span class="badge bg-light text-dark">{{ $item->kode }}</span></td>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->keterangan ?: '-' }}</td>
                        <td>{!! $item->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                        <td class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditSatuan{{ $item->id }}">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('persediaan.satuan.destroy', $item) }}" data-swal-message="Hapus satuan ini?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada data satuan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $satuan->links() }}
    </div>
</div>

<div class="modal fade" id="modalTambahSatuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('persediaan.satuan.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Satuan</h5>
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
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
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

@foreach($satuan as $item)
    <div class="modal fade" id="modalEditSatuan{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('persediaan.satuan.update', $item) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Satuan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Kode</label>
                            <input type="text" name="kode" class="form-control" value="{{ $item->kode }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" class="form-control" value="{{ $item->nama }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2">{{ $item->keterangan }}</textarea>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ $item->status ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ !$item->status ? 'selected' : '' }}>Non Aktif</option>
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
@endforeach
@endsection
