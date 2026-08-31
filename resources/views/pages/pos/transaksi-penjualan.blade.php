@extends('layouts.app')

@section('title', 'Transaksi Penjualan')

@push('styles')
    <style>
        .transaksi-penjualan-page {
            overflow-x: clip;
        }

        .transaksi-penjualan-page .row {
            margin-right: 0;
            margin-left: 0;
        }

        .transaksi-penjualan-page .row > [class*="col-"] {
            padding-right: calc(var(--bs-gutter-x) * .5);
            padding-left: calc(var(--bs-gutter-x) * .5);
        }

        .transaksi-penjualan-page .select2-container {
            max-width: 100% !important;
        }

        .transaksi-penjualan-page .select2-container--bootstrap4 .select2-selection--single {
            min-height: 38px;
            border-radius: .375rem;
        }

        .transaksi-penjualan-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: .75rem;
            padding-right: 2rem;
        }

        .transaksi-penjualan-page .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: .4rem;
        }

        .transaksi-penjualan-page .select2-dropdown {
            z-index: 1060;
        }

        .transaksi-penjualan-page .required-mark {
            color: #dc3545;
            font-weight: 700;
            margin-left: .15rem;
        }

        .transaksi-penjualan-page #search-results-dropdown {
            max-width: 100%;
        }

        #paymentModal .modal-dialog {
            max-width: min(760px, calc(100vw - 2rem));
        }

        #paymentModal .modal-body {
            overflow-x: hidden;
        }

        #paymentModal .table-responsive {
            overflow-x: hidden;
        }

        #paymentModal #payment-list-body td,
        #paymentModal #payment-list-body th {
            word-break: break-word;
        }

        #paymentModal .select2-container {
            width: 100% !important;
        }

        #paymentModal .select2-container--bootstrap4 .select2-selection--single {
            min-height: 38px;
        }

        #paymentModal .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: .75rem;
            padding-right: 2rem;
        }

        #paymentModal .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: .4rem;
        }

        #paymentModal .select2-dropdown {
            z-index: 2000;
        }
    </style>
@endpush

@section('content')
    <div class="transaksi-penjualan-page">
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">POS</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item active" aria-current="page">Transaksi Penjualan</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <strong>Cabang Aktif:</strong> {{ $activeCabang?->nama ?? '-' }}
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

    @if(!empty($staleOpenShiftDate))
        <div class="alert alert-danger">
            Tanggal {{ $staleOpenShiftDate }} belum tutup kasir. Tutup kasir sebelum melanjutkan transaksi hari ini.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="small text-muted mb-3">
                        <span class="required-mark">*</span> Wajib diisi
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No KO</label>
                            <div class="input-group">
                                <input type="text" id="no_ko" class="form-control"
                                    placeholder="Kosongkan untuk transaksi baru">
                                <button type="button" id="btn-check-ko" class="btn btn-outline-primary">Cek</button>
                            </div>
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
                            <input type="text" id="phone" class="form-control" placeholder="No HP" inputmode="numeric" autocomplete="tel" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alamat</label>
                            <input type="text" id="address" class="form-control" placeholder="Alamat">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Keterangan</label>
                            <input type="text" id="order_note" class="form-control" placeholder="Catatan order">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CS</label>
                            <select id="cs_user_id" class="form-select single-select w-100" data-placeholder="Cari CS">
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
                            <select id="cs1_user_id" class="form-select single-select w-100" data-placeholder="Cari CS 1">
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
                            <select id="cs2_user_id" class="form-select single-select w-100" data-placeholder="Cari CS 2">
                                <option value="">- Pilih CS 2 -</option>
                                @foreach($csCandidates as $userOption)
                                    <option value="{{ $userOption->id }}">
                                        {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fotografer</label>
                            <select id="fotografer_user_id" class="form-select single-select w-100" data-placeholder="Cari Fotografer">
                                <option value="">- Pilih Fotografer -</option>
                                @foreach($fotograferCandidates as $userOption)
                                    <option value="{{ $userOption->id }}">
                                        {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">SPV <span class="required-mark">*</span></label>
                            <select id="spv_user_id" class="form-select single-select w-100" data-placeholder="Cari SPV"
                                required>
                                <option value="">- Pilih SPV -</option>
                                @foreach($spvCandidates as $userOption)
                                    <option value="{{ $userOption->id }}">
                                        {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-none" id="kasir-name-wrap">
                            <label class="form-label">Kasir</label>
                            <input type="text" id="kasir_name" class="form-control" placeholder="Kasir akan tampil saat KO existing dipanggil" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Transaksi <span class="required-mark">*</span></label>
                            <input type="date" id="transaction_date" class="form-control"
                                value="{{ now()->format('Y-m-d') }}" @if(!($canTransaksiBackdate ?? false))
                                min="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" @endif>
                            @if(!($canTransaksiBackdate ?? false))
                                <small class="text-muted">Tanggal transaksi hanya bisa hari ini.</small>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Selesai (Deadline KO) <span class="required-mark">*</span></label>
                            <input type="date" id="tanggal_selesai" class="form-control" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_booking">
                                <label class="form-check-label" for="is_booking">Booking Studio</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-none" id="booking-date-wrap">
                            <label class="form-label">Tanggal Booking <span class="required-mark">*</span></label>
                            <input type="date" id="booking_date" class="form-control">
                        </div>
                        <div class="col-md-4 d-none" id="booking-time-wrap">
                            <label class="form-label">Jam Booking <span class="required-mark">*</span></label>
                            <input type="time" id="booking_time" class="form-control">
                        </div>
                    </div>

                    <div id="ko-banner" class="mt-3 d-none"></div>
                    <div id="ko-existing-items" class="small text-muted mt-1"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="position-relative mb-3">
                        <div class="input-group">
                            <input type="text" id="product-search" class="form-control"
                                placeholder="Cari paket / barang / jasa...">
                            <button class="btn btn-primary" id="add-product-btn" type="button">Tambah Item</button>
                        </div>
                        <div id="search-results-dropdown"
                            class="border rounded bg-white shadow-sm position-absolute w-100 mt-1"
                            style="display:none; z-index:1050; max-height:260px; overflow:auto;">
                            <div class="list-group list-group-flush" id="search-results"></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th width="130">Harga</th>
                                    <th width="90">Qty</th>
                                    <th width="120">Diskon</th>
                                    <th width="150">Subtotal</th>
                                    <th width="80">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="product-table-body">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada item.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Ringkasan</h6>
                    <div class="d-flex justify-content-between"><span>Subtotal Item</span><strong id="subtotal">Rp
                            0</strong></div>
                    <div class="d-flex justify-content-between"><span>Diskon Item</span><strong id="discount-item">Rp
                            0</strong></div>
                    <div class="d-flex justify-content-between"><span>Diskon Promo</span><strong id="discount-promo">Rp
                            0</strong></div>
                    <hr>
                    <div class="d-flex justify-content-between"><span>Total Tambahan</span><strong id="grand-total">Rp
                            0</strong></div>
                    <div class="d-flex justify-content-between text-muted"><span>Paid Existing</span><span
                            id="existing-paid">Rp 0</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>Sisa Existing</span><span
                            id="existing-balance">Rp 0</span></div>
                    <div class="d-flex justify-content-between"><strong>Estimasi Sisa Baru</strong><strong
                            id="estimated-balance">Rp 0</strong></div>
                    <div class="mt-2">
                        <span id="payment-status-badge" class="badge bg-secondary">BELUM BAYAR</span>
                        <small id="payment-status-note" class="d-block text-muted mt-1">Tambahkan pembayaran untuk melihat
                            status akhir transaksi.</small>
                    </div>

                    <hr>
                    <label class="form-label">Promosi</label>
                    <div class="d-grid gap-2 mb-2">
                        <button type="button" id="btn-promo-modal" class="btn btn-outline-primary">Pilih Promosi /
                            Voucher</button>
                    </div>
                    <small id="promo-selected-text" class="text-muted d-block mb-3">Belum ada promosi dipilih.</small>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Pembayaran Ditambahkan</span>
                            <strong id="payment-total">Rp 0</strong>
                        </div>
                        <small id="payment-list-summary" class="text-muted d-block">Belum ada pembayaran
                            ditambahkan.</small>
                    </div>
                    @if(($metodePembayaran ?? collect())->isEmpty())
                        <small class="text-danger d-block mb-2">Cabang aktif belum punya metode pembayaran. Atur dulu di menu
                            Cabang.</small>
                    @endif

                    <button type="button" id="pay-btn" class="btn btn-primary w-100">Bayar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Promosi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <input type="text" id="voucher-code-input" class="form-control"
                                placeholder="Masukkan kode voucher">
                        </div>
                        <div class="col-md-4 d-grid">
                            <button type="button" class="btn btn-outline-success" id="btn-apply-voucher-code">Apply Kode
                                Voucher</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Sumber</th>
                                    <th>Diskon</th>
                                    <th width="100">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="promo-table-body">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Memuat promosi...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-remove-promo" class="btn btn-outline-danger">Hapus Promo</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pembayaran Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran</label>
                            <select id="payment-modal-method" class="form-select single-select w-100"
                                data-placeholder="Cari metode pembayaran" {{ ($metodePembayaran ?? collect())->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Pilih metode pembayaran</option>
                                @foreach($metodePembayaran as $metode)
                                    <option value="{{ $metode->id }}" data-kode="{{ strtoupper((string) ($metode->kode ?? '')) }}">{{ $metode->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nominal</label>
                            <input type="text" id="payment-modal-amount" inputmode="decimal" class="form-control" value="0">
                        </div>
                        <div class="col-md-2 d-grid">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" id="btn-add-payment" class="btn btn-outline-primary">Tambah</button>
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <button type="button" id="btn-fill-billing" class="btn btn-sm btn-outline-secondary">Salin Tagihan/Sisa</button>
                            <small id="payment-modal-copy-note" class="text-muted">Sisa tagihan saat ini Rp 0.</small>
                        </div>
                        <div class="col-12">
                            <div id="payment-modal-cash-info" class="small text-success d-none"></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Metode</th>
                                    <th class="text-end" width="180">Nominal</th>
                                    <th width="80">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="payment-list-body">
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada pembayaran.</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-end">Total</th>
                                    <th class="text-end" id="payment-modal-total">Rp 0</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="btn-submit-payment" class="btn btn-primary">Simpan & Cetak</button>
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
                        Terdapat perbedaan harga dari master/template harga. Diperlukan otorisasi Supervisor / Manager untuk melanjutkan transaksi.
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
    </div>
@endsection

@push('scripts')
    <script>
        const CABANG_DEFAULT_ID = {{ (int) ($cabangDefaultId ?? 0) }};
        const URL_CARI_PRODUK = "{{ route('transaksi-penjualan.produk-cari') }}";
        const URL_CEK_KO = "{{ route('transaksi-penjualan.cek-ko') }}";
        const URL_PROMO = "{{ route('transaksi-penjualan.promosi-tersedia') }}";
        const URL_SIMPAN = "{{ route('transaksi-penjualan.simpan') }}";
        const URL_STRUK_BASE = "{{ url('/transaksi-penjualan/struk') }}";
        const URL_AUTHORIZE_PRICE_OVERRIDE = "{{ route('pos.authorize-price-override') }}";
        const CSRF_TOKEN = "{{ csrf_token() }}";
        const STALE_SHIFT_DATE = @json($staleOpenShiftDate ?? null);
        const CAN_TRANSAKSI_BACKDATE = @json((bool) ($canTransaksiBackdate ?? false));
        const CAN_OVERRIDE_PRICE = @json((bool) auth()->user()?->hasPermission('pos.transaksi.override_price'));

        let searchResultsData = [];
        let koState = { exists: false, canAdd: false, canEditExistingItems: false, order: null };
        let koAutofilled = false;
        let selectedPromo = null;
        let cachedPromosi = [];
        let searchTimer = null;
        let searchRequest = null;
        let pendingPayments = [];
        let activeClientRequestId = null;
        let submitInFlight = false;
        let priceOverrideAuthorizerId = null;
        const searchCache = new Map();
        function normalizeRowDiscountFromPromo() {
            $('#product-table-body tr').each(function () {
                if ($(this).find('td[colspan]').length || isExistingRow(this)) return;
                const $row = $(this);
                const currentDiscount = parseCurrencyInput($row.find('.item-diskon').val());
                const promoDiscount = parseFloat($row.attr('data-promo-discount') || '0') || 0;
                const manualDiscount = Math.max(currentDiscount - promoDiscount, 0);
                $row.attr('data-promo-discount', '0');
                $row.find('.item-diskon').val(formatCurrencyInput(manualDiscount));
                updateRowSubtotal($row);
            });
        }
        function applyPromoDiscountToRows() {
            normalizeRowDiscountFromPromo();
            if (!selectedPromo) return;

            const promoTotal = Math.max(parseFloat(selectedPromo.diskon_hitung || 0), 0);
            if (promoTotal <= 0) return;

            const eligiblePaketIds = Array.isArray(selectedPromo.paket_ids)
                ? selectedPromo.paket_ids.map(id => parseInt(id, 10)).filter(id => id > 0)
                : [];

            // Diskon general (eligiblePaketIds kosong): TIDAK distribusikan ke baris.
            // Diskon langsung dipotong dari total transaksi (order-level).
            if (eligiblePaketIds.length === 0) return;

            const eligibleRows = [];
            $('#product-table-body tr').each(function () {
                if ($(this).find('td[colspan]').length || isExistingRow(this)) return;
                const $row = $(this);
                const itemType = String($row.data('item-type') || '');
                const itemId = parseInt($row.data('item-id') || 0, 10);
                if (itemType !== 'PAKET' || !eligiblePaketIds.includes(itemId)) return;

                const harga = parseCurrencyInput($row.find('.item-harga').val());
                const qty = parseFloat($row.find('.item-qty').val()) || 0;
                const manualDiscount = parseCurrencyInput($row.find('.item-diskon').val());
                const rowNet = Math.max((harga * qty) - manualDiscount, 0);
                if (rowNet <= 0) return;
                eligibleRows.push({ row: $row, rowNet, manualDiscount });
            });
            if (!eligibleRows.length) return;

            const totalEligible = eligibleRows.reduce((sum, item) => sum + item.rowNet, 0);
            let remaining = Math.min(promoTotal, totalEligible);
            eligibleRows.forEach((item, idx) => {
                let allocation = 0;
                if (idx === eligibleRows.length - 1) {
                    allocation = remaining;
                } else if (totalEligible > 0) {
                    allocation = promoTotal * (item.rowNet / totalEligible);
                    allocation = Math.min(allocation, remaining);
                }
                allocation = Math.min(allocation, item.rowNet);
                remaining -= allocation;

                item.row.attr('data-promo-discount', String(allocation));
                item.row.find('.item-diskon').val(formatCurrencyInput(item.manualDiscount + allocation));
                updateRowSubtotal(item.row);
            });
        }

        function formatRupiah(v) {
            const n = parseFloat(v || 0);
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
        }
        function parseCurrencyInput(value) {
            const raw = String(value ?? '').trim().replace(/[^\d,.\-]/g, '');
            if (!raw || raw === '-' || raw === ',' || raw === '.') return 0;
            const negative = raw.startsWith('-');
            const unsigned = raw.replace(/-/g, '');
            const lastComma = unsigned.lastIndexOf(',');
            const lastDot = unsigned.lastIndexOf('.');
            const decimalIndex = Math.max(lastComma, lastDot);
            const hasBothSeparator = lastComma !== -1 && lastDot !== -1;
            const digitsAfterLastSeparator = decimalIndex >= 0 ? (unsigned.length - decimalIndex - 1) : 0;
            const useDecimalSeparator = decimalIndex >= 0 && (
                hasBothSeparator ||
                (digitsAfterLastSeparator > 0 && digitsAfterLastSeparator <= 2)
            );

            let normalized = '';
            if (useDecimalSeparator) {
                const intPart = unsigned.slice(0, decimalIndex).replace(/[.,]/g, '');
                const fracPart = unsigned.slice(decimalIndex + 1).replace(/[.,]/g, '');
                normalized = intPart + (fracPart ? '.' + fracPart : '');
            } else {
                normalized = unsigned.replace(/[.,]/g, '');
            }

            const parsed = parseFloat((negative ? '-' : '') + normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }
        function toPlainCurrencyString(value) {
            const num = parseCurrencyInput(value);
            if (Math.abs(num - Math.round(num)) < 0.0000001) {
                return String(Math.round(num));
            }
            return num.toFixed(2).replace(/\.?0+$/, '');
        }
        function formatCurrencyInput(value) {
            const num = parseCurrencyInput(value);
            const isInteger = Math.abs(num - Math.round(num)) < 0.0000001;
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: isInteger ? 0 : 2,
                maximumFractionDigits: 2
            }).format(num);
        }
        function applyCurrencyFormat(el) {
            if (!el) return;
            el.value = formatCurrencyInput(el.value);
        }
        function sanitizeIntegerInput(value, minValue = 0) {
            const cleaned = String(value ?? '').replace(/[^\d]/g, '');
            if (cleaned === '') return String(minValue);
            return String(Math.max(parseInt(cleaned, 10) || 0, minValue));
        }
        function generateClientRequestId() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }

            return 'trx-' + Date.now() + '-' + Math.random().toString(16).slice(2, 10);
        }
        function setSubmitState(isSubmitting) {
            submitInFlight = isSubmitting;
            $('#btn-submit-payment').prop('disabled', isSubmitting);
            $('#pay-btn').prop('disabled', isSubmitting || !!STALE_SHIFT_DATE);
            $('#btn-submit-payment').text(isSubmitting ? 'Memproses...' : 'Simpan & Cetak');
        }
        function sanitizePhoneInput(value) {
            return String(value ?? '').replace(/[^\d]/g, '');
        }
        function normalizeIntegerFields(scope) {
            const $scope = $(scope || document);
            $scope.find('input.item-qty').each(function () {
                this.value = sanitizeIntegerInput(this.value, 1);
            });
            $scope.find('input.item-diskon').each(function () {
                this.value = sanitizeIntegerInput(this.value, 0);
            });
        }
        function isExistingRow(rowEl) {
            return String($(rowEl).data('existing') || '0') === '1';
        }
        function getSubtotals() {
            let subtotal = 0, diskonItem = 0;
            $('#product-table-body tr').each(function () {
                if ($(this).find('td[colspan]').length) return;
                if (isExistingRow(this)) return;
                const h = parseCurrencyInput($(this).find('.item-harga').val());
                const q = parseFloat($(this).find('.item-qty').val()) || 0;
                const d = parseCurrencyInput($(this).find('.item-diskon').val());
                const promoD = parseFloat($(this).attr('data-promo-discount') || '0') || 0;
                subtotal += (h * q);
                diskonItem += Math.max(d - promoD, 0);
                const line = (h * q) - d;
                $(this).find('.item-subtotal').text(formatRupiah(line));
            });
            return { subtotal, diskonItem, netItem: Math.max(subtotal - diskonItem, 0) };
        }
        function calculateExistingRowMetrics(rowEl) {
            const $row = $(rowEl);
            const harga = parseCurrencyInput($row.find('.item-harga').val());
            const qtyBaru = Math.max(parseFloat($row.find('.item-qty').val()) || 0, 0);
            const qtyLama = Math.max(parseFloat($row.data('original-qty') || 0), 0);
            const diskonLama = parseFloat($row.data('original-diskon') || 0);
            const subtotalLama = parseFloat($row.data('original-subtotal') || 0);
            const grossLama = parseFloat($row.data('original-gross') || (harga * qtyLama));
            const diskonPerUnit = qtyLama > 0 ? (diskonLama / qtyLama) : 0;
            const diskonBaru = qtyBaru > 0 ? (diskonPerUnit * qtyBaru) : 0;
            const grossBaru = harga * qtyBaru;
            const subtotalBaru = Math.max(grossBaru - diskonBaru, 0);

            return {
                qtyLama,
                qtyBaru,
                grossLama,
                grossBaru,
                diskonLama,
                diskonBaru,
                subtotalLama,
                subtotalBaru,
                grossDelta: grossBaru - grossLama,
                discountDelta: diskonBaru - diskonLama,
                netDelta: subtotalBaru - subtotalLama
            };
        }
        function getExistingItemAdjustments() {
            let grossDelta = 0, discountDelta = 0, netDelta = 0;
            $('#product-table-body tr').each(function () {
                if (!isExistingRow(this)) return;
                const metrics = calculateExistingRowMetrics(this);
                grossDelta += metrics.grossDelta;
                discountDelta += metrics.discountDelta;
                netDelta += metrics.netDelta;
            });

            return { grossDelta, discountDelta, netDelta };
        }
        function updateRowSubtotal(rowEl) {
            const tr = $(rowEl);
            if (!tr.length) return;

            if (isExistingRow(tr)) {
                const metrics = calculateExistingRowMetrics(tr);
                tr.find('.item-subtotal').text(formatRupiah(metrics.subtotalBaru));
                return;
            }

            const hText = tr.find('.item-harga').val() || '0';
            const qText = tr.find('.item-qty').val() || '0';
            const dText = tr.find('.item-diskon').val() || '0';
            const harga = parseFloat(hText.replace(/\./g, '').replace(/,/g, '.')) || 0;
            const qty = parseFloat(qText.replace(/\./g, '').replace(/,/g, '.')) || 0;
            const diskon = parseFloat(dText.replace(/\./g, '').replace(/,/g, '.')) || 0;
            const newSub = Math.max((harga * qty) - diskon, 0);
            tr.find('.item-subtotal').text(formatRupiah(newSub));
        }
        function getPromoDiscount(netItem) {
            if (!selectedPromo) return 0;
            return Math.min(parseFloat(selectedPromo.diskon_hitung || 0), netItem);
        }
        function getTotalPaymentAmount() {
            return pendingPayments.reduce((sum, row) => sum + Math.max(parseFloat(row.nominal || 0), 0), 0);
        }
        function getPaymentMethodName(metodeId) {
            return ($('#payment-modal-method option[value="' + metodeId + '"]').text() || '-').trim();
        }
        function getSelectedPaymentMethodMeta() {
            const $selected = $('#payment-modal-method option:selected');
            const kode = String($selected.data('kode') || '').trim().toUpperCase();
            const nama = String($selected.text() || '').trim().toUpperCase();
            const isCash = kode === 'CASH' || nama === 'CASH' || nama.includes('TUNAI');
            return { kode, nama, isCash };
        }
        function getRemainingBillAmount() {
            const projection = getKoPaymentProjection();
            return Math.max(parseFloat(projection.totalTagihan || 0) - parseFloat(projection.totalPayments || 0), 0);
        }
        function refreshPaymentModalMeta() {
            const remaining = getRemainingBillAmount();
            const typedAmount = Math.max(parseCurrencyInput($('#payment-modal-amount').val()), 0);
            const methodMeta = getSelectedPaymentMethodMeta();
            const amountPaid = Math.min(typedAmount, remaining);
            const amountChange = Math.max(typedAmount - remaining, 0);

            $('#btn-fill-billing').prop('disabled', remaining <= 0);
            $('#payment-modal-copy-note').text(
                remaining > 0
                    ? `Sisa tagihan saat ini ${formatRupiah(remaining)}.`
                    : 'Tagihan sudah lunas.'
            );

            if (methodMeta.isCash && typedAmount > 0) {
                $('#payment-modal-cash-info')
                    .removeClass('d-none')
                    .text(`Uang diterima ${formatRupiah(typedAmount)} | Terbayar ${formatRupiah(amountPaid)} | Kembalian ${formatRupiah(amountChange)}`);
            } else {
                $('#payment-modal-cash-info').addClass('d-none').text('');
            }
        }
        function renderPayments() {
            const $tbody = $('#payment-list-body');
            if (!pendingPayments.length) {
                $tbody.html('<tr><td colspan="3" class="text-center text-muted">Belum ada pembayaran.</td></tr>');
            } else {
                const rows = pendingPayments.map((row, idx) => `
                        <tr>
                            <td>${row.metode_nama}</td>
                            <td class="text-end">${formatRupiah(row.nominal)}</td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-payment" data-idx="${idx}">Hapus</button></td>
                        </tr>
                    `).join('');
                $tbody.html(rows);
            }
            const total = getTotalPaymentAmount();
            $('#payment-modal-total').text(formatRupiah(total));
            $('#payment-total').text(formatRupiah(total));
            if (!pendingPayments.length) {
                $('#payment-list-summary').text('Belum ada pembayaran ditambahkan.');
            } else {
                $('#payment-list-summary').text(`${pendingPayments.length} metode, total ${formatRupiah(total)}.`);
            }
            refreshPaymentModalMeta();
        }
        function getKoPaymentProjection() {
            const calc = getSubtotals();
            const existingAdjustments = getExistingItemAdjustments();
            const promoDisc = getPromoDiscount(calc.netItem);
            const totalTambahan = (calc.netItem + existingAdjustments.netDelta) - promoDisc;
            const existingBalance = Math.max(parseFloat(koState.order?.balance || 0), 0);
            const totalPayments = Math.max(getTotalPaymentAmount(), 0);
            const totalTagihan = koState.canAdd
                ? Math.max(existingBalance + totalTambahan, 0)
                : Math.max(totalTambahan, 0);
            const estimated = Math.max(totalTagihan - totalPayments, 0);

            return { calc, existingAdjustments, promoDisc, totalTambahan, existingBalance, totalPayments, totalTagihan, estimated };
        }
        function getPaymentStatusMeta(projection) {
            if (projection.totalTagihan <= 0) {
                return {
                    label: 'TIDAK ADA TAGIHAN',
                    badgeClass: 'bg-secondary',
                    note: 'Belum ada nilai tagihan pada transaksi ini.',
                    isSettled: true
                };
            }
            if (projection.totalPayments <= 0) {
                return {
                    label: 'BELUM BAYAR',
                    badgeClass: 'bg-secondary',
                    note: 'Belum ada pembayaran ditambahkan.',
                    isSettled: false
                };
            }
            if (projection.estimated <= 0) {
                return {
                    label: 'LUNAS',
                    badgeClass: 'bg-success',
                    note: 'Pembayaran saat ini menutup seluruh tagihan.',
                    isSettled: true
                };
            }
            return {
                label: 'KURANG BAYAR',
                badgeClass: 'bg-danger',
                note: `Masih kurang ${formatRupiah(projection.estimated)}.`,
                isSettled: false
            };
        }
        function refreshSummary() {
            const projection = getKoPaymentProjection();
            const { calc, existingAdjustments, promoDisc, totalTambahan, existingBalance, estimated } = projection;
            const existingPaid = Math.max(parseFloat(koState.order?.paid_total || 0), 0);
            const existingDiskonOtomatis = Math.max(parseFloat(koState.order?.diskon_otomatis || 0), 0);
            const statusMeta = getPaymentStatusMeta(projection);
            const subtotalLive = calc.subtotal + existingAdjustments.grossDelta;
            const discountLive = calc.diskonItem + existingAdjustments.discountDelta;
            // Tampilkan diskon promo: promo yang sedang dipilih, atau diskon_otomatis dari order existing
            const displayedPromoDisc = selectedPromo ? promoDisc : existingDiskonOtomatis;

            $('#subtotal').text(formatRupiah(subtotalLive));
            $('#discount-item').text(formatRupiah(discountLive));
            $('#discount-promo').text(formatRupiah(displayedPromoDisc));
            $('#grand-total').text(formatRupiah(totalTambahan));
            $('#existing-paid').text(formatRupiah(existingPaid));
            $('#existing-balance').text(formatRupiah(existingBalance));
            $('#estimated-balance').text(formatRupiah(estimated));
            $('#payment-status-badge')
                .removeClass('bg-secondary bg-success bg-danger')
                .addClass(statusMeta.badgeClass)
                .text(statusMeta.label);
            $('#payment-status-note').text(statusMeta.note);
            if (selectedPromo) {
                $('#promo-selected-text').text(`${selectedPromo.kode} - ${selectedPromo.nama} (${formatRupiah(getPromoDiscount(calc.netItem))})`);
            } else {
                $('#promo-selected-text').text('Belum ada promosi dipilih.');
            }
            refreshPaymentModalMeta();
        }
        function resetTableIfEmpty() {
            if ($('#product-table-body tr').length === 0) {
                $('#product-table-body').html('<tr><td colspan="6" class="text-center text-muted">Belum ada item.</td></tr>');
            }
        }
        function clearPlaceholder() {
            if ($('#product-table-body td[colspan]').length) $('#product-table-body').empty();
        }
        function formatItemMeta(item) {
            if (item.tipe === 'PRODUK') {
                return `${item.kode || '-'} | ${item.tipe}`;
            }
            return item.tipe;
        }
        function formatExistingItemMeta(item) {
            const tipe = item.jenis_item || '-';
            if (tipe === 'PRODUK') {
                return `${item.kode || '-'} | ${tipe}`;
            }
            return tipe;
        }
        function removeExistingRows() {
            $('#product-table-body tr').filter(function () {
                return isExistingRow(this);
            }).remove();
            resetTableIfEmpty();
        }
        function collectExistingQtyDrafts() {
            const drafts = {};
            $('#product-table-body tr').each(function () {
                if (!isExistingRow(this)) return;
                const id = parseInt($(this).data('existing-id') || 0, 10);
                if (!id) return;
                const qty = parseFloat($(this).find('.item-qty').val()) || 0;
                drafts[id] = qty;
            });
            return drafts;
        }
        function renderExistingOrderItems(items) {
            removeExistingRows();
            if (!Array.isArray(items) || !items.length) return;
            clearPlaceholder();

            const allowEdit = !!koState.canEditExistingItems;

            const htmlRows = items.map(function (item) {
                const harga = parseFloat(item.harga || 0);
                const qty = parseFloat(item.qty || 0);
                const diskon = parseFloat(item.diskon || 0);
                const subtotal = parseFloat(item.subtotal || Math.max((harga * qty) - diskon, 0));
                const nama = item.nama || '-';
                const meta = formatExistingItemMeta(item);
                const id = item.id;

                return `
                        <tr data-existing="1" data-existing-id="${id}" data-original-qty="${qty}" data-original-diskon="${diskon}" data-original-subtotal="${subtotal}" data-original-gross="${harga * qty}">
                            <td>
                                <div class="fw-semibold">${nama}</div>
                                <small class="text-muted">${meta}</small>
                                <span class="badge bg-light text-dark ms-1">Existing</span>
                            </td>
                            <td><input class="form-control form-control-sm item-harga" type="text" value="${formatCurrencyInput(harga)}" readonly disabled></td>
                            <td><input class="form-control form-control-sm item-qty ${allowEdit ? '' : 'disabled'}" type="number" min="0" step="1" inputmode="numeric" value="${qty}" ${allowEdit ? '' : 'readonly disabled'}></td>
                            <td><input class="form-control form-control-sm item-diskon" type="text" value="${formatCurrencyInput(diskon)}" readonly disabled></td>
                            <td class="item-subtotal">${formatRupiah(subtotal)}</td>
                            <td>
                                ${allowEdit
                                    ? '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-existing-item">Remove</button>'
                                    : '<span class="text-muted small">Terkunci</span>'}
                            </td>
                        </tr>
                    `;
            }).join('');

            $('#product-table-body').append(htmlRows);
        }
        function renderSearchResults(items) {
            const t = $('#search-results').empty();
            if (!items.length) {
                t.html('<div class="list-group-item">Tidak ditemukan</div>');
                return;
            }
            items.forEach(item => {
                t.append(`<div class="list-group-item search-result-item" data-id="${item.id}" data-tipe="${item.tipe}" style="cursor:pointer">
                        <div class="d-flex justify-content-between">
                            <div><strong>${item.nama}</strong> <small class="text-muted">(${formatItemMeta(item)})</small></div>
                            <div>${formatRupiah(item.harga_default)}</div>
                        </div>
                    </div>`);
            });
        }

        function renderSubitemRows(items, parentId) {
            if (!items || !items.length) {
                return `<tr class="subitem-empty-row"><td colspan="3" class="text-center text-muted py-2 small">Paket tidak memiliki item produk. Klik "Tambah Produk" di atas.</td></tr>`;
            }
            return items.map(function (sub, sIdx) {
                return `
                    <tr data-sub-index="${sIdx}">
                        <td>
                            <span class="fw-semibold">${sub.nama || 'Produk'}</span>
                            ${sub.kode ? `<small class="text-muted ms-1">(${sub.kode})</small>` : ''}
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-center subitem-qty" min="0.01" step="any" value="${sub.qty}" data-parent="${parentId}" data-sub-index="${sIdx}">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs btn-outline-danger btn-remove-subitem" data-parent="${parentId}" data-sub-index="${sIdx}" title="Hapus Produk dari Paket">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function escapeHtml(text) {
            return String(text ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getPaketSubitemState(parentId) {
            if (!window.paketSubitemSearchState) {
                window.paketSubitemSearchState = {};
            }

            if (!window.paketSubitemSearchState[parentId]) {
                window.paketSubitemSearchState[parentId] = {
                    timer: null,
                    request: null,
                    cache: new Map(),
                    results: [],
                };
            }

            return window.paketSubitemSearchState[parentId];
        }

        function closePaketSubitemDropdown(parentId) {
            const $dropdown = $('#paket-subitem-results-' + parentId);
            if (!$dropdown.length) return;
            $dropdown.addClass('d-none').empty();
        }

        function renderPaketSubitemResults(parentId, results) {
            const $dropdown = $('#paket-subitem-results-' + parentId);
            if (!$dropdown.length) return;

            if (!Array.isArray(results) || results.length === 0) {
                $dropdown.html('<div class="list-group-item text-muted small py-2">Produk tidak ditemukan.</div>');
                $dropdown.removeClass('d-none');
                return;
            }

            const html = results.slice(0, 8).map(function (item) {
                return `
                    <button type="button" class="list-group-item list-group-item-action paket-subitem-result" data-parent="${parentId}" data-product-id="${item.id}">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="text-start">
                                <div class="fw-semibold">${escapeHtml(item.nama || '-')}</div>
                                <div class="small text-muted">${escapeHtml(item.kode || '')}</div>
                            </div>
                            <div class="text-primary fw-bold">${formatRupiah(item.harga_default || 0)}</div>
                        </div>
                    </button>
                `;
            }).join('');

            $dropdown.html(html).removeClass('d-none');
        }

        function searchPaketSubitem(parentId, term, forceShowAll = false) {
            const state = getPaketSubitemState(parentId);
            const q = (term || '').trim();

            if (!forceShowAll && q.length > 0 && q.length < 2) {
                closePaketSubitemDropdown(parentId);
                return;
            }

            const cacheKey = q.toLowerCase();
            if (state.cache.has(cacheKey)) {
                state.results = state.cache.get(cacheKey) || [];
                renderPaketSubitemResults(parentId, state.results);
                return;
            }

            if (state.timer) {
                clearTimeout(state.timer);
            }

            state.timer = setTimeout(function () {
                if (state.request && state.request.readyState !== 4) {
                    state.request.abort();
                }

                state.request = $.ajax({
                    url: URL_CARI_PRODUK,
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        q: q,
                        cabang_id: CABANG_DEFAULT_ID,
                        sales_mode_id: $('#sales_mode_id').val() || 0
                    }
                }).done(function (res) {
                    const results = (res || []).filter(function (item) {
                        return String(item.tipe || '').toUpperCase() === 'PRODUK';
                    });
                    state.results = results;
                    state.cache.set(cacheKey, results);
                    renderPaketSubitemResults(parentId, results);
                }).fail(function (xhr) {
                    if (xhr?.statusText === 'abort') return;
                    state.results = [];
                    renderPaketSubitemResults(parentId, []);
                });
            }, 250);
        }

        function addSubitemToPackage(parentId, product) {
            const $parent = $('#' + parentId);
            if (!$parent.length || !product) return;

            let customItems = JSON.parse($parent.attr('data-custom-items') || '[]');
            const existingSub = customItems.find(s => Number(s.produk_id) === Number(product.id));
            if (existingSub) {
                existingSub.qty += 1;
            } else {
                customItems.push({
                    produk_id: Number(product.id),
                    nama: product.nama,
                    kode: product.kode || '',
                    qty: 1
                });
            }

            $parent.attr('data-custom-items', JSON.stringify(customItems));
            $parent.find('.paket-count').text(customItems.length);
            $parent.find('.badge-customized').removeClass('d-none');
            const $tbody = $('#collapse-' + parentId).find('.subitem-table tbody');
            $tbody.html(renderSubitemRows(customItems, parentId));
            closePaketSubitemDropdown(parentId);
        }

        function addProduct(item) {
            clearPlaceholder();
            const isPaket = item.tipe === 'PAKET';
            const defaultItems = Array.isArray(item.items) ? JSON.parse(JSON.stringify(item.items)) : [];
            const rowId = 'row-' + Date.now() + '-' + Math.floor(Math.random() * 1000);

            let paketButtonHtml = '';
            if (isPaket) {
                paketButtonHtml = `
                    <div class="mt-1">
                        <button type="button" class="btn btn-xs btn-outline-info btn-toggle-paket" data-target="#collapse-${rowId}">
                            <i class="bi bi-box-seam me-1"></i> Rincian Paket (<span class="paket-count">${defaultItems.length}</span>) <i class="bi bi-chevron-down ms-1"></i>
                        </button>
                        <span class="badge bg-secondary badge-customized d-none ms-1">Customized</span>
                    </div>
                `;
            }

            const mainRow = `
                <tr id="${rowId}" data-item-id="${item.id}" data-item-type="${item.tipe}" data-existing="0" data-original-price="${item.harga_default}" data-custom-items='${JSON.stringify(defaultItems)}'>
                    <td>
                        <div class="fw-semibold">${item.nama}</div>
                        <small class="text-muted">${formatItemMeta(item)}</small>
                        <span class="badge bg-warning text-dark badge-override-price d-none ms-1"><i class="bi bi-pencil-square"></i> Harga Diubah</span>
                        ${paketButtonHtml}
                    </td>
                    <td><input class="form-control form-control-sm item-harga" type="text" inputmode="decimal" value="${formatCurrencyInput(item.harga_default)}"></td>
                    <td><input class="form-control form-control-sm item-qty" type="number" min="1" step="1" inputmode="numeric" value="1"></td>
                    <td><input class="form-control form-control-sm item-diskon" type="text" inputmode="decimal" value="0"></td>
                    <td class="item-subtotal">${formatRupiah(item.harga_default)}</td>
                    <td><button class="btn btn-sm btn-outline-danger remove-item">Hapus</button></td>
                </tr>
            `;

            let collapseRow = '';
            if (isPaket) {
                collapseRow = `
                    <tr id="collapse-${rowId}" class="paket-items-row bg-light d-none" data-parent="${rowId}">
                        <td colspan="6" class="p-3">
                            <div class="card card-body border-0 shadow-none p-2 mb-0 bg-white rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom gap-2 flex-wrap">
                                    <strong class="small text-primary"><i class="bi bi-boxes me-1"></i> Rincian Item Paket "${item.nama}"</strong>
                                    <div class="position-relative paket-subitem-search-wrap" style="min-width: 320px; max-width: 460px; width: 100%;">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control paket-subitem-search" data-parent="${rowId}" placeholder="Cari produk untuk paket..." autocomplete="off">
                                            <button type="button" class="btn btn-outline-primary btn-add-subitem" data-parent="${rowId}">Tambah</button>
                                        </div>
                                        <div id="paket-subitem-results-${rowId}" class="list-group paket-subitem-results position-absolute w-100 shadow-sm d-none" style="z-index: 1060; max-height: 240px; overflow-y: auto;"></div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 align-middle subitem-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Produk</th>
                                                <th width="100" class="text-center">Qty / Paket</th>
                                                <th width="70" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${renderSubitemRows(defaultItems, rowId)}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }

            $('#product-table-body').append(mainRow + collapseRow);
            normalizeIntegerFields('#' + rowId);
            refreshSummary();
        }
        function renderKoBanner(type, text) {
            const el = $('#ko-banner');
            el.removeClass('d-none alert alert-success alert-danger alert-warning');
            el.addClass(`alert alert-${type}`);
            el.text(text);
        }
        function clearKoBanner() {
            $('#ko-banner').addClass('d-none').text('');
            $('#ko-existing-items').text('');
        }
        function toggleKasirField(show, value = '') {
            const $wrap = $('#kasir-name-wrap');
            $('#kasir_name').val(value || '');
            $wrap.toggleClass('d-none', !show);
        }
        function collectAutofillDrafts() {
            return {
                customer_name: $('#customer_name').val() || '',
                phone: $('#phone').val() || '',
                address: $('#address').val() || '',
                tanggal_selesai: $('#tanggal_selesai').val() || '',
                sales_mode_id: $('#sales_mode_id').val() || '',
                cs_user_id: $('#cs_user_id').val() || '',
                cs1_user_id: $('#cs1_user_id').val() || '',
                cs2_user_id: $('#cs2_user_id').val() || '',
                fotografer_user_id: $('#fotografer_user_id').val() || '',
                spv_user_id: $('#spv_user_id').val() || '',
            };
        }
        function ensureSelectOption($select, user) {
            if (!$select || !$select.length) return;

            const userId = user?.id ? String(user.id) : '';
            if (!userId) {
                $select.val(null).trigger('change');
                return;
            }

            let $option = $select.find(`option[value="${userId}"]`);
            if (!$option.length) {
                $option = $('<option></option>')
                    .val(userId)
                    .text(user?.name || `User #${userId}`);
                $select.append($option);
            } else if (user?.name) {
                $option.text(user.name);
            }

            $select.val(userId).trigger('change');
        }
        function resetKoAutofillFields() {
            if (!koAutofilled) {
                $('#ko-existing-items').text('');
                return;
            }
            $('#customer_name').val('');
            $('#phone').val('');
            $('#address').val('');
            $('#tanggal_selesai').val('');
            ensureSelectOption($('#cs_user_id'), null);
            ensureSelectOption($('#cs1_user_id'), null);
            ensureSelectOption($('#cs2_user_id'), null);
            ensureSelectOption($('#fotografer_user_id'), null);
            ensureSelectOption($('#spv_user_id'), null);
            toggleKasirField(false, '');
            $('#ko-existing-items').text('');
            koAutofilled = false;
        }
        function renderPromoTable(list) {
            const body = $('#promo-table-body').empty();
            if (!list.length) {
                body.html('<tr><td colspan="5" class="text-center text-muted">Tidak ada promosi yang cocok.</td></tr>');
                return;
            }
            list.forEach((p, idx) => {
                const isActive = selectedPromo && String(selectedPromo.kode || '') === String(p.kode || '');
                body.append(`
                        <tr>
                            <td>${p.kode}</td>
                            <td>
                                ${p.nama}
                                ${isActive ? '<span class="badge bg-success ms-1">Sedang Dipakai</span>' : ''}
                            </td>
                            <td>${p.sumber}</td>
                            <td>${formatRupiah(p.diskon_hitung)}</td>
                            <td><button class="btn btn-sm ${isActive ? 'btn-outline-danger' : 'btn-primary'} btn-apply-promo" data-idx="${idx}">${isActive ? 'Cancel' : 'Apply'}</button></td>
                        </tr>
                    `);
            });
        }
        function fetchPromosi(callback = null) {
            const calc = getSubtotals();
            const netItem = Math.max(calc.netItem, 0);
            const currentPromoCode = selectedPromo ? String(selectedPromo.kode || '') : '';
            const payloadItems = [];
            $('#product-table-body tr').each(function () {
                if ($(this).find('td[colspan]').length) return;
                if (isExistingRow(this)) return;
                const itemId = $(this).data('item-id');
                const itemType = $(this).data('item-type');
                const harga = parseCurrencyInput($(this).find('.item-harga').val());
                const qty = parseFloat($(this).find('.item-qty').val()) || 0;
                const diskonInput = parseCurrencyInput($(this).find('.item-diskon').val());
                const promoDiskonBaris = parseFloat($(this).attr('data-promo-discount') || '0') || 0;
                const diskon = Math.max(diskonInput - promoDiskonBaris, 0);
                if (!itemId || qty <= 0) return;
                payloadItems.push({
                    jenis_item: itemType === 'PAKET' ? 'PAKET' : 'PRODUK',
                    paket_id: itemType === 'PAKET' ? itemId : null,
                    qty: qty,
                    harga: harga,
                    diskon: diskon
                });
            });
            if (netItem <= 0) {
                cachedPromosi = [];
                selectedPromo = null;
                normalizeRowDiscountFromPromo();
                renderPromoTable(cachedPromosi);
                refreshSummary();
                if (callback) callback([]);
                return;
            }

            $.get(URL_PROMO, {
                cabang_id: CABANG_DEFAULT_ID,
                subtotal: netItem,
                tanggal: $('#transaction_date').val(),
                items: payloadItems
            }).done(function (list) {
                cachedPromosi = list || [];
                const stillExists = currentPromoCode !== ''
                    ? cachedPromosi.find(x => String(x.kode || '') === currentPromoCode)
                    : null;
                if (stillExists) {
                    selectedPromo = stillExists;
                    applyPromoDiscountToRows();
                } else if (currentPromoCode !== '') {
                    selectedPromo = null;
                    normalizeRowDiscountFromPromo();
                }
                renderPromoTable(cachedPromosi);
                refreshSummary();
                if (callback) callback(cachedPromosi);
            }).fail(function () {
                cachedPromosi = [];
                renderPromoTable([]);
                if (callback) callback([]);
            });
        }
        function checkKo(callbackAfter = null, options = {}) {
            const preserveExistingEdits = !!options.preserveExistingEdits;
            const existingQtyDrafts = preserveExistingEdits ? collectExistingQtyDrafts() : {};
            const autofillDrafts = preserveExistingEdits ? collectAutofillDrafts() : null;
            const noKo = ($('#no_ko').val() || '').trim();
            if (!noKo) {
                koState = { exists: false, canAdd: false, canEditExistingItems: false, order: null };
                resetKoAutofillFields();
                removeExistingRows();
                clearKoBanner();
                refreshSummary();
                if (callbackAfter) callbackAfter();
                return;
            }
            $.get(URL_CEK_KO, { no_ko: noKo })
                .done(function (res) {
                    koState = {
                        exists: !!res.exists,
                        canAdd: !!res.can_add,
                        canEditExistingItems: !!res.can_edit_existing_items,
                        order: res.order || null
                    };

                    if (!koState.exists) {
                        resetKoAutofillFields();
                        removeExistingRows();
                        renderKoBanner(res.reusable ? 'info' : 'warning', res.message || 'KO tidak ditemukan. Transaksi baru akan dibuat.');
                    } else if (koState.canAdd) {
                        renderExistingOrderItems(koState.order?.items || []);
                        if (preserveExistingEdits) {
                            $('#product-table-body tr').each(function () {
                                if (!isExistingRow(this)) return;
                                const id = parseInt($(this).data('existing-id') || 0, 10);
                                const qtyDraft = existingQtyDrafts[id];
                                if (typeof qtyDraft === 'number' && qtyDraft >= 0) {
                                    $(this).find('.item-qty').val(qtyDraft);
                                }
                            });
                        }
                        const projection = getKoPaymentProjection();
                        const statusMeta = getPaymentStatusMeta(projection);
                        const noKoLabel = koState.order?.nomor_ko || ($('#no_ko').val() || '-');
                        const deadlineLabel = koState.order?.tanggal_selesai || '-';
                        let infoSisa = `Sisa sebelumnya: ${formatRupiah(projection.existingBalance)}.`;
                        if (projection.totalTagihan > 0 && projection.totalPayments > 0) {
                            infoSisa += statusMeta.isSettled
                                ? ' Pembayaran saat ini menutup seluruh tagihan (pelunasan).'
                                : ` Estimasi sisa setelah bayar sekarang: ${formatRupiah(projection.estimated)}.`;
                        } else if (projection.existingBalance <= 0) {
                            infoSisa += ' Order sebelumnya sudah lunas.';
                        }
                        renderKoBanner('success', `KO ditemukan (${noKoLabel}). Deadline: ${deadlineLabel}. ${infoSisa}`);
                        $('#ko-existing-items').text('Item existing: ' + (koState.order.items || []).map(x => `${x.nama} x${x.qty}`).slice(0, 6).join(', '));
                        $('#customer_name').val(koState.order.pelanggan?.nama || '');
                        $('#phone').val(koState.order.pelanggan?.no_hp || '');
                        $('#address').val(koState.order.pelanggan?.alamat || '');
                        $('#tanggal_selesai').val(koState.order.tanggal_selesai || '');
                        // Set CS fields
                        ensureSelectOption($('#cs_user_id'), koState.order.cs || null);
                        ensureSelectOption($('#cs1_user_id'), koState.order.cs1 || null);
                        ensureSelectOption($('#cs2_user_id'), koState.order.cs2 || null);
                        // Set Fotografer
                        ensureSelectOption($('#fotografer_user_id'), koState.order.fotografer || null);
                        ensureSelectOption($('#spv_user_id'), koState.order.spv || null);
                        toggleKasirField(true, koState.order.kasir?.name || '');
                        koAutofilled = true;
                        if (koState.order.sales_mode_id) $('#sales_mode_id').val(String(koState.order.sales_mode_id));
                        if (preserveExistingEdits && autofillDrafts) {
                            if (autofillDrafts.customer_name !== '') $('#customer_name').val(autofillDrafts.customer_name);
                            if (autofillDrafts.phone !== '') $('#phone').val(autofillDrafts.phone);
                            if (autofillDrafts.address !== '') $('#address').val(autofillDrafts.address);
                            if (autofillDrafts.tanggal_selesai !== '') $('#tanggal_selesai').val(autofillDrafts.tanggal_selesai);
                            if (autofillDrafts.sales_mode_id !== '') $('#sales_mode_id').val(String(autofillDrafts.sales_mode_id));
                            if (autofillDrafts.cs_user_id !== '') ensureSelectOption($('#cs_user_id'), { id: autofillDrafts.cs_user_id });
                            if (autofillDrafts.cs1_user_id !== '') ensureSelectOption($('#cs1_user_id'), { id: autofillDrafts.cs1_user_id });
                            if (autofillDrafts.cs2_user_id !== '') ensureSelectOption($('#cs2_user_id'), { id: autofillDrafts.cs2_user_id });
                            if (autofillDrafts.fotografer_user_id !== '') ensureSelectOption($('#fotografer_user_id'), { id: autofillDrafts.fotografer_user_id });
                            if (autofillDrafts.spv_user_id !== '') ensureSelectOption($('#spv_user_id'), { id: autofillDrafts.spv_user_id });
                        }
                    } else {
                        removeExistingRows();
                        toggleKasirField(false, '');
                        renderKoBanner('danger', `KO tidak bisa diproses. Status: ${koState.order?.status_pembayaran || '-'}, sisa: ${formatRupiah(koState.order?.balance || 0)}.`);
                    }
                    refreshSummary();
                    if (callbackAfter) callbackAfter();
                })
                .fail(function () {
                    koState = { exists: false, canAdd: false, canEditExistingItems: false, order: null };
                    resetKoAutofillFields();
                    removeExistingRows();
                    renderKoBanner('danger', 'Gagal cek KO.');
                    if (callbackAfter) callbackAfter();
                });
        }

        function buildPayload(allowMinusStock = false) {
            const existingItems = [];
            $('#product-table-body tr').each(function () {
                if (!isExistingRow(this)) return;
                const id = parseInt($(this).data('existing-id') || 0, 10);
                const originalQty = parseFloat($(this).data('original-qty') || 0);
                const qty = parseFloat($(this).find('.item-qty').val()) || 0;
                if (!id || qty < 0) return;
                if (Math.abs(qty - originalQty) < 0.00001) return;
                existingItems.push({
                    id: id,
                    qty: qty
                });
            });

            const items = [];
            $('#product-table-body tr').each(function () {
                if ($(this).find('td[colspan]').length) return;
                if (isExistingRow(this)) return;
                if ($(this).hasClass('paket-items-row')) return;
                const itemId = $(this).data('item-id');
                const itemType = $(this).data('item-type');
                const harga = parseCurrencyInput($(this).find('.item-harga').val());
                const qty = parseFloat($(this).find('.item-qty').val()) || 0;
                const diskonInput = parseCurrencyInput($(this).find('.item-diskon').val());
                const promoDiskonBaris = parseFloat($(this).attr('data-promo-discount') || '0') || 0;
                const diskon = Math.max(diskonInput - promoDiskonBaris, 0);
                if (!itemId || qty <= 0) return;

                let customItems = null;
                const customAttr = $(this).attr('data-custom-items');
                if (customAttr) {
                    try {
                        customItems = JSON.parse(customAttr);
                    } catch (e) {}
                }

                if (itemType === 'PAKET') {
                    items.push({
                        jenis_item: 'PAKET',
                        paket_id: itemId,
                        qty,
                        harga,
                        diskon,
                        custom_paket_items: customItems
                    });
                } else {
                    items.push({ jenis_item: 'PRODUK', produk_id: itemId, qty, harga, diskon });
                }
            });

            const totalPayments = getTotalPaymentAmount();
            const projection = getKoPaymentProjection();
            const totalTambahan = Math.max(projection.totalTambahan || 0, 0);
            const hasItem = items.length > 0;
            let tipeBayar = 'DP';
            if (koState.canAdd) {
                if (hasItem || (Array.isArray(existingItems) && existingItems.length > 0)) {
                    tipeBayar = 'ADDON';
                } else {
                    const totalTagihan = Math.max(parseFloat(projection.totalTagihan || 0), 0);
                    tipeBayar = totalPayments >= totalTagihan ? 'FINAL' : 'DP';
                }
            } else if (totalPayments >= totalTambahan && totalTambahan > 0) {
                tipeBayar = 'FINAL';
            }

            let hasOverride = false;
            $('#product-table-body tr').each(function () {
                if ($(this).find('td[colspan]').length || isExistingRow(this) || $(this).hasClass('paket-items-row')) return;
                const orig = parseFloat($(this).attr('data-original-price') || 0);
                const cur = parseCurrencyInput($(this).find('.item-harga').val());
                if (Math.abs(cur - orig) > 0.0001) {
                    hasOverride = true;
                }
            });

            return {
                client_request_id: activeClientRequestId || generateClientRequestId(),
                cabang_id: CABANG_DEFAULT_ID,
                sales_mode_id: $('#sales_mode_id').val(),
                tanggal: $('#transaction_date').val(),
                no_ko: ($('#no_ko').val() || '').trim(),
                customer_name: $('#customer_name').val(),
                phone: sanitizePhoneInput($('#phone').val()),
                address: $('#address').val(),
                order_note: $('#order_note').val(),
                tanggal_selesai: $('#tanggal_selesai').val() || null,
                cs_user_id: $('#cs_user_id').val() || null,
                cs1_user_id: $('#cs1_user_id').val() || null,
                cs2_user_id: $('#cs2_user_id').val() || null,
                fotografer_user_id: $('#fotografer_user_id').val() || null,
                spv_user_id: $('#spv_user_id').val() || null,
                is_booking: $('#is_booking').is(':checked') ? 1 : 0,
                booking_date: $('#is_booking').is(':checked') ? $('#booking_date').val() : null,
                booking_time: $('#is_booking').is(':checked') ? $('#booking_time').val() : null,
                promo_kode: selectedPromo?.kode || null,
                promo_sumber: selectedPromo?.sumber || null,
                promo_diskon: getPromoDiscount(getSubtotals().netItem),
                allow_minus_stock: allowMinusStock ? 1 : 0,
                has_price_override: hasOverride ? 1 : 0,
                authorizer_user_id: priceOverrideAuthorizerId || null,
                items: items,
                existing_items: existingItems.length > 0 ? existingItems : null,
                remove_otp: null,
                remove_reason: null,
                payments: totalPayments > 0 ? pendingPayments.map(function (row) {
                    return {
                        metode_pembayaran_id: row.metode_pembayaran_id,
                        nominal: row.nominal,
                        tipe: tipeBayar
                    };
                }) : []
            };
        }

        function hasRemoveExistingItems(payload) {
            if (!Array.isArray(payload?.existing_items)) return false;
            return payload.existing_items.some(function (row) {
                return parseFloat(row?.qty || 0) <= 0;
            });
        }
        function shouldRequireRemoveOtp(payload, projection) {
            if (!(koState.exists && koState.canAdd && hasRemoveExistingItems(payload))) {
                return false;
            }

            // OTP hanya diperlukan jika order sudah LUNAS (PAID)
            // Untuk order DRAFT atau PARTIALLY_PAID, tidak perlu OTP
            const orderStatus = koState.order?.status_pembayaran;
            return orderStatus === 'PAID';
        }

        async function promptRemoveAuthorization() {
            const paymentModalEl = document.getElementById('paymentModal');
            const modalResult = await Swal.fire({
                title: 'OTP Remove Item',
                target: paymentModalEl || document.body,
                backdrop: false,
                html: `
                    <input id="remove-auth-otp" class="swal2-input" placeholder="Masukkan OTP Remove" autocomplete="off">
                    <textarea id="remove-auth-reason" class="swal2-textarea" placeholder="Alasan remove item/paket" rows="3"></textarea>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                didOpen: () => {
                    const otpEl = document.getElementById('remove-auth-otp');
                    if (otpEl) {
                        otpEl.focus();
                    }
                },
                preConfirm: () => {
                    const otp = ($('#remove-auth-otp').val() || '').trim();
                    const reason = ($('#remove-auth-reason').val() || '').trim();
                    if (!otp || !reason) {
                        Swal.showValidationMessage('OTP remove dan alasan wajib diisi.');
                        return false;
                    }
                    return { otp, reason };
                }
            });

            if (!modalResult.isConfirmed) {
                return null;
            }

            return modalResult.value || null;
        }

        function promptPriceOverrideModal(items) {
            return new Promise((resolve) => {
                const $tableBody = $('#override-price-items-table tbody').empty();
                items.forEach(it => {
                    $tableBody.append(`
                        <tr>
                            <td>${it.nama}</td>
                            <td class="text-end">${formatRupiah(it.original_price)}</td>
                            <td class="text-end fw-bold text-primary">${formatRupiah(it.current_price)}</td>
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
                        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
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

        async function submitData(allowMinusStock = false) {
            if (submitInFlight) {
                return;
            }

            activeClientRequestId = activeClientRequestId || generateClientRequestId();
            const payload = buildPayload(allowMinusStock);
            const projection = getKoPaymentProjection();
            const totalPayments = getTotalPaymentAmount();
            const totalItemBaru = Math.max(projection.calc.netItem - projection.promoDisc, 0);
            const hasItem = payload.items.length > 0;
            if (!payload.sales_mode_id) {
                Swal.fire('Validasi', 'Pilih sales mode dulu.', 'warning');
                return;
            }
            if (!payload.customer_name || !payload.phone) {
                Swal.fire('Validasi', 'Nama customer dan No HP wajib diisi.', 'warning');
                return;
            }
            if (!/^\d+$/.test(payload.phone)) {
                Swal.fire('Validasi', 'No HP hanya boleh berisi angka.', 'warning');
                return;
            }
            const hasExistingItemChanges = Array.isArray(payload.existing_items) && payload.existing_items.length > 0;
            const allowExistingKoNoNewItems = koState.exists && koState.canAdd && ((payload.payments?.length || 0) > 0 || hasExistingItemChanges);
            if (!payload.items.length && !allowExistingKoNoNewItems) {
                Swal.fire('Validasi', 'Tambahkan minimal satu item, atau untuk KO existing ubah/remove qty item existing / isi pembayaran pelunasan.', 'warning');
                return;
            }
            const isExistingKoAppend = koState.exists && koState.canAdd;
            if (hasItem && !payload.is_booking && !isExistingKoAppend && totalPayments < (totalItemBaru * 0.5)) {
                Swal.fire('Validasi', 'Harus bayar minimal 50% dari nilai penjualan.', 'warning');
                return;
            }
            if (hasItem && payload.is_booking && totalPayments < 50000) {
                Swal.fire('Validasi', 'Untuk booking, DP minimal Rp 50.000.', 'warning');
                return;
            }
            if (payload.is_booking && !payload.booking_date) {
                Swal.fire('Validasi', 'Tanggal booking wajib diisi.', 'warning');
                return;
            }
            if (payload.is_booking && !payload.booking_time) {
                Swal.fire('Validasi', 'Jam booking wajib diisi.', 'warning');
                return;
            }
            if (totalPayments > 0 && payload.payments.some(p => !p.metode_pembayaran_id)) {
                Swal.fire('Validasi', 'Metode pembayaran harus dipilih untuk setiap pembayaran.', 'warning');
                return;
            }
            const maxPayNow = Math.max(projection.totalTagihan, 0);
            if (totalPayments > (maxPayNow + 0.00001)) {
                Swal.fire('Validasi', `Nominal bayar melebihi tagihan. Maksimal saat ini ${formatRupiah(maxPayNow)}.`, 'warning');
                return;
            }

            // Pengecekan Otorisasi Perubahan Harga
            const overriddenItems = [];
            $('#product-table-body tr').each(function () {
                if (isExistingRow(this) || $(this).find('td[colspan]').length || $(this).hasClass('paket-items-row')) return;
                const $r = $(this);
                const originalPrice = parseFloat($r.attr('data-original-price') || 0);
                const currentPrice = parseCurrencyInput($r.find('.item-harga').val());
                if (Math.abs(currentPrice - originalPrice) > 0.0001) {
                    overriddenItems.push({
                        nama: $r.find('.fw-semibold').text(),
                        original_price: originalPrice,
                        current_price: currentPrice
                    });
                }
            });

            if (overriddenItems.length > 0 && !CAN_OVERRIDE_PRICE && !priceOverrideAuthorizerId) {
                const authSuccess = await promptPriceOverrideModal(overriddenItems);
                if (!authSuccess) {
                    return;
                }
                payload.has_price_override = 1;
                payload.authorizer_user_id = priceOverrideAuthorizerId;
            }

            // Warning: jika remove item menyebabkan tagihan lebih kecil dari pembayaran yang sudah masuk
            if (hasRemoveExistingItems(payload) && koState.canAdd) {
                const paidTotal = Math.max(parseFloat(koState.order?.paid_total || 0), 0);
                const newBalance = Math.max(projection.totalTagihan - paidTotal, 0);
                if (paidTotal > projection.totalTagihan) {
                    const selisih = paidTotal - projection.totalTagihan;
                    await Swal.fire({
                        title: 'Perhatian',
                        html: `Pengurangan item menyebabkan tagihan menjadi lebih kecil dari pembayaran yang sudah masuk.<br><br>Pembayaran sudah masuk: <b>${formatRupiah(paidTotal)}</b><br>Tagihan baru: <b>${formatRupiah(projection.totalTagihan)}</b><br>Selisih kelebihan bayar: <b>${formatRupiah(selisih)}</b><br><br>Sisa tagihan setelah penyesuaian: <b>${formatRupiah(newBalance)}</b>`,
                        icon: 'info',
                        confirmButtonText: 'Mengerti, Lanjutkan'
                    });
                }
            }

            const needRemoveAuthorization = shouldRequireRemoveOtp(payload, projection);
            if (needRemoveAuthorization) {
                const authPayload = await promptRemoveAuthorization();
                if (!authPayload) {
                    return;
                }
                payload.remove_otp = authPayload.otp;
                payload.remove_reason = authPayload.reason;
            }

            setSubmitState(true);
            $.ajax({
                url: URL_SIMPAN,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                data: payload
            }).done(function (res) {
                const orderId = res?.data?.id;
                if (orderId) {
                    window.open(`${URL_STRUK_BASE}/${orderId}`, '_blank');
                }
                Swal.fire('Berhasil', res.message || 'Transaksi tersimpan.', 'success').then(() => window.location.reload());
            }).fail(function (xhr) {
                setSubmitState(false);
                if ((xhr?.status || 0) > 0) {
                    activeClientRequestId = null;
                }
                const res = xhr.responseJSON;
                if (res && res.status === 'INSUFFICIENT_STOCK' && Array.isArray(res.insufficient_items)) {
                    setSubmitState(false);
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
                        <p class="small text-muted mb-0">Apakah Anda ingin tetap melanjutkan transaksi ini? (Stok akan tetap dipotong hingga minus)</p>
                    `;

                    Swal.fire({
                        title: 'Stok Tidak Mencukupi',
                        html: itemsHtml,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Ya, Lanjutkan Transaksi (Minus)',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitData(true);
                        }
                    });
                    return;
                }

                const e = xhr.responseJSON?.errors;
                if (e) {
                    const k = Object.keys(e)[0];
                    Swal.fire('Gagal', e[k][0], 'error');
                    return;
                }
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
            });
        }

        $(function () {
            function initUserRoleSelect2() {
                // Initialize all CS, Fotografer and SPV single-selects
                const singleSelectors = ['#cs_user_id', '#cs1_user_id', '#cs2_user_id', '#fotografer_user_id', '#spv_user_id'];
                singleSelectors.forEach(function (selector) {
                    const $select = $(selector);
                    if (!$select.length || typeof $.fn.select2 !== 'function') {
                        return;
                    }
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                    $select.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: $select.data('placeholder') || 'Pilih user',
                        allowClear: true
                    });
                });
            }

            function initPaymentMethodSelect2() {
                const $modal = $('#paymentModal');
                const $select = $('#payment-modal-method');
                if (!$select.length || typeof $.fn.select2 !== 'function') {
                    return;
                }
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: 'Cari metode pembayaran',
                    allowClear: true
                });
            }

            if (!CAN_TRANSAKSI_BACKDATE) {
                const today = '{{ now()->format('Y-m-d') }}';
                $('#transaction_date').val(today);
            }

            if (STALE_SHIFT_DATE) {
                $('#pay-btn').prop('disabled', true);
            }

            if (!$('#booking_time').val()) {
                $('#booking_time').val('{{ now()->format('H:i') }}');
            }

            $('#is_booking').on('change', function () {
                $('#booking-date-wrap').toggleClass('d-none', !this.checked);
                $('#booking-time-wrap').toggleClass('d-none', !this.checked);
            });

            $('#btn-check-ko').on('click', () => checkKo());
            $('#no_ko').on('blur', () => checkKo());

            $('#product-search').on('input', function () {
                const q = $(this).val().trim();
                if (q.length < 2) { $('#search-results-dropdown').hide(); return; }
                if (!$('#sales_mode_id').val()) {
                    $('#search-results-dropdown').hide();
                    Swal.fire('Pilih Sales Mode', 'Silakan pilih sales mode dulu sebelum mencari item.', 'warning');
                    return;
                }
                if (searchTimer) {
                    clearTimeout(searchTimer);
                }

                const salesModeId = $('#sales_mode_id').val() || 0;
                const cacheKey = `${CABANG_DEFAULT_ID}|${salesModeId}|${q.toLowerCase()}`;
                if (searchCache.has(cacheKey)) {
                    searchResultsData = searchCache.get(cacheKey) || [];
                    renderSearchResults(searchResultsData);
                    $('#search-results-dropdown').show();
                    return;
                }

                searchTimer = setTimeout(function () {
                    if (searchRequest && searchRequest.readyState !== 4) {
                        searchRequest.abort();
                    }
                    searchRequest = $.get(URL_CARI_PRODUK, { q, cabang_id: CABANG_DEFAULT_ID, sales_mode_id: salesModeId })
                        .done(function (res) {
                            searchResultsData = res || [];
                            searchCache.set(cacheKey, searchResultsData);
                            renderSearchResults(searchResultsData);
                            $('#search-results-dropdown').show();
                        })
                        .fail(function (xhr) {
                            if (xhr?.statusText === 'abort') return;
                            searchResultsData = [];
                            $('#search-results').html('<div class="list-group-item text-danger">Gagal memuat data item</div>');
                            $('#search-results-dropdown').show();
                        });
                }, 250);
            });
            $(document).on('click', '.search-result-item', function () {
                const id = $(this).data('id');
                const item = searchResultsData.find(x => x.id == id);
                if (item) addProduct(item);
                $('#product-search').val('');
                $('#search-results-dropdown').hide();
                fetchPromosi();
            });
            $('#add-product-btn').on('click', function () {
                if (!$('#sales_mode_id').val()) {
                    Swal.fire('Pilih Sales Mode', 'Silakan pilih sales mode dulu sebelum menambah item.', 'warning');
                    return;
                }
                if (searchResultsData.length) {
                    addProduct(searchResultsData[0]);
                    fetchPromosi();
                }
            });
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#product-search, #search-results-dropdown, #add-product-btn').length) {
                    $('#search-results-dropdown').hide();
                }
            });
            $(document).on('input', '.item-harga, .item-qty, .item-diskon', function () {
                if ($(this).hasClass('item-qty')) {
                    const tr = $(this).closest('tr');
                    const minQty = isExistingRow(tr) ? 0 : 1;
                    this.value = sanitizeIntegerInput(this.value, minQty);
                } else if ($(this).hasClass('item-harga') || $(this).hasClass('item-diskon')) {
                    this.value = this.value.replace(/[^\d,.\-]/g, '');
                }

                if ($(this).hasClass('item-harga')) {
                    const tr = $(this).closest('tr');
                    if (!isExistingRow(tr)) {
                        const originalPrice = parseFloat(tr.attr('data-original-price') || 0);
                        const currentPrice = parseCurrencyInput($(this).val());
                        const hasOverride = Math.abs(currentPrice - originalPrice) > 0.0001;
                        tr.find('.badge-override-price').toggleClass('d-none', !hasOverride);
                        priceOverrideAuthorizerId = null;
                    }
                }

                // Recalculate subtotal for the row actively
                const tr = $(this).closest('tr');
                if (tr.length) {
                    updateRowSubtotal(tr);
                }

                refreshSummary();
                fetchPromosi();
            });
            $('#phone').on('input', function () {
                this.value = sanitizePhoneInput(this.value);
            });
            $(document).on('focus', '.item-harga, .item-diskon, #payment-modal-amount', function () {
                this.value = toPlainCurrencyString(this.value);
            });
            $(document).on('blur', '.item-qty', function () {
                const tr = $(this).closest('tr');
                if (tr.data('existing') === 1) {
                    // Existing booking item can be set to zero to mark as removed.
                    if (parseInt(this.value) < 0 || isNaN(parseInt(this.value))) {
                        this.value = 0;
                        $(this).trigger('input');
                    }
                }
            });
            $(document).on('click', '.btn-remove-existing-item', function () {
                const tr = $(this).closest('tr');
                tr.find('.item-qty').val('0').trigger('input');
            });
            $(document).on('blur', '.item-harga, .item-diskon, #payment-modal-amount', function () {
                applyCurrencyFormat(this);
                refreshSummary();
            });
            $('#payment-modal-amount').on('input', function () {
                this.value = this.value.replace(/[^\d,.\-]/g, '');
                refreshPaymentModalMeta();
            });
            $('#payment-modal-method').on('change', function () {
                refreshPaymentModalMeta();
            });
            $('#btn-fill-billing').on('click', function () {
                const remaining = getRemainingBillAmount();
                if (remaining <= 0) {
                    refreshPaymentModalMeta();
                    return;
                }
                $('#payment-modal-amount').val(formatCurrencyInput(remaining));
                refreshPaymentModalMeta();
            });
            // Toggle Paket Collapse
            $(document).on('click', '.btn-toggle-paket', function () {
                const targetId = $(this).data('target');
                const $collapseRow = $(targetId);
                $collapseRow.toggleClass('d-none');
                const isVisible = !$collapseRow.hasClass('d-none');
                $(this).find('i.bi-chevron-down, i.bi-chevron-up')
                    .toggleClass('bi-chevron-down', !isVisible)
                    .toggleClass('bi-chevron-up', isVisible);
            });

            // Subitem Qty Change
            $(document).on('change input', '.subitem-qty', function () {
                const parentId = $(this).data('parent');
                const subIdx = parseInt($(this).data('sub-index'), 10);
                const newQty = parseFloat($(this).val()) || 0;
                const $parent = $('#' + parentId);
                let customItems = JSON.parse($parent.attr('data-custom-items') || '[]');
                if (customItems[subIdx]) {
                    customItems[subIdx].qty = newQty;
                    $parent.attr('data-custom-items', JSON.stringify(customItems));
                    $parent.find('.badge-customized').removeClass('d-none');
                }
            });

            // Remove Subitem from Package
            $(document).on('click', '.btn-remove-subitem', function () {
                const parentId = $(this).data('parent');
                const subIdx = parseInt($(this).data('sub-index'), 10);
                const $parent = $('#' + parentId);
                let customItems = JSON.parse($parent.attr('data-custom-items') || '[]');
                customItems.splice(subIdx, 1);
                $parent.attr('data-custom-items', JSON.stringify(customItems));
                $parent.find('.paket-count').text(customItems.length);
                $parent.find('.badge-customized').removeClass('d-none');
                const $tbody = $('#collapse-' + parentId).find('.subitem-table tbody');
                $tbody.html(renderSubitemRows(customItems, parentId));
            });

            // Live search subitem di rincian paket
            $(document).on('focusin', '.paket-subitem-search', function () {
                const parentId = $(this).data('parent');
                searchPaketSubitem(parentId, $(this).val(), true);
            });

            $(document).on('input', '.paket-subitem-search', function () {
                const parentId = $(this).data('parent');
                searchPaketSubitem(parentId, $(this).val(), false);
            });

            $(document).on('click', '.btn-add-subitem', function () {
                const parentId = $(this).data('parent');
                const $input = $('.paket-subitem-search[data-parent="' + parentId + '"]');
                if ($input.length) {
                    $input.trigger('focus');
                    searchPaketSubitem(parentId, $input.val(), true);
                }
            });

            $(document).on('click', '.paket-subitem-result', function () {
                const parentId = $(this).data('parent');
                const productId = parseInt($(this).data('product-id'), 10);
                const state = getPaketSubitemState(parentId);
                const product = state.results.find(function (item) {
                    return Number(item.id) === productId;
                });

                if (product) {
                    addSubitemToPackage(parentId, product);
                }
            });

            $(document).on('keydown', '.paket-subitem-search', function (event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();

                const parentId = $(this).data('parent');
                const state = getPaketSubitemState(parentId);
                if (state.results.length > 0) {
                    addSubitemToPackage(parentId, state.results[0]);
                    return;
                }

                searchPaketSubitem(parentId, $(this).val(), false);
            });

            $(document).on('click', function (event) {
                const $wrap = $(event.target).closest('.paket-subitem-search-wrap');
                if ($wrap.length) return;

                $('.paket-subitem-results').addClass('d-none').empty();
            });

            $(document).on('click', '.remove-item', function () {
                const $row = $(this).closest('tr');
                const rowId = $row.attr('id');
                if (rowId) {
                    $('#collapse-' + rowId).remove();
                }
                $row.remove();
                resetTableIfEmpty();
                refreshSummary();
                fetchPromosi();
            });
            $('#btn-promo-modal').on('click', function () {
                fetchPromosi(function () {
                    const modal = new bootstrap.Modal(document.getElementById('promoModal'));
                    modal.show();
                });
            });
            $(document).on('click', '.btn-apply-promo', function () {
                const idx = parseInt($(this).data('idx'));
                const promo = cachedPromosi[idx] || null;
                if (!promo) {
                    selectedPromo = null;
                    normalizeRowDiscountFromPromo();
                    renderPromoTable(cachedPromosi);
                    refreshSummary();
                    Swal.fire('Promo tidak valid', 'Promo tidak ditemukan.', 'warning');
                    return;
                }

                if (selectedPromo && String(selectedPromo.kode || '') === String(promo.kode || '')) {
                    selectedPromo = null;
                    normalizeRowDiscountFromPromo();
                    renderPromoTable(cachedPromosi);
                    refreshSummary();
                    Swal.fire('Promo Dibatalkan', 'Promo berhasil dilepas dari transaksi.', 'info');
                    return;
                }

                selectedPromo = promo;
                applyPromoDiscountToRows();
                renderPromoTable(cachedPromosi);
                refreshSummary();
                Swal.fire('Promo Diterapkan', `${promo.kode} - ${promo.nama}`, 'success');
            });
            $('#btn-apply-voucher-code').on('click', function () {
                const code = ($('#voucher-code-input').val() || '').trim().toUpperCase();
                if (!code) {
                    Swal.fire('Kode kosong', 'Masukkan kode voucher.', 'warning');
                    return;
                }
                const found = cachedPromosi.find(x => (x.kode || '').toUpperCase() === code);
                if (!found) {
                    Swal.fire('Voucher tidak ditemukan', 'Kode voucher tidak valid untuk transaksi ini.', 'error');
                    return;
                }
                selectedPromo = found;
                applyPromoDiscountToRows();
                renderPromoTable(cachedPromosi);
                refreshSummary();
                Swal.fire('Voucher Diterapkan', `${found.kode} - ${found.nama}`, 'success');
            });
            $('#btn-remove-promo').on('click', function () {
                selectedPromo = null;
                normalizeRowDiscountFromPromo();
                renderPromoTable(cachedPromosi);
                refreshSummary();
                Swal.fire('Promo Dihapus', 'Transaksi kembali tanpa promosi.', 'info');
            });
            $('#btn-add-payment').on('click', function () {
                const metodeId = parseInt($('#payment-modal-method').val() || 0, 10);
                const nominalInput = Math.max(parseCurrencyInput($('#payment-modal-amount').val()), 0);
                const remaining = getRemainingBillAmount();
                const methodMeta = getSelectedPaymentMethodMeta();
                if (!metodeId) {
                    Swal.fire('Validasi', 'Pilih metode pembayaran dulu.', 'warning');
                    return;
                }
                if (nominalInput <= 0) {
                    Swal.fire('Validasi', 'Nominal pembayaran harus lebih dari 0.', 'warning');
                    return;
                }
                if (remaining <= 0) {
                    Swal.fire('Validasi', 'Tagihan sudah lunas.', 'warning');
                    return;
                }
                if (!methodMeta.isCash && nominalInput > (remaining + 0.00001)) {
                    Swal.fire('Validasi', `Nominal bayar melebihi sisa tagihan. Maksimal saat ini ${formatRupiah(remaining)}.`, 'warning');
                    return;
                }
                const nominalApplied = methodMeta.isCash
                    ? Math.min(nominalInput, remaining)
                    : nominalInput;
                pendingPayments.push({
                    metode_pembayaran_id: metodeId,
                    metode_nama: getPaymentMethodName(metodeId),
                    nominal: nominalApplied
                });
                $('#payment-modal-amount').val('0');
                applyCurrencyFormat(document.getElementById('payment-modal-amount'));
                renderPayments();
                refreshSummary();
            });
            $(document).on('click', '.btn-remove-payment', function () {
                const idx = parseInt($(this).data('idx'), 10);
                if (!Number.isNaN(idx) && pendingPayments[idx]) {
                    pendingPayments.splice(idx, 1);
                    renderPayments();
                    refreshSummary();
                }
            });
            $('#transaction_date').on('change', fetchPromosi);
            $('#transaction_date').on('change', function () {
                if (!CAN_TRANSAKSI_BACKDATE) {
                    const today = '{{ now()->format('Y-m-d') }}';
                    if ($(this).val() !== today) {
                        $(this).val(today);
                        Swal.fire('Akses Ditolak', 'Anda tidak memiliki akses input transaksi backdate.', 'warning');
                    }
                }
            });

            $('#pay-btn').on('click', function () {
                if (STALE_SHIFT_DATE) {
                    Swal.fire(
                        'Shift Belum Ditutup',
                        `Tanggal ${STALE_SHIFT_DATE} belum tutup kasir. Tutup kasir sebelum melanjutkan transaksi hari ini.`,
                        'warning'
                    );
                    return;
                }
                checkKo(function () {
                    if (koState.exists && !koState.canAdd) {
                        Swal.fire('Ditolak', `KO tidak bisa diproses.\nStatus: ${koState.order?.status_pembayaran || '-'}\nSisa: ${formatRupiah(koState.order?.balance || 0)}`, 'error');
                        return;
                    }
                    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
                    modal.show();
                }, { preserveExistingEdits: true });
            });
            $('#paymentModal').on('shown.bs.modal', function () {
                initPaymentMethodSelect2();
                refreshPaymentModalMeta();
                $('#payment-modal-method').trigger('focus');
            });

            $('#btn-submit-payment').on('click', function () {
                const proceed = () => submitData();
                checkKo(function () {
                    if (koState.exists && koState.canAdd) {
                        const projection = getKoPaymentProjection();
                        const statusMeta = getPaymentStatusMeta(projection);
                        const noKoLabel = koState.order?.nomor_ko || ($('#no_ko').val() || '-');
                        const noSoLabel = koState.order?.nomor_so || '-';
                        const statusInfo = statusMeta.isSettled
                            ? 'Status setelah bayar sekarang: <span class="badge bg-success">LUNAS</span>.'
                            : (statusMeta.label === 'KURANG BAYAR'
                                ? `Status setelah bayar sekarang: <span class="badge bg-danger">KURANG BAYAR ${formatRupiah(projection.estimated)}</span>.`
                                : `Status setelah bayar sekarang: <span class="badge bg-secondary">${statusMeta.label}</span>.`);
                        const detail = `
                                KO <b>${noKoLabel}</b> ditemukan.<br>
                                Referensi SO: <b>${noSoLabel}</b><br>
                                Sisa tagihan sebelumnya: <b>${formatRupiah(projection.existingBalance)}</b><br>
                                Perubahan transaksi saat ini: <b>${formatRupiah(projection.totalTambahan)}</b><br>
                                Total pembayaran saat ini: <b>${formatRupiah(projection.totalPayments)}</b><br>
                                ${statusInfo}<br>
                                Lanjut simpan transaksi?
                            `;
                        Swal.fire({
                            title: 'KO Existing Ditemukan',
                            html: detail,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Lanjutkan',
                            cancelButtonText: 'Batal'
                        }).then(r => { if (r.isConfirmed) proceed(); });
                    } else if (koState.exists && !koState.canAdd) {
                        Swal.fire('Ditolak', `KO tidak bisa diproses.\nStatus: ${koState.order?.status_pembayaran || '-'}\nSisa: ${formatRupiah(koState.order?.balance || 0)}`, 'error');
                    } else {
                        proceed();
                    }
                }, { preserveExistingEdits: true });
            });

            applyCurrencyFormat(document.getElementById('payment-modal-amount'));
            initUserRoleSelect2();
            initPaymentMethodSelect2();
            renderPayments();
            refreshSummary();
            refreshPaymentModalMeta();
        });
    </script>
@endpush
