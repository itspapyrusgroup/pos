@extends('layouts.app')

@section('title', 'Laporan Tutup Kasir')

@include('components.date-range-picker-assets')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Laporan Tutup Kasir</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="text" id="tutup_kasir_date_range" class="form-control date-range-picker-input" value="{{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}" data-date-range-picker data-date-from="#tutup_kasir_date_from" data-date-to="#tutup_kasir_date_to" readonly>
                <input type="hidden" id="tutup_kasir_date_from" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" id="tutup_kasir_date_to" name="date_to" value="{{ $filters['date_to'] }}">
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
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="OPEN" @selected(($filters['status'] ?? '') === 'OPEN')>OPEN</option>
                    <option value="CLOSED" @selected(($filters['status'] ?? '') === 'CLOSED')>CLOSED</option>
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
        <div class="card h-100"><div class="card-body"><small class="text-muted">Jumlah Shift</small><h5 class="mb-0">{{ number_format((float) $summary['jumlah_shift'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Total Modal Awal</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_modal_awal'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Kas Tunai Kotor</small><h5 class="mb-0">Rp {{ number_format((float) ($summary['total_pendapatan_tunai_kotor'] ?? 0), 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Void/Refund Tunai</small><h5 class="mb-0 text-danger">- Rp {{ number_format((float) ($summary['total_pendapatan_tunai_void'] ?? 0), 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Kas Tunai Bersih</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_pendapatan_tunai'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Kas Expected</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_kas_expected'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Kas Fisik</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_kas_fisik'], 0, ',', '.') }}</h5></div></div>
    </div>
    <div class="col-md-2">
        <div class="card h-100"><div class="card-body"><small class="text-muted">Total Selisih</small><h5 class="mb-0">Rp {{ number_format((float) $summary['total_selisih'], 0, ',', '.') }}</h5></div></div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Shift</th>
                    <th>Kasir</th>
                    <th>Cabang</th>
                    <th>Dibuka</th>
                    <th>Ditutup</th>
                    <th class="text-end">Modal Awal</th>
                    <th class="text-end">Kas Tunai Kotor</th>
                    <th class="text-end">Void/Refund Tunai</th>
                    <th class="text-end">Kas Tunai Bersih</th>
                    <th class="text-end">Kas Expected</th>
                    <th class="text-end">Kas Fisik</th>
                    <th class="text-end">Selisih</th>
                    <th>Status</th>
                    <th>Aksi</th>
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
                        <td class="text-end">Rp {{ number_format((float) $row['modal_awal'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($row['pendapatan_tunai_kotor'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end text-danger">- Rp {{ number_format((float) ($row['pendapatan_tunai_void'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['pendapatan_tunai'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['kas_expected'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['kas_fisik'], 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $row['selisih'], 0, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $row['status'] === 'OPEN' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $row['status'] }}</span>
                        </td>
                        <td>
                            @if($row['status'] === 'CLOSED')
                                <form method="POST" action="{{ route('laporan-tutup-kasir.resend-email', $row['id']) }}" onsubmit="return confirm('Kirim ulang email laporan untuk shift ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Kirim Ulang Email</button>
                                </form>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center text-muted">Belum ada data tutup kasir.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
