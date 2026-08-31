@extends('layouts.app')

@section('title', 'Daftar Pemasok')

@section('content')

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Pemasok</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('pemasok.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Pemasok
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="d-flex align-items-center">
            <h5 class="mb-0">Daftar Pemasok</h5>
            <form class="ms-auto position-relative" method="GET">
                <div class="position-absolute top-50 translate-middle-y search-icon px-3"><i class="bi bi-search"></i></div>
                <input class="form-control ps-5" type="text" name="nama_pemasok" value="{{ request('nama_pemasok') }}" placeholder="Cari pemasok...">
            </form>
        </div>

        <!-- Filter Section -->
        <div class="row mt-3 mb-3">
            <div class="col-md-12">
                <form class="row g-3" method="GET">
                    <div class="col-md-4">
                        <label for="nama_pemasok" class="form-label">Nama Pemasok</label>
                        <input type="text" class="form-control" id="nama_pemasok" name="nama_pemasok" value="{{ request('nama_pemasok') }}" placeholder="Nama pemasok...">
                    </div>
                    <div class="col-md-4">
                        <label for="kategori" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori" name="kategori">
                            <option value="">Semua Kategori</option>
                            <option value="Default" {{ request('kategori') === 'Default' ? 'selected' : '' }}>Default</option>
                            <!-- Tambahkan kategori lain jika ada -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filter</button>
                        <a href="{{ route('pemasok.index') }}" class="btn btn-outline-secondary ms-2"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Nama Pemasok</th>
                        <th>Kode Pemasok</th>
                        <th>Credit Terms</th>
                        <th>Alamat</th>
                        <th>Kontak</th>
                        <th>Telepon</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pemasok as $key => $item)
                    <tr>
                        <td>{{ $pemasok->firstItem() + $key }}</td>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->kode ?? '-' }}</td>
                        <td>{{ $item->credit_terms_hari ?? 0 }} hari</td>
                        <td>{{ $item->alamat ?? '-' }}</td>
                        <td>{{ $item->kontak ?? '-' }}</td>
                        <td>{{ $item->telepon ?? '-' }}</td>
                        <td>{{ $item->kategori ?? 'Default' }}</td>
                        <td>
                            @if($item->status)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('pemasok.edit', $item->id) }}" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="{{ $item->id }}">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                                <form id="delete-form-{{ $item->id }}" action="{{ route('pemasok.destroy', $item->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">Belum ada data pemasok.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="row mt-4">
            <div class="col-sm-12 col-md-6">
                <div class="dataTables_info">
                    Menampilkan {{ $pemasok->firstItem() ?? 0 }} sampai {{ $pemasok->lastItem() ?? 0 }} dari {{ $pemasok->total() }} entri
                </div>
            </div>
            <div class="col-sm-12 col-md-6">
                <div class="float-end">{{ $pemasok->links() }}</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Handle delete button click
        $('.delete-btn').on('click', function() {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Pemasok akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        });
    });
</script>
@endpush

@push('styles')
<style>
    .search-icon {
        z-index: 10;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
    .badge.bg-success {
        background-color: #198754 !important;
    }
    .badge.bg-danger {
        background-color: #dc3545 !important;
    }
</style>
@endpush
