@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@push('styles')
<link href="{{ asset('assets/vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
<style>
    .sales-report-date-range-input[readonly] {
        background-color: #fff;
        cursor: pointer;
    }
    html[data-bs-theme="dark"] .sales-report-date-range-input[readonly],
    html.dark-theme .sales-report-date-range-input[readonly] {
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
                <li class="breadcrumb-item active" aria-current="page">Laporan Penjualan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input
                    type="text"
                    id="sales_report_date_range"
                    class="form-control sales-report-date-range-input"
                    value="{{ \Carbon\Carbon::parse(request('date_from') ?? now()->toDateString())->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse(request('date_to') ?? request('date_from') ?? now()->toDateString())->format('d-m-Y') }}"
                    readonly
                >
                <input type="hidden" id="sales_report_date_from" name="date_from" value="{{ request('date_from') ?? now()->toDateString() }}">
                <input type="hidden" id="sales_report_date_to" name="date_to" value="{{ request('date_to') ?? request('date_from') ?? now()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" {{ (string) request('cabang_id') === (string) $cabang->id ? 'selected' : '' }}>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ request('no_ko') }}" placeholder="KO-...">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
            <div class="col-12">
                <a href="{{ route('laporan-penjualan') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Jumlah Transaksi</small>
                <h5 class="mb-0">{{ number_format((float) $summary['count'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Gross Penjualan</small>
                <h5 class="mb-0">Rp {{ number_format((float) ($summary['gross_total'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Net Penjualan</small>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['total'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Sisa Piutang</small>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['balance'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body table-responsive">
        <h6 class="mb-3">Ringkasan Pendapatan Harian (berdasarkan Tanggal Bayar)</h6>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal Bayar</th>
                    <th class="text-end">Jumlah Pembayaran</th>
                    <th class="text-end">Net Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentDaily as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</td>
                        <td class="text-end">{{ number_format((float) $row->jumlah_pembayaran, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row->total_pendapatan, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Belum ada pembayaran pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body table-responsive">
        <h6 class="mb-3">Ringkasan Void Harian (berdasarkan Tanggal Efektif Void)</h6>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal Efektif</th>
                    <th class="text-end">Jumlah Void</th>
                    <th class="text-end">Total Pengurangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($voidDaily ?? collect()) as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</td>
                        <td class="text-end">{{ number_format((float) $row->jumlah_void, 0, ',', '.') }}</td>
                        <td class="text-end text-danger">- Rp {{ number_format((float) $row->total_void, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Belum ada void pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>No SO</th>
                    <th>No KO</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Void</th>
                    <th class="text-end">Net</th>
                    <th class="text-end">Terbayar (Periode)</th>
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
                        <td>{{ $order->cabang->nama ?? '-' }}</td>
                        <td>{{ $order->nomor_so }}</td>
                        <td>{{ $order->kantongOrder->nomor_ko ?? '-' }}</td>
                        <td>{{ $order->pelanggan->nama ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float) ((float) $order->total + (float) ($order->void_total_order ?? 0)), 0, ',', '.') }}</td>
                        <td class="text-end text-danger">- Rp {{ number_format((float) ($order->void_total_order ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($order->paid_total_period ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $order->balance, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge bg-{{ $order->status_pembayaran === 'PAID' ? 'success' : ($order->status_pembayaran === 'PARTIALLY_PAID' ? 'warning text-dark' : ($order->status_pembayaran === 'VOID' ? 'danger' : 'secondary')) }}">
                                {{ $order->status_pembayaran }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('riwayat-penjualan.detail', $order) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted">Data penjualan tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $orders->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/flatpickr/flatpickr.min.js') }}"></script>
<script>
    (function () {
        const dateRangeInput = document.getElementById('sales_report_date_range');
        const dateFromInput = document.getElementById('sales_report_date_from');
        const dateToInput = document.getElementById('sales_report_date_to');

        if (!dateRangeInput || !dateFromInput || !dateToInput || typeof flatpickr === 'undefined') {
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
@endpush
