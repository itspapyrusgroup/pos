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
        .notes { margin-top: 10px; line-height: 1.45; }
        .sign { width: 260px; margin-left: auto; text-align: center; margin-top: 18px; }
        .sign .space { height: 56px; }
    </style>
</head>
<body>
@php($perusahaan = $permintaan->cabang->perusahaan ?? null)
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
                <div class="to-label">Untuk Cabang</div>
                <div class="to-name">{{ $permintaan->cabang->nama ?? '-' }}</div>
                <div>{{ $permintaan->cabang->alamat ?? '-' }}</div>
            </td>
            <td style="width: 45%;">
                <div class="title">Permintaan Barang</div>
                <table class="meta">
                    <tr><td>Nomor</td><td>: {{ $permintaan->nomor_permintaan }}</td></tr>
                    <tr><td>Tanggal</td><td>: {{ \Carbon\Carbon::parse($permintaan->tanggal_permintaan)->format('d M Y') }}</td></tr>
                    <tr><td>Tgl Dibutuhkan</td><td>: {{ $permintaan->tanggal_dibutuhkan ? \Carbon\Carbon::parse($permintaan->tanggal_dibutuhkan)->format('d M Y') : '-' }}</td></tr>
                    <tr><td>Status</td><td>: {{ $permintaan->status }}</td></tr>
                    <tr><td>Dibuat Oleh</td><td>: {{ $permintaan->pembuat->name ?? auth()->user()?->name ?? '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 38px;">No</th>
                <th style="width: 130px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th class="text-right" style="width: 90px;">Kts.</th>
                <th style="width: 140px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
        @forelse($permintaan->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->produk->kode ?? '-' }}</td>
                <td>{{ $item->produk->nama ?? '-' }}</td>
                <td class="text-right">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                <td>{{ $item->catatan ?: '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center;">Tidak ada item.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="notes">
        <strong>Keterangan:</strong> {{ $permintaan->catatan ?: '-' }}
    </div>

    <div class="sign">
        <div>Bagian Pembelian</div>
        <div class="space"></div>
        <div><strong>{{ $permintaan->pembuat->name ?? auth()->user()?->name ?? '-' }}</strong></div>
    </div>
</body>
</html>
