@extends('layouts.app')

@section('title', 'Detail Penjualan')

@push('styles')
<style>
    .detail-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px 18px;
    }

    .detail-info-item {
        min-width: 0;
    }

    .detail-info-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--bs-secondary-color, #6c757d);
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .detail-info-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--bs-body-color, #212529);
        line-height: 1.35;
        word-break: break-word;
    }

    .detail-info-item {
        padding: 10px 12px;
        border: 1px solid var(--bs-border-color, rgba(255, 255, 255, .08));
        border-radius: .5rem;
        background-color: var(--bs-tertiary-bg, rgba(255, 255, 255, .02));
    }

    .detail-info-item.full-width {
        grid-column: 1 / -1;
    }
</style>
@endpush

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('riwayat-penjualan') }}">Riwayat Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        @if(auth()->user()?->hasPermission('pos.koreksi_transaksi.read'))
            <a href="{{ route('koreksi-transaksi-penjualan', ['no_ko' => $order->kantongOrder->nomor_ko ?? '']) }}" class="btn btn-outline-warning">Koreksi Transaksi</a>
        @endif
        <a href="{{ route('riwayat-penjualan') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="detail-info-grid">
            <div class="detail-info-item">
                <span class="detail-info-label">No SO</span>
                <div class="detail-info-value">{{ $order->nomor_so }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">No KO</span>
                <div class="detail-info-value">{{ $order->kantongOrder->nomor_ko ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">Tanggal</span>
                <div class="detail-info-value">{{ $order->created_at?->format('d-m-Y H:i') ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">Cabang</span>
                <div class="detail-info-value">{{ $order->cabang->nama ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">Deadline KO</span>
                <div class="detail-info-value">{{ $order->kantongOrder?->tanggal_selesai?->format('d-m-Y') ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">Customer</span>
                <div class="detail-info-value">{{ $order->customer_name ?? $order->pelanggan->nama ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">No HP</span>
                <div class="detail-info-value">{{ $order->customer_phone ?? $order->pelanggan->no_hp ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">Status Bayar</span>
                <div class="detail-info-value">{{ $order->status_pembayaran }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">Kasir</span>
                <div class="detail-info-value">{{ $order->kasir?->name ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">CS 1</span>
                <div class="detail-info-value">{{ $order->cs1?->name ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">CS 2</span>
                <div class="detail-info-value">{{ $order->cs2?->name ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">SPV</span>
                <div class="detail-info-value">{{ $order->spv?->name ?? '-' }}</div>
            </div>
            <div class="detail-info-item">
                <span class="detail-info-label">Fotografer</span>
                <div class="detail-info-value">{{ $order->fotografer?->name ?? '-' }}</div>
            </div>
            <div class="detail-info-item full-width">
                <span class="detail-info-label">Catatan</span>
                <div class="detail-info-value">{{ $order->catatan ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Item Penjualan</strong></div>
    <div class="card-body table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Jenis</th>
                    <th>Item</th>
                    <th>Kasir Input Item</th>
                    <th>Status Item</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Harga</th>
                    <th class="text-end">Diskon</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->paket_id ? 'PAKET' : 'PRODUK' }}</td>
                        <td>{{ $item->produk->nama ?? $item->paket->nama ?? '-' }}</td>
                        <td>{{ $item->kasir?->name ?? ($order->kasir?->name ?? '-') }}</td>
                        <td>
                            @if($item->is_void)
                                @if((string) ($item->voidLog?->tipe_void ?? '') === 'REMOVE')
                                    <span class="badge bg-warning text-dark">REMOVE</span>
                                @else
                                    <span class="badge bg-danger">VOID</span>
                                @endif
                            @else
                                <span class="badge bg-success">AKTIF</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $item->qty, 2, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $item->harga, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $item->diskon, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">Tidak ada item.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Riwayat Void</strong></div>
    <div class="card-body table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Waktu Void</th>
                    <th>Tipe</th>
                    <th>Transaksi</th>
                    <th class="text-end">Nominal Void/Remove</th>
                    <th>Tgl Efektif</th>
                    <th>Alasan</th>
                    <th>Eksekutor</th>
                    <th>Generator OTP</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($order->voidLogs ?? collect()) as $void)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $void->voided_at?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td>
                            @if((string) $void->tipe_void === 'REMOVE')
                                <span class="badge bg-warning text-dark">REMOVE</span>
                            @else
                                {{ $void->tipe_void }}
                            @endif
                        </td>
                        <td>{{ $void->tipe_transaksi }}</td>
                        <td class="text-end text-danger">- Rp {{ number_format((float) $void->nominal_void, 0, ',', '.') }}</td>
                        <td>{{ $void->void_effective_date?->format('d-m-Y') ?? '-' }}</td>
                        <td>{{ $void->alasan }}</td>
                        <td>{{ $void->voidedBy?->name ?? '-' }}</td>
                        <td>{{ $void->authorizedBy?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">Belum ada riwayat void.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Riwayat Pembayaran</strong></div>
    <div class="card-body table-responsive">
        @php($totalVoid = (float) (($order->voidLogs ?? collect())->sum('nominal_void')))
        @php($totalPembayaranKotor = (float) (($order->pembayaran ?? collect())->sum('nominal')))
        @php($totalPembayaranBersih = max($totalPembayaranKotor - $totalVoid, 0))
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Metode</th>
                    <th>Tipe</th>
                    <th class="text-end">Nominal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->pembayaran as $pay)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $pay->tanggal_bayar?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td>{{ $pay->metodePembayaran->nama ?? '-' }}</td>
                        <td>{{ $pay->tipe }}</td>
                        <td class="text-end">Rp {{ number_format((float) $pay->nominal, 0, ',', '.') }}</td>
                        <td>
                            @if(auth()->user()?->hasPermission('pos.riwayat.change_payment_method') && (float) $pay->nominal > 0 && (string) $pay->tipe !== 'VOID')
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary btn-open-payment-method-modal"
                                    data-action="{{ route('riwayat-penjualan.payment-method.update', [$order, $pay]) }}"
                                    data-payment-date="{{ $pay->tanggal_bayar?->format('d-m-Y H:i') ?? '-' }}"
                                    data-payment-method="{{ $pay->metodePembayaran->nama ?? '-' }}"
                                    data-payment-nominal="{{ number_format((float) $pay->nominal, 0, ',', '.') }}"
                                >
                                    Ganti Metode
                                </button>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada pembayaran.</td></tr>
                @endforelse
                <tr class="table-light">
                    <td colspan="5" class="text-end fw-semibold">Total Pembayaran Kotor</td>
                    <td class="text-end fw-semibold">Rp {{ number_format($totalPembayaranKotor, 0, ',', '.') }}</td>
                </tr>
                <tr class="table-danger">
                    <td colspan="5" class="text-end fw-semibold">Total Void (Pengurang Pendapatan)</td>
                    <td class="text-end fw-semibold">- Rp {{ number_format($totalVoid, 0, ',', '.') }}</td>
                </tr>
                <tr class="table-success">
                    <td colspan="5" class="text-end fw-semibold">Pembayaran Bersih</td>
                    <td class="text-end fw-semibold">Rp {{ number_format($totalPembayaranBersih, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Log Ganti Metode Pembayaran</strong></div>
    <div class="card-body table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Waktu</th>
                    <th>Metode Lama</th>
                    <th>Metode Baru</th>
                    <th class="text-end">Nominal</th>
                    <th>Alasan</th>
                    <th>Eksekutor</th>
                    <th>Generator OTP</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($order->paymentMethodLogs ?? collect()) as $log)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $log->corrected_at?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td>{{ $log->fromMethod?->nama ?? '-' }}</td>
                        <td>{{ $log->toMethod?->nama ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float) $log->nominal, 0, ',', '.') }}</td>
                        <td>{{ $log->alasan }}</td>
                        <td>{{ $log->correctedBy?->name ?? '-' }}</td>
                        <td>{{ $log->authorizedBy?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">Belum ada log ganti metode pembayaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between"><span>Pendapatan Bersih (Setelah Void)</span><strong>Rp {{ number_format((float) $order->total, 0, ',', '.') }}</strong></div>
        <div class="d-flex justify-content-between"><span>Terbayar Bersih</span><strong>Rp {{ number_format((float) $order->paid_total, 0, ',', '.') }}</strong></div>
        <div class="d-flex justify-content-between"><span>Sisa</span><strong>Rp {{ number_format((float) $order->balance, 0, ',', '.') }}</strong></div>
    </div>
</div>

@if(auth()->user()?->hasPermission('pos.riwayat.change_payment_method'))
<div class="modal fade" id="paymentMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="paymentMethodForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ganti Metode Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><strong>Tanggal Bayar:</strong> <span id="payment_method_date">-</span></div>
                    <div class="mb-2"><strong>Metode Saat Ini:</strong> <span id="payment_method_old">-</span></div>
                    <div class="mb-3"><strong>Nominal:</strong> <span id="payment_method_nominal">Rp 0</span></div>
                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran Baru</label>
                        <select name="metode_pembayaran_id" class="form-select" id="payment_method_new" required>
                            <option value="">Pilih metode pembayaran</option>
                            @foreach(($metodePembayaran ?? collect()) as $metode)
                                <option value="{{ $metode->id }}">{{ $metode->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">OTP</label>
                        <input type="text" name="otp" class="form-control" required placeholder="OTP ganti metode pembayaran">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Alasan</label>
                        <textarea name="alasan" class="form-control" rows="3" required placeholder="Wajib isi alasan"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if(auth()->user()?->hasPermission('pos.riwayat.change_payment_method'))
<script>
    (function () {
        const modalEl = document.getElementById('paymentMethodModal');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('paymentMethodForm');
        const dateEl = document.getElementById('payment_method_date');
        const oldMethodEl = document.getElementById('payment_method_old');
        const nominalEl = document.getElementById('payment_method_nominal');

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-open-payment-method-modal');
            if (!btn) return;

            form.reset();
            form.setAttribute('action', btn.getAttribute('data-action') || '');
            dateEl.textContent = btn.getAttribute('data-payment-date') || '-';
            oldMethodEl.textContent = btn.getAttribute('data-payment-method') || '-';
            nominalEl.textContent = 'Rp ' + (btn.getAttribute('data-payment-nominal') || '0');
            modal.show();
        });
    })();
</script>
@endif
@endpush
