<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Tutup Kasir</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.45;">
    <h2 style="margin-bottom: 8px;">Laporan Tutup Kasir Harian</h2>
    <p style="margin: 0 0 14px 0;">
        Cabang: <strong>{{ $report['cabang_name'] }}</strong><br>
        Tanggal: <strong>{{ $report['report_date_label'] }}</strong><br>
        Ditutup oleh: <strong>{{ $report['closed_by'] }}</strong><br>
        Waktu kirim: <strong>{{ $report['closed_at'] }}</strong>
    </p>

    <h4 style="margin: 12px 0 6px 0;">Kasir Incharge</h4>
    <p style="margin-top: 0;">{{ $report['closed_by'] }}</p>

    <h4 style="margin: 12px 0 6px 0;">Ringkasan Pendapatan</h4>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 760px;">
        <tbody>
            <tr>
                <td>Jumlah Shift</td>
                <td style="text-align: right;">{{ number_format((float) $report['summary']['jumlah_shift'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Jumlah Transaksi</td>
                <td style="text-align: right;">{{ number_format((float) $report['summary']['jumlah_transaksi'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Kas Masuk Kotor</td>
                <td style="text-align: right;">Rp {{ number_format((float) ($report['summary']['total_pembayaran_kotor'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Void/Refund Kas</td>
                <td style="text-align: right;">- Rp {{ number_format((float) ($report['summary']['total_pembayaran_void'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Pendapatan Bersih</strong></td>
                <td style="text-align: right;"><strong>Rp {{ number_format((float) $report['summary']['pendapatan_bersih'], 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>Total Void Order</td>
                <td style="text-align: right;">- Rp {{ number_format((float) ($report['summary']['total_void_order'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Sisa Piutang</td>
                <td style="text-align: right;">Rp {{ number_format((float) $report['summary']['total_sisa'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h4 style="margin: 14px 0 6px 0;">Pendapatan Per Metode Pembayaran</h4>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 760px;">
        <thead style="background: #f3f4f6;">
            <tr>
                <th align="left">Kode</th>
                <th align="left">Metode</th>
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
                    <td align="right">Rp {{ number_format((float) ($row['total_kotor'] ?? 0), 0, ',', '.') }}</td>
                    <td align="right">- Rp {{ number_format((float) ($row['total_void'] ?? 0), 0, ',', '.') }}</td>
                    <td align="right">Rp {{ number_format((float) $row['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" align="center">Belum ada pembayaran di tanggal ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
