@extends('layouts.app')

@section('title', 'Laporan Pembayaran Detail')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('laporan-pembayaran') }}">Laporan Pembayaran</a></li>
                <li class="breadcrumb-item active">Laporan Pembayaran Detail</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="pembayaran_detail_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}" data-date-range-picker data-date-from="#pembayaran_detail_date_from" data-date-to="#pembayaran_detail_date_to" readonly>
                <input type="hidden" id="pembayaran_detail_date_from" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="pembayaran_detail_date_to" name="date_to" value="{{ $filters['date_to'] }}">
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
            <div class="col-md-2">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ $filters['no_ko'] ?? '' }}" placeholder="Cari No KO">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('laporan-pembayaran', request()->query()) }}" class="btn btn-outline-secondary w-100">Ke Rekap</a>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Jumlah KO</small>
                <h5 class="mb-0">{{ number_format((float) ($summary['jumlah_ko'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Gross Pembayaran</small>
                <h5 class="mb-0">Rp {{ number_format((float) ($summary['total_gross'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Void Pembayaran</small>
                <h5 class="mb-0 text-danger">Rp {{ number_format((float) ($summary['total_void'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Net Pembayaran</small>
                <h5 class="mb-0">Rp {{ number_format((float) ($summary['total_net'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal Bayar</th>
                    <th>No KO</th>
                    <th>Nama</th>
                    <th>Kasir</th>
                    <th>Metode Bayar</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Void</th>
                    <th class="text-end">Net</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->tanggal_bayar)->format('d-m-Y H:i') }}</td>
                        <td>{{ $row->nomor_ko ?? '-' }}</td>
                        <td>{{ $row->customer_name ?? '-' }}</td>
                        <td>{{ $row->kasir_nama ?? '-' }}</td>
                        <td>{{ $row->metode_pembayaran_nama ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row->gross_nominal, 0, ',', '.') }}</td>
                        <td class="text-end text-danger">Rp {{ number_format((float) $row->void_nominal, 0, ',', '.') }}</td>
                        <td class="text-end fw-semibold">Rp {{ number_format((float) $row->net_nominal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada data pembayaran detail.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="5" class="text-end">TOTAL</th>
                    <th class="text-end">Rp {{ number_format((float) ($summary['total_gross'] ?? 0), 0, ',', '.') }}</th>
                    <th class="text-end text-danger">Rp {{ number_format((float) ($summary['total_void'] ?? 0), 0, ',', '.') }}</th>
                    <th class="text-end">Rp {{ number_format((float) ($summary['total_net'] ?? 0), 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if(method_exists($rows, 'links'))
    <div class="mt-3">
        {{ $rows->links() }}
    </div>
@endif
@endsection
