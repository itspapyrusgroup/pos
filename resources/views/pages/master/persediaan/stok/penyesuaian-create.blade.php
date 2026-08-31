@extends('layouts.app')

@section('title', 'Tambah Penyesuaian Stok')

@push('styles')
<style>
    .stok-penyesuaian-page .select2-container--default .select2-selection--single,
    .select2-container--default.select2-stok-penyesuaian .select2-selection--single {
        min-height: 38px;
        border-color: var(--bs-border-color, #ced4da);
        background-color: var(--bs-body-bg, #fff);
    }

    .stok-penyesuaian-page .select2-container--default .select2-selection--single .select2-selection__rendered,
    .select2-container--default.select2-stok-penyesuaian .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color, #212529);
        line-height: 36px;
    }

    .stok-penyesuaian-page .select2-container--default .select2-selection--single .select2-selection__placeholder,
    .select2-container--default.select2-stok-penyesuaian .select2-selection--single .select2-selection__placeholder {
        color: var(--bs-secondary-color, #6c757d);
    }

    .stok-penyesuaian-page .select2-container--default .select2-selection--single .select2-selection__arrow,
    .select2-container--default.select2-stok-penyesuaian .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    .select2-dropdown.select2-stok-penyesuaian,
    .select2-dropdown.select2-stok-penyesuaian .select2-search--dropdown .select2-search__field,
    .select2-dropdown.select2-stok-penyesuaian .select2-results > .select2-results__options {
        background-color: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
    }

    .select2-dropdown.select2-stok-penyesuaian .select2-results__option {
        color: var(--bs-body-color, #212529);
    }

    .select2-dropdown.select2-stok-penyesuaian .select2-results__option--highlighted[aria-selected],
    .select2-dropdown.select2-stok-penyesuaian .select2-results__option--highlighted[data-selected] {
        background-color: var(--bs-primary, #0d6efd);
        color: #fff;
    }

    .select2-dropdown.select2-stok-penyesuaian .select2-results__option[aria-selected=true],
    .select2-dropdown.select2-stok-penyesuaian .select2-results__option[data-selected=true] {
        background-color: rgba(13, 110, 253, 0.14);
        color: var(--bs-body-color, #212529);
    }

    .select2-dropdown.select2-stok-penyesuaian {
        border-color: var(--bs-border-color, #ced4da);
    }
</style>
@endpush

@section('content')
<div class="stok-penyesuaian-page">
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('persediaan.stok.penyesuaian') }}">Penyesuaian Stok</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah</li>
            </ol>
        </nav>
    </div>
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

<form method="POST" action="{{ route('persediaan.stok.penyesuaian.store') }}" id="penyesuaianForm">
    @csrf

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cabang</label>
                    <select class="form-select" name="cabang_id" id="cabangId" required>
                        @foreach($cabangList as $cabang)
                            <option value="{{ $cabang->id }}" {{ (string) old('cabang_id', $selectedCabangId) === (string) $cabang->id ? 'selected' : '' }}>
                                {{ $cabang->kode }} - {{ $cabang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Penyesuaian</label>
                    <input type="date" class="form-control" name="tanggal_penyesuaian" id="tanggalPenyesuaian" value="{{ old('tanggal_penyesuaian', $selectedTanggal) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Catatan</label>
                    <input type="text" class="form-control" name="catatan" value="{{ old('catatan') }}" placeholder="Opsional">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light">
            <strong>Pilih Produk</strong>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label class="form-label">Cari Barang/Jasa (track stok aktif)</label>
                    <select id="produkPicker" class="form-select"></select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="button" class="btn btn-outline-primary" id="btnTambahProduk">Tambah</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong>Item Penyesuaian</strong>
            <span class="text-muted small">Isi stok akhir sesuai hasil stok opname.</span>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle" id="tabelPenyesuaian">
                <thead class="table-light">
                    <tr>
                        <th width="40">#</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Satuan</th>
                        <th>Golongan</th>
                        <th class="text-end">On Hand</th>
                        <th class="text-end">On Order</th>
                        <th class="text-end">Tersedia</th>
                        <th class="text-end" style="min-width:220px;">Stok Setelah</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody id="penyesuaianBody">
                    @foreach($selectedRows as $idx => $row)
                        <tr data-produk-id="{{ $row['id'] }}">
                            <td class="row-no">{{ $idx + 1 }}</td>
                            <td>{{ $row['kode'] }}</td>
                            <td>{{ $row['nama'] }}</td>
                            <td>{{ $row['satuan'] ?? '-' }}</td>
                            <td>{{ $row['kategori'] }}</td>
                            <td class="text-end stok-sebelum">{{ number_format((float) $row['stok_eksisting'], 0, '.', '') }}</td>
                            <td class="text-end stok-on-order">{{ number_format((float) ($row['stok_on_order'] ?? 0), 0, '.', '') }}</td>
                            <td class="text-end stok-tersedia">{{ number_format((float) $row['stok_eksisting'] - (float) ($row['stok_on_order'] ?? 0), 0, '.', '') }}</td>
                            <td>
                                <input type="number" step="1" min="0" class="form-control text-end" name="target_qty[{{ $row['id'] }}]" value="{{ (int) $row['target_qty'] }}">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-item">Hapus</button>
                            </td>
                        </tr>
                    @endforeach
                    @if(empty($selectedRows))
                        <tr id="emptyRow">
                            <td colspan="10" class="text-center text-muted">Belum ada produk dipilih.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">Simpan Penyesuaian</button>
            <a href="{{ route('persediaan.stok.penyesuaian') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const picker = $('#produkPicker');
        const cabangInput = document.getElementById('cabangId');
        const tanggalInput = document.getElementById('tanggalPenyesuaian');
        const body = document.getElementById('penyesuaianBody');
        const emptyRowId = 'emptyRow';

        function formatNumber(value) {
            const number = Number(value || 0);
            return String(Math.round(number));
        }

        function renumberRows() {
            let i = 1;
            body.querySelectorAll('tr[data-produk-id]').forEach(function (row) {
                const noCell = row.querySelector('.row-no');
                if (noCell) noCell.textContent = String(i++);
            });
        }

        function ensureEmptyState() {
            const hasRows = body.querySelector('tr[data-produk-id]') !== null;
            const emptyRow = document.getElementById(emptyRowId);
            if (hasRows && emptyRow) emptyRow.remove();
            if (!hasRows && !emptyRow) {
                const tr = document.createElement('tr');
                tr.id = emptyRowId;
                tr.innerHTML = '<td colspan="10" class="text-center text-muted">Belum ada produk dipilih.</td>';
                body.appendChild(tr);
            }
            renumberRows();
        }

        function addRow(item) {
            const existing = body.querySelector('tr[data-produk-id="' + item.id + '"]');
            if (existing) {
                const qtyInput = existing.querySelector('input[name="target_qty[' + item.id + ']"]');
                if (qtyInput) qtyInput.focus();
                return;
            }

            const tr = document.createElement('tr');
            tr.dataset.produkId = String(item.id);
            tr.innerHTML = '' +
                '<td class="row-no"></td>' +
                '<td>' + item.kode + '</td>' +
                '<td>' + item.nama + '</td>' +
                '<td>' + (item.satuan || '-') + '</td>' +
                '<td>' + (item.kategori || '-') + '</td>' +
                '<td class="text-end stok-sebelum">' + formatNumber(item.stok_eksisting) + '</td>' +
                '<td class="text-end stok-on-order">' + formatNumber(item.stok_on_order || 0) + '</td>' +
                '<td class="text-end stok-tersedia">' + formatNumber((Number(item.stok_eksisting || 0) - Number(item.stok_on_order || 0))) + '</td>' +
                '<td><input type="number" step="1" min="0" class="form-control text-end" name="target_qty[' + item.id + ']" value="' + Math.round(Number(item.stok_eksisting || 0)) + '"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger btn-hapus-item">Hapus</button></td>';
            body.appendChild(tr);
            ensureEmptyState();
        }

        picker.on('select2:open', function () {
            document.querySelectorAll('.select2-container--open, .select2-dropdown').forEach(function (el) {
                el.classList.add('select2-stok-penyesuaian');
            });
        });

        picker.select2({
            width: '100%',
            placeholder: 'Ketik nama/kode produk...',
            minimumInputLength: 1,
            ajax: {
                url: '{{ route('persediaan.stok.penyesuaian.produk-cari') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        cabang_id: cabangInput.value,
                        tanggal: tanggalInput.value
                    };
                },
                processResults: function (data) {
                    const results = (data.results || []).map(function (item) {
                        return {
                            id: item.id,
                            text: item.kode + ' - ' + item.nama + ' | Sat: ' + (item.satuan || '-') + ' | On Hand: ' + formatNumber(item.stok_eksisting) + ' | On Order: ' + formatNumber(item.stok_on_order || 0),
                            raw: item
                        };
                    });
                    return { results: results };
                }
            }
        });

        document.getElementById('btnTambahProduk').addEventListener('click', function () {
            const selectedData = picker.select2('data');
            if (!selectedData || !selectedData.length || !selectedData[0].raw) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi',
                        text: 'Pilih produk dulu.',
                        confirmButtonText: 'OK',
                    });
                }
                return;
            }
            addRow(selectedData[0].raw);
            picker.val(null).trigger('change');
        });

        body.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-hapus-item');
            if (!btn) return;
            const row = btn.closest('tr[data-produk-id]');
            if (!row) return;
            row.remove();
            ensureEmptyState();
        });

        [cabangInput, tanggalInput].forEach(function (el) {
            el.addEventListener('change', function () {
                picker.val(null).trigger('change');
            });
        });

        ensureEmptyState();
    })();
</script>
@endpush
