@extends('layouts.app')

@section('title', 'Studio')

@section('content')
@php
    $user = auth()->user();
    $canCreateStudio = $user?->hasPermission('konfigurasi.studio.create') ?? false;
    $canUpdateStudio = $user?->hasPermission('konfigurasi.studio.update') ?? false;
    $canDeleteStudio = $user?->hasPermission('konfigurasi.studio.delete') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Studio</li>
            </ol>
        </nav>
    </div>
    @if($canCreateStudio)
        <div class="ms-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Tambah Studio
            </button>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Daftar Studio</h5>

        <form id="searchForm" class="row g-3 mb-3">
            <div class="col-md-3">
                <label for="searchCabangId" class="form-label">Cabang</label>
                <select id="searchCabangId" class="form-select">
                    <option value="">Semua Cabang</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="searchNama" class="form-label">Nama Studio</label>
                <input type="text" class="form-control" id="searchNama" placeholder="Cari nama studio...">
            </div>
            <div class="col-md-3">
                <label for="searchTemaStudioId" class="form-label">Tema Studio</label>
                <select id="searchTemaStudioId" class="form-select">
                    <option value="">Semua Tema</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="searchStatus" class="form-label">Status</label>
                <select id="searchStatus" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Non Aktif</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filter</button>
                <button type="reset" class="btn btn-outline-secondary ms-2"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle" id="studioTable">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Cabang</th>
                        <th>Nama Studio</th>
                        <th>Tema Studio</th>
                        <th>Status</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <div class="dataTables_info">-</div>
            </div>
            <div class="col-md-6">
                <ul class="pagination justify-content-end mb-0"></ul>
            </div>
        </div>
    </div>
</div>

@if($canCreateStudio)
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Studio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addCabangId" class="form-label">Cabang <span class="text-danger">*</span></label>
                        <select id="addCabangId" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label for="addNama" class="form-label">Nama Studio <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="addNama" required>
                    </div>
                    <div class="mb-3">
                        <label for="addTemaStudioId" class="form-label">Tema Studio</label>
                        <select id="addTemaStudioId" class="form-select">
                            <option value="">Tanpa Tema</option>
                        </select>
                    </div>
                    <div>
                        <label for="addStatus" class="form-label">Status</label>
                        <select id="addStatus" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
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

@if($canUpdateStudio)
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Studio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm">
                <input type="hidden" id="editId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editCabangId" class="form-label">Cabang <span class="text-danger">*</span></label>
                        <select id="editCabangId" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label for="editNama" class="form-label">Nama Studio <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editNama" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTemaStudioId" class="form-label">Tema Studio</label>
                        <select id="editTemaStudioId" class="form-select">
                            <option value="">Tanpa Tema</option>
                        </select>
                    </div>
                    <div>
                        <label for="editStatus" class="form-label">Status</label>
                        <select id="editStatus" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
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
    $(document).ready(function() {
        const canUpdateStudio = @json($canUpdateStudio);
        const canDeleteStudio = @json($canDeleteStudio);
        const apiUrl = '/konfigurasi/studio/data';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        let cabangList = [];
        let temaStudioList = [];

        function fillCabangOptions(selector, includeAll = false) {
            const select = $(selector);
            select.empty();
            if (includeAll) {
                select.append('<option value="">Semua Cabang</option>');
            }
            cabangList.forEach((item) => {
                select.append(`<option value="${item.id}">${item.kode} - ${item.nama}</option>`);
            });
        }

        function fillTemaStudioOptions(selector, includeAll = false) {
            const select = $(selector);
            select.empty();
            if (includeAll) {
                select.append('<option value="">Semua Tema</option>');
            } else {
                select.append('<option value="">Tanpa Tema</option>');
            }
            temaStudioList.forEach((item) => {
                select.append(`<option value="${item.id}">${item.nama}</option>`);
            });
        }

        async function loadMasterData() {
            const cabangRequest = $.ajax({ url: `${apiUrl}/cabang/list`, method: 'GET' });
            const temaRequest = $.ajax({ url: `${apiUrl}/tema-studio/list`, method: 'GET' });

            const [cabangResponse, temaResponse] = await Promise.all([cabangRequest, temaRequest]);

            cabangList = cabangResponse?.data || [];
            temaStudioList = temaResponse?.data || [];

            fillCabangOptions('#searchCabangId', true);
            fillCabangOptions('#addCabangId');
            fillCabangOptions('#editCabangId');
            fillTemaStudioOptions('#searchTemaStudioId', true);
            fillTemaStudioOptions('#addTemaStudioId');
            fillTemaStudioOptions('#editTemaStudioId');
        }

        function renderTable(data, pagination) {
            const tbody = $('#studioTable tbody');
            tbody.empty();

            if (!data || data.length === 0) {
                tbody.append('<tr><td colspan="6" class="text-center">Tidak ada data studio</td></tr>');
                $('.dataTables_info').text('Menampilkan 0 data');
                $('.pagination').empty();
                return;
            }

            const startIndex = ((pagination?.current_page || 1) - 1) * (pagination?.per_page || data.length);
            data.forEach((row, index) => {
                const statusBadge = row.status
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-danger">Non Aktif</span>';
                let actionButtons = '';
                if (canUpdateStudio) {
                    actionButtons += `
                        <a href="javascript:;" class="text-warning edit-btn" data-id="${row.id}">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                    `;
                }
                if (canDeleteStudio) {
                    actionButtons += `
                        <a href="javascript:;" class="text-danger delete-btn" data-id="${row.id}">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    `;
                }
                if (!actionButtons) {
                    actionButtons = '<span class="text-muted">-</span>';
                }

                tbody.append(`
                    <tr>
                        <td>${startIndex + index + 1}</td>
                        <td>${row.cabang ? `${row.cabang.kode} - ${row.cabang.nama}` : '-'}</td>
                        <td>${row.nama}</td>
                        <td>${row.tema_studio ? row.tema_studio.nama : '-'}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="table-actions d-flex align-items-center gap-3 fs-6">
                                ${actionButtons}
                            </div>
                        </td>
                    </tr>
                `);
            });

            if (pagination) {
                const start = (pagination.current_page - 1) * pagination.per_page + 1;
                const end = Math.min(start + pagination.per_page - 1, pagination.total);
                $('.dataTables_info').text(`Menampilkan ${start} sampai ${end} dari ${pagination.total} entri`);
                renderPagination(pagination);
            }
        }

        function renderPagination(pagination) {
            const container = $('.pagination');
            container.empty();

            const currentPage = pagination.current_page;
            const lastPage = pagination.last_page;
            if (lastPage <= 1) {
                return;
            }

            container.append(`
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
                </li>
            `);

            for (let i = 1; i <= lastPage; i++) {
                container.append(`
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }

            container.append(`
                <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
                </li>
            `);
        }

        function currentFilters() {
            return {
                cabang_id: $('#searchCabangId').val(),
                nama: $('#searchNama').val(),
                tema_studio_id: $('#searchTemaStudioId').val(),
                status: $('#searchStatus').val(),
            };
        }

        function loadData(extra = {}) {
            const params = new URLSearchParams({ ...currentFilters(), ...extra }).toString();
            $.ajax({
                url: `${apiUrl}?${params}`,
                method: 'GET',
                success: function(response) {
                    renderTable(response.data || [], {
                        total: response.total || 0,
                        current_page: response.current_page || 1,
                        per_page: response.per_page || 10,
                        last_page: response.last_page || 1
                    });
                },
                error: function() {
                    Swal.fire('Error', 'Gagal memuat data studio', 'error');
                }
            });
        }

        function buildPayload(prefix) {
            return {
                cabang_id: $(`#${prefix}CabangId`).val(),
                nama: $(`#${prefix}Nama`).val(),
                tema_studio_id: $(`#${prefix}TemaStudioId`).val() || null,
                status: $(`#${prefix}Status`).val()
            };
        }

        async function initialize() {
            try {
                await loadMasterData();
                loadData();
            } catch (error) {
                Swal.fire('Error', 'Gagal memuat data master studio', 'error');
            }
        }

        initialize();

        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            loadData({ page: 1 });
        });

        $('#searchForm').on('reset', function() {
            setTimeout(() => loadData({ page: 1 }), 0);
        });

        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                loadData({ page });
            }
        });

        $('#addForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: apiUrl,
                method: 'POST',
                data: buildPayload('add'),
                success: function(response) {
                    Swal.fire('Berhasil', response.message || 'Studio berhasil ditambahkan', 'success').then(() => {
                        $('#addModal').modal('hide');
                        $('#addForm')[0].reset();
                        $('#addStatus').val('1');
                        loadData({ page: 1 });
                    });
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors;
                    const message = errors
                        ? Object.values(errors).map(v => v[0]).join('<br>')
                        : (xhr.responseJSON?.message || 'Gagal menambahkan studio');
                    Swal.fire({ title: 'Error', html: message, icon: 'error' });
                }
            });
        });

        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            $.ajax({
                url: `${apiUrl}/${id}`,
                method: 'GET',
                success: function(response) {
                    $('#editId').val(response.id);
                    $('#editCabangId').val(response.cabang_id);
                    $('#editNama').val(response.nama);
                    $('#editTemaStudioId').val(response.tema_studio_id ?? '');
                    $('#editStatus').val(response.status ? '1' : '0');
                    $('#editModal').modal('show');
                },
                error: function() {
                    Swal.fire('Error', 'Gagal memuat data studio', 'error');
                }
            });
        });

        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            const id = $('#editId').val();
            $.ajax({
                url: `${apiUrl}/${id}`,
                method: 'PUT',
                data: buildPayload('edit'),
                success: function(response) {
                    Swal.fire('Berhasil', response.message || 'Studio berhasil diperbarui', 'success').then(() => {
                        $('#editModal').modal('hide');
                        loadData();
                    });
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors;
                    const message = errors
                        ? Object.values(errors).map(v => v[0]).join('<br>')
                        : (xhr.responseJSON?.message || 'Gagal memperbarui studio');
                    Swal.fire({ title: 'Error', html: message, icon: 'error' });
                }
            });
        });

        $(document).on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data studio akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                $.ajax({
                    url: `${apiUrl}/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        Swal.fire('Berhasil', response.message || 'Studio berhasil dihapus', 'success').then(() => {
                            loadData();
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghapus studio', 'error');
                    }
                });
            });
        });
    });
</script>
@endpush
