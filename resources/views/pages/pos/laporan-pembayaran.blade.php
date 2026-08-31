@extends('layouts.app')

@section('title', 'Laporan Pembayaran')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Laporan Pembayaran</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            @php($isBelumLunas = ($filters['mode'] ?? 'rekap') === 'belum_lunas')
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="pembayaran_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}" data-date-range-picker data-date-from="#pembayaran_date_from" data-date-to="#pembayaran_date_to" readonly>
                <input type="hidden" id="pembayaran_date_from" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="pembayaran_date_to" name="date_to" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" @selected((int) ($filters['cabang_id'] ?? 0) === (int) $cabang->id)>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(!$isBelumLunas)
                <div class="col-md-2">
                    <label class="form-label">Kasir</label>
                    <select name="kasir_user_id" class="form-select">
                        <option value="">Semua Kasir</option>
                        @foreach($kasirList as $kasir)
                            <option value="{{ $kasir->id }}" @selected((int) ($filters['kasir_user_id'] ?? 0) === (int) $kasir->id)>
                                {{ $kasir->name }}{{ $kasir->username ? ' (' . $kasir->username . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Metode Bayar</label>
                    <select name="metode_pembayaran_id" class="form-select">
                        <option value="">Semua Metode</option>
                        @foreach($metodeList as $metode)
                            <option value="{{ $metode->id }}" @selected((int) ($filters['metode_pembayaran_id'] ?? 0) === (int) $metode->id)>
                                {{ $metode->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <label class="form-label">Mode</label>
                <select name="mode" class="form-select">
                    <option value="rekap" @selected(($filters['mode'] ?? 'rekap') === 'rekap')>Rekap</option>
                    <option value="harian" @selected(($filters['mode'] ?? '') === 'harian')>Harian</option>
                    <option value="belum_lunas" @selected(($filters['mode'] ?? '') === 'belum_lunas')>Belum Lunas</option>
                </select>
            </div>
            @if($isBelumLunas)
                <div class="col-md-2">
                    <label class="form-label">No KO</label>
                    <input type="text" name="no_ko" class="form-control" value="{{ $filters['no_ko'] ?? '' }}" placeholder="Cari No KO">
                </div>
            @endif
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            @if(!$isBelumLunas)
                <div class="col-md-2 d-flex align-items-end">
                    <a href="{{ route('laporan-pembayaran-detail', request()->query()) }}" class="btn btn-outline-secondary w-100">Lihat Detail</a>
                </div>
            @endif
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">{{ $isBelumLunas ? 'Jumlah KO Belum Lunas' : 'Jumlah Transaksi Pembayaran' }}</small>
                <h5 class="mb-0">{{ number_format((float) $summary['jumlah_transaksi'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">{{ $isBelumLunas ? 'Total Sisa Tagihan' : 'Net Pembayaran (Masuk)' }}</small>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['total_nominal'], 0, ',', '.') }}</h5>
                @if(!$isBelumLunas)
                    <small class="text-muted d-block">Gross Rp {{ number_format((float) ($summary['total_nominal_kotor'] ?? 0), 0, ',', '.') }}</small>
                    <small class="text-muted d-block">Void Rp {{ number_format((float) ($summary['total_nominal_void'] ?? 0), 0, ',', '.') }}</small>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        @if($isBelumLunas)
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>No KO</th>
                        <th>Pelanggan</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Terbayar</th>
                        <th class="text-end">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') }}</td>
                            <td>{{ $row->nomor_ko ?? '-' }}</td>
                            <td>{{ $row->customer_name ?: '-' }}</td>
                            <td><span class="badge bg-warning text-dark">{{ $row->status_pembayaran }}</span></td>
                            <td class="text-end">Rp {{ number_format((float) $row->total, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format((float) $row->paid_total, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format((float) $row->balance, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Tidak ada KO belum lunas pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if(method_exists($rows, 'links'))
                <div class="mt-3">
                    {{ $rows->links() }}
                </div>
            @endif
        @else
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ ($filters['mode'] ?? 'rekap') === 'harian' ? 'Tanggal' : 'Nama Kasir' }}</th>
                        @foreach($metodeList as $metode)
                            <th class="text-end">{{ $metode->nama }}</th>
                        @endforeach
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            @foreach($metodeList as $metode)
                                @php($cell = $row['amounts'][$metode->id] ?? ['gross' => 0, 'void' => 0, 'net' => 0, 'detail_key' => null])
                                <td class="text-end">
                                    @if((float) ($cell['void'] ?? 0) > 0 && !empty($cell['detail_key']))
                                        <button
                                            type="button"
                                            class="btn btn-link btn-sm p-0 text-end text-decoration-none btn-open-void-detail"
                                            data-detail-key="{{ $cell['detail_key'] }}"
                                            data-method-name="{{ $metode->nama }}"
                                            data-row-label="{{ $row['label'] }}"
                                        >
                                            Rp {{ number_format((float) ($cell['net'] ?? 0), 0, ',', '.') }}
                                        </button>
                                        <div><small class="text-danger">Void Rp {{ number_format((float) ($cell['void'] ?? 0), 0, ',', '.') }}</small></div>
                                    @else
                                        Rp {{ number_format((float) ($cell['net'] ?? 0), 0, ',', '.') }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-end fw-semibold">Rp {{ number_format((float) $row['row_total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + $metodeList->count() }}" class="text-center text-muted">Belum ada data pembayaran.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>TOTAL</th>
                        @foreach($metodeList as $metode)
                            <th class="text-end">Rp {{ number_format((float) ($totalsByMetode[$metode->id] ?? 0), 0, ',', '.') }}</th>
                        @endforeach
                        <th class="text-end">Rp {{ number_format((float) $grandTotal, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>
</div>

@if(!$isBelumLunas)
<div class="modal fade" id="voidDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Detail Void Metode Pembayaran</h5>
                    <small class="text-muted" id="voidDetailModalSubtitle">-</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No KO</th>
                                <th>Nama</th>
                                <th class="text-end">Nominal Void</th>
                                <th>Waktu Void</th>
                                <th>Informasi Void</th>
                            </tr>
                        </thead>
                        <tbody id="voidDetailTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada detail void.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@if(!$isBelumLunas)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const voidDetailsMap = @json($voidDetailsMap ?? []);
        const modalEl = document.getElementById('voidDetailModal');
        const subtitleEl = document.getElementById('voidDetailModalSubtitle');
        const tableBodyEl = document.getElementById('voidDetailTableBody');

        if (!modalEl || !subtitleEl || !tableBodyEl || typeof bootstrap === 'undefined') {
            return;
        }

        const modal = new bootstrap.Modal(modalEl);

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-open-void-detail');
            if (!btn) {
                return;
            }

            const detailKey = btn.dataset.detailKey || '';
            const methodName = btn.dataset.methodName || '-';
            const rowLabel = btn.dataset.rowLabel || '-';
            const details = voidDetailsMap[detailKey] || [];

            subtitleEl.textContent = `${methodName} | ${rowLabel}`;

            if (!details.length) {
                tableBodyEl.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Belum ada detail void.</td></tr>';
                modal.show();
                return;
            }

            tableBodyEl.innerHTML = details.map(function (item) {
                const infoLines = Array.isArray(item.void_info) && item.void_info.length
                    ? item.void_info.map(function (line) {
                        return `<div>${escapeHtml(line)}</div>`;
                    }).join('')
                    : '<span class="text-muted">-</span>';

                return `
                    <tr>
                        <td>${escapeHtml(item.no_ko || '-')}</td>
                        <td>${escapeHtml(item.nama || '-')}</td>
                        <td class="text-end">Rp ${formatNumber(item.nominal || 0)}</td>
                        <td>${escapeHtml(item.tanggal_void || '-')}</td>
                        <td>${infoLines}</td>
                    </tr>
                `;
            }).join('');

            modal.show();
        });

        function formatNumber(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value || 0));
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    });
</script>
@endpush
@endif
