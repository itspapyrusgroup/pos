<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kasir - {{ $report['cabang_name'] ?? 'Cabang' }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #111; margin: 0; padding: 6px; }
        .sheet { border: 1px solid #222; padding: 0; }
        .head { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; padding: 4px 6px; }
        .title { font-size: 44px; font-weight: 500; line-height: 1; margin: 0 0 6px 0; }
        .meta-line { font-size: 40px; font-weight: 700; margin: 2px 0; }
        .meta-label { display: inline-block; min-width: 110px; }
        .timebox { text-align: right; font-size: 42px; line-height: 1.1; }
        table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .grid th, .grid td { border: 1px solid #222; padding: 5px 6px; vertical-align: top; }
        .grid th { background: #f6f6f6; font-weight: 700; text-align: center; }
        .tr { text-align: right; }
        .tc { text-align: center; }
        .bg { border-left: 3px solid #222 !important; }
        .br { border-right: 3px solid #222 !important; }
        .summary { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .summary td { vertical-align: top; padding: 0 6px 6px 0; }
        .sign-title { font-size: 42px; font-weight: 700; margin-bottom: 4px; }
        .sign-box { border: 2px solid #222; height: 120px; }
        .box { border: 2px solid #222; min-height: 120px; padding: 8px; }
        .line { display: table; width: 100%; margin-bottom: 6px; font-size: 38px; font-weight: 700; }
        .line-left, .line-mid, .line-right { display: table-cell; }
        .line-mid { width: 100%; border-bottom: 2px solid #111; }
        .line-right { white-space: nowrap; text-align: right; }
        .stats { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 40px; font-weight: 700; }
        .stats td { padding: 2px 0; }
        .stats td:last-child { text-align: right; }
    </style>
</head>
<body>
@php
    $detail = $report['detail_report'] ?? [];
    $metodeCols = collect($detail['metode_columns'] ?? []);
    $rows = $detail['table_rows'] ?? [];
    $totals = $detail['totals'] ?? [];
    $metodeColspan = max(1, $metodeCols->count());
    $totalColumns = 15 + $metodeColspan;
@endphp

<div class="sheet">
    <table class="head">
        <tr>
            <td>
                <h1 class="title">Laporan Kasir</h1>
                <div class="meta-line"><span class="meta-label">Tanggal</span>{{ \Carbon\Carbon::parse($detail['report_date'] ?? now()->toDateString())->format('d/m/Y') }}</div>
                <div class="meta-line"><span class="meta-label">Kasir</span>{{ $detail['kasir_label'] ?? '-' }}</div>
            </td>
            <td class="timebox">
                <div>{{ \Carbon\Carbon::parse($detail['report_date'] ?? now()->toDateString())->format('d/m/Y') }}</div>
                <div>{{ now()->format('H:i:s') }}</div>
            </td>
        </tr>
    </table>

    <table class="grid">
        <thead>
        <tr>
            <th rowspan="2">Jam</th>
            <th rowspan="2">KO</th>
            <th rowspan="2">Member</th>
            <th rowspan="2">Nama</th>
            <th rowspan="2">Kode</th>
            <th rowspan="2">Jenis</th>
            <th rowspan="2">Qty</th>
            <th rowspan="2">@Rp</th>
            <th rowspan="2">Disc</th>
            <th rowspan="2">@Item</th>
            <th rowspan="2" class="br">Total</th>
            <th colspan="2" class="bg br">Order Lalu</th>
            <th colspan="2" class="bg br">Order Hari Ini</th>
            <th colspan="{{ $metodeColspan }}" class="bg">Sistem Pembayaran</th>
        </tr>
        <tr>
            <th class="bg">DP</th>
            <th class="br">Lunas</th>
            <th class="bg">DP</th>
            <th class="br">Lunas</th>
            @forelse($metodeCols as $idx => $metode)
                <th class="{{ $idx === 0 ? 'bg' : '' }}">{{ $metode['nama'] ?? '-' }}</th>
            @empty
                <th class="bg">-</th>
            @endforelse
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['jam'] }}</td>
                <td>{{ $row['ko'] }}</td>
                <td>{{ $row['member'] }}</td>
                <td>{{ $row['nama_customer'] }}</td>
                <td>{{ $row['kode'] }}</td>
                <td>{{ $row['jenis'] }}</td>
                <td class="tr">{{ $row['qty'] !== '' ? number_format((float) $row['qty'], 0, ',', '.') : '' }}</td>
                <td class="tr">{{ $row['harga'] !== '' ? number_format((float) $row['harga'], 0, ',', '.') : '' }}</td>
                <td class="tr">{{ $row['disc'] !== '' ? number_format((float) $row['disc'], 0, ',', '.') : '' }}</td>
                <td class="tr">{{ $row['item_total'] !== '' ? number_format((float) $row['item_total'], 0, ',', '.') : '' }}</td>
                <td class="tr br">{{ $row['total'] !== '' ? number_format((float) $row['total'], 0, ',', '.') : '' }}</td>
                <td class="tr bg">{{ $row['order_lalu_dp'] !== '' ? number_format((float) $row['order_lalu_dp'], 0, ',', '.') : '' }}</td>
                <td class="tr br">{{ $row['order_lalu_lunas'] !== '' ? number_format((float) $row['order_lalu_lunas'], 0, ',', '.') : '' }}</td>
                <td class="tr bg">{{ $row['order_hari_ini_dp'] !== '' ? number_format((float) $row['order_hari_ini_dp'], 0, ',', '.') : '' }}</td>
                <td class="tr br">{{ $row['order_hari_ini_lunas'] !== '' ? number_format((float) $row['order_hari_ini_lunas'], 0, ',', '.') : '' }}</td>
                @forelse($metodeCols as $idx => $metode)
                    <td class="tr {{ $idx === 0 ? 'bg' : '' }}">
                        {{ $row['pembayaran'] ? number_format((float) ($row['pembayaran'][$metode['id']] ?? 0), 0, ',', '.') : '' }}
                    </td>
                @empty
                    <td class="tr bg">0</td>
                @endforelse
            </tr>
        @empty
            <tr>
                <td colspan="{{ $totalColumns }}" class="tc">Tidak ada pembayaran pada tanggal ini.</td>
            </tr>
        @endforelse
        <tr>
            <td colspan="11" class="tr"><strong>TOTAL</strong></td>
            <td class="tr bg">{{ number_format((float) ($totals['order_lalu_dp'] ?? 0), 0, ',', '.') }}</td>
            <td class="tr br">{{ number_format((float) ($totals['order_lalu_lunas'] ?? 0), 0, ',', '.') }}</td>
            <td class="tr bg">{{ number_format((float) ($totals['order_hari_ini_dp'] ?? 0), 0, ',', '.') }}</td>
            <td class="tr br">{{ number_format((float) ($totals['order_hari_ini_lunas'] ?? 0), 0, ',', '.') }}</td>
            @forelse($metodeCols as $idx => $metode)
                <td class="tr {{ $idx === 0 ? 'bg' : '' }}">{{ number_format((float) ($totals['metode'][$metode['id']] ?? 0), 0, ',', '.') }}</td>
            @empty
                <td class="tr bg">0</td>
            @endforelse
        </tr>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td style="width: 23%;">
                <div class="sign-title">Ttd Kasir</div>
                <div class="sign-box"></div>
            </td>
            <td style="width: 23%;">
                <div class="sign-title">Ttd Supervisor</div>
                <div class="sign-box"></div>
            </td>
            <td style="width: 54%;">
                <div class="box">
                    <div class="line"><span class="line-left">Setoran :</span><span class="line-mid"></span><span class="line-right">Rp {{ number_format((float) ($totals['setoran'] ?? 0), 0, ',', '.') }}</span></div>
                    <div class="line"><span class="line-left">Selisih:</span><span class="line-mid"></span><span class="line-right">Rp {{ number_format((float) ($totals['selisih'] ?? 0), 0, ',', '.') }}</span></div>
                    <table class="stats">
                        <tr><td>Omzet Penjualan</td><td>{{ number_format((float) ($totals['omzet_penjualan'] ?? 0), 0, ',', '.') }}</td></tr>
                        <tr><td>Total Voucher</td><td>{{ number_format((float) ($totals['total_voucher'] ?? 0), 0, ',', '.') }}</td></tr>
                        <tr><td>Total Internal</td><td>{{ number_format((float) ($totals['total_internal'] ?? 0), 0, ',', '.') }}</td></tr>
                        <tr><td>Total Prive</td><td>{{ number_format((float) ($totals['total_prive'] ?? 0), 0, ',', '.') }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
