@extends('layouts.app')

@section('title', 'Koreksi Transaksi')

@push('styles')
<style>
    .koreksi-summary-card {
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: .75rem;
        padding: 1rem;
        background: var(--bs-tertiary-bg, #f8f9fa);
    }

    .koreksi-readonly {
        background: var(--bs-secondary-bg, #f1f3f5);
    }

    .koreksi-item-locked {
        border-left: 4px solid #fd7e14;
    }

    .koreksi-row-deleted {
        background: #fff3cd;
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $canUpdate = $user?->hasPermission('pos.koreksi_transaksi.update') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Koreksi Transaksi</li>
            </ol>
        </nav>
    </div>
</div>

<div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <strong>Cabang Aktif:</strong> {{ $activeCabang?->nama ?? '-' }}
    </div>
    @if(($cabangTersedia ?? collect())->count() > 1)
        <form method="POST" action="{{ route('active-cabang.update') }}" class="d-flex align-items-center gap-2">
            @csrf
            <label class="mb-0 small">Switch Cabang:</label>
            <select name="active_cabang_id" class="form-select form-select-sm">
                @foreach($cabangTersedia as $cabangOption)
                    <option value="{{ $cabangOption->id }}" @selected((int) $cabangOption->id === (int) ($cabangDefaultId ?? 0))>
                        {{ $cabangOption->nama }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
        </form>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ request('no_ko') }}" placeholder="Masukkan No KO">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">Ambil Data</button>
                <a href="{{ route('koreksi-transaksi-penjualan') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
            <div class="col-md-5 d-flex align-items-end">
                <div class="small text-muted">
                    Gunakan halaman ini untuk koreksi transaksi secara aman. Ganti paket atau hapus item akan diblok jika item sudah dipakai di tracking/antrian studio.
                </div>
            </div>
        </form>
    </div>
</div>

@if(request('no_ko') && !$order)
    <div class="alert alert-warning">No KO tidak ditemukan di cabang aktif.</div>
@endif

@if($order)
    <form method="POST" action="{{ route('koreksi-transaksi-penjualan.update', $order) }}" id="koreksiForm">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header"><strong>Data Transaksi</strong></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">No SO</label>
                                <input type="text" class="form-control koreksi-readonly" value="{{ $order->nomor_so }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No KO</label>
                                <input type="text" class="form-control koreksi-readonly" value="{{ $order->kantongOrder?->nomor_ko ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status Bayar</label>
                                <input type="text" class="form-control koreksi-readonly" value="{{ $order->status_pembayaran }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Customer</label>
                                <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $order->customer_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No HP Customer</label>
                                <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $order->customer_phone) }}" inputmode="numeric" maxlength="20" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Alamat Customer</label>
                                <input type="text" name="customer_address" class="form-control" value="{{ old('customer_address', $order->customer_address) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CS 1</label>
                                <select name="cs1_user_id" class="form-select">
                                    <option value="">- Pilih CS 1 -</option>
                                    @foreach($userOptions as $userOption)
                                        <option value="{{ $userOption->id }}" @selected((string) old('cs1_user_id', $order->cs1_user_id) === (string) $userOption->id)>
                                            {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CS 2</label>
                                <select name="cs2_user_id" class="form-select">
                                    <option value="">- Pilih CS 2 -</option>
                                    @foreach($userOptions as $userOption)
                                        <option value="{{ $userOption->id }}" @selected((string) old('cs2_user_id', $order->cs2_user_id) === (string) $userOption->id)>
                                            {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SPV</label>
                                <select name="spv_user_id" class="form-select">
                                    <option value="">- Pilih SPV -</option>
                                    @foreach($userOptions as $userOption)
                                        <option value="{{ $userOption->id }}" @selected((string) old('spv_user_id', $order->spv_user_id) === (string) $userOption->id)>
                                            {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kasir</label>
                                <select name="kasir_user_id" class="form-select">
                                    <option value="">- Pilih Kasir -</option>
                                    @foreach($userOptions as $userOption)
                                        <option value="{{ $userOption->id }}" @selected((string) old('kasir_user_id', $order->kasir_user_id) === (string) $userOption->id)>
                                            {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fotografer</label>
                                <select name="fotografer_user_id" class="form-select">
                                    <option value="">- Pilih Fotografer -</option>
                                    @foreach($userOptions as $userOption)
                                        <option value="{{ $userOption->id }}" @selected((string) old('fotografer_user_id', $order->fotografer_user_id) === (string) $userOption->id)>
                                            {{ $userOption->name }}{{ $userOption->role?->nama ? ' (' . $userOption->role->nama . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Catatan Transaksi</label>
                                <textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $order->catatan) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><strong>Item Transaksi</strong></div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Harga</th>
                                    <th>Diskon</th>
                                    <th>Subtotal</th>
                                    <th>Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items->where('is_void', false)->values() as $index => $item)
                                    @php
                                        $itemOld = old("items.$index", []);
                                        $isLocked = (bool) ($item->workflow_locked ?? false);
                                        $isPackage = !empty($item->paket_id);
                                        $deleteValue = (bool) ($itemOld['delete'] ?? false);
                                        $hargaValue = $itemOld['harga'] ?? $item->harga;
                                        $diskonValue = $itemOld['diskon'] ?? $item->diskon;
                                    @endphp
                                    <tr class="{{ $isLocked ? 'koreksi-item-locked' : '' }}">
                                        <td style="min-width: 260px;">
                                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            @if($isPackage)
                                                @if($isLocked || !$canUpdate)
                                                    <input type="hidden" name="items[{{ $index }}][paket_id]" value="{{ $itemOld['paket_id'] ?? $item->paket_id }}">
                                                @endif
                                                <select
                                                    name="items[{{ $index }}][paket_id]"
                                                    class="form-select item-paket-select2"
                                                    data-default-price="{{ (float) ($item->paket?->harga_default ?? $item->harga) }}"
                                                    @disabled($isLocked || !$canUpdate)
                                                >
                                                    @foreach($paketOptions as $paket)
                                                        <option
                                                            value="{{ $paket->id }}"
                                                            data-price="{{ (float) $paket->harga_default }}"
                                                            @selected((string) ($itemOld['paket_id'] ?? $item->paket_id) === (string) $paket->id)
                                                        >
                                                            {{ $paket->nama }} ({{ $paket->kode }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" class="form-control koreksi-readonly" value="{{ $item->produk?->nama ?? '-' }}" readonly>
                                            @endif
                                            <div class="small text-muted mt-1">
                                                {{ $isPackage ? 'PAKET' : 'PRODUK' }}
                                                @if($isLocked)
                                                    | Item ini sudah dipakai di tracking/antrian, jadi paket tidak bisa diganti atau dihapus.
                                                @endif
                                            </div>
                                        </td>
                                        <td style="width: 110px;">
                                            <input type="text" class="form-control koreksi-readonly text-end item-qty" value="{{ number_format((float) $item->qty, 2, '.', '') }}" readonly>
                                        </td>
                                        <td style="width: 170px;">
                                            <input type="number" name="items[{{ $index }}][harga]" class="form-control text-end item-harga" min="0" step="0.01" value="{{ $hargaValue }}" @disabled(!$canUpdate) required>
                                        </td>
                                        <td style="width: 170px;">
                                            <input type="number" name="items[{{ $index }}][diskon]" class="form-control text-end item-diskon" min="0" step="0.01" value="{{ $diskonValue }}" @disabled(!$canUpdate) required>
                                        </td>
                                        <td style="width: 170px;">
                                            <input type="text" class="form-control koreksi-readonly text-end item-subtotal" value="0" readonly>
                                        </td>
                                        <td style="width: 100px;" class="text-center">
                                            <input type="hidden" name="items[{{ $index }}][delete]" value="0">
                                            <input type="checkbox" name="items[{{ $index }}][delete]" value="1" class="form-check-input item-delete" @checked($deleteValue) @disabled($isLocked || !$canUpdate)>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><strong>Pembayaran Aktif</strong></div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tipe</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Nominal</th>
                                    <th>Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->pembayaran->filter(fn ($payment) => (float) $payment->nominal >= 0 && (string) $payment->tipe !== 'VOID')->values() as $index => $payment)
                                    @php($paymentOld = old("payments.$index", []))
                                    <tr>
                                        <td>{{ $payment->tanggal_bayar?->format('d-m-Y H:i') ?? '-' }}</td>
                                        <td>{{ $payment->tipe }}</td>
                                        <td style="min-width: 220px;">
                                            <input type="hidden" name="payments[{{ $index }}][id]" value="{{ $payment->id }}">
                                            <select name="payments[{{ $index }}][metode_pembayaran_id]" class="form-select" @disabled(!$canUpdate) required>
                                                @foreach($metodePembayaranOptions as $metode)
                                                    <option value="{{ $metode->id }}" @selected((string) ($paymentOld['metode_pembayaran_id'] ?? $payment->metode_pembayaran_id) === (string) $metode->id)>
                                                        {{ $metode->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td style="width: 180px;">
                                            <input type="number" name="payments[{{ $index }}][nominal]" class="form-control text-end payment-nominal" min="0" step="0.01" value="{{ $paymentOld['nominal'] ?? $payment->nominal }}" @disabled(!$canUpdate) required>
                                        </td>
                                        <td style="width: 100px;" class="text-center">
                                            <input type="hidden" name="payments[{{ $index }}][delete]" value="0">
                                            <input type="checkbox" name="payments[{{ $index }}][delete]" value="1" class="form-check-input payment-delete" @checked((bool) ($paymentOld['delete'] ?? false)) @disabled(!$canUpdate)>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if($order->pembayaran->contains(fn ($payment) => (string) $payment->tipe === 'VOID' || (float) $payment->nominal < 0))
                            <div class="alert alert-light border mb-0">
                                <strong>Pembayaran reversal/VOID tetap dipertahankan otomatis.</strong>
                                <div class="small text-muted">Baris pembayaran bernilai negatif tidak diedit dari halaman ini dan tetap ikut dihitung ke total terbayar bersih.</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><strong>Alasan Koreksi</strong></div>
                    <div class="card-body">
                        <textarea name="alasan_koreksi" class="form-control" rows="4" placeholder="Wajib isi alasan koreksi transaksi" @disabled(!$canUpdate) required>{{ old('alasan_koreksi') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="koreksi-summary-card mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Item Aktif</span>
                        <strong id="summary_total_item">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Pembayaran Bersih</span>
                        <strong id="summary_paid_total">Rp 0</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Sisa Tagihan</span>
                        <strong id="summary_balance">Rp 0</strong>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><strong>Snapshot Saat Ini</strong></div>
                    <div class="card-body small">
                        <div class="mb-2"><strong>Tanggal:</strong> {{ $order->created_at?->format('d-m-Y H:i') ?? '-' }}</div>
                        <div class="mb-2"><strong>Kasir:</strong> {{ $order->kasir?->name ?? '-' }}</div>
                        <div class="mb-2"><strong>CS 1:</strong> {{ $order->cs1?->name ?? '-' }}</div>
                        <div class="mb-2"><strong>CS 2:</strong> {{ $order->cs2?->name ?? '-' }}</div>
                        <div class="mb-2"><strong>SPV:</strong> {{ $order->spv?->name ?? '-' }}</div>
                        <div class="mb-0"><strong>Fotografer:</strong> {{ $order->fotografer?->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><strong>Log Koreksi</strong></div>
                    <div class="card-body">
                        @forelse($order->editLogs->sortByDesc('edited_at')->take(10) as $log)
                            <div class="border rounded p-2 mb-2">
                                <div class="small fw-semibold">{{ $log->edited_at?->format('d-m-Y H:i') ?? '-' }}</div>
                                <div class="small text-muted">oleh {{ $log->editedBy?->name ?? '-' }}</div>
                                <div class="small mt-1">{{ $log->alasan }}</div>
                            </div>
                        @empty
                            <div class="text-muted small">Belum ada log koreksi.</div>
                        @endforelse
                    </div>
                </div>

                <div class="d-grid gap-2">
                    @if($canUpdate)
                        <button type="submit" class="btn btn-primary">Simpan Koreksi</button>
                    @endif
                    <a href="{{ route('riwayat-penjualan.detail', $order) }}" class="btn btn-outline-secondary">Lihat Detail Transaksi</a>
                </div>
            </div>
        </div>
    </form>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('koreksiForm');
        if (!form) return;

        const currency = new Intl.NumberFormat('id-ID');

        function toNumber(value) {
            const parsed = parseFloat(String(value || '').replace(',', '.'));
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function formatRupiah(value) {
            return 'Rp ' + currency.format(Math.round(value || 0));
        }

        // Initialize Select2 for paket dropdowns
        if (typeof $.fn.select2 !== 'undefined') {
            $('.item-paket-select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Pilih Paket --',
                allowClear: false,
                language: {
                    noResults: function () {
                        return 'Paket tidak ditemukan';
                    }
                }
            }).on('select2:select', function (e) {
                const selected = e.params.data.element;
                const price = $(selected).data('price') || 0;
                const row = $(this).closest('tr');
                const hargaInput = row.find('.item-harga');
                if (hargaInput.length && !hargaInput.prop('disabled')) {
                    hargaInput.val(price.toFixed(2));
                    recalcRow(row);
                    recalcSummary();
                }
            });
        }

        function recalcRow(row) {
            const qty = toNumber(row.querySelector('.item-qty')?.value);
            const harga = Math.max(0, toNumber(row.querySelector('.item-harga')?.value));
            const diskonInput = row.querySelector('.item-diskon');
            const deleteInput = row.querySelector('.item-delete');
            let diskon = Math.max(0, toNumber(diskonInput?.value));
            const subtotalTarget = row.querySelector('.item-subtotal');
            const maxDiskon = qty * harga;

            if (diskon > maxDiskon) {
                diskon = maxDiskon;
                if (diskonInput) diskonInput.value = diskon.toFixed(2);
            }

            const subtotal = deleteInput?.checked ? 0 : Math.max((qty * harga) - diskon, 0);
            if (subtotalTarget) {
                subtotalTarget.value = formatRupiah(subtotal);
            }

            return subtotal;
        }

        function recalcSummary() {
            let totalItem = 0;
            let paidTotal = 0;

            form.querySelectorAll('tbody tr').forEach(function (row) {
                if (row.querySelector('.item-subtotal')) {
                    totalItem += recalcRow(row);
                }
            });

            form.querySelectorAll('.payment-nominal').forEach(function (input) {
                const row = input.closest('tr');
                const deleteInput = row?.querySelector('.payment-delete');
                if (deleteInput?.checked) {
                    return;
                }

                paidTotal += Math.max(0, toNumber(input.value));
            });

            const balance = Math.max(totalItem - paidTotal, 0);
            const totalItemEl = document.getElementById('summary_total_item');
            const paidTotalEl = document.getElementById('summary_paid_total');
            const balanceEl = document.getElementById('summary_balance');

            if (totalItemEl) totalItemEl.textContent = formatRupiah(totalItem);
            if (paidTotalEl) paidTotalEl.textContent = formatRupiah(paidTotal);
            if (balanceEl) balanceEl.textContent = formatRupiah(balance);
        }

        form.addEventListener('input', function (event) {
            if (event.target.matches('.item-harga, .item-diskon, .payment-nominal')) {
                recalcSummary();
            }
        });

        form.addEventListener('change', function (event) {
            if (event.target.matches('.item-delete')) {
                recalcSummary();
            }
            if (event.target.matches('.payment-delete')) {
                const row = event.target.closest('tr');
                if (row) {
                    row.classList.toggle('koreksi-row-deleted', event.target.checked);
                }
                recalcSummary();
            }
        });

        form.querySelectorAll('.payment-delete').forEach(function (input) {
            const row = input.closest('tr');
            if (row) {
                row.classList.toggle('koreksi-row-deleted', input.checked);
            }
        });

        recalcSummary();
    })();
</script>
@endpush
