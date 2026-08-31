@extends('layouts.app')

@section('title', 'Cabang')

@section('content')
@php
    $user = auth()->user();
    $canCreateCabang = $user?->hasPermission('konfigurasi.cabang.create') ?? false;
    $canUpdateCabang = $user?->hasPermission('konfigurasi.cabang.update') ?? false;
    $canDeleteCabang = $user?->hasPermission('konfigurasi.cabang.delete') ?? false;
@endphp

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Cabang</li>
            </ol>
        </nav>
    </div>
    @if($canCreateCabang)
        <div class="ms-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Tambah Cabang
            </button>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <h5 class="mb-0">Daftar Cabang</h5>
        </div>

        <!-- Filter Section -->
        <div class="row mt-3 mb-3">
            <div class="col-md-12">
                <form class="row g-3" id="searchForm">
                    <div class="col-md-4">
                        <label for="searchNama" class="form-label">Cari Cabang</label>
                        <input type="text" class="form-control" id="searchNama" placeholder="Nama Cabang...">
                    </div>
                    <div class="col-md-4">
                        <label for="searchKode" class="form-label">Kode Cabang</label>
                        <input type="text" class="form-control" id="searchKode" placeholder="Kode Cabang...">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status">
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
            <table class="table align-middle" id="branchesTable">
                <thead class="table-secondary">
                    <tr>
                        <th>#</th>
                        <th>Kode Cabang</th>
                        <th>Nama Perusahaan</th>
                        <th>Nama Cabang</th>
                        <th>Warna Header</th>
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

@if($canCreateCabang)
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addKode" class="form-label">Kode Cabang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="addKode" required readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addPerusahaanId" class="form-label">Perusahaan <span class="text-danger">*</span></label>
                                <select class="form-select" id="addPerusahaanId" required>
                                    <option value="">Pilih Perusahaan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="addNama" class="form-label">Nama Cabang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="addNama" required>
                    </div>
                    <div class="mb-3">
                        <label for="addAlamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="addAlamat" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addNoHp" class="form-label">No. HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="addNoHp" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addStatus" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="addStatus" required>
                                    <option value="1">Aktif</option>
                                    <option value="0">Non Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="addAllowMinusStock" class="form-label">Allow Minus Stock</label>
                        <select class="form-select" id="addAllowMinusStock">
                            <option value="0" selected>OFF (stok tidak boleh minus)</option>
                            <option value="1">ON (stok boleh minus)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addStrukFooter" class="form-label">Footer Struk</label>
                        <textarea class="form-control" id="addStrukFooter" rows="3" placeholder="Contoh: Terima kasih sudah berkunjung."></textarea>
                        <small class="text-muted">Akan tampil di bagian bawah struk kasir cabang ini.</small>
                    </div>
                    <div class="mb-3">
                        <label for="addWarnaHeader" class="form-label">Warna Header Bar (Top Bar)</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" id="addWarnaHeaderPicker" value="#3461ff" title="Pilih Warna Header">
                            <input type="text" class="form-control" id="addWarnaHeader" placeholder="#3461ff (Opsional, kosongkan untuk default)" maxlength="30">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addWarnaHeaderReset">Reset</button>
                        </div>
                        <small class="text-muted">Pilih warna bar atas untuk membedakan cabang ini dengan cabang lain.</small>
                    </div>
                    <div class="mb-3">
                        <label for="addTutupKasirEmailEnabled" class="form-label">Kirim Laporan Tutup Kasir via Email</label>
                        <select class="form-select" id="addTutupKasirEmailEnabled">
                            <option value="0" selected>Tidak Aktif</option>
                            <option value="1">Aktif</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addTutupKasirEmailRecipients" class="form-label">Email Penerima Laporan Tutup Kasir</label>
                        <textarea class="form-control" id="addTutupKasirEmailRecipients" rows="3" placeholder="finance@contoh.com, owner@contoh.com"></textarea>
                        <small class="text-muted">Bisa lebih dari satu email. Pisahkan dengan koma atau baris baru.</small>
                    </div>
                    <div class="mb-3">
                        <label for="addMetodePembayaranIds" class="form-label">Metode Pembayaran</label>
                        <select class="form-select multiple-select w-100" id="addMetodePembayaranIds" multiple data-placeholder="Pilih metode pembayaran">
                        </select>
                        <small class="text-muted">Pilih satu atau beberapa metode pembayaran yang berlaku di cabang ini.</small>
                    </div>
                    <hr>
                    <h6>Setting Sales Mode Cabang</h6>
                    <small class="text-muted d-block mb-2">Centang sales mode yang aktif, lalu pilih template harga untuk mode tersebut.</small>
                    <div id="addSalesModeContainer"></div>
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

@if($canUpdateCabang)
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm">
                <input type="hidden" id="editId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editKode" class="form-label">Kode Cabang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editKode" required readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editPerusahaanId" class="form-label">Perusahaan <span class="text-danger">*</span></label>
                                <select class="form-select" id="editPerusahaanId" required>
                                    <option value="">Pilih Perusahaan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editNama" class="form-label">Nama Cabang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editNama" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAlamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="editAlamat" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editNoHp" class="form-label">No. HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editNoHp" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editStatus" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="editStatus" required>
                                    <option value="1">Aktif</option>
                                    <option value="0">Non Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editAllowMinusStock" class="form-label">Allow Minus Stock</label>
                        <select class="form-select" id="editAllowMinusStock">
                            <option value="0">OFF (stok tidak boleh minus)</option>
                            <option value="1">ON (stok boleh minus)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editStrukFooter" class="form-label">Footer Struk</label>
                        <textarea class="form-control" id="editStrukFooter" rows="3" placeholder="Contoh: Terima kasih sudah berkunjung."></textarea>
                        <small class="text-muted">Akan tampil di bagian bawah struk kasir cabang ini.</small>
                    </div>
                    <div class="mb-3">
                        <label for="editWarnaHeader" class="form-label">Warna Header Bar (Top Bar)</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" id="editWarnaHeaderPicker" value="#3461ff" title="Pilih Warna Header">
                            <input type="text" class="form-control" id="editWarnaHeader" placeholder="#3461ff (Opsional, kosongkan untuk default)" maxlength="30">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="editWarnaHeaderReset">Reset</button>
                        </div>
                        <small class="text-muted">Pilih warna bar atas untuk membedakan cabang ini dengan cabang lain.</small>
                    </div>
                    <div class="mb-3">
                        <label for="editTutupKasirEmailEnabled" class="form-label">Kirim Laporan Tutup Kasir via Email</label>
                        <select class="form-select" id="editTutupKasirEmailEnabled">
                            <option value="0">Tidak Aktif</option>
                            <option value="1">Aktif</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editTutupKasirEmailRecipients" class="form-label">Email Penerima Laporan Tutup Kasir</label>
                        <textarea class="form-control" id="editTutupKasirEmailRecipients" rows="3" placeholder="finance@contoh.com, owner@contoh.com"></textarea>
                        <small class="text-muted">Bisa lebih dari satu email. Pisahkan dengan koma atau baris baru.</small>
                    </div>
                    <div class="mb-3">
                        <label for="editMetodePembayaranIds" class="form-label">Metode Pembayaran</label>
                        <select class="form-select multiple-select w-100" id="editMetodePembayaranIds" multiple data-placeholder="Pilih metode pembayaran">
                        </select>
                        <small class="text-muted">Pilih satu atau beberapa metode pembayaran yang berlaku di cabang ini.</small>
                    </div>
                    <hr>
                    <h6>Setting Sales Mode Cabang</h6>
                    <small class="text-muted d-block mb-2">Centang sales mode yang aktif, lalu pilih template harga untuk mode tersebut.</small>
                    <div id="editSalesModeContainer"></div>
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
                <h5 class="modal-title">Detail Cabang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Kode Cabang</h6>
                        <p id="viewKode">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Perusahaan</h6>
                        <p id="viewPerusahaan">-</p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6>Nama Cabang</h6>
                    <p id="viewNama">-</p>
                </div>
                <div class="mb-3">
                    <h6>Alamat</h6>
                    <p id="viewAlamat">-</p>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>No. HP</h6>
                        <p id="viewNoHp">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Status</h6>
                        <p id="viewStatus">-</p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6>Footer Struk</h6>
                    <p id="viewStrukFooter">-</p>
                </div>
                <div class="mb-3">
                    <h6>Warna Header Bar</h6>
                    <div id="viewWarnaHeader">-</div>
                </div>
                <div class="mb-3">
                    <h6>Allow Minus Stock</h6>
                    <p id="viewAllowMinusStock">-</p>
                </div>
                <div class="mb-3">
                    <h6>Kirim Laporan Tutup Kasir via Email</h6>
                    <p id="viewTutupKasirEmailEnabled">-</p>
                </div>
                <div class="mb-3">
                    <h6>Email Penerima Laporan Tutup Kasir</h6>
                    <p id="viewTutupKasirEmailRecipients">-</p>
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
        const canCreateCabang = @json($canCreateCabang);
        const canUpdateCabang = @json($canUpdateCabang);
        const canDeleteCabang = @json($canDeleteCabang);
        // Base endpoint
        const apiUrl = '/konfigurasi/cabang/data';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        let perusahaanList = [];
        let salesModeList = [];
        let templateHargaList = [];
        let metodePembayaranList = [];

        function initMetodePembayaranSelect(selector, modalSelector) {
            const element = $(selector);
            if (!element.length) return;

            if (element.hasClass('select2-hidden-accessible')) {
                element.select2('destroy');
            }

            element.select2({
                dropdownParent: $(modalSelector),
                placeholder: element.data('placeholder') || 'Pilih metode pembayaran',
                allowClear: true,
                width: '100%',
            });
        }

        function renderMetodePembayaranOptions(selector, selectedIds = []) {
            const element = $(selector);
            if (!element.length) return;

            const normalizedSelectedIds = (selectedIds || []).map(id => String(id));
            const options = metodePembayaranList.map(item => {
                const selected = normalizedSelectedIds.includes(String(item.id)) ? 'selected' : '';
                return `<option value="${item.id}" ${selected}>${item.kode} - ${item.nama}</option>`;
            }).join('');

            element.html(options);
            element.trigger('change.select2');
        }

        // Fungsi untuk load perusahaan
        function loadPerusahaan() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `${apiUrl}/perusahaan/list`, // Pastikan sesuai dengan route
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            perusahaanList = response.data;
                            // Isi dropdown perusahaan di modal add
                            const perusahaanSelect = $('#addPerusahaanId');
                            perusahaanSelect.empty();
                            perusahaanSelect.append('<option value="">Pilih Perusahaan</option>');
                            perusahaanList.forEach(perusahaan => {
                                perusahaanSelect.append(`<option value="${perusahaan.id}">${perusahaan.kode} - ${perusahaan.nama}</option>`);
                            });
                            resolve(response.data);
                        } else {
                            reject(new Error('Format response tidak valid'));
                        }
                    },
                    error: function(xhr) {
                        console.error('Gagal memuat perusahaan:', xhr.responseText);
                        reject(xhr);
                    }
                });
            });
        }

        function loadSalesModeTemplate() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `${apiUrl}/sales-mode-template/list`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            salesModeList = response.data.sales_mode || [];
                            templateHargaList = response.data.template_harga || [];
                            metodePembayaranList = response.data.metode_pembayaran || [];
                            renderSalesModeRows('#addSalesModeContainer');
                            renderSalesModeRows('#editSalesModeContainer');
                            renderMetodePembayaranOptions('#addMetodePembayaranIds');
                            renderMetodePembayaranOptions('#editMetodePembayaranIds');
                            resolve(response.data);
                        } else {
                            reject(new Error('Format response sales mode tidak valid'));
                        }
                    },
                    error: function(xhr) {
                        reject(xhr);
                    }
                });
            });
        }

        function renderSalesModeRows(containerSelector, selectedMap = {}) {
            const container = $(containerSelector);
            if (!container.length) return;

            let html = '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">';
            html += '<thead><tr><th>Aktif</th><th>Sales Mode</th><th>Template Harga</th></tr></thead><tbody>';

            salesModeList.forEach((mode, idx) => {
                const selected = selectedMap[mode.id] || null;
                const checked = selected ? 'checked' : '';
                const disabled = checked ? '' : 'disabled';
                const templateSelected = selected && selected.template_harga_id ? selected.template_harga_id : '';
                html += `
                    <tr>
                        <td style="width:90px">
                            <input type="checkbox" class="form-check-input sales-mode-checkbox" data-index="${idx}" ${checked}>
                        </td>
                        <td>
                            ${mode.nama}
                            <input type="hidden" class="sales-mode-id" value="${mode.id}">
                        </td>
                        <td>
                            <select class="form-select form-select-sm sales-mode-template" ${disabled}>
                                <option value="">-- Pilih Template --</option>
                                ${templateHargaList.map(template => `<option value="${template.id}" ${String(template.id) === String(templateSelected) ? 'selected' : ''}>${template.nama}</option>`).join('')}
                            </select>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.html(html);
        }

        function ambilSalesModesDariContainer(containerSelector) {
            const result = [];
            $(`${containerSelector} tbody tr`).each(function() {
                const checkbox = $(this).find('.sales-mode-checkbox');
                const salesModeId = $(this).find('.sales-mode-id').val();
                const templateHargaId = $(this).find('.sales-mode-template').val();
                const isActive = checkbox.is(':checked');
                result.push({
                    sales_mode_id: salesModeId,
                    template_harga_id: templateHargaId || null,
                    status: isActive ? 1 : 0
                });
            });
            return result;
        }

        function parseEmails(rawValue) {
            return String(rawValue || '')
                .split(/[\n,;]/)
                .map(email => email.trim().toLowerCase())
                .filter(Boolean);
        }

        function formatEmailsForTextarea(emails = []) {
            return (emails || []).join('\n');
        }

        // Fungsi untuk generate kode cabang
        function generateKodeCabang() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: `${apiUrl}/generate-kode`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            resolve(response.kode);
                        } else {
                            reject(new Error('Gagal generate kode'));
                        }
                    },
                    error: function(xhr) {
                        reject(xhr);
                    }
                });
            });
        }

        // Fungsi untuk load data cabang
        function loadData(params = {}) {
            const queryParams = new URLSearchParams(params).toString();
            $.ajax({
                url: `${apiUrl}?${queryParams}`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        renderTable(response.data, {
                            total: response.total,
                            current_page: response.current_page,
                            per_page: response.per_page,
                            last_page: response.last_page
                        });
                    } else {
                        renderTable([], null);
                        Swal.fire({
                            title: 'Info',
                            text: 'Tidak ada data cabang ditemukan',
                            icon: 'info',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr.responseText);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memuat data cabang',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }

        // Fungsi untuk render tabel
        function renderTable(data, pagination) {
            const tbody = $('#branchesTable tbody');
            tbody.empty();

            if (!data || data.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada data ditemukan</td>
                    </tr>
                `);
                return;
            }

            data.forEach((branch, index) => {
                // Cari data perusahaan dari perusahaanList
                const perusahaan = perusahaanList.find(p => p.id === branch.perusahaan_id);
                const perusahaanNama = perusahaan ? `${perusahaan.kode} - ${perusahaan.nama}` : '-';

                const statusBadge = branch.status == 1
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-danger">Non Aktif</span>';

                const warnaHeaderBadge = branch.warna_header
                    ? `<div class="d-flex align-items-center gap-2"><span class="d-inline-block rounded-circle border shadow-sm" style="width: 18px; height: 18px; background-color: ${branch.warna_header};"></span><small class="fw-bold">${branch.warna_header}</small></div>`
                    : '<span class="badge bg-light text-secondary border">Default</span>';

                let actionButtons = `
                    <a href="javascript:;" class="text-primary view-btn" data-id="${branch.id}">
                        <i class="bi bi-eye-fill"></i>
                    </a>
                `;
                if (canUpdateCabang) {
                    actionButtons += `
                        <a href="javascript:;" class="text-warning edit-btn" data-id="${branch.id}">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                    `;
                }
                if (canDeleteCabang) {
                    actionButtons += `
                        <a href="javascript:;" class="text-danger delete-btn" data-id="${branch.id}">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    `;
                }

                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${branch.kode}</td>
                        <td>${perusahaanNama}</td>
                        <td>${branch.nama}</td>
                        <td>${warnaHeaderBadge}</td>
                        <td>${branch.alamat || '-'}</td>
                        <td>${branch.no_hp}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="table-actions d-flex align-items-center gap-3 fs-6">
                                ${actionButtons}
                            </div>
                        </td>
                    </tr>
                `);
            });

            // Update pagination info jika ada
            if (pagination) {
                const start = (pagination.current_page - 1) * pagination.per_page + 1;
                const end = Math.min(start + pagination.per_page - 1, pagination.total);
                $('.dataTables_info').text(`Menampilkan ${start} sampai ${end} dari ${pagination.total} entri`);
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

        // Inisialisasi data
        async function initializeData() {
            try {
                initMetodePembayaranSelect('#addMetodePembayaranIds', '#addModal');
                initMetodePembayaranSelect('#editMetodePembayaranIds', '#editModal');
                await loadPerusahaan();
                await loadSalesModeTemplate();
                loadData();
            } catch (error) {
                console.error('Initialization error:', error);
                loadData();

                Swal.fire({
                    title: 'Peringatan',
                    text: 'Data perusahaan gagal dimuat, tetapi data cabang tetap ditampilkan',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            }
        }

        // Panggil initialize saat dokumen ready
        initializeData();

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

        // Set kode cabang otomatis saat modal add dibuka
        $('#addModal').on('show.bs.modal', async function() {
            try {
                const kode = await generateKodeCabang();
                $('#addKode').val(kode);

                // Pastikan perusahaan sudah di-load
                if (perusahaanList.length === 0) {
                    await loadPerusahaan();
                }

                if (salesModeList.length === 0 || templateHargaList.length === 0) {
                    await loadSalesModeTemplate();
                } else {
                    renderSalesModeRows('#addSalesModeContainer');
                }

                renderMetodePembayaranOptions('#addMetodePembayaranIds', []);
                $('#addTutupKasirEmailEnabled').val('0');
                $('#addTutupKasirEmailRecipients').val('');
                $('#addWarnaHeader').val('');
                $('#addWarnaHeaderPicker').val('#3461ff');
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Gagal memuat data awal',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });

        // Color picker sync handlers
        $('#addWarnaHeaderPicker').on('input change', function() {
            $('#addWarnaHeader').val($(this).val());
        });
        $('#addWarnaHeader').on('input', function() {
            const val = $(this).val().trim();
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                $('#addWarnaHeaderPicker').val(val);
            }
        });
        $('#addWarnaHeaderReset').on('click', function() {
            $('#addWarnaHeader').val('');
            $('#addWarnaHeaderPicker').val('#3461ff');
        });

        $('#editWarnaHeaderPicker').on('input change', function() {
            $('#editWarnaHeader').val($(this).val());
        });
        $('#editWarnaHeader').on('input', function() {
            const val = $(this).val().trim();
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                $('#editWarnaHeaderPicker').val(val);
            }
        });
        $('#editWarnaHeaderReset').on('click', function() {
            $('#editWarnaHeader').val('');
            $('#editWarnaHeaderPicker').val('#3461ff');
        });

        // Handle add form submission
        $('#addForm').on('submit', function(e) {
            e.preventDefault();

            // Validasi client-side
            if (!$('#addPerusahaanId').val()) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Perusahaan harus dipilih',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if (!$('#addNama').val()) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Nama cabang harus diisi',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            const formData = {
                perusahaan_id: $('#addPerusahaanId').val(),
                nama: $('#addNama').val(),
                alamat: $('#addAlamat').val(),
                no_hp: $('#addNoHp').val(),
                allow_minus_stock: $('#addAllowMinusStock').val(),
                struk_footer: $('#addStrukFooter').val(),
                warna_header: $('#addWarnaHeader').val().trim() || null,
                tutup_kasir_email_enabled: $('#addTutupKasirEmailEnabled').val(),
                tutup_kasir_email_recipients: parseEmails($('#addTutupKasirEmailRecipients').val()),
                status: $('#addStatus').val(),
                metode_pembayaran_ids: $('#addMetodePembayaranIds').val() || [],
                sales_modes: ambilSalesModesDariContainer('#addSalesModeContainer')
            };

            $.ajax({
                url: apiUrl,
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.message) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            $('#addForm')[0].reset();
                            $('#addTutupKasirEmailEnabled').val('0');
                            $('#addTutupKasirEmailRecipients').val('');
                            $('#addMetodePembayaranIds').val(null).trigger('change');
                            $('#addModal').modal('hide');
                            loadData();
                        });
                    } else {
                        throw new Error('Response tidak valid');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Gagal menambahkan cabang';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).join('<br>');
                    }

                    Swal.fire({
                        title: 'Error!',
                        html: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
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
                    const perusahaan = perusahaanList.find(p => p.id === response.perusahaan_id);
                    const perusahaanNama = perusahaan ? `${perusahaan.kode} - ${perusahaan.nama}` : '-';

                    $('#viewKode').text(response.kode);
                    $('#viewNama').text(response.nama);
                    $('#viewPerusahaan').text(perusahaanNama);
                    $('#viewAlamat').text(response.alamat || '-');
                    $('#viewNoHp').text(response.no_hp);
                    $('#viewAllowMinusStock').html(response.allow_minus_stock ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-secondary">OFF</span>');
                    $('#viewStrukFooter').text(response.struk_footer || '-');
                    const viewColor = response.warna_header
                        ? `<div class="d-flex align-items-center gap-2"><span class="d-inline-block rounded-circle border shadow-sm" style="width: 20px; height: 20px; background-color: ${response.warna_header};"></span><b>${response.warna_header}</b></div>`
                        : '<span class="text-muted">Default</span>';
                    $('#viewWarnaHeader').html(viewColor);
                    $('#viewTutupKasirEmailEnabled').html(response.tutup_kasir_email_enabled
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Tidak Aktif</span>');
                    $('#viewTutupKasirEmailRecipients').html((response.tutup_kasir_email_recipients || []).length
                        ? (response.tutup_kasir_email_recipients || []).join('<br>')
                        : '-');
                    $('#viewStatus').html(response.status == 1
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-danger">Non Aktif</span>');

                    $('#viewModal').modal('show');
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memuat detail cabang',
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
                    const salesModeMap = {};
                    (response.sales_modes || []).forEach(function(item) {
                        salesModeMap[item.sales_mode_id] = item;
                    });
                    renderSalesModeRows('#editSalesModeContainer', salesModeMap);

                    $('#editId').val(response.id);
                    $('#editKode').val(response.kode);

                    // Isi dropdown perusahaan
                    const perusahaanSelect = $('#editPerusahaanId');
                    perusahaanSelect.empty();
                    perusahaanList.forEach(perusahaan => {
                        perusahaanSelect.append(`<option value="${perusahaan.id}" ${perusahaan.id === response.perusahaan_id ? 'selected' : ''}>
                            ${perusahaan.kode} - ${perusahaan.nama}
                        </option>`);
                    });

                    $('#editNama').val(response.nama);
                    $('#editAlamat').val(response.alamat);
                    $('#editNoHp').val(response.no_hp);
                    $('#editAllowMinusStock').val(response.allow_minus_stock ? '1' : '0');
                    $('#editStrukFooter').val(response.struk_footer || '');
                    $('#editWarnaHeader').val(response.warna_header || '');
                    $('#editWarnaHeaderPicker').val(response.warna_header || '#3461ff');
                    $('#editTutupKasirEmailEnabled').val(response.tutup_kasir_email_enabled ? '1' : '0');
                    $('#editTutupKasirEmailRecipients').val(formatEmailsForTextarea(response.tutup_kasir_email_recipients || []));
                    $('#editStatus').val(response.status ? '1' : '0');
                    renderMetodePembayaranOptions('#editMetodePembayaranIds', response.metode_pembayaran_ids || []);

                    $('#editModal').modal('show');
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memuat data cabang',
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
                perusahaan_id: $('#editPerusahaanId').val(),
                nama: $('#editNama').val(),
                alamat: $('#editAlamat').val(),
                no_hp: $('#editNoHp').val(),
                allow_minus_stock: $('#editAllowMinusStock').val(),
                struk_footer: $('#editStrukFooter').val(),
                warna_header: $('#editWarnaHeader').val().trim() || null,
                tutup_kasir_email_enabled: $('#editTutupKasirEmailEnabled').val(),
                tutup_kasir_email_recipients: parseEmails($('#editTutupKasirEmailRecipients').val()),
                status: $('#editStatus').val(),
                metode_pembayaran_ids: $('#editMetodePembayaranIds').val() || [],
                sales_modes: ambilSalesModesDariContainer('#editSalesModeContainer')
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
                            text: 'Gagal memperbarui cabang',
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
                text: 'Anda akan menghapus cabang ini',
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
                                text: 'Gagal menghapus cabang',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('change', '.sales-mode-checkbox', function() {
            const row = $(this).closest('tr');
            const templateSelect = row.find('.sales-mode-template');
            if ($(this).is(':checked')) {
                templateSelect.prop('disabled', false);
            } else {
                templateSelect.val('').prop('disabled', true);
            }
        });
    });
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
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        min-height: 38px;
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
