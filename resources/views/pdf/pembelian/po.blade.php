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
        .meta td:first-child { width: 95px; color: #4b5563; }
        .to-label { color: #6b7280; margin-bottom: 3px; }
        .to-name { font-weight: 700; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th { background: #f3f4f6; font-weight: 700; text-align: left; border: 1px solid #d1d5db; padding: 7px; }
        .table td { border: 1px solid #d1d5db; padding: 7px; }
        .text-right { text-align: right; }
        .summary-wrap { width: 310px; margin-left: auto; margin-top: 10px; }
        .summary { width: 100%; border-collapse: collapse; }
        .summary td { padding: 4px 0; }
        .summary td:last-child { text-align: right; width: 110px; }
        .sign { width: 260px; margin-left: auto; text-align: center; margin-top: 18px; }
        .sign .space { height: 56px; }
    </style>
</head>
<body>
@php($perusahaan = $po->cabang->perusahaan ?? null)
@php($total = (float) $po->items->sum('subtotal'))
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
                <div class="to-label">Kepada</div>
                <div class="to-name">{{ $po->pemasok->nama ?? '-' }}</div>
                <div>{{ $po->pemasok->alamat ?? '-' }}</div>
                @if(!empty($po->pemasok->telepon))
                <div>Telp: {{ $po->pemasok->telepon }}</div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="title">Pesanan Pembelian</div>
                <table class="meta">
                    <tr><td>Nomor</td><td>: {{ $po->nomor_po }}</td></tr>
                    <tr><td>Tanggal</td><td>: {{ \Carbon\Carbon::parse($po->tanggal_po)->format('d M Y') }}</td></tr>
                    <tr><td>Tanggal Kirim</td><td>: {{ $po->tanggal_kirim ? \Carbon\Carbon::parse($po->tanggal_kirim)->format('d M Y') : '-' }}</td></tr>
                    <tr><td>Status</td><td>: {{ $po->status }}</td></tr>
                    <tr><td>Dibuat Oleh</td><td>: {{ $po->pembuat->name ?? auth()->user()?->name ?? '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 38px;">No</th>
                <th style="width: 110px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th class="text-right" style="width: 80px;">Kts.</th>
                <th class="text-right" style="width: 100px;">@Harga</th>
                <th class="text-right" style="width: 110px;">Total</th>
            </tr>
        </thead>
        <tbody>
        @forelse($po->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->produk->kode ?? '-' }}</td>
                <td>{{ $item->produk->nama ?? '-' }}</td>
                <td class="text-right">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format((float) $item->harga, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;">Tidak ada item.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary">
            <tr><td>Sub Total</td><td>{{ number_format($total, 0, ',', '.') }}</td></tr>
            <tr><td>Outstanding Qty</td><td>{{ number_format((float) $outstandingQty, 2, ',', '.') }}</td></tr>
            <tr><td><strong>Total</strong></td><td><strong>{{ number_format($total, 0, ',', '.') }}</strong></td></tr>
        </table>
    </div>

    <div style="margin-top:10px;"><strong>Keterangan:</strong> {{ $po->catatan ?: '-' }}</div>

    <div class="sign">
        <div>Bagian Pembelian</div>
        <div class="space"></div>
        <div><strong>{{ $po->pembuat->name ?? auth()->user()?->name ?? '-' }}</strong></div>
    </div>
</body>
</html>
