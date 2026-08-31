@extends('layouts.app')

@section('title', 'Daftar Konfirmasi')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Customer Service</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Daftar Konfirmasi</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
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
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ $filters['no_ko'] ?? '' }}" placeholder="Cari nomor KO">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="PENDING" @selected(($filters['status'] ?? 'PENDING') === 'PENDING')>Belum Lengkap</option>
                    <option value="DONE" @selected(($filters['status'] ?? 'PENDING') === 'DONE')>Sudah Lengkap</option>
                    <option value="ALL" @selected(($filters['status'] ?? 'PENDING') === 'ALL')>Semua</option>
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
                <small class="text-muted">Belum Lengkap</small>
                <h5 class="mb-0 text-warning">{{ number_format((int) ($summary['pending_ko'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Sudah Lengkap</small>
                <h5 class="mb-0 text-success">{{ number_format((int) ($summary['done_ko'] ?? 0), 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <div class="alert alert-info mb-3">
            <strong>Panduan Singkat:</strong>
            1) Gunakan filter untuk cari KO yang perlu dikonfirmasi.
            2) Centang checkbox pada kolom <strong>Kirim File</strong>, <strong>Kirim Hasil</strong>, atau <strong>Pengambilan</strong> untuk menandai step selesai.
            3) Checkbox yang sudah tercentang akan terkunci dan menampilkan nama user serta waktu update.
            4) Status <strong>SELESAI</strong> berarti kedua step sudah lengkap.
        </div>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>No KO</th>
                    <th>Pelanggan</th>
                    <th>Cabang</th>
                    <th>Deadline</th>
                    <th>Kirim File</th>
                    <th>Kirim Hasil</th>
                    <th>Pengambilan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($rows ?? collect()) as $index => $row)
                    @php($kirimFile = $row['steps']['KIRIM_FILE'] ?? null)
                    @php($kirimHasil = $row['steps']['KIRIM_HASIL'] ?? null)
                    @php($pengambilan = $row['steps']['PENGAMBILAN'] ?? null)
                    <tr>
                        <td>{{ ($rows->firstItem() ?? 0) + $index }}</td>
                        <td>{{ $row['nomor_ko'] }}</td>
                        <td>{{ $row['customer_name'] ?? '-' }}</td>
                        <td>{{ $row['cabang_nama'] ?? '-' }}</td>
                        <td>{{ $row['tanggal_selesai']?->format('d-m-Y') ?? '-' }}</td>
                        <td>
                            @if($kirimFile && ($kirimFile['can_update'] ?? false) && !($kirimFile['is_checked'] ?? false))
                                <form method="POST" action="{{ route('konfirmasi.ko-check.update') }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="no_ko" value="{{ $row['nomor_ko'] }}">
                                    <input type="hidden" name="step_kode" value="KIRIM_FILE">
                                    <div class="form-check d-inline-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" onchange="this.form.submit()">
                                    </div>
                                </form>
                            @elseif($kirimFile)
                                <div class="form-check d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" disabled {{ ($kirimFile['is_checked'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label text-muted">{{ ($kirimFile['is_checked'] ?? false) ? 'Selesai' : 'Belum' }}</label>
                                </div>
                            @else
                                <div class="form-check d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" disabled>
                                    <label class="form-check-label text-muted">Belum</label>
                                </div>
                            @endif
                            @if($kirimFile && ($kirimFile['is_checked'] ?? false))
                                <div class="small text-muted mt-1">
                                    {{ $kirimFile['checked_by'] ?? '-' }}
                                    @if($kirimFile['checked_at'] ?? false)
                                        | {{ $kirimFile['checked_at']->format('d-m-Y H:i') }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($kirimHasil && ($kirimHasil['can_update'] ?? false) && !($kirimHasil['is_checked'] ?? false))
                                <form method="POST" action="{{ route('konfirmasi.ko-check.update') }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="no_ko" value="{{ $row['nomor_ko'] }}">
                                    <input type="hidden" name="step_kode" value="KIRIM_HASIL">
                                    <div class="form-check d-inline-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" onchange="this.form.submit()">
                                    </div>
                                </form>
                            @elseif($kirimHasil)
                                <div class="form-check d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" disabled {{ ($kirimHasil['is_checked'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label text-muted">{{ ($kirimHasil['is_checked'] ?? false) ? 'Selesai' : 'Belum' }}</label>
                                </div>
                            @else
                                <div class="form-check d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" disabled>
                                    <label class="form-check-label text-muted">Belum</label>
                                </div>
                            @endif
                            @if($kirimHasil && ($kirimHasil['is_checked'] ?? false))
                                <div class="small text-muted mt-1">
                                    {{ $kirimHasil['checked_by'] ?? '-' }}
                                    @if($kirimHasil['checked_at'] ?? false)
                                        | {{ $kirimHasil['checked_at']->format('d-m-Y H:i') }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($pengambilan && ($pengambilan['can_update'] ?? false) && !($pengambilan['is_checked'] ?? false))
                                <form method="POST" action="{{ route('konfirmasi.ko-check.update') }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="no_ko" value="{{ $row['nomor_ko'] }}">
                                    <input type="hidden" name="step_kode" value="PENGAMBILAN">
                                    <div class="form-check d-inline-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" onchange="this.form.submit()">
                                    </div>
                                </form>
                            @elseif($pengambilan)
                                <div class="form-check d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" disabled {{ ($pengambilan['is_checked'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label text-muted">{{ ($pengambilan['is_checked'] ?? false) ? 'Selesai' : 'Belum' }}</label>
                                </div>
                            @else
                                <div class="form-check d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" disabled>
                                    <label class="form-check-label text-muted">Belum</label>
                                </div>
                            @endif
                            @if($pengambilan && ($pengambilan['is_checked'] ?? false))
                                <div class="small text-muted mt-1">
                                    {{ $pengambilan['checked_by'] ?? '-' }}
                                    @if($pengambilan['checked_at'] ?? false)
                                        | {{ $pengambilan['checked_at']->format('d-m-Y H:i') }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($row['is_done'] ?? false)
                                <span class="badge bg-success">SELESAI</span>
                            @else
                                <span class="badge bg-warning text-dark">PENDING</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">Belum ada data KO untuk filter ini.</td>
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
