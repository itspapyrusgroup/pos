@extends('layouts.app')

@section('title', 'Edit Pesanan Pembelian')

@push('styles')
<style>
    .po-form-page .select2-container {
        width: 100% !important;
    }

    .po-form-page .select2-container--bootstrap4 .select2-selection--single {
        min-height: 38px;
        background-color: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #ced4da);
    }

    .po-form-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color, #212529);
        line-height: 36px;
        padding-left: 0.75rem;
    }

    .po-form-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        color: var(--bs-secondary-color, #6c757d);
    }

    .po-form-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    .po-form-page .select2-container--bootstrap4 .select2-dropdown,
    .po-form-page .select2-container--bootstrap4 .select2-search--dropdown .select2-search__field,
    .po-form-page .select2-container--bootstrap4 .select2-results > .select2-results__options {
        background-color: var(--bs-body-bg, #fff);
        color: var(--bs-body-color, #212529);
    }

    .po-form-page .select2-container--bootstrap4 .select2-results__option {
        color: var(--bs-body-color, #212529);
    }

    .po-form-page .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected],
    .po-form-page .select2-container--bootstrap4 .select2-results__option--highlighted[data-selected] {
        background-color: var(--bs-primary, #0d6efd);
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="po-form-page">
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('pembelian.pesanan') }}">Pesanan Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit PO</li>
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
        <form method="POST" action="{{ route('pembelian.pesanan.update', $po) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nomor PO</label>
                    <input type="text" class="form-control" value="{{ $po->nomor_po }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal PO</label>
                    <input type="date" class="form-control" name="tanggal_po" value="{{ old('tanggal_po', $po->tanggal_po) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Kirim</label>
                    <input type="date" class="form-control" name="tanggal_kirim" value="{{ old('tanggal_kirim', $po->tanggal_kirim) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pemasok</label>
                    <select class="form-select js-pemasok-select" name="pemasok_id" data-placeholder="Pilih Pemasok" required>
                        @foreach($pemasokList as $pemasok)
                            <option value="{{ $pemasok->id }}" {{ (string) $po->pemasok_id === (string) $pemasok->id ? 'selected' : '' }}>{{ $pemasok->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cabang</label>
                    <select class="form-select js-cabang-select" name="cabang_id" data-placeholder="Pilih Cabang" required>
                        @foreach($cabangList as $cabang)
                            <option value="{{ $cabang->id }}" {{ (string) $po->cabang_id === (string) $cabang->id ? 'selected' : '' }}>{{ $cabang->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dari Permintaan (Opsional)</label>
                    <select class="form-select js-permintaan-select" name="permintaan_barang_id" id="permintaan_barang_id" data-placeholder="Cari permintaan...">
                        <option value="">Tanpa Permintaan</option>
                        @foreach($permintaanList as $permintaan)
                            <option value="{{ $permintaan->id }}" {{ (string) $po->permintaan_barang_id === (string) $permintaan->id ? 'selected' : '' }}>
                                {{ $permintaan->nomor_permintaan }} - {{ $permintaan->cabang->nama ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" name="catatan" rows="2">{{ old('catatan', $po->catatan) }}</textarea>
                </div>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-bordered" id="tabel-item">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th width="120">Qty</th>
                            <th width="180">Harga</th>
                            <th>Catatan</th>
                            <th width="60"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($po->items as $item)
                            <tr>
                                <td>
                                    <select class="form-select produk-select" name="produk_id[]">
                                        <option value="">Pilih Produk</option>
                                        @foreach($produkList as $produk)
                                            <option value="{{ $produk->id }}" {{ (string) $item->produk_id === (string) $produk->id ? 'selected' : '' }}>
                                                {{ $produk->kode }} - {{ $produk->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0.01" class="form-control" name="qty[]" value="{{ (float) $item->qty }}"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control" name="harga[]" value="{{ (float) $item->harga }}"></td>
                                <td><input type="text" class="form-control" name="catatan_item[]" value="{{ $item->catatan }}"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger btn-hapus-item" {{ $loop->count === 1 ? 'disabled' : '' }}>Hapus</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-tambah-item">Tambah Item</button>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('pembelian.pesanan') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    const produkOptionsJson = {!! $produkOptionsHtml !!};
    const URL_PERMINTAAN_OPTIONS = "{{ route('pembelian.pesanan.permintaan-options') }}";
    const URL_PERMINTAAN_SHOW_BASE = "{{ url('/pembelian/pesanan/permintaan') }}";
    const EDIT_PO_PERMINTAAN_ID = {{ (int) ($po->permintaan_barang_id ?? 0) }};

    function buildProdukOptionsHtml() {
        if (!Array.isArray(produkOptionsJson)) return '<option value="">Pilih Produk</option>';
        return '<option value="">Pilih Produk</option>' +
            produkOptionsJson.map(function(p) {
                return '<option value="' + p.id + '">' + $('<div>').text(p.text).html() + '</option>';
            }).join('');
    }

    function appendItemRow(data = {}) {
        const tbody = document.querySelector('#tabel-item tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select class="form-select produk-select" name="produk_id[]">
                    ${buildProdukOptionsHtml()}
                </select>
            </td>
            <td><input type="number" step="0.01" min="0.01" class="form-control" name="qty[]"></td>
            <td><input type="number" step="0.01" min="0" class="form-control" name="harga[]" value="0"></td>
            <td><input type="text" class="form-control" name="catatan_item[]"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger btn-hapus-item">Hapus</button></td>
        `;
        tbody.appendChild(row);
        if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
            window.jQuery(row).find('select.produk-select').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Pilih Produk',
            });
        }

        if (data.produk_id) {
            const produkSelect = row.querySelector('[name="produk_id[]"]');
            produkSelect.value = String(data.produk_id);
            if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
                window.jQuery(produkSelect).trigger('change');
            }
        }
        if (data.qty) row.querySelector('[name="qty[]"]').value = String(data.qty);
        if (data.catatan) row.querySelector('[name="catatan_item[]"]').value = data.catatan;
    }

    function fillRowsFromPermintaanItems(items = []) {
        if (!Array.isArray(items) || !items.length) return;

        const tbody = document.querySelector('#tabel-item tbody');
        tbody.innerHTML = '';
        items.forEach((item) => appendItemRow(item));
    }

    async function loadPermintaanItems(permintaanId) {
        if (!permintaanId) return;

        const response = await fetch(`${URL_PERMINTAAN_SHOW_BASE}/${permintaanId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Gagal mengambil data permintaan.');
        }

        return response.json();
    }

    document.getElementById('btn-tambah-item').addEventListener('click', function () {
        appendItemRow();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('btn-hapus-item')) return;
        const tbody = document.querySelector('#tabel-item tbody');
        if (tbody.querySelectorAll('tr').length === 1) return;
        e.target.closest('tr').remove();
    });

    (function initPoSelects() {
        const setup = function () {
            if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') {
                return false;
            }

            const $ = window.jQuery;
            const $pemasok = $('.js-pemasok-select');
            const $cabang = $('.js-cabang-select');
            const $permintaan = $('.js-permintaan-select');
            const baseConfig = { theme: 'bootstrap4', width: '100%' };

            const initOne = function ($el, config) {
                if (!$el.length) return;
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.select2(Object.assign({}, baseConfig, config || {}));
            };

            const initProdukSelects = function (scope) {
                const $scope = scope ? $(scope) : $(document);
                $scope.find('select.produk-select').each(function () {
                    initOne($(this), { placeholder: 'Pilih Produk' });
                });
            };

            initOne($pemasok, { placeholder: $pemasok.data('placeholder') || 'Pilih Pemasok' });
            initOne($cabang, { placeholder: $cabang.data('placeholder') || 'Pilih Cabang' });
            initOne($permintaan, {
                placeholder: $permintaan.data('placeholder') || 'Cari permintaan...',
                allowClear: true,
                ajax: {
                    url: URL_PERMINTAAN_OPTIONS,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            selected_id: EDIT_PO_PERMINTAAN_ID,
                        };
                    },
                    processResults: function (data) {
                        return { results: Array.isArray(data?.results) ? data.results : [] };
                    },
                    cache: false,
                },
            });

            $permintaan.off('change.po').on('change.po', async function () {
                const permintaanId = this.value;
                if (!permintaanId) return;

                try {
                    const payload = await loadPermintaanItems(permintaanId);
                    fillRowsFromPermintaanItems(payload.items || []);
                } catch (error) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', error.message || 'Tidak bisa memuat item permintaan.', 'error');
                    }
                }
            });

            initProdukSelects(document);
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
