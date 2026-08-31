@extends('layouts.app')

@section('title', 'Laporan Kasir')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Laporan Kasir</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="kasir_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}" data-date-range-picker data-date-from="#kasir_date_from" data-date-to="#kasir_date_to" readonly>
                <input type="hidden" id="kasir_date_from" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="kasir_date_to" name="date_to" value="{{ $filters['date_to'] }}">
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
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Tarik Laporan</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="export_xlsx" value="1" class="btn btn-success w-100">Export Excel</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('laporan-kasir-detail', ['report_date' => $filters['date_to'], 'cabang_id' => $filters['cabang_id'] ?? null, 'kasir_user_id' => $filters['kasir_user_id'] ?? null]) }}" class="btn btn-outline-primary w-100">
                    Laporan Detail
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-lg-3 col-xl-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Jumlah Transaksi</small><h5 class="mb-0">{{ number_format((float) $summary['jumlah_transaksi'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Penjualan Kotor</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_penjualan_kotor'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Pengurangan Void</small><h5 class="mb-0 text-danger">- Rp {{ number_format((float) $summary['total_void'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Pengurangan Diskon</small><h5 class="mb-0 text-danger">- Rp {{ number_format((float) ($summary['total_diskon'] ?? 0), 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Penjualan Bersih</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_penjualan'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Net Pembayaran (Masuk)</small><h5 class="mb-0">Rp {{ number_format((float) ($summary['total_pembayaran'] ?? 0), 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Void Efektif</small><h5 class="mb-0 text-danger">- Rp {{ number_format((float) ($summary['total_pembayaran_void'] ?? 0), 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-6 col-lg-3 col-xl-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Total Sisa</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_sisa'], 0, ',', '.') }}</h5></div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body table-responsive">
        <h6 class="mb-3">Rekap Per Kasir</h6>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kasir</th>
                    <th class="text-end">Jumlah Transaksi</th>
                    <th class="text-end">Penjualan Kotor</th>
                    <th class="text-end">Void</th>
                    <th class="text-end">Diskon</th>
                    <th class="text-end">Penjualan Bersih</th>
                    <th class="text-end">Net Pembayaran</th>
                    <th class="text-end">Total Sisa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rekapKasir as $row)
                    @php($kasir = $usersMap->get($row['kasir_user_id']))
                    <tr>
                        <td>{{ $kasir?->name ?? 'User #' . $row['kasir_user_id'] }}</td>
                        <td class="text-end">{{ number_format((float) $row['jumlah_transaksi'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['total_penjualan_kotor'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">- Rp {{ number_format((float) $row['total_void'], 0, ',', '.') }}</td>
                        <td class="text-end text-danger">- Rp {{ number_format((float) ($row['total_diskon'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['total_penjualan'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['total_pembayaran'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['total_sisa'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada data kasir pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body table-responsive">
        <h6 class="mb-3">Rekap Shift & Setoran Tunai</h6>
        <small class="text-muted d-block mb-2">Shift OPEN dihitung setoran fisik = 0, jadi selisih akan menunjukkan kas tunai yang masih berjalan.</small>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Shift</th>
                    <th>Kasir</th>
                    <th>Cabang</th>
                    <th>Dibuka</th>
                    <th>Ditutup</th>
                    <th class="text-end">Pendapatan Tunai</th>
                    <th class="text-end">Setoran Fisik</th>
                    <th class="text-end">Selisih Setoran</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shiftRows as $row)
                    <tr>
                        <td>#{{ $row['id'] }}</td>
                        <td>{{ $row['kasir']?->name ?? '-' }}</td>
                        <td>{{ $row['cabang']?->nama ?? '-' }}</td>
                        <td>{{ $row['dibuka_pada']?->format('d-m-Y H:i') }}</td>
                        <td>{{ $row['ditutup_pada']?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['pendapatan_tunai'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['setoran_fisik'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['selisih_setoran'], 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $row['status'] === 'OPEN' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $row['status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">Belum ada data shift kasir.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <h6 class="mb-3">Detail Transaksi</h6>
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Kasir</th>
                    <th>Cabang</th>
                    <th>No SO</th>
                    <th>No KO</th>
                    <th class="text-end">Total Kotor</th>
                    <th class="text-end">Void</th>
                    <th class="text-end">Total Bersih</th>
                    <th class="text-end">Terbayar</th>
                    <th class="text-end">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $trx)
                    <tr>
                        <td>{{ $transactions->firstItem() + $index }}</td>
                        <td>{{ $trx->created_at?->format('d-m-Y H:i') }}</td>
                        <td>{{ $trx->kasir?->name ?? '-' }}</td>
                        <td>{{ $trx->cabang?->nama ?? '-' }}</td>
                        <td>{{ $trx->nomor_so }}</td>
                        <td>{{ $trx->kantongOrder?->nomor_ko ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float) ((float) $trx->total + (float) ($trx->void_total_order ?? 0)), 0, ',', '.') }}</td>
                        <td class="text-end text-danger">- Rp {{ number_format((float) ($trx->void_total_order ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $trx->total, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $trx->paid_total, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $trx->balance, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted">Belum ada transaksi kasir.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $transactions->links() }}
    </div>
</div>
@endsection
