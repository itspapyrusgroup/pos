@extends('layouts.app')

@section('title', 'Perusahaan')

@section('content')
@php
    $user = auth()->user();
    $canCreatePerusahaan = $user?->hasPermission('konfigurasi.perusahaan.create') ?? false;
    $canUpdatePerusahaan = $user?->hasPermission('konfigurasi.perusahaan.update') ?? false;
    $canDeletePerusahaan = $user?->hasPermission('konfigurasi.perusahaan.delete') ?? false;
@endphp

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Perusahaan</li>
            </ol>
        </nav>
    </div>
    @if($canCreatePerusahaan)
        <div class="ms-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Tambah Perusahaan
            </button>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <h5 class="mb-0">Daftar Perusahaan</h5>
        </div>

        <!-- Filter Section -->
        <div class="row mt-3 mb-3">
            <div class="col-md-12">
                <form class="row g-3" id="searchForm">
                    <div class="col-md-4">
                        <label for="searchNama" class="form-label">Cari Perusahaan</label>
                        <input type="text" class="form-control" id="searchNama" placeholder="Nama Perusahaan...">
                    </div>
                    <div class="col-md-4">
                        <label for="searchKode" class="form-label">Kode Perusahaan</label>
                        <input type="text" class="form-control" id="searchKode" placeholder="Kode Perusahaan...">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select multiple-select" id="status">
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
                        <button type="reset" class="btn btn-secondary ms-2">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table align-middle" id="companiesTable">
                <thead class="table-secondary">
                    <tr>
                        <th>#</th>
                        <th>Kode Perusahaan</th>
                        <th>Nama</th>
                        <th>NPWP</th>
                        <th>Alamat</th>
                        <th>No HP</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data akan diisi via JavaScript -->
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="dataTables_info">Menampilkan 0 sampai 0 dari 0 entri</div>
                <div class="dataTables_paginate">
                    <ul class="pagination">
                        <!-- Pagination akan diisi via JavaScript -->
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canCreatePerusahaan)
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Perusahaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addKode" class="form-label">Kode Perusahaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="addKode" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addNama" class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="addNama" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addNpwp" class="form-label">NPWP</label>
                                <input type="text" class="form-control" id="addNpwp">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addNoHp" class="form-label">No. HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="addNoHp" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="addAlamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="addAlamat" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="addStatus" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="addStatus" required>
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($canUpdatePerusahaan)
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Perusahaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm">
                <input type="hidden" id="editId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editKode" class="form-label">Kode Perusahaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editKode" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editNama" class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editNama" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editNpwp" class="form-label">NPWP</label>
                                <input type="text" class="form-control" id="editNpwp">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editNoHp" class="form-label">No. HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editNoHp" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editAlamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="editAlamat" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="editStatus" required>
                            <option value="1">Aktif</option>
                            <option value="0">Non Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Perusahaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Kode Perusahaan</h6>
                        <p id="viewKode">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Nama Perusahaan</h6>
                        <p id="viewNama">-</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>NPWP</h6>
                        <p id="viewNpwp">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6>No. HP</h6>
                        <p id="viewNoHp">-</p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6>Alamat</h6>
                    <p id="viewAlamat">-</p>
                </div>
                <div class="mb-3">
                    <h6>Status</h6>
                    <p id="viewStatus">-</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
    const canCreatePerusahaan = @json($canCreatePerusahaan);
    const canUpdatePerusahaan = @json($canUpdatePerusahaan);
    const canDeletePerusahaan = @json($canDeletePerusahaan);
    // Initialize select2
    $('.multiple-select').select2({
        placeholder: "Pilih Status",
        allowClear: true
    });

    // Base endpoint
    const apiUrl = '/konfigurasi/perusahaan/data';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    });

    // Render table function
    function renderTable(data, pagination) {
        const tbody = $('#companiesTable tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data ditemukan</td>
                </tr>
            `);
            return;
        }

        data.forEach((company, index) => {
            const statusBadge = company.status == 1
                ? '<span class="badge bg-success">Aktif</span>'
                : '<span class="badge bg-danger">Non Aktif</span>';

            let actionButtons = `
                <a href="javascript:;" class="text-primary view-btn" data-id="${company.id}">
                    <i class="bi bi-eye-fill"></i>
                </a>
            `;
            if (canUpdatePerusahaan) {
                actionButtons += `
                    <a href="javascript:;" class="text-warning edit-btn" data-id="${company.id}">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                `;
            }
            if (canDeletePerusahaan) {
                actionButtons += `
                    <a href="javascript:;" class="text-danger delete-btn" data-id="${company.id}">
                        <i class="bi bi-trash-fill"></i>
                    </a>
                `;
            }

            tbody.append(`
                <tr>
                    <td>${index + 1}</td>
                    <td>${company.kode}</td>
                    <td>${company.nama}</td>
                    <td>${company.npwp || '-'}</td>
                    <td>${company.alamat || '-'}</td>
                    <td>${company.no_hp}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="table-actions d-flex align-items-center gap-3 fs-6">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `);
        });

        // Update pagination info
        if (pagination) {
            const start = (pagination.current_page - 1) * pagination.per_page + 1;
            const end = Math.min(start + pagination.per_page - 1, pagination.total);
            $('.dataTables_info').text(`Menampilkan ${start} sampai ${end} dari ${pagination.total} entri`);

            // Render pagination
            renderPagination(pagination);
        }
    }

    // Render pagination
    function renderPagination(pagination) {
        const paginationEl = $('.pagination');
        paginationEl.empty();

        const currentPage = pagination.current_page;
        const lastPage = pagination.last_page;

        // Previous button
        paginationEl.append(`
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
            </li>
        `);

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        if (startPage > 1) {
            paginationEl.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
                ${startPage > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
            `);
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationEl.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        if (endPage < lastPage) {
            paginationEl.append(`
                ${endPage < lastPage - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a>
                </li>
            `);
        }

        // Next button
        paginationEl.append(`
            <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
            </li>
        `);
    }

    // Load data from API
    function loadData(params = {}) {
        const queryParams = new URLSearchParams(params).toString();
        $.ajax({
            url: `${apiUrl}?${queryParams}`,
            method: 'GET',
            success: function(response) {
                renderTable(response.data, {
                    total: response.total,
                    current_page: response.current_page,
                    per_page: response.per_page,
                    last_page: response.last_page
                });
            },
            error: function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data perusahaan',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    // Initial load
    loadData();

    // Search form handler
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();

        const params = {
            nama: $('#searchNama').val(),
            kode: $('#searchKode').val(),
            status: $('#status').val()
        };

        loadData(params);
    });

    // Reset form handler
    $('#searchForm').on('reset', function() {
        loadData();
    });

    // Pagination click handler
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) {
            const params = {
                nama: $('#searchNama').val(),
                kode: $('#searchKode').val(),
                status: $('#status').val(),
                page: page
            };
            loadData(params);
        }
    });

    // Handle add form submission
    $('#addForm').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            nama: $('#addNama').val(),
            npwp: $('#addNpwp').val(),
            alamat: $('#addAlamat').val(),
            no_hp: $('#addNoHp').val(),
            status: $('#addStatus').val()
        };

        $.ajax({
            url: apiUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    $('#addForm')[0].reset();
                    $('#addModal').modal('hide');
                    loadData();
                });
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    let errorMessages = [];
                    $.each(errors, function(key, value) {
                        errorMessages.push(value[0]);
                    });
                    Swal.fire({
                        title: 'Error!',
                        html: errorMessages.join('<br>'),
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal menambahkan perusahaan',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });

    // Handle view button click
    $(document).on('click', '.view-btn', function() {
        const id = $(this).data('id');

        $.ajax({
            url: `${apiUrl}/${id}`,
            method: 'GET',
            success: function(response) {
                $('#viewKode').text(response.kode);
                $('#viewNama').text(response.nama);
                $('#viewNpwp').text(response.npwp || '-');
                $('#viewNoHp').text(response.no_hp);
                $('#viewAlamat').text(response.alamat || '-');
                $('#viewStatus').html(response.status == 1
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-danger">Non Aktif</span>');

                $('#viewModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat detail perusahaan',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Handle edit button click
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');

        $.ajax({
            url: `${apiUrl}/${id}`,
            method: 'GET',
            success: function(response) {
                $('#editId').val(response.id);
                $('#editKode').val(response.kode);
                $('#editNama').val(response.nama);
                $('#editNpwp').val(response.npwp);
                $('#editAlamat').val(response.alamat);
                $('#editNoHp').val(response.no_hp);
                $('#editStatus').val(response.status ? '1' : '0');

                $('#editModal').modal('show');
            },
            error: function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data perusahaan',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Handle edit form submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();

        const id = $('#editId').val();
        const formData = {
            nama: $('#editNama').val(),
            npwp: $('#editNpwp').val(),
            alamat: $('#editAlamat').val(),
            no_hp: $('#editNoHp').val(),
            status: $('#editStatus').val()
        };

        $.ajax({
            url: `${apiUrl}/${id}`,
            method: 'PUT',
            data: formData,
            success: function(response) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    $('#editModal').modal('hide');
                    loadData();
                });
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    let errorMessages = [];
                    $.each(errors, function(key, value) {
                        errorMessages.push(value[0]);
                    });
                    Swal.fire({
                        title: 'Error!',
                        html: errorMessages.join('<br>'),
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memperbarui perusahaan',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });

    // Handle delete button click
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: 'Anda akan menghapus perusahaan ini',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${apiUrl}/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        Swal.fire(
                            'Dihapus!',
                            response.message,
                            'success'
                        ).then(() => {
                            loadData();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Gagal menghapus perusahaan',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });

    // Set kode perusahaan otomatis saat modal add dibuka
    $('#addModal').on('show.bs.modal', function() {
        $.ajax({
            url: `${apiUrl}/generate-kode`,
            method: 'GET',
            success: function(response) {
                $('#addKode').val(response.kode);
            }
        });
    });
});
    // $(document).ready(function() {
    //     // Initialize select2
    //     $('.multiple-select').select2({
    //         placeholder: "Pilih Status",
    //         allowClear: true
    //     });

    //     // Sample data (in real app, this would come from API)
    //     let companies = [
    //         {
    //             id: 1,
    //             kode: 'C01',
    //             nama: 'CV Cahaya Kasih Utama',
    //             npwp: '36.0812.12515',
    //             alamat: 'Jl Bengawan No 29, Bandung',
    //             no_hp: '08111111111',
    //             status: 1
    //         },
    //         {
    //             id: 2,
    //             kode: 'C02',
    //             nama: 'Papyrus CCM',
    //             npwp: '36.0812.12515',
    //             alamat: 'Jl CCM No 29, Bogor',
    //             no_hp: '08111111112',
    //             status: 0
    //         }
    //     ];

    //     // Render table function
    //     function renderTable(data) {
    //         const tbody = $('#companiesTable tbody');
    //         tbody.empty();

    //         if (data.length === 0) {
    //             tbody.append(`
    //                 <tr>
    //                     <td colspan="8" class="text-center">Tidak ada data ditemukan</td>
    //                 </tr>
    //             `);
    //             return;
    //         }

    //         data.forEach((company, index) => {
    //             const statusBadge = company.status == 1
    //                 ? '<span class="badge bg-success">Aktif</span>'
    //                 : '<span class="badge bg-danger">Non Aktif</span>';

    //             tbody.append(`
    //                 <tr>
    //                     <td>${index + 1}</td>
    //                     <td>${company.kode}</td>
    //                     <td>${company.nama}</td>
    //                     <td>${company.npwp || '-'}</td>
    //                     <td>${company.alamat || '-'}</td>
    //                     <td>${company.no_hp}</td>
    //                     <td>${statusBadge}</td>
    //                     <td>
    //                         <div class="table-actions d-flex align-items-center gap-3 fs-6">
    //                             <a href="javascript:;" class="text-primary view-btn" data-id="${company.id}">
    //                                 <i class="bi bi-eye-fill"></i>
    //                             </a>
    //                             <a href="javascript:;" class="text-warning edit-btn" data-id="${company.id}">
    //                                 <i class="bi bi-pencil-fill"></i>
    //                             </a>
    //                             <a href="javascript:;" class="text-danger delete-btn" data-id="${company.id}">
    //                                 <i class="bi bi-trash-fill"></i>
    //                             </a>
    //                         </div>
    //                     </td>
    //                 </tr>
    //             `);
    //         });

    //         // Update pagination info
    //         $('.dataTables_info').text(`Menampilkan 1 sampai ${data.length} dari ${data.length} entri`);
    //     }

    //     // Initial render
    //     renderTable(companies);

    //     // Search form handler
    //     $('#searchForm').on('submit', function(e) {
    //         e.preventDefault();

    //         const nama = $('#searchNama').val().toLowerCase();
    //         const kode = $('#searchKode').val().toLowerCase();
    //         const status = $('#status').val();

    //         let filtered = companies;

    //         if (nama) {
    //             filtered = filtered.filter(c => c.nama.toLowerCase().includes(nama));
    //         }

    //         if (kode) {
    //             filtered = filtered.filter(c => c.kode.toLowerCase().includes(kode));
    //         }

    //         if (status) {
    //             filtered = filtered.filter(c => c.status == status);
    //         }

    //         renderTable(filtered);
    //     });

    //     // Reset form handler
    //     $('#searchForm').on('reset', function() {
    //         renderTable(companies);
    //     });

    //     // Handle add form submission
    //     $('#addForm').on('submit', function(e) {
    //         e.preventDefault();

    //         // Get form data
    //         const newCompany = {
    //             id: companies.length + 1,
    //             kode: $('#addKode').val(),
    //             nama: $('#addNama').val(),
    //             npwp: $('#addNpwp').val(),
    //             alamat: $('#addAlamat').val(),
    //             no_hp: $('#addNoHp').val(),
    //             status: $('#addStatus').val()
    //         };

    //         // Add to array (in real app, this would be an AJAX call)
    //         companies.unshift(newCompany);

    //         // Show success message
    //         Swal.fire({
    //             title: 'Berhasil!',
    //             text: 'Perusahaan berhasil ditambahkan',
    //             icon: 'success',
    //             confirmButtonText: 'OK'
    //         }).then(() => {
    //             // Reset form and close modal
    //             $('#addForm')[0].reset();
    //             $('#addModal').modal('hide');

    //             // Refresh table
    //             renderTable(companies);
    //         });
    //     });

    //     // Handle view button click
    //     $(document).on('click', '.view-btn', function() {
    //         const id = $(this).data('id');
    //         const company = companies.find(c => c.id == id);

    //         if (company) {
    //             $('#viewKode').text(company.kode);
    //             $('#viewNama').text(company.nama);
    //             $('#viewNpwp').text(company.npwp || '-');
    //             $('#viewNoHp').text(company.no_hp);
    //             $('#viewAlamat').text(company.alamat || '-');
    //             $('#viewStatus').html(company.status == 1
    //                 ? '<span class="badge bg-success">Aktif</span>'
    //                 : '<span class="badge bg-danger">Non Aktif</span>');

    //             $('#viewModal').modal('show');
    //         }
    //     });

    //     // Handle edit button click
    //     $(document).on('click', '.edit-btn', function() {
    //         const id = $(this).data('id');
    //         const company = companies.find(c => c.id == id);

    //         if (company) {
    //             $('#editId').val(company.id);
    //             $('#editKode').val(company.kode);
    //             $('#editNama').val(company.nama);
    //             $('#editNpwp').val(company.npwp);
    //             $('#editAlamat').val(company.alamat);
    //             $('#editNoHp').val(company.no_hp);
    //             $('#editStatus').val(company.status);

    //             $('#editModal').modal('show');
    //         }
    //     });

    //     // Handle edit form submission
    //     $('#editForm').on('submit', function(e) {
    //         e.preventDefault();

    //         // Get form data
    //         const id = $('#editId').val();
    //         const updatedCompany = {
    //             kode: $('#editKode').val(),
    //             nama: $('#editNama').val(),
    //             npwp: $('#editNpwp').val(),
    //             alamat: $('#editAlamat').val(),
    //             no_hp: $('#editNoHp').val(),
    //             status: $('#editStatus').val()
    //         };

    //         // Update in array (in real app, this would be an AJAX call)
    //         const index = companies.findIndex(c => c.id == id);
    //         if (index !== -1) {
    //             companies[index] = { ...companies[index], ...updatedCompany };
    //         }

    //         // Show success message
    //         Swal.fire({
    //             title: 'Berhasil!',
    //             text: 'Perusahaan berhasil diperbarui',
    //             icon: 'success',
    //             confirmButtonText: 'OK'
    //         }).then(() => {
    //             // Close modal
    //             $('#editModal').modal('hide');

    //             // Refresh table
    //             renderTable(companies);
    //         });
    //     });

    //     // Handle delete button click
    //     $(document).on('click', '.delete-btn', function() {
    //         const id = $(this).data('id');
    //         const company = companies.find(c => c.id == id);

    //         if (!company) return;

    //         Swal.fire({
    //             title: 'Apakah Anda yakin?',
    //             text: `Anda akan menghapus perusahaan ${company.nama}`,
    //             icon: 'warning',
    //             showCancelButton: true,
    //             confirmButtonColor: '#3085d6',
    //             cancelButtonColor: '#d33',
    //             confirmButtonText: 'Ya, hapus!',
    //             cancelButtonText: 'Batal'
    //         }).then((result) => {
    //             if (result.isConfirmed) {
    //                 // Delete from array (in real app, this would be an AJAX call)
    //                 companies = companies.filter(c => c.id != id);

    //                 Swal.fire(
    //                     'Dihapus!',
    //                     'Perusahaan telah dihapus.',
    //                     'success'
    //                 ).then(() => {
    //                     // Refresh table
    //                     renderTable(companies);
    //                 });
    //             }
    //         });
    //     });
    // });
</script>
@endpush

@push('styles')
<style>
    .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da;
        height: 38px;
        padding: 5px 10px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
    .table-actions a {
        cursor: pointer;
    }
</style>
@endpush
