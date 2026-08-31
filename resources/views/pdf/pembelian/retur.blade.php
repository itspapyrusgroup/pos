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
        .summary-wrap { width: 290px; margin-left: auto; margin-top: 10px; }
        .summary { width: 100%; border-collapse: collapse; }
        .summary td { padding: 4px 0; }
        .summary td:last-child { text-align: right; width: 100px; }
        .sign { width: 260px; margin-left: auto; text-align: center; margin-top: 18px; }
        .sign .space { height: 56px; }
    </style>
</head>
<body>
@php($perusahaan = $retur->cabang->perusahaan ?? null)
@php($totalQty = (float) $retur->items->sum('qty'))
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
                <div class="to-name">{{ $retur->pemasok->nama ?? '-' }}</div>
                <div>{{ $retur->pemasok->alamat ?? '-' }}</div>
                @if(!empty($retur->pemasok->telepon))
                <div>Telp: {{ $retur->pemasok->telepon }}</div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="title">Retur Pembelian</div>
                <table class="meta">
                    <tr><td>Nomor</td><td>: {{ $retur->nomor_retur }}</td></tr>
                    <tr><td>No PO</td><td>: {{ $retur->pesananPembelian->nomor_po ?? '-' }}</td></tr>
                    <tr><td>No Penerimaan</td><td>: {{ $retur->penerimaanBarang->nomor_penerimaan ?? '-' }}</td></tr>
                    <tr><td>Tanggal</td><td>: {{ \Carbon\Carbon::parse($retur->tanggal_retur)->format('d M Y') }}</td></tr>
                    <tr><td>Status</td><td>: {{ $retur->status }}</td></tr>
                    <tr><td>Dibuat Oleh</td><td>: {{ $retur->pembuat->name ?? auth()->user()?->name ?? '-' }}</td></tr>
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
                <th class="text-right" style="width: 90px;">Qty Terima</th>
                <th class="text-right" style="width: 90px;">Qty Retur</th>
                <th style="width: 140px;">Alasan</th>
            </tr>
        </thead>
        <tbody>
        @forelse($retur->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->produk->kode ?? '-' }}</td>
                <td>{{ $item->produk->nama ?? '-' }}</td>
                <td class="text-right">{{ number_format((float) ($item->penerimaanBarangItem->qty_terima ?? 0), 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                <td>{{ $item->alasan_retur ?: '-' }}</td>
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
            <tr><td>Total Qty Retur</td><td>{{ number_format($totalQty, 2, ',', '.') }}</td></tr>
        </table>
    </div>

    <div style="margin-top:10px;"><strong>Keterangan:</strong> {{ $retur->catatan ?: '-' }}</div>

    <div class="sign">
        <div>Bagian Pembelian</div>
        <div class="space"></div>
        <div><strong>{{ $retur->pembuat->name ?? auth()->user()?->name ?? '-' }}</strong></div>
    </div>
</body>
</html>
