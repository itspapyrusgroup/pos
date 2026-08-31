@extends('layouts.app')

@section('title', 'Buat Penerimaan Barang')

@push('styles')
<style>
    .po-form-page .select2-container,
    .penerimaan-form .select2-container {
        width: 100% !important;
    }

    .po-form-page .select2-container--bootstrap4 .select2-selection--single,
    .penerimaan-form .select2-container--bootstrap4 .select2-selection--single {
        min-height: 38px;
        background-color: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #ced4da);
    }

    .po-form-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered,
    .penerimaan-form .select2-container--bootstrap4 .select2-selection__single .select2-selection__rendered {
        color: var(--bs-body-color, #212529);
        line-height: 36px;
        padding-left: 0.75rem;
    }

    .po-form-page .select2-container--bootstrap4 .select2-selection__placeholder,
    .penerimaan-form .select2-container--bootstrap4 .select2-selection__placeholder {
        color: var(--bs-secondary-color, #6c757d);
    }

    .po-form-page .select2-container--bootstrap4 .select2-selection__arrow,
    .penerimaan-form .select2-container--bootstrap4 .select2-selection__arrow {
        height: 36px;
    }

    .po-form-page .select2-container--bootstrap4 .select2-dropdown,
    .po-form-page .select2-container--bootstrap4 .select2-search--dropdown .select2-search__field,
    .po-form-page .select2-container--bootstrap4 .select2-results > .select2-results__options,
    .penerimaan-form .select2-container--bootstrap4 .select2-dropdown,
    .penerimaan-form .select2-container--bootstrap4 .select2-search--dropdown .select2-search__field,
    .penerimaan-form .select2-container--bootstrap4 .select2-results > .select2-results__options {
        background-color: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
    }

    .po-form-page .select2-container--bootstrap4 .select2-results__option,
    .penerimaan-form .select2-container--bootstrap4 .select2-results__option {
        color: var(--bs-body-color, #212529);
    }

    .po-form-page .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected],
    .po-form-page .select2-container--bootstrap4 .select2-results__option--highlighted[data-selected],
    .penerimaan-form .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected],
    .penerimaan-form .select2-container--bootstrap4 .select2-results__option--highlighted[data-selected] {
        background-color: var(--bs-primary, #0d6efd);
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="penerimaan-form">
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('pembelian.penerimaan') }}">Penerimaan Barang</a></li>
                <li class="breadcrumb-item active" aria-current="page">Buat</li>
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

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('pembelian.penerimaan.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">No Penerimaan</label>
                    <input type="text" class="form-control" value="{{ $nomorPenerimaan }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">No PO</label>
                    <select class="form-select js-po-select" name="pesanan_pembelian_id" id="pesanan_pembelian_id" data-placeholder="Cari PO..." required>
                        <option value="">Pilih PO</option>
                        @foreach($poList as $po)
                            <option value="{{ $po->id }}">{{ $po->nomor_po }} - {{ $po->pemasok->nama ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Penerimaan</label>
                    <input type="date" class="form-control" name="tanggal_penerimaan" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">No Surat Jalan <span class="text-muted">(Opsional)</span></label>
                    <input type="text" class="form-control" name="nomor_surat_jalan" placeholder="Masukkan nomor surat jalan">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" name="catatan" rows="2"></textarea>
                </div>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-bordered" id="tabel-item">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th width="120">Qty PO</th>
                            <th width="140">Sisa</th>
                            <th width="160">Qty Terima</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Simpan</button>
                <a href="{{ route('pembelian.penerimaan') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    const poPayload = @json($poPayload);

    // Initialize PO Select with Select2
    (function initSelects() {
        const setup = function () {
            if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') {
                return false;
            }

            const $ = window.jQuery;
            const $poSelect = $('.js-po-select');

            if ($poSelect.hasClass('select2-hidden-accessible')) {
                $poSelect.select2('destroy');
            }

            $poSelect.select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: $poSelect.data('placeholder') || 'Pilih PO',
            });

            $poSelect.on('change', function () {
                const tbody = document.querySelector('#tabel-item tbody');
                tbody.innerHTML = '';
                const items = poPayload[this.value] || [];
                items.forEach((item) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            ${item.produk_nama}
                            <input type="hidden" name="po_item_id[]" value="${item.id}">
                        </td>
                        <td>${item.qty_po}</td>
                        <td>${item.qty_sisa}</td>
                        <td><input type="number" step="0.01" min="0" max="${item.qty_sisa}" class="form-control" name="qty_terima[]" value="0"></td>
                        <td><input type="text" class="form-control" name="catatan_item[]"></td>
                    `;
                    tbody.appendChild(row);
                });
            });

            return true;
        };

        let tries = 0;
        const timer = setInterval(function () {
            tries++;
            if (setup() || tries >= 30) {
                clearInterval(timer);
            }
        }, 120);
    })();
</script>
@endpush
