@extends('layouts.app')

@section('title', 'Input Order')

@push('styles')
    <style>
        .input-order-page {
            overflow-x: clip;
        }

        .input-order-page .row {
            margin-right: 0;
            margin-left: 0;
        }

        .input-order-page .row > [class*="col-"] {
            padding-right: calc(var(--bs-gutter-x) * .5);
            padding-left: calc(var(--bs-gutter-x) * .5);
        }

        .input-order-page .select2-container {
            max-width: 100% !important;
        }

        .input-order-page .select2-container--bootstrap4 .select2-selection--single {
            min-height: 38px;
            border-radius: .375rem;
        }

        .input-order-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: .75rem;
            padding-right: 2rem;
        }

        .input-order-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: .4rem;
        }

        .input-order-page .select2-dropdown {
            z-index: 1060;
        }

        .input-order-page .required-mark {
            color: #dc3545;
            font-weight: 700;
            margin-left: .15rem;
        }

        .input-order-page #search-results-dropdown {
            max-width: 100%;
        }

        .ko-badge-display {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            color: #fff;
            display: inline-block;
        }
    </style>
@endpush

@section('content')
<div class="input-order-page">
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">POS</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Input Order</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <strong>Cabang Aktif:</strong> {{ $activeCabang?->nama ?? '-' }}
            <span class="badge bg-primary ms-2">Mode: Input Order (Tanpa Pembayaran Kasir)</span>
        </div>
        @if(($cabangTersedia ?? collect())->count() > 1)
            <form method="POST" action="{{ route('active-cabang.update') }}" class="d-flex align-items-center gap-2">
                @csrf
                <label class="mb-0 small">Switch Cabang:</label>
                <select name="active_cabang_id" class="form-select form-select-sm">
                    @foreach($cabangTersedia as $cabangOption)
                        <option value="{{ $cabangOption->id }}" @selected((int) $cabangOption->id === (int) ($cabangDefaultId ?? 0))>
                            {{ $cabangOption->nama }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
            </form>
        @endif
    </div>

    <div class="row g-3">
        {{-- Left Column: Form Info Customer & Input Items --}}
        <div class="col-lg-8">
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-1"></i> Data Customer & Order</h6>
                    <small class="text-muted"><span class="required-mark">*</span> Wajib diisi</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No KO</label>
                            <div class="input-group">
                                <input type="text" id="no_ko" class="form-control"
                                    placeholder="Kosongkan untuk No KO otomatis baru">
                                <button type="button" id="btn-check-ko" class="btn btn-outline-primary">
                                    <i class="bi bi-search"></i> Cek
                                </button>
                            </div>
                            <small class="text-muted">Isi jika ingin menambahkan item ke KO yang sudah ada.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sales Mode <span class="required-mark">*</span></label>
                            <select id="sales_mode_id" class="form-select">
                                <option value="">Pilih Sales Mode</option>
                                @foreach($salesModesCabang as $mode)
                                    <option value="{{ $mode['sales_mode_id'] }}">{{ $mode['nama'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nama Customer <span class="required-mark">*</span></label>
                            <input type="text" id="customer_name" class="form-control" placeholder="Nama customer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No HP <span class="required-mark">*</span></label>
                            <input type="text" id="phone" class="form-control" placeholder="08xxxxxxxxxx" inputmode="numeric" autocomplete="tel" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alamat</label>
                            <input type="text" id="address" class="form-control" placeholder="Alamat customer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Keterangan / Catatan</label>
                            <input type="text" id="order_note" class="form-control" placeholder="Catatan khusus pesanan">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">CS</label>
                            <select id="cs_user_id" class="form-select single-select w-100" data-placeholder="Pilih CS">
                                <option value="">- Pilih CS -</option>
                                @foreach($csCandidates as $userOption)
                                    <option value="{{ $userOption->id }}">
                                        {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CS 1</label>
                            <select id="cs1_user_id" class="form-select single-select w-100" data-placeholder="Pilih CS 1">
                                <option value="">- Pilih CS 1 -</option>
                                @foreach($csCandidates as $userOption)
                                    <option value="{{ $userOption->id }}">
                                        {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CS 2</label>
                            <select id="cs2_user_id" class="form-select single-select w-100" data-placeholder="Pilih CS 2">
                                <option value="">- Pilih CS 2 -</option>
                                @foreach($csCandidates as $userOption)
                                    <option value="{{ $userOption->id }}">
                                        {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SPV</label>
                            <select id="spv_user_id" class="form-select single-select w-100" data-placeholder="Pilih SPV">
                                <option value="">- Pilih SPV -</option>
                                @foreach($spvCandidates as $userOption)
                                    <option value="{{ $userOption->id }}">
                                        {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fotografer</label>
                            <select id="fotografer_user_id" class="form-select single-select w-100" data-placeholder="Pilih Fotografer">
                                <option value="">- Pilih Fotografer -</option>
                                @foreach($fotograferCandidates as $userOption)
                                    <option value="{{ $userOption->id }}">
                                        {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Transaksi</label>
                            <input type="date" id="tanggal_transaksi" class="form-control"
                                value="{{ now()->format('Y-m-d') }}"
                                @readonly(!$canTransaksiBackdate)>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Selesai KO</label>
                            <input type="date" id="tanggal_selesai" class="form-control" min="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4 pt-2">
                                <input class="form-check-input" type="checkbox" id="is_booking">
                                <label class="form-check-label fw-bold" for="is_booking">Booking Studio</label>
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="booking_date_container">
                            <label class="form-label">Tanggal Booking <span class="required-mark">*</span></label>
                            <input type="date" id="booking_date" class="form-control" min="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 d-none" id="booking_time_container">
                            <label class="form-label">Jam Booking <span class="required-mark">*</span></label>
                            <input type="time" id="booking_time" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Search & Cart Table --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam me-1"></i> Paket & Produk Pesanan</h6>
                </div>
                <div class="card-body">
                    <div class="position-relative mb-3">
                        <label class="form-label">Cari Paket / Produk / Addon</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="product_search" class="form-control"
                                placeholder="Ketik nama atau kode paket/produk...">
                        </div>
                        <div id="search-results-dropdown"
                            class="list-group position-absolute w-100 shadow d-none"
                            style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle table-hover mb-0" id="cart-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Item</th>
                                    <th style="width: 100px;">Tipe</th>
                                    <th style="width: 110px;">Qty</th>
                                    <th style="width: 130px;">Harga (Rp)</th>
                                    <th style="width: 120px;">Diskon (Rp)</th>
                                    <th style="width: 140px;">Subtotal (Rp)</th>
                                    <th style="width: 60px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="cart-body">
                                <tr id="cart-empty-row">
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-cart-x fs-2 d-block mb-1"></i>
                                        Belum ada item pesanan yang dipilih. Cari paket atau produk di atas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Summary & Simpan Order Button --}}
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-receipt-cutoff me-1"></i> Ringkasan Order</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Promosi / Diskon</label>
                        <select id="promo_select" class="form-select">
                            <option value="">- Tidak menggunakan promosi -</option>
                        </select>
                        <small class="text-muted d-block mt-1" id="promo-hint">Pilih item terlebih dahulu untuk melihat promo yang tersedia.</small>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Subtotal Item:</span>
                        <span class="fw-bold" id="summary-subtotal">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Diskon Promo:</span>
                        <span class="fw-bold" id="summary-promo-discount">- Rp 0</span>
                    </div>

                    <div class="p-3 bg-light rounded border my-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-6 fw-bold">Total Order:</span>
                            <span class="fs-4 fw-bold text-primary" id="summary-total">Rp 0</span>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Order ini disimpan berstatus <strong>Draft</strong> tanpa pembayaran kasir. Konsumen langsung mendapatkan <strong>No KO</strong> untuk masuk antrian studio.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" id="btn-simpan-order" class="btn btn-primary btn-lg shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Order
                        </button>
                        <button type="button" id="btn-reset-form" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Success Order Modal --}}
<div class="modal fade" id="modalOrderSuccess" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-3">
            <div class="modal-body">
                <div class="text-success mb-3">
                    <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                </div>
                <h4 class="fw-bold mb-2">Order Berhasil Disimpan!</h4>
                <p class="text-muted mb-3" id="modal-success-message">Data order transaksi telah disimpan.</p>

                <div class="p-3 bg-light rounded border mb-3 text-start">
                    <div class="text-center mb-3">
                        <div class="small text-muted mb-1">Nomor Kantong Order (No KO)</div>
                        <div class="ko-badge-display" id="modal-success-ko">-</div>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Nomor SO:</span>
                        <span class="fw-bold" id="modal-success-so">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Customer:</span>
                        <span class="fw-bold" id="modal-success-customer">-</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Total Tagihan:</span>
                        <span class="fw-bold text-primary" id="modal-success-total">Rp 0</span>
                    </div>
                </div>

                <div class="alert alert-info small py-2 mb-3">
                    <i class="bi bi-lightbulb me-1"></i> Gunakan No KO di atas untuk memasukkan konsumen ke antrian studio foto.
                </div>

                <div class="d-grid gap-2">
                    <a href="#" id="modal-btn-queue" class="btn btn-success btn-lg">
                        <i class="bi bi-people me-1"></i> Masuk ke Antrian Studio
                    </a>
                    <button type="button" id="modal-btn-new-order" class="btn btn-outline-primary" data-bs-dismiss="modal">
                        <i class="bi bi-plus-circle me-1"></i> Input Order Baru
                    </button>
                    <button type="button" class="btn btn-link text-muted btn-sm" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPriceOverrideAuth" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark py-2">
                <h6 class="modal-title fw-bold mb-0"><i class="bi bi-shield-lock me-1"></i> Otorisasi Perubahan Harga</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Terdapat perbedaan harga dari master/template harga. Diperlukan otorisasi Supervisor / Manager untuk melanjutkan simpan order.
                </div>
                <div class="table-responsive mb-3" style="max-height: 180px; overflow-y: auto;">
                    <table class="table table-sm table-bordered mb-0 small" id="override-price-items-table">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Harga Master</th>
                                <th class="text-end">Harga Baru</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Username / Email Otorisator <span class="text-danger">*</span></label>
                    <input type="text" id="auth_override_username" class="form-control form-control-sm" placeholder="Username / Email SPV / Manager" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Password Otorisator <span class="text-danger">*</span></label>
                    <input type="password" id="auth_override_password" class="form-control form-control-sm" placeholder="Password" autocomplete="off">
                </div>
                <div id="auth_override_error" class="alert alert-danger py-1 small d-none"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btn-submit-price-override" class="btn btn-primary btn-sm">
                    <i class="bi bi-check2-circle me-1"></i> Setujui & Lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        const CABANG_ID = parseInt(@json($cabangDefaultId ?? 0), 10);
        const SEARCH_PRODUK_URL = @json(route('input-order.produk-cari'));
        const CEK_KO_URL = @json(route('input-order.cek-ko'));
        const PROMO_URL = @json(route('input-order.promosi-tersedia'));
        const SIMPAN_ORDER_URL = @json(route('input-order.simpan'));
        const URL_AUTHORIZE_PRICE_OVERRIDE = @json(route('pos.authorize-price-override'));
        const CAN_OVERRIDE_PRICE = @json((bool) auth()->user()?->hasPermission('pos.transaksi.override_price'));

        let cartItems = [];
        let availablePromos = [];
        let selectedPromo = null;
        let searchDebounce = null;
        let promoDebounce = null;
        let isSubmitting = false;
        let priceOverrideAuthorizerId = null;
        let searchProductsData = [];

        $('.single-select').select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true
        });

        function formatRupiah(num) {
            const val = Math.round(Number(num) || 0);
            return 'Rp ' + val.toLocaleString('id-ID');
        }

        function generateUUID() {
            return 'req-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        }

        // Toggle Booking
        $('#is_booking').on('change', function () {
            if ($(this).is(':checked')) {
                $('#booking_date_container, #booking_time_container').removeClass('d-none');
            } else {
                $('#booking_date_container, #booking_time_container').addClass('d-none');
                $('#booking_date, #booking_time').val('');
            }
        });

        // Search Product/Package
        $('#product_search').on('input', function () {
            clearTimeout(searchDebounce);
            const q = $(this).val().trim();
            const salesModeId = $('#sales_mode_id').val();

            if (q.length < 1) {
                $('#search-results-dropdown').addClass('d-none').empty();
                searchProductsData = [];
                return;
            }

            searchDebounce = setTimeout(function () {
                $.ajax({
                    url: SEARCH_PRODUK_URL,
                    type: 'GET',
                    data: {
                        q: q,
                        cabang_id: CABANG_ID,
                        sales_mode_id: salesModeId
                    },
                    success: function (res) {
                        const dropdown = $('#search-results-dropdown');
                        dropdown.empty();
                        searchProductsData = res || [];

                        if (!searchProductsData || searchProductsData.length === 0) {
                            dropdown.append('<div class="list-group-item text-muted small py-2">Tidak ditemukan item dengan kata kunci tersebut.</div>');
                        } else {
                            searchProductsData.forEach(function (item, sIdx) {
                                const badgeClass = item.tipe === 'PAKET' ? 'bg-primary' : 'bg-success';
                                const html = `
                                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center btn-select-item"
                                        data-search-idx="${sIdx}">
                                        <div>
                                            <span class="badge ${badgeClass} me-2">${item.tipe}</span>
                                            <strong>${item.nama}</strong>
                                            ${item.kode ? `<small class="text-muted ms-1">(${item.kode})</small>` : ''}
                                        </div>
                                        <div class="fw-bold text-primary">
                                            ${formatRupiah(item.harga_default)}
                                        </div>
                                    </button>
                                `;
                                dropdown.append(html);
                            });
                        }

                        dropdown.removeClass('d-none');
                    },
                    error: function () {
                        $('#search-results-dropdown').addClass('d-none').empty();
                        searchProductsData = [];
                    }
                });
            }, 300);
        });

        // Close dropdown on click outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#product_search, #search-results-dropdown').length) {
                $('#search-results-dropdown').addClass('d-none');
            }
        });

        // Select item from search dropdown
        $(document).on('click', '.btn-select-item', function () {
            const sIdx = parseInt($(this).data('search-idx'), 10);
            const itemRaw = searchProductsData[sIdx];
            if (!itemRaw) return;

            const item = {
                id: itemRaw.id,
                tipe: itemRaw.tipe,
                kode: itemRaw.kode || '',
                nama: itemRaw.nama,
                harga: parseFloat(itemRaw.harga_default) || 0,
                original_harga: parseFloat(itemRaw.harga_default) || 0,
                qty: 1,
                diskon: 0,
                expanded: false,
                is_customized: false,
                custom_paket_items: itemRaw.tipe === 'PAKET' && Array.isArray(itemRaw.items)
                    ? JSON.parse(JSON.stringify(itemRaw.items))
                    : []
            };

            addItemToCart(item);
            $('#product_search').val('');
            $('#search-results-dropdown').addClass('d-none').empty();
        });

        function addItemToCart(newItem) {
            const existing = cartItems.find(i => i.tipe === newItem.tipe && i.id === newItem.id);
            if (existing && existing.tipe !== 'PAKET') {
                existing.qty += 1;
            } else {
                cartItems.push(newItem);
            }
            renderCart();
            fetchPromotions();
        }

        function renderSubitemRowsHtml(items, parentIdx) {
            if (!items || !items.length) {
                return `<tr><td colspan="3" class="text-center text-muted py-2 small">Paket tidak memiliki item produk. Klik "Tambah Produk" di atas.</td></tr>`;
            }
            return items.map(function (sub, sIdx) {
                return `
                    <tr>
                        <td>
                            <span class="fw-semibold">${sub.nama || 'Produk'}</span>
                            ${sub.kode ? `<small class="text-muted ms-1">(${sub.kode})</small>` : ''}
                        </td>
                        <td width="100" class="text-center">
                            <input type="number" class="form-control form-control-sm text-center input-subitem-qty" min="0.01" step="any" value="${sub.qty}" data-parent-idx="${parentIdx}" data-sub-idx="${sIdx}">
                        </td>
                        <td width="60" class="text-center">
                            <button type="button" class="btn btn-xs btn-outline-danger btn-remove-subitem" data-parent-idx="${parentIdx}" data-sub-idx="${sIdx}" title="Hapus Produk dari Paket">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderCart() {
            const tbody = $('#cart-body');
            tbody.empty();

            if (cartItems.length === 0) {
                tbody.append(`
                    <tr id="cart-empty-row">
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-cart-x fs-2 d-block mb-1"></i>
                            Belum ada item pesanan yang dipilih. Cari paket atau produk di atas.
                        </td>
                    </tr>
                `);
            } else {
                cartItems.forEach(function (item, idx) {
                    const subtotal = Math.max((item.qty * item.harga) - (item.diskon || 0), 0);
                    const isPaket = item.tipe === 'PAKET';
                    const badgeClass = isPaket ? 'bg-primary' : 'bg-success';
                    const hasPriceOverride = Math.abs(item.harga - item.original_harga) > 0.0001;

                    let paketButtonHtml = '';
                    if (isPaket) {
                        const count = item.custom_paket_items ? item.custom_paket_items.length : 0;
                        paketButtonHtml = `
                            <div class="mt-1">
                                <button type="button" class="btn btn-xs btn-outline-info btn-toggle-paket-collapse" data-index="${idx}">
                                    <i class="bi bi-box-seam me-1"></i> Rincian Paket (<span class="paket-count">${count}</span>) <i class="bi bi-chevron-${item.expanded ? 'up' : 'down'} ms-1"></i>
                                </button>
                                <span class="badge bg-secondary badge-customized ${item.is_customized ? '' : 'd-none'} ms-1">Customized</span>
                            </div>
                        `;
                    }

                    const mainRowHtml = `
                        <tr>
                            <td class="text-center">${idx + 1}</td>
                            <td>
                                <div class="fw-bold">${item.nama}</div>
                                ${item.kode ? `<small class="text-muted">${item.kode}</small>` : ''}
                                ${hasPriceOverride ? '<span class="badge bg-warning text-dark ms-1"><i class="bi bi-pencil-square"></i> Harga Diubah</span>' : ''}
                                ${paketButtonHtml}
                            </td>
                            <td><span class="badge ${badgeClass}">${item.tipe}</span></td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-qty-minus" data-index="${idx}">-</button>
                                    <input type="number" class="form-control text-center input-item-qty" data-index="${idx}" value="${item.qty}" min="1">
                                    <button type="button" class="btn btn-outline-secondary btn-qty-plus" data-index="${idx}">+</button>
                                </div>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm input-item-harga" data-index="${idx}" value="${item.harga}" min="0">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm input-item-diskon" data-index="${idx}" value="${item.diskon}" min="0">
                            </td>
                            <td class="fw-bold text-end pe-3">
                                ${formatRupiah(subtotal)}
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-index="${idx}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;

                    let collapseRowHtml = '';
                    if (isPaket && item.expanded) {
                        collapseRowHtml = `
                            <tr class="bg-light">
                                <td colspan="8" class="p-3">
                                    <div class="card card-body border-0 shadow-none p-2 mb-0 bg-white rounded">
                                        <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                            <strong class="small text-primary"><i class="bi bi-boxes me-1"></i> Rincian Item Paket "${item.nama}"</strong>
                                            <button type="button" class="btn btn-xs btn-outline-primary btn-add-subitem" data-parent-idx="${idx}">
                                                <i class="bi bi-plus-circle me-1"></i> Tambah Produk
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0 align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Nama Produk</th>
                                                        <th width="100" class="text-center">Qty / Paket</th>
                                                        <th width="60" class="text-center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${renderSubitemRowsHtml(item.custom_paket_items, idx)}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }

                    tbody.append(mainRowHtml + collapseRowHtml);
                });
            }

            calculateSummary();
        }

        // Toggle Paket Collapse
        $(document).on('click', '.btn-toggle-paket-collapse', function () {
            const idx = $(this).data('index');
            if (cartItems[idx]) {
                cartItems[idx].expanded = !cartItems[idx].expanded;
                renderCart();
            }
        });

        // Subitem Qty Change
        $(document).on('change input', '.input-subitem-qty', function () {
            const parentIdx = $(this).data('parent-idx');
            const subIdx = $(this).data('sub-idx');
            const newQty = parseFloat($(this).val()) || 0;
            if (cartItems[parentIdx] && cartItems[parentIdx].custom_paket_items[subIdx]) {
                cartItems[parentIdx].custom_paket_items[subIdx].qty = newQty;
                cartItems[parentIdx].is_customized = true;
            }
        });

        // Remove Subitem from Package
        $(document).on('click', '.btn-remove-subitem', function () {
            const parentIdx = $(this).data('parent-idx');
            const subIdx = $(this).data('sub-idx');
            if (cartItems[parentIdx] && cartItems[parentIdx].custom_paket_items) {
                cartItems[parentIdx].custom_paket_items.splice(subIdx, 1);
                cartItems[parentIdx].is_customized = true;
                renderCart();
            }
        });

        // Add Subitem to Package
        $(document).on('click', '.btn-add-subitem', async function () {
            const parentIdx = $(this).data('parent-idx');
            if (!cartItems[parentIdx]) return;

            const { value: productQuery } = await Swal.fire({
                title: 'Cari Produk untuk Paket',
                input: 'text',
                inputPlaceholder: 'Ketik nama produk...',
                showCancelButton: true,
                confirmButtonText: 'Cari',
                cancelButtonText: 'Batal'
            });

            if (!productQuery) return;

            $.ajax({
                url: SEARCH_PRODUK_URL,
                data: { q: productQuery, cabang_id: CABANG_ID, sales_mode_id: $('#sales_mode_id').val() },
                success: function (res) {
                    const produkOnly = (res || []).filter(r => r.tipe === 'PRODUK');
                    if (!produkOnly.length) {
                        Swal.fire('Hasil', 'Tidak ada produk yang cocok.', 'info');
                        return;
                    }

                    let optionsHtml = produkOnly.map(p => `<option value="${p.id}" data-nama="${p.nama}" data-kode="${p.kode || ''}">${p.nama} (${p.kode || '-'})</option>`).join('');

                    Swal.fire({
                        title: 'Pilih Produk & Masukkan Qty',
                        html: `
                            <div class="mb-3 text-start">
                                <label class="form-label small fw-bold">Pilih Produk:</label>
                                <select id="swal-select-subproduct" class="form-select">${optionsHtml}</select>
                            </div>
                            <div class="mb-2 text-start">
                                <label class="form-label small fw-bold">Jumlah Qty per Paket:</label>
                                <input type="number" id="swal-subproduct-qty" class="form-control" value="1" min="0.01" step="any">
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Tambahkan ke Paket',
                        preConfirm: () => {
                            const $sel = $('#swal-select-subproduct option:selected');
                            const pid = parseInt($sel.val(), 10);
                            const pnama = $sel.data('nama');
                            const pkode = $sel.data('kode');
                            const pqty = parseFloat($('#swal-subproduct-qty').val()) || 1;
                            return { produk_id: pid, nama: pnama, kode: pkode, qty: pqty };
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            if (!cartItems[parentIdx].custom_paket_items) {
                                cartItems[parentIdx].custom_paket_items = [];
                            }
                            const existingSub = cartItems[parentIdx].custom_paket_items.find(s => s.produk_id === result.value.produk_id);
                            if (existingSub) {
                                existingSub.qty += result.value.qty;
                            } else {
                                cartItems[parentIdx].custom_paket_items.push(result.value);
                            }
                            cartItems[parentIdx].is_customized = true;
                            renderCart();
                        }
                    });
                }
            });
        });

        // Cart Item Events
        $(document).on('click', '.btn-qty-plus', function () {
            const idx = $(this).data('index');
            if (cartItems[idx]) {
                cartItems[idx].qty += 1;
                renderCart();
                fetchPromotions();
            }
        });

        $(document).on('click', '.btn-qty-minus', function () {
            const idx = $(this).data('index');
            if (cartItems[idx]) {
                if (cartItems[idx].qty > 1) {
                    cartItems[idx].qty -= 1;
                } else {
                    cartItems.splice(idx, 1);
                }
                renderCart();
                fetchPromotions();
            }
        });

        $(document).on('change', '.input-item-qty', function () {
            const idx = $(this).data('index');
            const val = parseInt($(this).val(), 10) || 1;
            if (cartItems[idx]) {
                cartItems[idx].qty = Math.max(val, 1);
                renderCart();
                fetchPromotions();
            }
        });

        $(document).on('change input', '.input-item-harga', function () {
            const idx = $(this).data('index');
            const val = parseFloat($(this).val()) || 0;
            if (cartItems[idx]) {
                cartItems[idx].harga = Math.max(val, 0);
                priceOverrideAuthorizerId = null;
                renderCart();
                fetchPromotions();
            }
        });

        $(document).on('change', '.input-item-diskon', function () {
            const idx = $(this).data('index');
            const val = parseFloat($(this).val()) || 0;
            if (cartItems[idx]) {
                cartItems[idx].diskon = Math.max(val, 0);
                renderCart();
                fetchPromotions();
            }
        });

        $(document).on('click', '.btn-remove-item', function () {
            const idx = $(this).data('index');
            if (cartItems[idx]) {
                cartItems.splice(idx, 1);
                renderCart();
                fetchPromotions();
            }
        });

        function calculateSummary() {
            let subtotal = 0;
            cartItems.forEach(function (item) {
                const itemSubtotal = Math.max((item.qty * item.harga) - (item.diskon || 0), 0);
                subtotal += itemSubtotal;
            });

            let promoDiscount = 0;
            if (selectedPromo) {
                promoDiscount = parseFloat(selectedPromo.diskon_hitung) || 0;
            }

            const total = Math.max(subtotal - promoDiscount, 0);

            $('#summary-subtotal').text(formatRupiah(subtotal));
            $('#summary-promo-discount').text('- ' + formatRupiah(promoDiscount));
            $('#summary-total').text(formatRupiah(total));
        }

        // Fetch promotions based on current cart
        function fetchPromotions() {
            clearTimeout(promoDebounce);
            if (cartItems.length === 0) {
                $('#promo_select').empty().append('<option value="">- Tidak menggunakan promosi -</option>');
                $('#promo-hint').text('Pilih item terlebih dahulu untuk melihat promo yang tersedia.');
                selectedPromo = null;
                calculateSummary();
                return;
            }

            promoDebounce = setTimeout(function () {
                let subtotal = 0;
                const itemsPayload = cartItems.map(function (i) {
                    const itemSub = Math.max((i.qty * i.harga) - (i.diskon || 0), 0);
                    subtotal += itemSub;
                    return {
                        jenis_item: i.tipe,
                        paket_id: i.tipe === 'PAKET' ? i.id : null,
                        produk_id: i.tipe === 'PRODUK' ? i.id : null,
                        qty: i.qty,
                        harga: i.harga,
                        diskon: i.diskon || 0
                    };
                });

                $.ajax({
                    url: PROMO_URL,
                    type: 'GET',
                    data: {
                        cabang_id: CABANG_ID,
                        subtotal: subtotal,
                        tanggal: $('#tanggal_transaksi').val(),
                        items: itemsPayload
                    },
                    success: function (res) {
                        availablePromos = res || [];
                        const select = $('#promo_select');
                        const prevSelectedCode = selectedPromo ? selectedPromo.kode : '';
                        select.empty();
                        select.append('<option value="">- Tidak menggunakan promosi -</option>');

                        if (availablePromos.length > 0) {
                            $('#promo-hint').text(`Tersedia ${availablePromos.length} promosi untuk pesanan ini.`);
                            availablePromos.forEach(function (promo) {
                                const isSelected = promo.kode === prevSelectedCode;
                                select.append(`
                                    <option value="${promo.kode}" ${isSelected ? 'selected' : ''}>
                                        ${promo.nama} (Potongan ${formatRupiah(promo.diskon_hitung)})
                                    </option>
                                `);
                            });
                        } else {
                            $('#promo-hint').text('Tidak ada promosi aktif yang memenuhi syarat.');
                        }

                        // Re-evaluate selected promo
                        const currentVal = select.val();
                        selectedPromo = availablePromos.find(p => p.kode === currentVal) || null;
                        calculateSummary();
                    }
                });
            }, 300);
        }

        $('#promo_select').on('change', function () {
            const kode = $(this).val();
            selectedPromo = availablePromos.find(p => p.kode === kode) || null;
            calculateSummary();
        });

        // Check Existing KO
        $('#btn-check-ko').on('click', function () {
            const noKo = $('#no_ko').val().trim();
            if (!noKo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: 'Silakan masukkan No KO yang ingin dicek.'
                });
                return;
            }

            $.ajax({
                url: CEK_KO_URL,
                type: 'GET',
                data: { no_ko: noKo },
                success: function (res) {
                    if (res.exists && res.order) {
                        const o = res.order;
                        $('#customer_name').val(o.pelanggan?.nama || '');
                        $('#phone').val(o.pelanggan?.no_hp || '');
                        $('#address').val(o.pelanggan?.alamat || '');
                        if (o.sales_mode_id) $('#sales_mode_id').val(o.sales_mode_id);
                        if (o.cs?.id) $('#cs_user_id').val(o.cs.id).trigger('change');
                        if (o.cs1?.id) $('#cs1_user_id').val(o.cs1.id).trigger('change');
                        if (o.cs2?.id) $('#cs2_user_id').val(o.cs2.id).trigger('change');
                        if (o.spv?.id) $('#spv_user_id').val(o.spv.id).trigger('change');
                        if (o.fotografer?.id) $('#fotografer_user_id').val(o.fotografer.id).trigger('change');
                        if (o.tanggal_selesai) $('#tanggal_selesai').val(o.tanggal_selesai);

                        Swal.fire({
                            icon: 'success',
                            title: 'KO Ditemukan',
                            text: `Data customer dari KO ${noKo} berhasil dimuat. Item baru yang Anda tambahkan akan dimasukkan ke KO ini.`
                        });
                    } else if (res.reusable) {
                        Swal.fire({
                            icon: 'info',
                            title: 'KO Bisa Digunakan',
                            text: res.message || 'Nomor KO ini bisa digunakan untuk transaksi baru.'
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'KO Baru',
                            text: 'No KO belum terdaftar dan akan dibuat sebagai KO baru saat disimpan.'
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal mengecek nomor KO.'
                    });
                }
            });
        });

        // Reset Form Button
        $('#btn-reset-form, #modal-btn-new-order').on('click', function () {
            $('#no_ko, #customer_name, #phone, #address, #order_note, #booking_date, #booking_time, #tanggal_selesai').val('');
            $('#sales_mode_id').val('');
            $('#cs_user_id, #cs1_user_id, #cs2_user_id, #spv_user_id, #fotografer_user_id').val('').trigger('change');
            $('#is_booking').prop('checked', false).trigger('change');
            $('#tanggal_transaksi').val('{{ now()->format('Y-m-d') }}');
            cartItems = [];
            selectedPromo = null;
            priceOverrideAuthorizerId = null;
            renderCart();
        });

        function promptPriceOverrideModal(items) {
            return new Promise((resolve) => {
                const $tableBody = $('#override-price-items-table tbody').empty();
                items.forEach(it => {
                    $tableBody.append(`
                        <tr>
                            <td>${it.nama}</td>
                            <td class="text-end">${formatRupiah(it.original_harga)}</td>
                            <td class="text-end fw-bold text-primary">${formatRupiah(it.harga)}</td>
                        </tr>
                    `);
                });

                $('#auth_override_username').val('');
                $('#auth_override_password').val('');
                $('#auth_override_error').addClass('d-none').text('');

                const modal = new bootstrap.Modal(document.getElementById('modalPriceOverrideAuth'));
                modal.show();

                $('#btn-submit-price-override').off('click').on('click', function () {
                    const username = $('#auth_override_username').val().trim();
                    const password = $('#auth_override_password').val();
                    if (!username || !password) {
                        $('#auth_override_error').removeClass('d-none').text('Username dan password wajib diisi.');
                        return;
                    }

                    const $btn = $(this);
                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memverifikasi...');
                    $('#auth_override_error').addClass('d-none').text('');

                    $.ajax({
                        url: URL_AUTHORIZE_PRICE_OVERRIDE,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: { username: username, password: password },
                        success: function (res) {
                            $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Setujui & Lanjutkan');
                            if (res.success) {
                                priceOverrideAuthorizerId = res.authorizer_user_id;
                                modal.hide();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Otorisasi Berhasil',
                                    text: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                resolve(true);
                            } else {
                                $('#auth_override_error').removeClass('d-none').text(res.message || 'Otorisasi gagal.');
                            }
                        },
                        error: function (xhr) {
                            $btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Setujui & Lanjutkan');
                            const msg = xhr.responseJSON?.message || 'Gagal memverifikasi otorisasi.';
                            $('#auth_override_error').removeClass('d-none').text(msg);
                        }
                    });
                });

                $('#modalPriceOverrideAuth').off('hidden.bs.modal').on('hidden.bs.modal', function () {
                    if (!priceOverrideAuthorizerId) {
                        resolve(false);
                    }
                });
            });
        }

        // Simpan Order Handler
        async function doSubmitOrder(allowMinusStock = false) {
            if (isSubmitting) return;

            // Validations
            const salesModeId = $('#sales_mode_id').val();
            const customerName = $('#customer_name').val().trim();
            const phone = $('#phone').val().trim();
            const isBooking = $('#is_booking').is(':checked');
            const bookingDate = $('#booking_date').val();
            const bookingTime = $('#booking_time').val();

            if (!salesModeId) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Sales Mode wajib dipilih.' });
                return;
            }
            if (!customerName) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Nama Customer wajib diisi.' });
                return;
            }
            if (!phone) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'No HP Customer wajib diisi.' });
                return;
            }
            if (!/^\d+$/.test(phone)) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'No HP harus berupa angka.' });
                return;
            }
            if (isBooking) {
                if (!bookingDate) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tanggal booking wajib diisi jika memilih Booking Studio.' });
                    return;
                }
                if (!bookingTime) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Jam booking wajib diisi jika memilih Booking Studio.' });
                    return;
                }
            }
            if (cartItems.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu paket atau produk pesanan.' });
                return;
            }

            // Pengecekan Otorisasi Perubahan Harga
            const overriddenItems = cartItems.filter(i => Math.abs(i.harga - i.original_harga) > 0.0001);
            if (overriddenItems.length > 0 && !CAN_OVERRIDE_PRICE && !priceOverrideAuthorizerId) {
                const authSuccess = await promptPriceOverrideModal(overriddenItems);
                if (!authSuccess) {
                    return;
                }
            }

            const itemsPayload = cartItems.map(function (item) {
                return {
                    jenis_item: item.tipe,
                    paket_id: item.tipe === 'PAKET' ? item.id : null,
                    produk_id: item.tipe === 'PRODUK' ? item.id : null,
                    custom_paket_items: item.tipe === 'PAKET' && item.custom_paket_items ? item.custom_paket_items : null,
                    qty: item.qty,
                    harga: item.harga,
                    diskon: item.diskon || 0
                };
            });

            const payload = {
                client_request_id: generateUUID(),
                cabang_id: CABANG_ID,
                sales_mode_id: parseInt(salesModeId, 10),
                tanggal: $('#tanggal_transaksi').val(),
                customer_name: customerName,
                phone: phone,
                address: $('#address').val().trim() || null,
                order_note: $('#order_note').val().trim() || null,
                cs_user_id: $('#cs_user_id').val() || null,
                cs1_user_id: $('#cs1_user_id').val() || null,
                cs2_user_id: $('#cs2_user_id').val() || null,
                spv_user_id: $('#spv_user_id').val() || null,
                fotografer_user_id: $('#fotografer_user_id').val() || null,
                is_booking: isBooking ? 1 : 0,
                booking_date: isBooking ? bookingDate : null,
                booking_time: isBooking ? bookingTime : null,
                tanggal_selesai: $('#tanggal_selesai').val() || null,
                no_ko: $('#no_ko').val().trim() || null,
                promo_kode: selectedPromo ? selectedPromo.kode : null,
                promo_sumber: selectedPromo ? selectedPromo.sumber : null,
                promo_diskon: selectedPromo ? selectedPromo.diskon_hitung : 0,
                allow_minus_stock: allowMinusStock ? 1 : 0,
                has_price_override: overriddenItems.length > 0 ? 1 : 0,
                authorizer_user_id: priceOverrideAuthorizerId || null,
                items: itemsPayload
            };

            isSubmitting = true;
            const btn = $('#btn-simpan-order');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

            $.ajax({
                url: SIMPAN_ORDER_URL,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function (res) {
                    if (res.success) {
                        $('#modal-success-message').text(res.message || 'Order berhasil disimpan.');
                        $('#modal-success-ko').text(res.nomor_ko || '-');
                        $('#modal-success-so').text(res.nomor_so || '-');
                        $('#modal-success-customer').text(res.customer_name || customerName);
                        $('#modal-success-total').text(formatRupiah(res.total || 0));
                        $('#modal-btn-queue').attr('href', res.queue_url || '{{ route("input-antrian") }}');

                        const modal = new bootstrap.Modal(document.getElementById('modalOrderSuccess'));
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: res.message || 'Terjadi kesalahan saat menyimpan order.'
                        });
                    }
                },
                error: function (xhr) {
                    const res = xhr.responseJSON;
                    if (res && res.status === 'INSUFFICIENT_STOCK' && Array.isArray(res.insufficient_items)) {
                        let itemsHtml = `
                            <div class="table-responsive text-start my-2" style="max-height: 220px; overflow-y: auto;">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-danger small">
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-center">Stok Ada</th>
                                            <th class="text-center">Diminta</th>
                                            <th class="text-center">Kurang</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        res.insufficient_items.forEach(function (it) {
                            itemsHtml += `
                                <tr class="small">
                                    <td><b>${it.nama}</b> ${it.kode ? `<small class="text-muted">(${it.kode})</small>` : ''}</td>
                                    <td class="text-center text-danger fw-bold">${it.stok_tersedia}</td>
                                    <td class="text-center">${it.qty_diminta}</td>
                                    <td class="text-center text-danger fw-bold">-${it.defisit}</td>
                                </tr>
                            `;
                        });
                        itemsHtml += `
                                    </tbody>
                                </table>
                            </div>
                            <p class="mb-1 fw-bold mt-2">Stok barang di atas tidak mencukupi / kosong.</p>
                            <p class="small text-muted mb-0">Apakah Anda ingin tetap melanjutkan transaksi order ini? (Stok akan tetap dipotong hingga minus)</p>
                        `;

                        Swal.fire({
                            title: 'Stok Tidak Mencukupi',
                            html: itemsHtml,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Ya, Lanjutkan Simpan Order (Minus)',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                doSubmitOrder(true);
                            }
                        });
                        return;
                    }

                    let msg = 'Gagal menyimpan order.';
                    if (res && res.errors) {
                        const firstKey = Object.keys(res.errors)[0];
                        msg = res.errors[firstKey][0];
                    } else if (res && res.message) {
                        msg = res.message;
                    }
                    Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                },
                complete: function () {
                    isSubmitting = false;
                    btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Simpan Order');
                }
            });
        }

        $('#btn-simpan-order').on('click', function () {
            doSubmitOrder(false);
        });
    });
</script>
@endpush
