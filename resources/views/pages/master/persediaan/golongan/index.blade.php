@extends('layouts.app')

@section('title', 'Golongan Barang/Jasa')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Golongan</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahGolongan">
            <i class="bi bi-plus-circle"></i> Tambah Golongan
        </button>
    </div>
</div>

<div id="ajaxFlashContainer"></div>

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
                <label class="form-label">Nama Golongan</label>
                <input type="text" name="nama" class="form-control" value="{{ request('nama') }}" placeholder="Cari nama golongan...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kode</label>
                <input type="text" name="kode" class="form-control" value="{{ request('kode') }}" placeholder="Cari kode...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipe</label>
                <select name="tipe" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="BARANG" {{ request('tipe') === 'BARANG' ? 'selected' : '' }}>Barang</option>
                    <option value="JASA" {{ request('tipe') === 'JASA' ? 'selected' : '' }}>Jasa</option>
                </select>
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
                <a href="{{ route('persediaan.golongan') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div id="golonganCrudContainer">
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th>Nama Golongan</th>
                        <th>Tracking</th>
                        <th>Divisi</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($golongan as $index => $item)
                        <tr>
                            <td>{{ $golongan->firstItem() + $index }}</td>
                            <td><span class="badge bg-light text-dark">{{ $item->kode }}</span></td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->trackingReference?->nama ?? '-' }}</td>
                            <td>{{ $item->divisi?->nama ?? '-' }}</td>
                            <td>{!! strtoupper((string) $item->tipe) === 'JASA' ? '<span class="badge bg-info">Jasa</span>' : '<span class="badge bg-primary">Barang</span>' !!}</td>
                            <td>{!! $item->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                            <td class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditGolongan{{ $item->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('persediaan.golongan.destroy', $item) }}" data-ajax-golongan="1" data-confirm-message="Hapus golongan ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada data golongan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $golongan->links() }}
        </div>
    </div>

    <div class="modal fade" id="modalTambahGolongan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('persediaan.golongan.store') }}" data-ajax-golongan="1">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Golongan</h5>
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
                        <label class="form-label">Tracking</label>
                        <select name="tracking_reference_id" class="form-select">
                            <option value="">- Pilih Tracking -</option>
                            @foreach(($trackingList ?? collect()) as $tracking)
                                <option value="{{ $tracking->id }}">{{ $tracking->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Divisi</label>
                        <select name="id_divisi" class="form-select">
                            <option value="">- Pilih Divisi -</option>
                            @foreach(($divisiList ?? collect()) as $divisi)
                                <option value="{{ $divisi->id }}">{{ $divisi->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tipe</label>
                        <select name="tipe" class="form-select" required>
                            <option value="BARANG">Barang</option>
                            <option value="JASA">Jasa</option>
                        </select>
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

    @foreach($golongan as $item)
        <div class="modal fade" id="modalEditGolongan{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('persediaan.golongan.update', $item) }}" data-ajax-golongan="1">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Golongan</h5>
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
                                <label class="form-label">Tracking</label>
                                <select name="tracking_reference_id" class="form-select">
                                    <option value="">- Pilih Tracking -</option>
                                    @foreach(($trackingList ?? collect()) as $tracking)
                                        <option value="{{ $tracking->id }}" {{ (string) $item->tracking_reference_id === (string) $tracking->id ? 'selected' : '' }}>
                                            {{ $tracking->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Divisi</label>
                                <select name="id_divisi" class="form-select">
                                    <option value="">- Pilih Divisi -</option>
                                    @foreach(($divisiList ?? collect()) as $divisi)
                                        <option value="{{ $divisi->id }}" {{ (string) $item->id_divisi === (string) $divisi->id ? 'selected' : '' }}>
                                            {{ $divisi->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Tipe</label>
                                <select name="tipe" class="form-select" required>
                                    <option value="BARANG" {{ strtoupper((string) $item->tipe) === 'BARANG' ? 'selected' : '' }}>Barang</option>
                                    <option value="JASA" {{ strtoupper((string) $item->tipe) === 'JASA' ? 'selected' : '' }}>Jasa</option>
                                </select>
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
</div>

@push('scripts')
<script>
    (function () {
        const containerSelector = '#golonganCrudContainer';
        const flashContainer = document.getElementById('ajaxFlashContainer');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function showFlash(message, type) {
            if (!flashContainer) return;
            flashContainer.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        }

        function clearFormErrors(form) {
            const errorBox = form.querySelector('.ajax-form-errors');
            if (errorBox) errorBox.remove();
        }

        function showFormErrors(form, errors) {
            clearFormErrors(form);
            const messages = Object.values(errors || {}).flat();
            if (!messages.length) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'alert alert-danger ajax-form-errors';
            wrapper.innerHTML = '<ul class="mb-0">' + messages.map(function (msg) {
                return '<li>' + msg + '</li>';
            }).join('') + '</ul>';

            const modalBody = form.querySelector('.modal-body');
            if (modalBody) {
                modalBody.prepend(wrapper);
                return;
            }

            form.prepend(wrapper);
        }

        async function refreshCrudArea() {
            const response = await fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector(containerSelector);
            const currentContent = document.querySelector(containerSelector);

            if (newContent && currentContent) {
                currentContent.outerHTML = newContent.outerHTML;
            }
        }

        async function submitAjaxForm(form) {
            clearFormErrors(form);

            const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'POST').toUpperCase();
            const isDelete = method === 'DELETE';

            if (isDelete) {
                const confirmMessage = form.dataset.confirmMessage || 'Yakin ingin menghapus data ini?';
                if (typeof Swal !== 'undefined') {
                    const result = await Swal.fire({
                        title: 'Konfirmasi Hapus',
                        text: confirmMessage,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                    });

                    if (!result.isConfirmed) return;
                }
            }

            const submitButton = form.querySelector('button[type="submit"], button:not([type])');
            if (submitButton) submitButton.disabled = true;

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });

            if (response.status === 422) {
                const data = await response.json();
                showFormErrors(form, data.errors || {});
                if (submitButton) submitButton.disabled = false;
                return;
            }

            if (!response.ok) {
                throw new Error('Terjadi kesalahan saat memproses data.');
            }

            const data = await response.json();

            const modalElement = form.closest('.modal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modalElement) || bootstrap.Modal.getOrCreateInstance(modalElement);
                await new Promise(function (resolve) {
                    modalElement.addEventListener('hidden.bs.modal', function onHidden() {
                        modalElement.removeEventListener('hidden.bs.modal', onHidden);
                        resolve();
                    }, { once: true });
                    modalInstance.hide();
                });
            }

            await refreshCrudArea();
            showFlash(data.message || 'Data berhasil diproses.', 'success');
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (!form.matches('form[data-ajax-golongan="1"]')) return;

            event.preventDefault();
            submitAjaxForm(form).catch(function (error) {
                showFlash(error.message || 'Terjadi kesalahan.', 'danger');
            }).finally(function () {
                const submitButton = form.querySelector('button[type="submit"], button:not([type])');
                if (submitButton) submitButton.disabled = false;
            });
        });
    })();
</script>
@endpush
@endsection
