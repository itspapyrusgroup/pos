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
        .sign { width: 260px; margin-left: auto; text-align: center; margin-top: 18px; }
        .sign .space { height: 56px; }
    </style>
</head>
<body>
@php($perusahaan = $penerimaan->cabang->perusahaan ?? null)
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
                <div class="to-name">{{ $penerimaan->pesananPembelian->pemasok->nama ?? '-' }}</div>
                <div>{{ $penerimaan->pesananPembelian->pemasok->alamat ?? '-' }}</div>
                @if(!empty($penerimaan->pesananPembelian->pemasok->telepon))
                <div>Telp: {{ $penerimaan->pesananPembelian->pemasok->telepon }}</div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="title">Penerimaan Barang</div>
                <table class="meta">
                    <tr><td>Nomor</td><td>: {{ $penerimaan->nomor_penerimaan }}</td></tr>
                    <tr><td>No PO</td><td>: {{ $penerimaan->pesananPembelian->nomor_po ?? '-' }}</td></tr>
                    <tr><td>Tanggal</td><td>: {{ \Carbon\Carbon::parse($penerimaan->tanggal_penerimaan)->format('d M Y') }}</td></tr>
                    <tr><td>Status</td><td>: {{ $penerimaan->status }}</td></tr>
                    <tr><td>Dibuat Oleh</td><td>: {{ $penerimaan->pembuat->name ?? auth()->user()?->name ?? '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 38px;">No</th>
                <th style="width: 120px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th class="text-right" style="width: 90px;">Qty PO</th>
                <th class="text-right" style="width: 90px;">Qty Terima</th>
                <th style="width: 130px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
        @forelse($penerimaan->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->produk->kode ?? '-' }}</td>
                <td>{{ $item->produk->nama ?? '-' }}</td>
                <td class="text-right">{{ number_format((float) ($item->pesananPembelianItem->qty ?? 0), 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format((float) $item->qty_terima, 2, ',', '.') }}</td>
                <td>{{ $item->catatan ?: '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;">Tidak ada item.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top:10px;"><strong>Keterangan:</strong> {{ $penerimaan->catatan ?: '-' }}</div>

    <div class="sign">
        <div>Bagian Pembelian</div>
        <div class="space"></div>
        <div><strong>{{ $penerimaan->pembuat->name ?? auth()->user()?->name ?? '-' }}</strong></div>
    </div>
</body>
</html>
