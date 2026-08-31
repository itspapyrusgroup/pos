<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .company { text-align: center; margin-bottom: 14px; }
        .company-name { font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .company-address { margin-top: 4px; line-height: 1.45; color: #374151; }
        .head { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .head td { vertical-align: top; }
        .title { text-align: right; font-size: 16px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; }
        .meta td:first-child { width: 105px; color: #4b5563; }
        .to-label { color: #6b7280; margin-bottom: 3px; }
        .to-name { font-weight: 700; }
        .card { border: 1px solid #d1d5db; margin-top: 10px; }
        .card-title { background: #f3f4f6; padding: 7px; font-weight: 700; }
        .card-body { padding: 8px; }
        .summary { width: 100%; border-collapse: collapse; }
        .summary td { padding: 4px 0; }
        .summary td:last-child { text-align: right; width: 120px; }
        .sign { width: 260px; margin-left: auto; text-align: center; margin-top: 18px; }
        .sign .space { height: 56px; }
    </style>
</head>
<body>
@php($faktur = $pembayaran->fakturPembelian)
@php($perusahaan = $faktur->cabang->perusahaan ?? null)
@php($sisa = max((float) ($faktur->total ?? 0) - (float) ($faktur->dibayar ?? 0), 0))
    <div class="company">
        <div class="company-name">{{ $perusahaan->nama ?? 'PERUSAHAAN' }}</div>
        <div class="company-address">
            {{ $perusahaan->alamat ?? '-' }}<br>
            @if(!empty($perusahaan->no_hp)){{ $perusahaan->no_hp }}@else - @endif
        </div>
    </div>

    <table class="head">
        <tr>
            <td style="width: 55%;">
                <div class="to-label">Pemasok</div>
                <div class="to-name">{{ $faktur->pemasok->nama ?? '-' }}</div>
                <div>{{ $faktur->pemasok->alamat ?? '-' }}</div>
                @if(!empty($faktur->pemasok->telepon))
                <div>Telp: {{ $faktur->pemasok->telepon }}</div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="title">Pembayaran Pembelian</div>
                <table class="meta">
                    <tr><td>Nomor</td><td>: {{ $pembayaran->nomor_pembayaran }}</td></tr>
                    <tr><td>No Faktur</td><td>: {{ $faktur->nomor_faktur ?? '-' }}</td></tr>
                    <tr><td>Tanggal Bayar</td><td>: {{ \Carbon\Carbon::parse($pembayaran->tanggal_bayar)->format('d M Y') }}</td></tr>
                    <tr><td>Metode</td><td>: {{ $pembayaran->metodePembayaran->nama ?? '-' }}</td></tr>
                    <tr><td>Dibuat Oleh</td><td>: {{ $pembayaran->pembuat->name ?? auth()->user()?->name ?? '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="card">
        <div class="card-title">Ringkasan Pembayaran</div>
        <div class="card-body">
            <table class="summary">
                <tr><td>Nominal Bayar</td><td>{{ number_format((float) $pembayaran->nominal, 0, ',', '.') }}</td></tr>
                <tr><td>Total Faktur</td><td>{{ number_format((float) ($faktur->total ?? 0), 0, ',', '.') }}</td></tr>
                <tr><td>Total Dibayar</td><td>{{ number_format((float) ($faktur->dibayar ?? 0), 0, ',', '.') }}</td></tr>
                <tr><td>Sisa Tagihan</td><td>{{ number_format($sisa, 0, ',', '.') }}</td></tr>
            </table>
        </div>
    </div>

    <div style="margin-top:10px;"><strong>Keterangan:</strong> {{ $pembayaran->catatan ?: '-' }}</div>

    <div class="sign">
        <div>Bagian Pembelian</div>
        <div class="space"></div>
        <div><strong>{{ $pembayaran->pembuat->name ?? auth()->user()?->name ?? '-' }}</strong></div>
    </div>
</body>
</html>
