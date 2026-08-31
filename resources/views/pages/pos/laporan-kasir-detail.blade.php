@extends('layouts.app')

@section('title', 'Laporan Kasir Detail')

@section('content')
<style>
    .print-area { position: relative; overflow-x: auto; padding-bottom: 8px; }
    .psnaps-sheet {
        background: #fff;
        border: 1px solid #222;
        padding: 16px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #111;
        width: max-content;
        min-width: 100%;
        box-sizing: border-box;
    }
    .psnaps-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
    .psnaps-title { font-size: 42px; font-weight: 500; letter-spacing: .2px; margin: 0; line-height: 1; }
    .psnaps-datetime { text-align: right; font-size: 28px; line-height: 1.2; }
    .psnaps-meta { margin-bottom: 8px; font-size: 30px; font-weight: 700; }
    .psnaps-meta-row { display: flex; gap: 20px; margin-top: 2px; }
    .psnaps-meta-label { min-width: 110px; }
    .psnaps-table {
        width: 100%;
        min-width: 1720px;
        border-collapse: collapse;
        table-layout: auto;
    }
    .psnaps-table th, .psnaps-table td {
        border: 1px solid #222;
        padding: 6px 8px;
        vertical-align: top;
        white-space: normal;
        word-break: break-word;
        line-height: 1.35;
    }
    .psnaps-table th { background: #f6f6f6; font-weight: 700; }
    .psnaps-table td:nth-child(2),
    .psnaps-table td:nth-child(4),
    .psnaps-table td:nth-child(6),
    .psnaps-table th:nth-child(2),
    .psnaps-table th:nth-child(4),
    .psnaps-table th:nth-child(6) {
        min-width: 120px;
    }
    .psnaps-table td:nth-child(3),
    .psnaps-table th:nth-child(3) {
        min-width: 110px;
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .nowrap { white-space: nowrap; }
    .border-group-left { border-left: 3px solid #222 !important; }
    .border-group-right { border-right: 3px solid #222 !important; }
    .summary-grid { display: grid; grid-template-columns: 220px 220px 1fr; gap: 16px; margin-top: 10px; align-items: start; }
    .sign-box { border: 2px solid #222; height: 110px; }
    .sign-title { font-size: 30px; font-weight: 700; margin-bottom: 2px; }
    .bottom-box { border: 2px solid #222; min-height: 150px; padding: 8px; }
    .bottom-line { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 24px; font-weight: 700; }
    .line-fill { border-bottom: 2px solid #111; flex: 1; height: 0; }
    .bottom-stats { display: grid; grid-template-columns: 1fr auto; row-gap: 4px; column-gap: 10px; margin-top: 6px; font-size: 24px; font-weight: 700; max-width: 420px; margin-left: auto; }
    .filters-card { margin-bottom: 14px; }
    .print-note { font-size: 12px; color: #777; margin-bottom: 8px; }
    @media print {
        @page { size: auto; margin: 8mm; }
        html, body { background: #fff !important; }
        body * { visibility: hidden !important; }
        .print-area, .print-area * { visibility: visible !important; }
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow: visible;
        }
        .no-print { display: none !important; }
        .psnaps-sheet {
            break-inside: avoid;
            border: 1px solid #000;
            margin: 0 0 12px 0;
            box-shadow: none !important;
            width: 100%;
            min-width: 0;
            padding: 10px;
        }
        .psnaps-table { min-width: 0; }
        .psnaps-table th, .psnaps-table td { padding: 3px 4px; }
    }
</style>

<div class="card filters-card no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tanggal Laporan</label>
                <input type="date" name="report_date" class="form-control" value="{{ $filters['report_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" @selected((int) ($filters['cabang_id'] ?? 0) === (int) $cabang->id)>{{ $cabang->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kasir</label>
                <select name="kasir_user_id" class="form-select">
                    <option value="">Semua Kasir</option>
                    @foreach($kasirList as $kasir)
                        <option value="{{ $kasir->id }}" @selected((int) ($filters['kasir_user_id'] ?? 0) === (int) $kasir->id)>
                            {{ $kasir->name }}{{ $kasir->username ? ' (' . $kasir->username . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-primary flex-fill">Tarik Laporan</button>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
            </div>
        </form>
    </div>
</div>

<div class="print-note no-print">
    Layout dibuat mengikuti format PDF PSnaps (header, tabel utama, total, tanda tangan, dan ringkasan bawah).
</div>

<div class="print-area">
    @forelse($kasirGroups as $group)
        <div class="psnaps-sheet">
            <div class="psnaps-header">
                <h1 class="psnaps-title">Laporan Kasir</h1>
                <div class="psnaps-datetime">
                    <div>{{ \Carbon\Carbon::parse($filters['report_date'])->format('d/m/Y') }}</div>
                    <div>{{ now()->format('H:i:s') }}</div>
                </div>
            </div>

            <div class="psnaps-meta">
                <div class="psnaps-meta-row">
                    <div class="psnaps-meta-label">Tanggal</div>
                    <div>{{ \Carbon\Carbon::parse($filters['report_date'])->format('d/m/Y') }}</div>
                </div>
                <div class="psnaps-meta-row">
                    <div class="psnaps-meta-label">Kasir</div>
                    <div>{{ strtolower((string) ($group['kasir']?->username ?? $group['kasir']?->name ?? ('kasir-' . $group['kasir_user_id']))) }}</div>
                </div>
            </div>

            @php
                $metodeCols = $metodeColumns ?? collect();
                $metodeColspan = max(1, $metodeCols->count());
                $totalColumns = 15 + $metodeColspan;
            @endphp
            <table class="psnaps-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center">Jam</th>
                        <th rowspan="2" class="text-center">KO</th>
                        <th rowspan="2" class="text-center">Member</th>
                        <th rowspan="2" class="text-center">Nama</th>
                        <th rowspan="2" class="text-center">Kode</th>
                        <th rowspan="2" class="text-center">Jenis</th>
                        <th rowspan="2" class="text-center">Qty</th>
                        <th rowspan="2" class="text-center">@Rp</th>
                        <th rowspan="2" class="text-center">Disc</th>
                        <th rowspan="2" class="text-center">@Item</th>
                        <th rowspan="2" class="text-center border-group-right">Total</th>

                        <th colspan="2" class="text-center border-group-left border-group-right">Order Lalu</th>
                        <th colspan="2" class="text-center border-group-left border-group-right">Order Hari Ini</th>
                        <th colspan="{{ $metodeColspan }}" class="text-center border-group-left">Sistem Pembayaran</th>
                    </tr>
                    <tr>
                        <th class="text-center border-group-left">DP</th>
                        <th class="text-center border-group-right">Lunas</th>

                        <th class="text-center border-group-left">DP</th>
                        <th class="text-center border-group-right">Lunas</th>
                        @forelse($metodeCols as $metodeIndex => $metode)
                            <th class="text-center {{ $metodeIndex === 0 ? 'border-group-left' : '' }}">{{ $metode->nama }}</th>
                        @empty
                            <th class="text-center border-group-left">-</th>
                        @endforelse
                    </tr>
                </thead>
                <tbody>
                    @forelse($group['table_rows'] as $row)
                        <tr>
                            <td class="nowrap">{{ $row['jam'] }}</td>
                            <td>{{ $row['ko'] }}</td>
                            <td>{{ $row['member'] }}</td>
                            <td>{{ $row['nama_customer'] }}</td>
                            <td>{{ $row['kode'] }}</td>
                            <td>{{ $row['jenis'] }}</td>
                            <td class="text-right">{{ $row['qty'] !== '' ? number_format((float) $row['qty'], 0, ',', '.') : '' }}</td>
                            <td class="text-right">{{ $row['harga'] !== '' ? number_format((float) $row['harga'], 0, ',', '.') : '' }}</td>
                            <td class="text-right">{{ $row['disc'] !== '' ? number_format((float) $row['disc'], 0, ',', '.') : '' }}</td>
                            <td class="text-right">{{ $row['item_total'] !== '' ? number_format((float) $row['item_total'], 0, ',', '.') : '' }}</td>
                            <td class="text-right border-group-right">{{ $row['total'] !== '' ? number_format((float) $row['total'], 0, ',', '.') : '' }}</td>

                            <td class="text-right border-group-left">{{ $row['order_lalu_dp'] !== '' ? number_format((float) $row['order_lalu_dp'], 0, ',', '.') : '' }}</td>
                            <td class="text-right border-group-right">{{ $row['order_lalu_lunas'] !== '' ? number_format((float) $row['order_lalu_lunas'], 0, ',', '.') : '' }}</td>
                            <td class="text-right border-group-left">{{ $row['order_hari_ini_dp'] !== '' ? number_format((float) $row['order_hari_ini_dp'], 0, ',', '.') : '' }}</td>
                            <td class="text-right border-group-right">{{ $row['order_hari_ini_lunas'] !== '' ? number_format((float) $row['order_hari_ini_lunas'], 0, ',', '.') : '' }}</td>

                            @forelse($metodeCols as $metodeIndex => $metode)
                                <td class="text-right {{ $metodeIndex === 0 ? 'border-group-left' : '' }}">
                                    {{ $row['pembayaran'] ? number_format((float) ($row['pembayaran'][$metode->id] ?? 0), 0, ',', '.') : '' }}
                                </td>
                            @empty
                                <td class="text-right border-group-left">0</td>
                            @endforelse
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $totalColumns }}" class="text-center">Tidak ada pembayaran pada tanggal ini.</td>
                        </tr>
                    @endforelse

                    <tr>
                        <td colspan="11" class="text-right"><strong>TOTAL</strong></td>
                        <td class="text-right border-group-left">{{ number_format((float) ($group['totals']['order_lalu_dp'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right border-group-right">{{ number_format((float) ($group['totals']['order_lalu_lunas'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right border-group-left">{{ number_format((float) ($group['totals']['order_hari_ini_dp'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right border-group-right">{{ number_format((float) ($group['totals']['order_hari_ini_lunas'] ?? 0), 0, ',', '.') }}</td>
                        @forelse($metodeCols as $metodeIndex => $metode)
                            <td class="text-right {{ $metodeIndex === 0 ? 'border-group-left' : '' }}">
                                {{ number_format((float) ($group['totals']['metode'][$metode->id] ?? 0), 0, ',', '.') }}
                            </td>
                        @empty
                            <td class="text-right border-group-left">0</td>
                        @endforelse
                    </tr>
                </tbody>
            </table>

            <div class="summary-grid">
                <div>
                    <div class="sign-title">Ttd Kasir</div>
                    <div class="sign-box"></div>
                </div>
                <div>
                    <div class="sign-title">Ttd Supervisor</div>
                    <div class="sign-box"></div>
                </div>
                <div class="bottom-box">
                    <div class="bottom-line">
                        <span>Setoran :</span>
                        <span class="line-fill"></span>
                        <span>Rp {{ number_format((float) ($group['totals']['setoran'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="bottom-line">
                        <span>Selisih:</span>
                        <span class="line-fill"></span>
                        <span>Rp {{ number_format((float) ($group['totals']['selisih'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="bottom-stats">
                        <div>Omzet Penjualan</div><div>{{ number_format((float) ($group['totals']['omzet_penjualan'] ?? 0), 0, ',', '.') }}</div>
                        <div>Total Voucher</div><div>{{ number_format((float) ($group['totals']['total_voucher'] ?? 0), 0, ',', '.') }}</div>
                        <div>Total Internal</div><div>{{ number_format((float) ($group['totals']['total_internal'] ?? 0), 0, ',', '.') }}</div>
                        <div>Total Prive</div><div>{{ number_format((float) ($group['totals']['total_prive'] ?? 0), 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted">
                Tidak ada data pembayaran masuk pada filter ini.
            </div>
        </div>
    @endforelse
</div>
@endsection
