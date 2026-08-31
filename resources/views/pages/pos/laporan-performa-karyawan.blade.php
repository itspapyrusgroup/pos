@extends('layouts.app')

@section('title', 'Laporan Performa Karyawan')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Laporan Performa Karyawan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="performa_karyawan_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}" data-date-range-picker data-date-from="#performa_karyawan_date_from" data-date-to="#performa_karyawan_date_to" readonly>
                <input type="hidden" id="performa_karyawan_date_from" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="performa_karyawan_date_to" name="date_to" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-md-3">
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
            <div class="col-md-3">
                <label class="form-label">Karyawan</label>
                <select name="karyawan_id" class="form-select">
                    <option value="">Semua Karyawan</option>
                    @foreach($karyawanList as $karyawan)
                        <option value="{{ $karyawan->id }}" @selected((int) ($filters['karyawan_id'] ?? 0) === (int) $karyawan->id)>
                            {{ $karyawan->nama }}{{ $karyawan->user?->username ? ' (' . $karyawan->user->username . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Total Checklist</small><h5 class="mb-0">{{ number_format((float) $summary['total_checklist'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Total Qty Checked</small><h5 class="mb-0">{{ number_format((float) $summary['total_qty_checked'], 2, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Karyawan Aktif</small><h5 class="mb-0">{{ number_format((float) $summary['total_karyawan_aktif'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Total Order Terproses</small><h5 class="mb-0">{{ number_format((float) $summary['total_order'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Total Cabang Aktif</small><h5 class="mb-0">{{ number_format((float) $summary['total_cabang'], 0, ',', '.') }}</h5></div></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body table-responsive">
                <h6 class="mb-3">Rekap Paket Yang Diceklis</h6>
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Paket</th>
                            <th class="text-end">Total Checklist</th>
                            <th class="text-end">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paketRows as $row)
                            <tr>
                                <td>{{ $row->paket_nama }}</td>
                                <td class="text-end">{{ number_format((float) $row->total_checklist, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $row->total_qty_checked, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada data paket.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body table-responsive">
                <h6 class="mb-3">Rekap Item Yang Diceklis</h6>
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Total Checklist</th>
                            <th class="text-end">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itemRows as $row)
                            <tr>
                                <td>{{ $row->item_nama }}</td>
                                <td class="text-end">{{ number_format((float) $row->total_checklist, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $row->total_qty_checked, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada data item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <h6 class="mb-3">Detail Checklist Performa</h6>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Waktu Checklist</th>
                    <th>Karyawan</th>
                    <th>Cabang</th>
                    <th>No SO</th>
                    <th>No KO</th>
                    <th>Paket</th>
                    <th>Item</th>
                    <th class="text-end">Qty Item di Paket</th>
                    <th class="text-end">Qty Order</th>
                    <th class="text-end">Qty Checked</th>
                </tr>
            </thead>
            <tbody>
                @forelse($details as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->checked_at)->format('d-m-Y H:i') }}</td>
                        <td>
                            {{ $row->karyawan_nama ?: ($row->checked_by_name ?? '-') }}
                            @if($row->checked_by_username)
                                <small class="text-muted d-block">{{ '@' . $row->checked_by_username }}</small>
                            @endif
                        </td>
                        <td>{{ $row->cabang_nama ?? '-' }}</td>
                        <td>{{ $row->nomor_so ?? '-' }}</td>
                        <td>{{ $row->nomor_ko ?? '-' }}</td>
                        <td>{{ $row->paket_nama }}</td>
                        <td>{{ $row->item_nama }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty_item_di_paket, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty_order_item, 2, ',', '.') }}</td>
                        <td class="text-end">{{ number_format((float) $row->qty_checked, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">Belum ada data performa karyawan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
            {{ $details->links() }}
        </div>
    </div>
</div>
@endsection
