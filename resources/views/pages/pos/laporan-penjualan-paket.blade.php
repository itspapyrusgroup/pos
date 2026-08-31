@extends('layouts.app')

@section('title', 'Laporan Penjualan Paket')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Laporan Penjualan Paket</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="paket_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse(request('date_from') ?? now()->toDateString())->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse(request('date_to') ?? request('date_from') ?? now()->toDateString())->format('d-m-Y') }}" data-date-range-picker data-date-from="#paket_date_from" data-date-to="#paket_date_to" readonly>
                <input type="hidden" id="paket_date_from" name="date_from" value="{{ request('date_from') ?? now()->toDateString() }}">
                <input type="hidden" id="paket_date_to" name="date_to" value="{{ request('date_to') ?? request('date_from') ?? now()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $cabang)
                        <option value="{{ $cabang->id }}" {{ (string) request('cabang_id') === (string) $cabang->id ? 'selected' : '' }}>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ request('no_ko') }}" placeholder="KO-...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Group By</label>
                <select name="group_by" class="form-select">
                    <option value="ko" @selected(($groupBy ?? 'ko') === 'ko')>No KO</option>
                    <option value="paket_kode" @selected(($groupBy ?? '') === 'paket_kode')>Kode Paket</option>
                    <option value="paket_nama" @selected(($groupBy ?? '') === 'paket_nama')>Nama Paket</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
            <div class="col-12">
                <a href="{{ route('laporan-penjualan-paket') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Jumlah Item Paket</small>
                <h5 class="mb-0">{{ number_format((float) $summary['count'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Jumlah No KO</small>
                <h5 class="mb-0">{{ number_format((float) ($summary['ko_count'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Total Qty Paket</small>
                <h5 class="mb-0">{{ number_format((float) $summary['qty'], 2, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Subtotal Penjualan Paket</small>
                <h5 class="mb-0">Rp {{ number_format((float) $summary['subtotal'], 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        @if(($groupBy ?? 'ko') === 'ko')
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Cabang</th>
                        <th>No SO</th>
                        <th>Kode Paket</th>
                        <th>Nama Paket</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Subtotal</th>
                        <th>Customer</th>
                        <th>Kasir</th>
                    </tr>
                </thead>
                <tbody>
                    @php($groupedRows = $rows->getCollection()->groupBy(function ($row) {
                        return $row->pesananPenjualan?->kantongOrder?->nomor_ko ?? '-';
                    }))
                    @php($runningNo = $rows->firstItem() ?? 1)
                    @forelse($groupedRows as $nomorKo => $koRows)
                        @php($subtotalKo = (float) $koRows->sum('subtotal'))
                        <tr class="table-secondary">
                            <td colspan="12">
                                <strong>No KO: {{ $nomorKo }}</strong>
                                <span class="text-muted"> | {{ $koRows->count() }} item paket | Subtotal Rp {{ number_format($subtotalKo, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                        @foreach($koRows as $row)
                            <tr>
                                <td>{{ $runningNo }}</td>
                                <td>{{ $row->pesananPenjualan?->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                <td>{{ $row->pesananPenjualan?->cabang?->nama ?? '-' }}</td>
                                <td>{{ $row->pesananPenjualan?->nomor_so ?? '-' }}</td>
                                <td>{{ $row->paket?->kode ?? '-' }}</td>
                                <td>{{ $row->paket?->nama ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) $row->qty, 2, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $row->harga, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $row->diskon, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $row->subtotal, 0, ',', '.') }}</td>
                                <td>{{ $row->pesananPenjualan?->pelanggan?->nama ?? '-' }}</td>
                                <td>{{ $row->pesananPenjualan?->kasir?->name ?? '-' }}</td>
                            </tr>
                            @php($runningNo++)
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">Data penjualan paket tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ ($groupBy ?? '') === 'paket_kode' ? 'Kode Paket' : 'Nama Paket' }}</th>
                        @if(($groupBy ?? '') === 'paket_kode')
                            <th>Nama Paket</th>
                        @else
                            <th class="text-end">Jumlah Kode</th>
                        @endif
                        <th class="text-end">Jumlah Item</th>
                        <th class="text-end">Jumlah KO</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end">Total Diskon</th>
                        <th class="text-end">Total Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td>{{ $rows->firstItem() + $index }}</td>
                            <td>{{ $row->group_key ?? '-' }}</td>
                            @if(($groupBy ?? '') === 'paket_kode')
                                <td>{{ $row->paket_nama ?? '-' }}</td>
                            @else
                                <td class="text-end">{{ number_format((float) ($row->kode_count ?? 0), 0, ',', '.') }}</td>
                            @endif
                            <td class="text-end">{{ number_format((float) ($row->item_count ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) ($row->total_ko ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) ($row->total_qty ?? 0), 2, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format((float) ($row->total_diskon ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format((float) ($row->total_subtotal ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">Data penjualan paket tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
        {{ $rows->links() }}
    </div>
</div>
@endsection
