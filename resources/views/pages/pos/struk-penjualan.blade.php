<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Struk {{ $order->nomor_so }}</title>
    <style>
        @page { size: 76mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: Consolas, "Lucida Console", "Courier New", Courier, monospace;
            font-size: 13px;
            line-height: 1.28;
            font-weight: 500;
            color: #000;
            margin: 0;
            padding: 0;
            overflow: visible;
            background: #fff;
            -webkit-font-smoothing: none;
            -moz-osx-font-smoothing: auto;
            text-rendering: optimizeSpeed;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            font-variant-numeric: lining-nums tabular-nums;
            font-feature-settings: "lnum" 1, "tnum" 1;
        }
        .wrap {
            width: 62mm;
            margin-left: 7mm;
            margin-right: 2mm;
            padding-top: 2mm;
            padding-bottom: 2mm;
        }
        .center { text-align: center; }
        .line {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .item-name { font-weight: 700; }
        .muted { color: #000; }
        .mt-4 { margin-top: 4px; }
        .mt-8 { margin-top: 8px; }
        .bold { font-weight: 700; }
        .preline { white-space: pre-line; }
        .store {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .2px;
        }
        .meta { font-size: 12px; line-height: 1.2; }
        .summary-total {
            font-size: 14px;
            font-weight: 700;
        }
        .num {
            font-weight: 700;
            letter-spacing: .15px;
        }
        .stamp {
            margin: 8px auto 2px;
            display: inline-block;
            border: 2px solid #000;
            padding: 1px 8px;
            font-weight: 700;
            letter-spacing: 0;
            transform: none;
        }
        @media print {
            body {
                font-family: Consolas, "Lucida Console", "Courier New", Courier, monospace;
                font-size: 13px;
                line-height: 1.28;
                font-weight: 500;
            }
        }
    </style>
</head>
<body>
    @php
        $subtotalBruto = (float) $order->items->sum(function ($item) {
            return (float) $item->qty * (float) $item->harga;
        });
        $paymentsSorted = $order->pembayaran
            ->sortBy(fn ($payment) => optional($payment->tanggal_bayar)?->timestamp ?? 0)
            ->values();
        $receiptPrintedAt = $paymentsSorted->last()?->tanggal_bayar ?? $order->created_at;
        $diskonItemTotal = (float) $order->items->sum('diskon');
        $diskonPromoTotal = max(0, ($subtotalBruto - $diskonItemTotal) - (float) $order->total);

        $promoLines = collect(explode("\n", (string) ($order->catatan ?? '')))
            ->map(fn ($line) => trim((string) $line))
            ->filter(fn ($line) => str_starts_with($line, 'Promo dipakai:'))
            ->values();
        $promoInfo = $promoLines->last();

        $kasirList = collect();
        $kasirUtama = trim((string) ($order->kasir?->name ?? ''));
        if ($kasirUtama !== '') {
            $kasirList->push($kasirUtama);
        }
        $kasirList = $kasirList
            ->concat(
                $order->items
                    ->map(fn ($item) => trim((string) ($item->kasir?->name ?? '')))
                    ->filter()
            )
            ->unique()
            ->values();
    @endphp
    <div class="wrap">
        <div class="center store">{{ strtoupper($order->cabang?->nama ?? 'PAPYRUS') }}</div>
        <div class="center meta">{{ $order->cabang?->alamat ?? '-' }}</div>
        <div class="center meta">HP: {{ $order->cabang?->no_hp ?? '-' }}</div>
        <div class="center meta">{{ $website }}</div>

        <div class="line"></div>
        <div>No SO : {{ $order->nomor_so }}</div>
        <div>No KO : {{ $order->kantongOrder?->nomor_ko ?? '-' }}</div>
        @if($kasirList->isNotEmpty())
            @foreach($kasirList as $kasirName)
                <div>Kasir {{ $loop->iteration }} : {{ $kasirName }}</div>
            @endforeach
        @else
            <div>Kasir 1 : -</div>
        @endif
        <div>Tgl   : {{ $receiptPrintedAt?->format('d-m-Y H:i') ?? '-' }}</div>
        <div>Cust  : {{ $order->customer_name ?? $order->pelanggan?->nama ?? '-' }}</div>
        <div>HP    : {{ $order->customer_phone ?? $order->pelanggan?->no_hp ?? '-' }}</div>

        <div class="line"></div>
        @foreach($order->items as $item)
            @php
                $namaItem = $item->produk?->nama ?? $item->paket?->nama ?? '-';
                $subtotal = (float) $item->subtotal;
            @endphp
            <div class="item-name">{{ $namaItem }}</div>
            <div class="row muted">
                <span class="num">{{ number_format((float) $item->qty, 0, ',', '.') }} x {{ number_format((float) $item->harga, 0, ',', '.') }}</span>
                <span class="num">{{ number_format($subtotal, 0, ',', '.') }}</span>
            </div>
        @endforeach

        <div class="line"></div>
        <div class="row"><span>Subtotal</span><span class="num">Rp {{ number_format($subtotalBruto, 0, ',', '.') }}</span></div>
        <div class="row"><span>Diskon Item</span><span class="num">Rp {{ number_format($diskonItemTotal, 0, ',', '.') }}</span></div>
        <div class="row"><span>Diskon Promo</span><span class="num">Rp {{ number_format($diskonPromoTotal, 0, ',', '.') }}</span></div>
        @if($promoInfo)
            <div class="muted mt-4">{{ $promoInfo }}</div>
        @endif
        <div class="row summary-total"><span>Total</span><span class="num">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</span></div>
        <div class="row"><span>Terbayar</span><span class="bold num">Rp {{ number_format((float) $order->paid_total, 0, ',', '.') }}</span></div>
        <div class="row"><span>Sisa</span><span class="bold num">Rp {{ number_format((float) $order->balance, 0, ',', '.') }}</span></div>

        <div class="line"></div>
        @if(!empty($isReprint))
            <div class="center">
                <span class="stamp">REPRINT</span>
            </div>
            <div class="line"></div>
        @endif

        <div class="bold">Pembayaran</div>
        @forelse($paymentsSorted as $pay)
            <div class="row mt-4">
                <span>{{ $pay->metodePembayaran?->nama ?? '-' }} ({{ $pay->tipe }})</span>
                <span class="num">Rp {{ number_format((float) $pay->nominal, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="muted">Belum ada pembayaran</div>
        @endforelse

        <div class="line"></div>
        <div class="center preline">
            {{ trim((string) ($order->cabang?->struk_footer ?: 'Terima kasih sudah berbelanja di Papyrus.')) }}
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
            setTimeout(function () { window.close(); }, 600);
        });
    </script>
</body>
</html>
