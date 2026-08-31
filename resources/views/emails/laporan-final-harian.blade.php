<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Final Harian</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.45;">
    <h2 style="margin-bottom: 8px;">Laporan Final Harian</h2>
    <p style="margin: 0 0 14px 0;">
        Cabang: <strong>{{ $report['cabang_name'] }}</strong><br>
        Tanggal: <strong>{{ $report['report_date_label'] }}</strong><br>
        Waktu generate: <strong>{{ $report['generated_at'] }}</strong><br>
        Timezone: <strong>{{ $report['timezone'] }}</strong>
    </p>

    <h4 style="margin: 12px 0 6px 0;">Ringkasan Utama</h4>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 760px;">
        <tbody>
            <tr><td>Jumlah Transaksi</td><td align="right">{{ number_format((float) $report['summary']['jumlah_transaksi'], 0, ',', '.') }}</td></tr>
            <tr><td>Total Item Terjual</td><td align="right">{{ number_format((float) $report['summary']['total_item_terjual'], 0, ',', '.') }}</td></tr>
            <tr><td>Total Paket Terjual</td><td align="right">{{ number_format((float) $report['summary']['total_paket_terjual'], 0, ',', '.') }}</td></tr>
            <tr><td>Kas Masuk Kotor</td><td align="right">Rp {{ number_format((float) ($report['summary']['total_pembayaran_kotor'] ?? 0), 0, ',', '.') }}</td></tr>
            <tr><td>Void/Refund Kas</td><td align="right">- Rp {{ number_format((float) ($report['summary']['total_pembayaran_void'] ?? 0), 0, ',', '.') }}</td></tr>
            <tr><td><strong>Pendapatan Bersih</strong></td><td align="right"><strong>Rp {{ number_format((float) $report['summary']['pendapatan_bersih'], 0, ',', '.') }}</strong></td></tr>
            <tr><td>Total Void Order</td><td align="right">- Rp {{ number_format((float) ($report['summary']['total_void_order'] ?? 0), 0, ',', '.') }}</td></tr>
            <tr><td>Total Diskon</td><td align="right">Rp {{ number_format((float) $report['summary']['total_diskon'], 0, ',', '.') }}</td></tr>
            <tr><td>Total Sisa Piutang</td><td align="right">Rp {{ number_format((float) $report['summary']['total_sisa'], 0, ',', '.') }}</td></tr>
            <tr><td>Shift Closed</td><td align="right">{{ number_format((float) $report['summary']['shift_closed'], 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <h4 style="margin: 14px 0 6px 0;">Pembayaran Per Metode</h4>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 760px;">
        <thead style="background: #f3f4f6;">
            <tr>
                <th align="left">Kode</th>
                <th align="left">Metode</th>
                <th align="right">Jumlah Transaksi</th>
                <th align="right">Kas Masuk Kotor</th>
                <th align="right">Void/Refund</th>
                <th align="right">Kas Bersih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['payment_by_method'] as $row)
                <tr>
                    <td>{{ $row['kode'] }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td align="right">{{ number_format((float) $row['jumlah_transaksi'], 0, ',', '.') }}</td>
                    <td align="right">Rp {{ number_format((float) ($row['total_kotor'] ?? 0), 0, ',', '.') }}</td>
                    <td align="right">- Rp {{ number_format((float) ($row['total_void'] ?? 0), 0, ',', '.') }}</td>
                    <td align="right">Rp {{ number_format((float) $row['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" align="center">Belum ada pembayaran di tanggal ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h4 style="margin: 14px 0 6px 0;">Detail Per Kasir</h4>
    @forelse(($report['kasir_grouped'] ?? []) as $group)
        <p style="margin: 8px 0 4px 0;"><strong>Kasir: {{ $group['kasir'] }}</strong></p>
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 960px; margin-bottom: 8px;">
            <thead style="background: #f3f4f6;">
                <tr>
                    <th align="left">No KO</th>
                    <th align="left">Customer</th>
                    <th align="left">Paket/Item</th>
                    <th align="left">Metode Pembayaran</th>
                    <th align="right">Total Kas Bersih</th>
                    <th align="right">Diskon</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['rows'] as $row)
                    <tr>
                        <td>{{ $row['no_ko'] }}</td>
                        <td>{{ $row['customer'] }}</td>
                        <td>{{ $row['item_ringkas'] }}</td>
                        <td>{{ $row['metode_pembayaran'] }}</td>
                        <td align="right">Rp {{ number_format((float) $row['total_bayar_masuk'], 0, ',', '.') }}</td>
                        <td align="right">Rp {{ number_format((float) ($row['total_diskon'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr style="background: #f8fafc;">
                    <td colspan="4" align="right"><strong>Subtotal {{ $group['kasir'] }}</strong></td>
                    <td align="right"><strong>Rp {{ number_format((float) $group['subtotal']['total_bayar_masuk'], 0, ',', '.') }}</strong></td>
                    <td align="right"><strong>Rp {{ number_format((float) $group['subtotal']['total_diskon'], 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>
    @empty
        <p>Belum ada detail transaksi kasir pada tanggal ini.</p>
    @endforelse

    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 960px;">
        <tbody>
            <tr style="background: #e5e7eb;">
                <td><strong>Grand Total Transaksi Kasir</strong></td>
                <td align="right"><strong>{{ number_format((float) ($report['kasir_grand_total']['jumlah_transaksi'] ?? 0), 0, ',', '.') }}</strong></td>
            </tr>
            <tr style="background: #e5e7eb;">
                <td><strong>Grand Total Kas Bersih</strong></td>
                <td align="right"><strong>Rp {{ number_format((float) ($report['kasir_grand_total']['total_bayar_masuk'] ?? 0), 0, ',', '.') }}</strong></td>
            </tr>
            <tr style="background: #e5e7eb;">
                <td><strong>Grand Total Diskon</strong></td>
                <td align="right"><strong>Rp {{ number_format((float) ($report['kasir_grand_total']['total_diskon'] ?? 0), 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 14px;">Lampiran Excel berisi detail lengkap: ringkasan, paket terjual, pembayaran, diskon, dan rekap per kasir.</p>
</body>
</html>
