@extends('layouts.app')

@section('title', 'Daftar QC')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Produksi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Daftar QC</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ $filters['no_ko'] ?? '' }}" placeholder="Cari nomor KO">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status QC</label>
                <select name="status_qc" class="form-select">
                    <option value="ALL" @selected(($filters['status_qc'] ?? 'ALL') === 'ALL')>Semua</option>
                    <option value="CHECKED" @selected(($filters['status_qc'] ?? 'ALL') === 'CHECKED')>Sudah QC</option>
                    <option value="UNCHECKED" @selected(($filters['status_qc'] ?? 'ALL') === 'UNCHECKED')>Belum QC</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tanggal Dari</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? now()->toDateString() }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tanggal Sampai</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? now()->toDateString() }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach(($cabangs ?? collect()) as $cabang)
                        <option value="{{ $cabang->id }}" @selected((string) ($filters['cabang_id'] ?? '') === (string) $cabang->id)>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Total KO</small>
                <h5 class="mb-0">{{ number_format((int) ($summary['total_ko'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Sudah QC</small>
                <h5 class="mb-0 text-success">{{ number_format((int) ($summary['checked_ko'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Belum QC</small>
                <h5 class="mb-0 text-danger">{{ number_format((int) ($summary['unchecked_ko'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>No KO</th>
                    <th>Pelanggan</th>
                    <th>Cabang</th>
                    <th>Deadline</th>
                    <th>Status QC</th>
                    <th>Dicek Oleh</th>
                    <th>Dicek Pada</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($rows ?? collect()) as $index => $row)
                    <tr>
                        <td>{{ ($rows->firstItem() ?? 0) + $index }}</td>
                        <td>{{ $row['nomor_ko'] }}</td>
                        <td>{{ $row['customer_name'] ?? '-' }}</td>
                        <td>{{ $row['cabang_nama'] ?? '-' }}</td>
                        <td>{{ $row['tanggal_selesai']?->format('d-m-Y') ?? '-' }}</td>
                        <td>
                            @if($row['is_qc_checked'] ?? false)
                                <span class="badge bg-success">SUDAH QC</span>
                            @else
                                <span class="badge bg-warning text-dark">BELUM QC</span>
                            @endif
                        </td>
                        <td>{{ $row['qc_checked_by'] ?? '-' }}</td>
                        <td>{{ $row['qc_checked_at']?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('tracking-order', ['no_ko' => $row['nomor_ko']]) }}" class="btn btn-sm btn-outline-primary">
                                Buka Tracking
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">Belum ada data KO pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(($rows ?? null) instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="card-footer">
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endsection
