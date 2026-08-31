@extends('layouts.app')

@section('title', 'Generate OTP Void/Remove')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Generate OTP Void/Remove</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tipe OTP</label>
                <select class="form-select" id="tipe_void">
                    <option value="FULL">Void Transaksi</option>
                    <option value="PARTIAL">Void Sebagian Item</option>
                    <option value="REMOVE">Remove Item/Paket (Belum Lunas)</option>
                    <option value="CHANGE_METHOD">Ganti Metode Pembayaran</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipe Transaksi</label>
                <select class="form-select" id="tipe_transaksi">
                    <option value="CURRENT_DAY">Current Day</option>
                    <option value="BACKDATE">Backdate</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">No KO / No SO</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="reference" placeholder="KO-... atau SO-...">
                    <button class="btn btn-outline-primary" type="button" id="btn-cari-order">Cek</button>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-danger w-100" type="button" id="btn-generate-otp">Generate OTP</button>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3 d-none" id="order-detail-card">
    <div class="card-header bg-light">
        <strong>Detail Transaksi</strong>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-3"><strong>No SO:</strong> <span id="detail_no_so">-</span></div>
            <div class="col-md-3"><strong>No KO:</strong> <span id="detail_no_ko">-</span></div>
            <div class="col-md-3"><strong>Customer:</strong> <span id="detail_customer">-</span></div>
            <div class="col-md-3"><strong>Status:</strong> <span id="detail_status">-</span></div>
            <div class="col-md-3"><strong>Total:</strong> <span id="detail_total">Rp 0</span></div>
            <div class="col-md-3"><strong>Terbayar:</strong> <span id="detail_paid">Rp 0</span></div>
            <div class="col-md-3"><strong>Sisa:</strong> <span id="detail_balance">Rp 0</span></div>
            <div class="col-md-3"><strong>Tanggal Transaksi:</strong> <span id="detail_date">-</span></div>
        </div>

        <div id="partial-items-wrap" class="d-none">
            <h6 id="partial-items-title">Pilih Item yang Akan Di-Void</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Item</th>
                            <th>Jenis</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Subtotal</th>
                            <th>Status Void</th>
                        </tr>
                    </thead>
                    <tbody id="partial-items-body">
                        <tr><td colspan="6" class="text-center text-muted">Belum ada data item.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="payment-items-wrap" class="d-none mt-3">
            <h6>Pilih Pembayaran yang Akan Diganti Metodenya</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Tanggal Bayar</th>
                            <th>Metode</th>
                            <th>Tipe</th>
                            <th class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody id="payment-items-body">
                        <tr><td colspan="5" class="text-center text-muted">Belum ada data pembayaran.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card d-none" id="otp-result-card">
    <div class="card-header bg-success text-white">
        <strong>OTP Berhasil Dibuat</strong>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3"><strong>Kode OTP:</strong> <span id="otp_code" class="badge bg-danger fs-6">-</span></div>
            <div class="col-md-3"><strong>Berlaku Sampai:</strong> <span id="otp_expired">-</span></div>
            <div class="col-md-3"><strong>Tipe Void:</strong> <span id="otp_tipe_void">-</span></div>
            <div class="col-md-3"><strong>Tipe Transaksi:</strong> <span id="otp_tipe_transaksi">-</span></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const URL_CARI_ORDER_VOID = "{{ route('pos.void-otp.cari-order') }}";
    const URL_GENERATE_OTP_VOID = "{{ route('pos.void-otp.generate') }}";
    const CSRF_TOKEN = "{{ csrf_token() }}";

    let currentOrder = null;

    function formatRupiah(v) {
        const n = parseFloat(v || 0);
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
    }

    function renderOrderDetail(order) {
        currentOrder = order;
        $('#order-detail-card').removeClass('d-none');
        $('#detail_no_so').text(order.nomor_so || '-');
        $('#detail_no_ko').text(order.nomor_ko || '-');
        $('#detail_customer').text(order.customer_name || '-');
        $('#detail_status').text(order.status_pembayaran || '-');
        $('#detail_total').text(formatRupiah(order.total || 0));
        $('#detail_paid').text(formatRupiah(order.paid_total || 0));
        $('#detail_balance').text(formatRupiah(order.balance || 0));
        $('#detail_date').text(order.created_at || '-');

        renderPartialItems(order.items || []);
        renderPaymentItems(order.payments || []);
    }

    function renderPartialItems(items) {
        const body = $('#partial-items-body').empty();
        if (!items.length) {
            body.html('<tr><td colspan="6" class="text-center text-muted">Tidak ada item.</td></tr>');
            return;
        }

        items.forEach((item, idx) => {
            const disabled = item.is_void ? 'disabled' : '';
            const status = item.is_void
                ? '<span class="badge bg-secondary">Sudah Void</span>'
                : '<span class="badge bg-success">Aktif</span>';
            body.append(`
                <tr>
                    <td><input type="checkbox" class="form-check-input partial-item-check" value="${item.id}" ${disabled}></td>
                    <td>${item.nama}</td>
                    <td>${item.jenis}</td>
                    <td class="text-end">${item.qty}</td>
                    <td class="text-end">${formatRupiah(item.subtotal || 0)}</td>
                    <td>${status}</td>
                </tr>
            `);
        });
    }

    function renderPaymentItems(payments) {
        const body = $('#payment-items-body').empty();
        if (!payments.length) {
            body.html('<tr><td colspan="5" class="text-center text-muted">Tidak ada pembayaran yang bisa dikoreksi.</td></tr>');
            return;
        }

        payments.forEach((payment) => {
            body.append(`
                <tr>
                    <td><input type="radio" name="payment_radio" class="form-check-input payment-item-check" value="${payment.id}"></td>
                    <td>${payment.tanggal_bayar || '-'}</td>
                    <td>${payment.metode || '-'}</td>
                    <td>${payment.tipe || '-'}</td>
                    <td class="text-end">${formatRupiah(payment.nominal || 0)}</td>
                </tr>
            `);
        });
    }

    function selectedItemIds() {
        return $('.partial-item-check:checked').map(function () {
            return parseInt($(this).val(), 10);
        }).get();
    }

    function selectedPaymentId() {
        const value = $('.payment-item-check:checked').val();
        return value ? parseInt(value, 10) : 0;
    }

    function refreshPartialMode() {
        const mode = ($('#tipe_void').val() || 'FULL');
        const needItemSelection = mode === 'PARTIAL' || mode === 'REMOVE';
        const needPaymentSelection = mode === 'CHANGE_METHOD';
        const title = mode === 'REMOVE'
            ? 'Pilih Item/Paket yang Akan Di-Remove'
            : 'Pilih Item yang Akan Di-Void';

        $('#partial-items-wrap').toggleClass('d-none', !needItemSelection);
        $('#payment-items-wrap').toggleClass('d-none', !needPaymentSelection);
        $('#partial-items-title').text(title);
    }

    function refreshTransactionTypeMode() {
        const mode = ($('#tipe_void').val() || 'FULL');
        const forceCurrentDay = mode === 'REMOVE' || mode === 'CHANGE_METHOD';
        const select = $('#tipe_transaksi');
        if (forceCurrentDay) {
            select.val('CURRENT_DAY');
        }
        select.prop('disabled', forceCurrentDay);
    }

    function cariOrder() {
        const reference = ($('#reference').val() || '').trim();
        if (!reference) {
            Swal.fire('Validasi', 'No KO / No SO wajib diisi.', 'warning');
            return;
        }

        $.get(URL_CARI_ORDER_VOID, { reference })
            .done(function (res) {
                renderOrderDetail(res.order || null);
                refreshPartialMode();
                refreshTransactionTypeMode();
            })
            .fail(function (xhr) {
                currentOrder = null;
                $('#order-detail-card').addClass('d-none');
                const msg = xhr.responseJSON?.message || 'Order tidak ditemukan.';
                Swal.fire('Gagal', msg, 'error');
            });
    }

    function generateOtp() {
        const reference = ($('#reference').val() || '').trim();
        const tipeVoid = $('#tipe_void').val();
        const tipeTransaksi = $('#tipe_transaksi').val();
        if (!reference) {
            Swal.fire('Validasi', 'No KO / No SO wajib diisi.', 'warning');
            return;
        }

        const payload = {
            reference: reference,
            tipe_void: tipeVoid,
            tipe_transaksi: tipeTransaksi,
            item_ids: (tipeVoid === 'PARTIAL' || tipeVoid === 'REMOVE') ? selectedItemIds() : [],
            payment_id: tipeVoid === 'CHANGE_METHOD' ? selectedPaymentId() : null,
        };

        if ((tipeVoid === 'PARTIAL' || tipeVoid === 'REMOVE') && payload.item_ids.length === 0) {
            Swal.fire(
                'Validasi',
                tipeVoid === 'REMOVE'
                    ? 'Pilih minimal satu item/paket untuk remove.'
                    : 'Pilih minimal satu item untuk void sebagian.',
                'warning'
            );
            return;
        }
        if (tipeVoid === 'CHANGE_METHOD' && !payload.payment_id) {
            Swal.fire('Validasi', 'Pilih satu pembayaran untuk ganti metode.', 'warning');
            return;
        }

        $.ajax({
            url: URL_GENERATE_OTP_VOID,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            data: payload
        }).done(function (res) {
            const d = res.data || {};
            $('#otp-result-card').removeClass('d-none');
            $('#otp_code').text(d.kode_otp || '-');
            $('#otp_expired').text(d.expired_at || '-');
            $('#otp_tipe_void').text(d.tipe_void || '-');
            $('#otp_tipe_transaksi').text(d.tipe_transaksi || '-');
            Swal.fire('Berhasil', res.message || 'OTP berhasil dibuat.', 'success');
        }).fail(function (xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                const key = Object.keys(errors)[0];
                Swal.fire('Gagal', errors[key][0], 'error');
                return;
            }
            Swal.fire('Gagal', xhr.responseJSON?.message || 'Tidak bisa generate OTP.', 'error');
        });
    }

    $(function () {
        $('#btn-cari-order').on('click', cariOrder);
        $('#reference').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cariOrder();
            }
        });
        $('#btn-generate-otp').on('click', generateOtp);
        $('#tipe_void').on('change', function () {
            refreshPartialMode();
            refreshTransactionTypeMode();
        });
        refreshPartialMode();
        refreshTransactionTypeMode();
    });
</script>
@endpush
