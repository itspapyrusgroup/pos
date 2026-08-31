@extends('layouts.app')

@section('title', 'Laporan Void')

@include('components.date-range-picker-assets')

@section('content')
<style>
    .void-report-wrap {
        overflow-x: auto;
        padding-bottom: 8px;
    }
    .void-report-sheet {
        width: max-content;
        min-width: 100%;
    }
    .void-report-table {
        width: 100%;
        min-width: 1780px;
        table-layout: auto;
    }
    .void-report-table th,
    .void-report-table td {
        vertical-align: top;
        white-space: normal;
        word-break: break-word;
        line-height: 1.4;
        padding: 10px 12px;
    }
    .void-report-table thead th {
        white-space: nowrap;
        font-size: 12px;
        letter-spacing: .2px;
    }
    .void-report-table tbody td {
        font-size: 13px;
    }
    .void-report-table th:nth-child(1),
    .void-report-table th:nth-child(2),
    .void-report-table td:nth-child(1),
    .void-report-table td:nth-child(2) {
        min-width: 138px;
    }
    .void-report-table th:nth-child(4),
    .void-report-table td:nth-child(4) {
        min-width: 120px;
    }
    .void-report-table th:nth-child(5),
    .void-report-table td:nth-child(5) {
        min-width: 190px;
    }
    .void-report-table th:nth-child(6),
    .void-report-table td:nth-child(6) {
        min-width: 135px;
    }
    .void-report-table th:nth-child(7),
    .void-report-table td:nth-child(7) {
        min-width: 260px;
    }
    .void-report-table th:nth-child(8),
    .void-report-table td:nth-child(8) {
        min-width: 140px;
    }
    .void-report-table th:nth-child(9),
    .void-report-table td:nth-child(9) {
        min-width: 230px;
    }
    .void-report-table th:nth-child(10),
    .void-report-table td:nth-child(10),
    .void-report-table th:nth-child(11),
    .void-report-table td:nth-child(11) {
        min-width: 160px;
    }
    .void-report-table th:nth-child(12),
    .void-report-table td:nth-child(12) {
        min-width: 240px;
    }
    .void-report-table tbody tr:nth-child(even) {
        background: rgba(33, 37, 41, 0.02);
    }
    .void-report-table .void-type-badge {
        min-width: 74px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        font-size: 11px;
        letter-spacing: .3px;
    }
    @media (max-width: 767.98px) {
        .void-report-table th,
        .void-report-table td {
            padding: 8px 10px;
            font-size: 12px;
        }
    }
</style>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('laporan.menu') }}">Menu Laporan</a></li>
                <li class="breadcrumb-item active">Laporan Void</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="void_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}" data-date-range-picker data-date-from="#void_date_from" data-date-to="#void_date_to" readonly>
                <input type="hidden" id="void_date_from" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="void_date_to" name="date_to" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" @selected((int) ($filters['cabang_id'] ?? 0) === (int) $cabang->id)>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Kasir Transaksi</label>
                <select name="kasir_user_id" class="form-select">
                    <option value="">Semua Kasir</option>
                    @foreach($kasirList as $kasir)
                        <option value="{{ $kasir->id }}" @selected((int) ($filters['kasir_user_id'] ?? 0) === (int) $kasir->id)>
                            {{ $kasir->name }}{{ $kasir->username ? ' (' . $kasir->username . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipe Void</label>
                <select name="tipe_void" class="form-select">
                    <option value="">Semua</option>
                    <option value="FULL" @selected(($filters['tipe_void'] ?? '') === 'FULL')>FULL</option>
                    <option value="PARTIAL" @selected(($filters['tipe_void'] ?? '') === 'PARTIAL')>PARTIAL</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ $filters['no_ko'] ?? '' }}" placeholder="Cari No KO">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Jumlah Void</small>
                <h5 class="mb-0">{{ number_format((float) ($summary['jumlah_void'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Full Void</small>
                <h5 class="mb-0">{{ number_format((float) ($summary['jumlah_full'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Partial Void</small>
                <h5 class="mb-0">{{ number_format((float) ($summary['jumlah_partial'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Total Nominal Void</small>
                <h5 class="mb-0 text-danger">Rp {{ number_format((float) ($summary['total_nominal_void'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="void-report-wrap">
            <div class="void-report-sheet">
            <table class="table table-sm table-hover align-middle mb-0 void-report-table">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Transaksi</th>
                        <th>Waktu Void</th>
                        <th>Tipe</th>
                        <th>No KO</th>
                        <th>Nama Customer</th>
                        <th>No HP</th>
                        <th>Item/Paket Void</th>
                        <th class="text-end">Nominal Void</th>
                        <th>Metode Pembayaran</th>
                        <th>Kasir</th>
                        <th>Di-void Oleh</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->tanggal_transaksi ? \Carbon\Carbon::parse($row->tanggal_transaksi)->format('d-m-Y H:i') : '-' }}</td>
                            <td>{{ $row->voided_at ? \Carbon\Carbon::parse($row->voided_at)->format('d-m-Y H:i') : '-' }}</td>
                            <td>
                                <span class="badge void-type-badge {{ strtoupper((string) $row->tipe_void) === 'FULL' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                    {{ $row->tipe_void }}
                                </span>
                            </td>
                            <td>{{ $row->nomor_ko ?? '-' }}</td>
                            <td>{{ $row->customer_name ?? '-' }}</td>
                            <td>{{ $row->customer_phone ?? '-' }}</td>
                            <td>{{ $row->void_items ?? '-' }}</td>
                            <td class="text-end text-danger">Rp {{ number_format((float) $row->nominal_void, 0, ',', '.') }}</td>
                            <td>{{ $row->payment_methods ?? '-' }}</td>
                            <td>{{ $row->kasir_nama ?? '-' }}</td>
                            <td>{{ $row->voided_by_name ?? '-' }}</td>
                            <td>{{ $row->alasan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">Belum ada data void pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="7" class="text-end">TOTAL VOID</th>
                        <th class="text-end text-danger">Rp {{ number_format((float) ($summary['total_nominal_void'] ?? 0), 0, ',', '.') }}</th>
                        <th colspan="4"></th>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>
    </div>
</div>

@if(method_exists($rows, 'links'))
    <div class="mt-3">
        {{ $rows->links() }}
    </div>
@endif
@endsection
