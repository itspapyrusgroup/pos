@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@if(!empty($isShortcutMode))
<div class="card radius-10 mb-3">
    <div class="card-body">
        <h5 class="mb-1">Selamat datang, {{ auth()->user()?->name ?? 'Karyawan' }}</h5>
        <p class="text-muted mb-0">Anda tidak memiliki akses ke dashboard ringkasan. Gunakan shortcut menu di bawah ini.</p>
    </div>
</div>

<div class="card radius-10">
    <div class="card-body">
        <h6 class="mb-3">Shortcut Menu</h6>
        <div class="row g-2">
            @forelse($shortcutMenus ?? [] as $menu)
                <div class="col-12 col-md-6 col-xl-4">
                    <a href="{{ route($menu['route']) }}" class="btn btn-outline-primary w-100 text-start py-2">
                        <i class="{{ $menu['icon'] }} me-2"></i>{{ $menu['label'] }}
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning mb-0">Belum ada menu yang bisa diakses untuk role Anda. Hubungi admin untuk pengaturan permission.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@else
@push('styles')
<link href="{{ asset('assets/vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
@endpush

<style>
    .esb-filter-card {
        border: 1px solid #bfe3ea;
        border-radius: 0;
        overflow: hidden;
        box-shadow: none;
    }
    .esb-filter-head {
        background: #dff1f6;
        color: #1b5f78;
        font-weight: 700;
        padding: 12px 16px;
        border-bottom: 1px solid #bfe3ea;
    }
    .esb-filter-body {
        padding: 18px 16px 16px;
        background: #fff;
    }
    .esb-filter-label {
        font-size: 12px;
        font-weight: 700;
        color: #213547;
        margin-bottom: 6px;
        display: inline-block;
    }
    .esb-filter-actions {
        display: flex;
        justify-content: flex-end;
        align-items: end;
        height: 100%;
    }
    .esb-search-btn {
        min-width: 118px;
        background: #1294a6;
        border-color: #1294a6;
        color: #fff;
    }
    .esb-search-btn:hover,
    .esb-search-btn:focus {
        background: #0f8090;
        border-color: #0f8090;
        color: #fff;
    }
    .esb-tabs {
        display: flex;
        gap: 4px;
        align-items: end;
        margin: 0 0 -1px;
        padding: 0;
        list-style: none;
    }
    .esb-tab {
        display: inline-flex;
        align-items: center;
        min-height: 38px;
        padding: 9px 14px;
        border: 1px solid transparent;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        color: #425466;
        background: transparent;
        font-size: 14px;
        cursor: pointer;
    }
    .esb-tab.active {
        background: #1294a6;
        border-color: #1294a6;
        color: #fff;
        font-weight: 700;
    }
    .esb-live-card {
        border: 1px solid #d8e2e8;
        border-radius: 0;
        box-shadow: none;
        overflow: hidden;
    }
    .esb-live-head {
        padding: 16px;
        border-bottom: 1px solid #e6edf1;
        background: #fff;
    }
    .esb-live-title {
        margin: 0;
        font-size: 32px;
        font-weight: 700;
        color: #1d2a36;
    }
    .esb-live-subtitle {
        margin-top: 6px;
        font-size: 13px;
        color: #6a7784;
        font-weight: 600;
    }
    .esb-live-body {
        padding: 14px;
        background: #f8fbfc;
    }
    .esb-stat-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 24px;
        margin-bottom: 18px;
    }
    .esb-stat-card {
        display: flex;
        align-items: stretch;
        min-height: 90px;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 6px 18px rgba(18, 35, 52, 0.06);
    }
    .esb-stat-icon {
        width: 86px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 34px;
        flex-shrink: 0;
    }
    .esb-stat-content {
        flex: 1;
        padding: 14px 18px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
    }
    .esb-stat-label {
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
        color: #60717f;
        margin-bottom: 4px;
        line-height: 1.35;
    }
    .esb-stat-value {
        font-size: 22px;
        font-weight: 700;
        color: #0f8ea4;
        line-height: 1.15;
        word-break: break-word;
    }
    .esb-stat-value.danger {
        color: #ff563d;
    }
    .esb-tone-green { background: linear-gradient(180deg, #11b692, #0da98b); }
    .esb-tone-blue { background: linear-gradient(180deg, #1e84c8, #1464b3); }
    .esb-tone-red { background: linear-gradient(180deg, #ff4b57, #ff7a3d); }
    .esb-tone-orange { background: linear-gradient(180deg, #ffa640, #ff7b3f); }
    .esb-tone-cyan { background: linear-gradient(180deg, #149db0, #107b95); }
    .esb-chart-card,
    .esb-table-card {
        border: 1px solid #d8e2e8;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(18, 35, 52, 0.05);
    }
    .esb-panel-head {
        padding: 16px 18px;
        border-bottom: 1px solid #edf2f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .esb-panel-title {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: var(--bs-body-color);
    }
    .esb-panel-subtitle {
        font-size: 12px;
        color: var(--bs-secondary-color);
        font-weight: 600;
    }
    .esb-panel-body {
        padding: 16px 18px 18px;
    }
    .esb-table-card .table thead th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .2px;
        color: #5c6c79;
        border-bottom-width: 1px;
        white-space: nowrap;
    }
    .esb-table-card .table tbody td {
        font-size: 13px;
        white-space: nowrap;
    }
    .esb-tab-pane { display: none; }
    .esb-tab-pane.active { display: block; }
    .esb-ranking-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }
    .esb-ranking-card {
        border: 1px solid #d8e2e8;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(18, 35, 52, 0.05);
        overflow: hidden;
    }
    .esb-ranking-head {
        padding: 14px 18px;
        border-bottom: 1px solid #edf2f5;
        background: #fff;
    }
    .esb-ranking-title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: var(--bs-body-color);
    }
    .esb-ranking-subtitle {
        margin-top: 4px;
        font-size: 12px;
        color: var(--bs-secondary-color);
        font-weight: 600;
    }
    .esb-ranking-body {
        padding: 14px 18px 18px;
    }
    @media (max-width: 991.98px) {
        .esb-stat-grid {
            grid-template-columns: 1fr;
        }
        .esb-ranking-grid {
            grid-template-columns: 1fr;
        }
    }
    html[data-bs-theme="dark"] .esb-filter-card,
    html.dark-theme .esb-filter-card {
        border-color: #31424c;
        background: #162029;
    }
    html[data-bs-theme="dark"] .esb-filter-head,
    html.dark-theme .esb-filter-head {
        background: #153744;
        color: #8ed9e5;
        border-bottom-color: #31424c;
    }
    html[data-bs-theme="dark"] .esb-filter-body,
    html.dark-theme .esb-filter-body,
    html[data-bs-theme="dark"] .esb-live-head,
    html.dark-theme .esb-live-head,
    html[data-bs-theme="dark"] .esb-chart-card,
    html.dark-theme .esb-chart-card,
    html[data-bs-theme="dark"] .esb-table-card,
    html.dark-theme .esb-table-card,
    html[data-bs-theme="dark"] .esb-ranking-card,
    html.dark-theme .esb-ranking-card,
    html[data-bs-theme="dark"] .esb-ranking-head,
    html.dark-theme .esb-ranking-head,
    html[data-bs-theme="dark"] .esb-stat-card,
    html.dark-theme .esb-stat-card {
        background: #1b2630;
    }
    html[data-bs-theme="dark"] .esb-live-card,
    html.dark-theme .esb-live-card {
        border-color: #31424c;
        background: #162029;
    }
    html[data-bs-theme="dark"] .esb-live-body,
    html.dark-theme .esb-live-body {
        background: #162029;
    }
    html[data-bs-theme="dark"] .esb-filter-label,
    html.dark-theme .esb-filter-label,
    html[data-bs-theme="dark"] .esb-live-title,
    html.dark-theme .esb-live-title,
    html[data-bs-theme="dark"] .esb-panel-title,
    html.dark-theme .esb-panel-title,
    html[data-bs-theme="dark"] .esb-ranking-title,
    html.dark-theme .esb-ranking-title {
        color: #e7eef5;
    }
    html[data-bs-theme="dark"] .esb-live-subtitle,
    html.dark-theme .esb-live-subtitle,
    html[data-bs-theme="dark"] .esb-panel-subtitle,
    html.dark-theme .esb-panel-subtitle,
    html[data-bs-theme="dark"] .esb-ranking-subtitle,
    html.dark-theme .esb-ranking-subtitle,
    html[data-bs-theme="dark"] .esb-stat-label,
    html.dark-theme .esb-stat-label {
        color: #99a9b7;
    }
    html[data-bs-theme="dark"] .esb-panel-head,
    html.dark-theme .esb-panel-head,
    html[data-bs-theme="dark"] .esb-ranking-head,
    html.dark-theme .esb-ranking-head,
    html[data-bs-theme="dark"] .esb-live-head,
    html.dark-theme .esb-live-head {
        border-bottom-color: #2a3944;
    }
    html[data-bs-theme="dark"] .esb-tab,
    html.dark-theme .esb-tab {
        color: #a7bac8;
    }
    html[data-bs-theme="dark"] .esb-table-card .table thead th,
    html.dark-theme .esb-table-card .table thead th,
    html[data-bs-theme="dark"] .esb-ranking-card .table thead th,
    html.dark-theme .esb-ranking-card .table thead th {
        color: #9db0be;
        border-bottom-color: #32414b;
    }
    html[data-bs-theme="dark"] .esb-table-card .table tbody td,
    html.dark-theme .esb-table-card .table tbody td,
    html[data-bs-theme="dark"] .esb-ranking-card .table tbody td,
    html.dark-theme .esb-ranking-card .table tbody td {
        color: #d6e0e8;
        border-color: #27353e;
    }
    html[data-bs-theme="dark"] .esb-stat-value,
    html.dark-theme .esb-stat-value {
        color: #62d7eb;
    }
    html[data-bs-theme="dark"] .esb-stat-value.danger,
    html.dark-theme .esb-stat-value.danger {
        color: #ff7c69;
    }
    .esb-date-range-input[readonly] {
        background-color: #fff;
        cursor: pointer;
    }
    html[data-bs-theme="dark"] .esb-date-range-input[readonly],
    html.dark-theme .esb-date-range-input[readonly] {
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

@php
    $periodLabel = \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') . ' - ' . \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y');
    $statCards = [
        [
            'label' => 'Kas Bersih / Net Sales',
            'value' => 'Rp ' . number_format((float) ($summary['total_pembayaran'] ?? 0), 0, ',', '.'),
            'icon' => 'bi bi-cash-stack',
            'tone' => 'esb-tone-green',
            'danger' => false,
        ],
        [
            'label' => 'Kas Masuk Kotor / Gross Sales',
            'value' => 'Rp ' . number_format((float) ($summary['total_pembayaran_kotor'] ?? 0), 0, ',', '.'),
            'icon' => 'bi bi-wallet2',
            'tone' => 'esb-tone-cyan',
            'danger' => false,
        ],
        [
            'label' => 'Kas Keluar Void / Refund',
            'value' => 'Rp ' . number_format((float) ($summary['total_pembayaran_void'] ?? 0), 0, ',', '.'),
            'icon' => 'bi bi-slash-circle',
            'tone' => 'esb-tone-red',
            'danger' => true,
        ],
        [
            'label' => 'Total Void Order',
            'value' => 'Rp ' . number_format((float) ($summary['total_void'] ?? 0), 0, ',', '.'),
            'icon' => 'bi bi-x-octagon',
            'tone' => 'esb-tone-red',
            'danger' => true,
        ],
        [
            'label' => 'Jumlah Transaksi',
            'value' => number_format((float) ($summary['jumlah_transaksi'] ?? 0), 0, ',', '.'),
            'icon' => 'bi bi-receipt-cutoff',
            'tone' => 'esb-tone-blue',
            'danger' => false,
        ],
        [
            'label' => 'Rata-rata Penjualan Harian',
            'value' => 'Rp ' . number_format((float) ($summary['rata_harian'] ?? 0), 0, ',', '.'),
            'icon' => 'bi bi-graph-up-arrow',
            'tone' => 'esb-tone-orange',
            'danger' => false,
        ],
    ];
@endphp

<div class="card esb-filter-card mb-3">
    <div class="esb-filter-head">Filter</div>
    <div class="esb-filter-body">
        <form method="GET" action="{{ route('dashboard') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label for="cabang_id" class="esb-filter-label">Branch</label>
                    <select id="cabang_id" name="cabang_id[]" class="form-select multiple-select" multiple data-placeholder="Pilih cabang">
                        @foreach ($cabangs as $cabang)
                            <option value="{{ $cabang->id }}" @selected(in_array((int) $cabang->id, array_map('intval', $filters['cabang_id'] ?? []), true))>{{ $cabang->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-5">
                    <label for="date_range" class="esb-filter-label">Tanggal</label>
                    <input
                        id="date_range"
                        type="text"
                        class="form-control esb-date-range-input"
                        placeholder="Pilih rentang tanggal"
                        value="{{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}"
                        readonly
                    >
                    <input id="date_from" name="date_from" type="hidden" value="{{ $filters['date_from'] }}">
                    <input id="date_to" name="date_to" type="hidden" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-12 col-lg-2">
                    <div class="esb-filter-actions">
                        <button type="submit" class="btn esb-search-btn">Search</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<ul class="esb-tabs">
    <li class="esb-tab active" data-target="live-view-pane">Live View</li>
    <li class="esb-tab" data-target="sales-growth-pane">Sales Growth</li>
    <li class="esb-tab" data-target="top-worst-paket-pane">Top &amp; Worst Paket</li>
    <li class="esb-tab" data-target="top-worst-item-pane">Top &amp; Worst Item/Barang</li>
</ul>

<div class="esb-tab-pane active" id="live-view-pane">
<div class="card esb-live-card">
    <div class="esb-live-head">
        <div>
            <h2 class="esb-live-title">Live View</h2>
            <div class="esb-live-subtitle">{{ $periodLabel }}</div>
        </div>
    </div>

    <div class="esb-live-body">
        <div class="esb-stat-grid">
            @foreach($statCards as $card)
                <div class="esb-stat-card">
                    <div class="esb-stat-icon {{ $card['tone'] }}">
                        <i class="{{ $card['icon'] }}"></i>
                    </div>
                    <div class="esb-stat-content">
                        <div class="esb-stat-label">{{ $card['label'] }}</div>
                        <div class="esb-stat-value {{ $card['danger'] ? 'danger' : '' }}">{{ $card['value'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-7">
                <div class="esb-chart-card h-100">
                    <div class="esb-panel-head">
                        <div>
                            <h6 class="esb-panel-title">Sales Trend</h6>
                            <div class="esb-panel-subtitle">Penjualan harian pada periode terpilih</div>
                        </div>
                    </div>
                    <div class="esb-panel-body">
                        <div id="chart-penjualan-harian"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="esb-table-card h-100">
                    <div class="esb-panel-head">
                        <div>
                            <h6 class="esb-panel-title">Daily Summary</h6>
                            <div class="esb-panel-subtitle">Ringkasan kas dan transaksi harian</div>
                        </div>
                    </div>
                    <div class="esb-panel-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th class="text-end">Transaksi</th>
                                        <th class="text-end">Penjualan</th>
                                        <th class="text-end">Kas Masuk</th>
                                        <th class="text-end">Void</th>
                                        <th class="text-end">Kas Bersih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($harian as $item)
                                        <tr>
                                            <td>{{ $item['label'] }}</td>
                                            <td class="text-end">{{ number_format($item['jumlah_transaksi'], 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($item['total_penjualan'], 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($item['total_pembayaran_kotor'], 0, ',', '.') }}</td>
                                            <td class="text-end text-danger">Rp {{ number_format($item['total_pembayaran_void'], 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($item['total_pembayaran'], 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Belum ada data penjualan pada periode ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="esb-tab-pane" id="sales-growth-pane">
    <div class="card esb-live-card">
        <div class="esb-live-head">
            <div>
                <h2 class="esb-live-title">Sales Growth</h2>
                <div class="esb-live-subtitle">{{ $periodLabel }}</div>
            </div>
        </div>
        <div class="esb-live-body">
            <div class="esb-chart-card">
                <div class="esb-panel-head">
                    <div>
                        <h6 class="esb-panel-title">Sales Growth Chart</h6>
                        <div class="esb-panel-subtitle">Nilai penjualan bersih per tanggal, sudah dikurangi void</div>
                    </div>
                </div>
                <div class="esb-panel-body">
                    <div id="chart-sales-growth"></div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge text-bg-warning">Monday</span>
                        <span class="badge" style="background:#fd7e14;">Tuesday</span>
                        <span class="badge bg-success">Wednesday</span>
                        <span class="badge bg-info text-dark">Thursday</span>
                        <span class="badge bg-primary">Friday</span>
                        <span class="badge" style="background:#1b4f9c;">Saturday</span>
                        <span class="badge bg-danger">Sunday</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="esb-tab-pane" id="top-worst-paket-pane">
    <div class="esb-ranking-grid">
        <div class="esb-ranking-card">
            <div class="esb-ranking-head">
                <h6 class="esb-ranking-title">Top 20 Paket</h6>
                <div class="esb-ranking-subtitle">Berdasarkan qty terjual dari transaksi lunas</div>
            </div>
            <div class="esb-ranking-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Paket</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($topWorstPaket['top'] ?? []) as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row->nama_item ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float) $row->total_qty, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $row->total_penjualan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada data paket lunas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="esb-ranking-card">
            <div class="esb-ranking-head">
                <h6 class="esb-ranking-title">Worst 20 Paket</h6>
                <div class="esb-ranking-subtitle">Qty terendah dari transaksi lunas</div>
            </div>
            <div class="esb-ranking-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Paket</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($topWorstPaket['worst'] ?? []) as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row->nama_item ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float) $row->total_qty, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $row->total_penjualan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada data paket lunas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="esb-tab-pane" id="top-worst-item-pane">
    <div class="esb-ranking-grid">
        <div class="esb-ranking-card">
            <div class="esb-ranking-head">
                <h6 class="esb-ranking-title">Top 20 Item/Barang</h6>
                <div class="esb-ranking-subtitle">Berdasarkan qty terjual dari transaksi lunas</div>
            </div>
            <div class="esb-ranking-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Item/Barang</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($topWorstItem['top'] ?? []) as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row->nama_item ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float) $row->total_qty, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $row->total_penjualan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada data item/barang lunas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="esb-ranking-card">
            <div class="esb-ranking-head">
                <h6 class="esb-ranking-title">Worst 20 Item/Barang</h6>
                <div class="esb-ranking-subtitle">Qty terendah dari transaksi lunas</div>
            </div>
            <div class="esb-ranking-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Item/Barang</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($topWorstItem['worst'] ?? []) as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row->nama_item ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float) $row->total_qty, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $row->total_penjualan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada data item/barang lunas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if(empty($isShortcutMode))
<script src="{{ asset('assets/vendor/flatpickr/flatpickr.min.js') }}"></script>
<script>
  (function () {
    document.querySelectorAll('.esb-tab[data-target]').forEach(function (tab) {
        tab.addEventListener('click', function () {
        const targetId = tab.getAttribute('data-target');
        document.querySelectorAll('.esb-tab').forEach(function (item) {
          item.classList.toggle('active', item === tab);
        });
        document.querySelectorAll('.esb-tab-pane').forEach(function (pane) {
          pane.classList.toggle('active', pane.id === targetId);
        });
        });
    });

    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
      const $branchSelect = $('#cabang_id');
      if ($branchSelect.length) {
        if ($branchSelect.hasClass('select2-hidden-accessible')) {
          $branchSelect.select2('destroy');
        }
        $branchSelect.select2({
          theme: 'bootstrap4',
          width: '100%',
          placeholder: $branchSelect.data('placeholder') || 'Pilih cabang',
          allowClear: true,
          closeOnSelect: false
        });
      }
    }

    if (typeof flatpickr !== 'undefined') {
      const dateRangeInput = document.getElementById('date_range');
      const dateFromInput = document.getElementById('date_from');
      const dateToInput = document.getElementById('date_to');

      if (dateRangeInput && dateFromInput && dateToInput) {
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
      }
    }

    const el = document.querySelector('#chart-penjualan-harian');
    if (!el || typeof ApexCharts === 'undefined') return;

    const data = @json($chart);
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark' ||
      document.documentElement.classList.contains('dark-theme');
    const labelColor = isDark ? '#aebdca' : '#5c6c79';
    const gridColor = isDark ? '#31424c' : '#dfe7ec';
    const weekdayPalette = {
      1: '#f6c445',
      2: '#fd7e14',
      3: '#48b461',
      4: '#19a7ce',
      5: '#0d6efd',
      6: '#1b4f9c',
      7: '#f04452'
    };
    const salesGrowthColors = (data.weekday_index || []).map(function (index) {
      return weekdayPalette[index] || '#1294a6';
    });

    const options = {
      chart: {
        type: 'line',
        height: 340,
        toolbar: { show: false }
      },
      theme: {
        mode: isDark ? 'dark' : 'light'
      },
      series: [
        {
          name: 'Total Penjualan',
          type: 'column',
          data: data.total_penjualan || []
        },
        {
          name: 'Jumlah Transaksi',
          type: 'line',
          data: data.jumlah_transaksi || []
        }
      ],
      stroke: {
        width: [0, 3]
      },
      xaxis: {
        categories: data.labels || [],
        labels: {
          style: {
            colors: labelColor
          }
        }
      },
      yaxis: [
        {
          labels: {
            style: {
              colors: [labelColor]
            },
            formatter: function (value) {
              return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
            }
          }
        },
        {
          opposite: true,
          labels: {
            style: {
              colors: [labelColor]
            },
            formatter: function (value) {
              return Number(value || 0).toLocaleString('id-ID');
            }
          }
        }
      ],
      dataLabels: {
        enabled: false
      },
      colors: ['#0d6efd', '#20c997'],
      grid: {
        strokeDashArray: 4,
        borderColor: gridColor
      },
      tooltip: {
        y: [
          {
            formatter: function (value) {
              return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
            }
          },
          {
            formatter: function (value) {
              return Number(value || 0).toLocaleString('id-ID') + ' transaksi';
            }
          }
        ]
      },
      legend: {
        position: 'top',
        labels: {
          colors: labelColor
        }
      }
    };

    const chart = new ApexCharts(el, options);
    chart.render();

    const growthEl = document.querySelector('#chart-sales-growth');
    if (!growthEl) return;

    const growthOptions = {
      chart: {
        type: 'bar',
        height: 360,
        toolbar: { show: false }
      },
      theme: {
        mode: isDark ? 'dark' : 'light'
      },
      series: [{
        name: 'Net Sales',
        data: data.net_sales || []
      }],
      plotOptions: {
        bar: {
          borderRadius: 4,
          distributed: true,
          columnWidth: '62%'
        }
      },
      dataLabels: {
        enabled: false
      },
      colors: salesGrowthColors,
      xaxis: {
        categories: (data.labels || []).map(function (label, index) {
          const weekday = (data.weekday_label || [])[index] || '';
          return label + (weekday ? ' (' + weekday.substring(0, 3) + ')' : '');
        }),
        labels: {
          rotate: -45,
          style: {
            colors: labelColor
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            colors: [labelColor]
          },
          formatter: function (value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
          }
        }
      },
      grid: {
        borderColor: gridColor,
        strokeDashArray: 4
      },
      legend: {
        show: false
      },
      tooltip: {
        y: {
          formatter: function (value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
          }
        }
      }
    };

    const growthChart = new ApexCharts(growthEl, growthOptions);
    growthChart.render();
  })();
</script>
@endif
@endpush
