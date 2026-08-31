@extends('layouts.app')

@section('title', 'Riwayat Penjualan')

@push('styles')
<link href="{{ asset('assets/vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
<style>
    .sales-date-range-input[readonly] {
        background-color: #fff;
        cursor: pointer;
    }
    html[data-bs-theme="dark"] .sales-date-range-input[readonly],
    html.dark-theme .sales-date-range-input[readonly] {
        background-color: #1b2630;
        color: #e7eef5;
        border-color: #31424c;
    }
    html[data-bs-theme="dark"] .flatpickr-calendar,
    html.dark-theme .flatpickr-calendar {
        background: #1b2630;
        border-color: #31424c;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
    }
    html[data-bs-theme="dark"] .flatpickr-months .flatpickr-month,
    html.dark-theme .flatpickr-months .flatpickr-month,
    html[data-bs-theme="dark"] .flatpickr-current-month .flatpickr-monthDropdown-months,
    html.dark-theme .flatpickr-current-month .flatpickr-monthDropdown-months,
    html[data-bs-theme="dark"] .flatpickr-current-month input.cur-year,
    html.dark-theme .flatpickr-current-month input.cur-year,
    html[data-bs-theme="dark"] span.flatpickr-weekday,
    html.dark-theme span.flatpickr-weekday,
    html[data-bs-theme="dark"] .flatpickr-weekdays,
    html.dark-theme .flatpickr-weekdays {
        background: #1b2630;
        color: #e7eef5;
        fill: #e7eef5;
    }
    html[data-bs-theme="dark"] .flatpickr-months .flatpickr-prev-month,
    html.dark-theme .flatpickr-months .flatpickr-prev-month,
    html[data-bs-theme="dark"] .flatpickr-months .flatpickr-next-month,
    html.dark-theme .flatpickr-months .flatpickr-next-month {
        color: #e7eef5;
        fill: #e7eef5;
    }
    html[data-bs-theme="dark"] .flatpickr-day,
    html.dark-theme .flatpickr-day {
        color: #d6e0e8;
    }
    html[data-bs-theme="dark"] .flatpickr-day.prevMonthDay,
    html.dark-theme .flatpickr-day.prevMonthDay,
    html[data-bs-theme="dark"] .flatpickr-day.nextMonthDay,
    html.dark-theme .flatpickr-day.nextMonthDay {
        color: #6f8493;
    }
    html[data-bs-theme="dark"] .flatpickr-day:hover,
    html.dark-theme .flatpickr-day:hover {
        background: #243442;
        border-color: #243442;
    }
    html[data-bs-theme="dark"] .flatpickr-day.inRange,
    html.dark-theme .flatpickr-day.inRange {
        background: #304353;
        border-color: #304353;
        box-shadow: -5px 0 0 #304353, 5px 0 0 #304353;
    }
    html[data-bs-theme="dark"] .flatpickr-day.startRange,
    html.dark-theme .flatpickr-day.startRange,
    html[data-bs-theme="dark"] .flatpickr-day.endRange,
    html.dark-theme .flatpickr-day.endRange,
    html[data-bs-theme="dark"] .flatpickr-day.selected,
    html.dark-theme .flatpickr-day.selected {
        background: #1294a6;
        border-color: #1294a6;
        color: #fff;
    }
    html[data-bs-theme="dark"] .flatpickr-day.today,
    html.dark-theme .flatpickr-day.today {
        border-color: #4cc9db;
        color: #4cc9db;
    }
    html[data-bs-theme="dark"] .flatpickr-day.today:hover,
    html.dark-theme .flatpickr-day.today:hover {
        background: #243442;
        color: #4cc9db;
    }
</style>
@endpush

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Riwayat Penjualan</li>
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

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
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
        @if(!($canBackdate ?? false))
            <div class="alert alert-warning py-2 mb-3">
                Filter tanggal dibatasi ke hari ini saja.
            </div>
        @endif
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input
                    type="text"
                    id="sales_date_range"
                    class="form-control sales-date-range-input"
                    value="{{ \Carbon\Carbon::parse($filterDateFrom ?? request('date_from') ?? now()->toDateString())->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filterDateTo ?? request('date_to') ?? now()->toDateString())->format('d-m-Y') }}"
                    readonly
                    @disabled(!($canBackdate ?? false))
                >
                <input type="hidden" id="sales_date_from" name="date_from" value="{{ $filterDateFrom ?? request('date_from') ?? now()->toDateString() }}">
                <input type="hidden" id="sales_date_to" name="date_to" value="{{ $filterDateTo ?? request('date_to') ?? now()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Customer / No HP</label>
                <input type="text" name="customer" class="form-control" value="{{ request('customer') }}" placeholder="Nama / No HP">
            </div>
            <div class="col-md-2">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ request('no_ko') }}" placeholder="KO-...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status Bayar</label>
                <select name="status_pembayaran" class="form-select">
                    <option value="">Semua</option>
                    @foreach(['DRAFT', 'PARTIALLY_PAID', 'PAID', 'VOID', 'CANCELLED'] as $status)
                        <option value="{{ $status }}" {{ request('status_pembayaran') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-12">
                <a href="{{ route('riwayat-penjualan') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>No SO</th>
                    <th>No KO</th>
                    <th>Deadline KO</th>
                    <th>Customer</th>
                    <th class="text-end">Total Kotor</th>
                    <th class="text-end">Void</th>
                    <th class="text-end">Total Bersih</th>
                    <th class="text-end">Terbayar</th>
                    <th class="text-end">Sisa</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $index => $order)
                    <tr>
                        <td>{{ $orders->firstItem() + $index }}</td>
                        <td>{{ $order->created_at?->format('d-m-Y H:i') }}</td>
                        <td>{{ $order->nomor_so }}</td>
                        <td>{{ $order->kantongOrder->nomor_ko ?? '-' }}</td>
                        <td>{{ $order->kantongOrder?->tanggal_selesai?->format('d-m-Y') ?? '-' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $order->customer_name ?? $order->pelanggan->nama ?? '-' }}</div>
                            <small class="text-muted">{{ $order->customer_phone ?? $order->pelanggan->no_hp ?? '-' }}</small>
                        </td>
                        <td class="text-end">Rp {{ number_format((float) ((float) $order->total + (float) ($order->void_total_order ?? 0)), 0, ',', '.') }}</td>
                        <td class="text-end text-danger">- Rp {{ number_format((float) ($order->void_total_order ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $order->paid_total, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $order->balance, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-{{ $order->status_pembayaran === 'PAID' ? 'success' : ($order->status_pembayaran === 'PARTIALLY_PAID' ? 'warning text-dark' : ($order->status_pembayaran === 'VOID' ? 'danger' : 'secondary')) }}">
                                {{ $order->status_pembayaran }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('riwayat-penjualan.detail', $order) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            @if(auth()->user()?->hasPermission('pos.koreksi_transaksi.read'))
                                <a href="{{ route('koreksi-transaksi-penjualan', ['no_ko' => $order->kantongOrder->nomor_ko ?? '']) }}" class="btn btn-sm btn-outline-warning">Koreksi</a>
                            @endif
                            @if(auth()->user()?->hasPermission('pos.riwayat.reprint'))
                                <a href="{{ route('riwayat-penjualan.reprint', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Reprint Struk</a>
                            @endif
                            @if(auth()->user()?->hasPermission('pos.riwayat.void') && !in_array((string) $order->status_pembayaran, ['VOID', 'CANCELLED'], true))
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger btn-open-void-modal"
                                    data-order-id="{{ $order->id }}"
                                    data-no-ko="{{ $order->kantongOrder->nomor_ko ?? '-' }}"
                                    data-customer="{{ $order->customer_name ?? $order->pelanggan->nama ?? '-' }}"
                                >
                                    Void
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted">Belum ada data riwayat penjualan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $orders->links() }}
    </div>
</div>

@if(auth()->user()?->hasPermission('pos.riwayat.void'))
<div class="modal fade" id="voidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="voidForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Void Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><strong>No KO:</strong> <span id="void_no_ko">-</span></div>
                    <div class="mb-3"><strong>Atas Nama:</strong> <span id="void_customer">-</span></div>
                    <div class="mb-3">
                        <label class="form-label">OTP</label>
                        <input type="text" name="otp" class="form-control" required placeholder="Masukkan OTP dari tim office/atasan">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Alasan Void</label>
                        <textarea name="alasan_void" class="form-control" rows="3" required placeholder="Wajib isi alasan void"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Proses Void</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/flatpickr/flatpickr.min.js') }}"></script>
<script>
    (function () {
        const dateRangeInput = document.getElementById('sales_date_range');
        const dateFromInput = document.getElementById('sales_date_from');
        const dateToInput = document.getElementById('sales_date_to');

        if (!dateRangeInput || !dateFromInput || !dateToInput || dateRangeInput.disabled || typeof flatpickr === 'undefined') {
            return;
        }

        const parseYmdDate = function (value) {
            if (!value) return null;
            const parts = value.split('-');
            if (parts.length !== 3) return null;

            const year = Number(parts[0]);
            const month = Number(parts[1]) - 1;
            const day = Number(parts[2]);

            if (!year || month < 0 || day < 1) return null;

            return new Date(year, month, day);
        };

        const syncDateRangeDisplay = function (instance, selectedDates) {
            if (!selectedDates.length) {
                dateRangeInput.value = '';
                return;
            }

            const startDate = selectedDates[0];
            const endDate = selectedDates[1] || selectedDates[0];

            dateFromInput.value = instance.formatDate(startDate, 'Y-m-d');
            dateToInput.value = instance.formatDate(endDate, 'Y-m-d');
            dateRangeInput.value = instance.formatDate(startDate, 'd-m-Y') + ' s/d ' + instance.formatDate(endDate, 'd-m-Y');
        };

        flatpickr(dateRangeInput, {
            mode: 'range',
            dateFormat: 'd-m-Y',
            defaultDate: [parseYmdDate(dateFromInput.value), parseYmdDate(dateToInput.value)].filter(Boolean),
            allowInput: false,
            disableMobile: true,
            onReady: function (selectedDates, dateStr, instance) {
                syncDateRangeDisplay(instance, selectedDates);
            },
            onChange: function (selectedDates, dateStr, instance) {
                if (!selectedDates.length) {
                    dateFromInput.value = '';
                    dateToInput.value = '';
                    dateRangeInput.value = '';
                    return;
                }

                syncDateRangeDisplay(instance, selectedDates);
            }
        });
    })();
</script>
@if(auth()->user()?->hasPermission('pos.riwayat.void'))
<script>
    (function () {
        const modalEl = document.getElementById('voidModal');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('voidForm');
        const noKoEl = document.getElementById('void_no_ko');
        const customerEl = document.getElementById('void_customer');

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-open-void-modal');
            if (!btn) return;
            const orderId = btn.getAttribute('data-order-id');
            const noKo = btn.getAttribute('data-no-ko') || '-';
            const customer = btn.getAttribute('data-customer') || '-';
            if (!orderId) return;

            form.setAttribute('action', `{{ url('/riwayat-penjualan') }}/${orderId}/void`);
            noKoEl.textContent = noKo;
            customerEl.textContent = customer;
            form.reset();
            modal.show();
        });
    })();
</script>
@endif
@endpush
