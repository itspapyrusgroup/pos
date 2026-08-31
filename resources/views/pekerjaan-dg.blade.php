@extends('layouts.app')

@section('title', 'Daftar Pekerjaan DG')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Produksi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Daftar Pekerjaan DG</li>
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
                <label class="form-label">Status DG</label>
                <select name="status_dg" class="form-select">
                    <option value="ALL" @selected(($filters['status_dg'] ?? 'PENDING') === 'ALL')>Semua</option>
                    <option value="DONE" @selected(($filters['status_dg'] ?? 'PENDING') === 'DONE')>Selesai</option>
                    <option value="PENDING" @selected(($filters['status_dg'] ?? 'PENDING') === 'PENDING')>Belum Selesai</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Deadline Dari</label>
                <input type="date" name="deadline_from" class="form-control" value="{{ $filters['deadline_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Deadline Sampai</label>
                <input type="date" name="deadline_to" class="form-control" value="{{ $filters['deadline_to'] ?? '' }}">
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
                <small class="text-muted">Total KO DG</small>
                <h5 class="mb-0">{{ number_format((int) ($summary['total_ko'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Selesai</small>
                <h5 class="mb-0 text-success">{{ number_format((int) ($summary['done_ko'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Belum Selesai</small>
                <h5 class="mb-0 text-danger">{{ number_format((int) ($summary['pending_ko'] ?? 0), 0, ',', '.') }}</h5>
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
                    <th>Paket</th>
                    <th>Cabang</th>
                    <th>Deadline</th>
                    <th>Progress</th>
                    <th>Status DG</th>
                    <th>Selesai Oleh</th>
                    <th>Selesai Pada</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($rows ?? collect()) as $index => $row)
                    @php
                        $isDone = (bool) ($row['is_done'] ?? false);
                        $requiredCount = (int) ($row['required_count'] ?? 0);
                        $checkedCount = (int) ($row['checked_count'] ?? 0);
                        $isOverdue = !$isDone
                            && $row['tanggal_selesai']
                            && $row['tanggal_selesai']->lt(now()->startOfDay());
                    @endphp
                    <tr @class(['table-danger' => $isOverdue])>
                        <td>{{ ($rows->firstItem() ?? 0) + $index }}</td>
                        <td>{{ $row['nomor_ko'] ?? '-' }}</td>
                        <td>{{ $row['customer_name'] ?? '-' }}</td>
                        <td>
                            @if(!empty($row['paket_names'] ?? []))
                                {{ implode(', ', $row['paket_names']) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $row['cabang_nama'] ?? '-' }}</td>
                        <td>{{ $row['tanggal_selesai']?->format('d-m-Y') ?? '-' }}</td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $checkedCount }}/{{ $requiredCount }}</span>
                        </td>
                        <td>
                            @if($isDone)
                                <span class="badge bg-success">SELESAI</span>
                            @else
                                <span class="badge bg-warning text-dark">BELUM</span>
                            @endif
                        </td>
                        <td>{{ $row['done_by'] ?? '-' }}</td>
                        <td>{{ $row['done_at']?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('tracking-order', ['no_ko' => $row['nomor_ko']]) }}" class="btn btn-sm btn-outline-primary">
                                Detail
                            </a>
                            @if(!$isDone && ($row['can_mark_done'] ?? false))
                                <form method="POST" action="{{ route('pekerjaan-dg.mark-done') }}" data-swal-message="Tandai semua pekerjaan DG KO ini selesai oleh Anda?">
                                    @csrf
                                    <input type="hidden" name="no_ko" value="{{ $row['nomor_ko'] }}">
                                    <button type="submit" class="btn btn-sm btn-success">Selesaikan Saya</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted">Belum ada data pekerjaan DG pada filter ini.</td>
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
