@extends('layouts.app')

@section('title', 'Sinkronisasi Cloud')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">System</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Sinkronisasi Cloud</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('sync_success'))
    <div class="alert alert-success">{{ session('sync_success') }}</div>
@endif
@if(session('sync_error'))
    <div class="alert alert-danger">{{ session('sync_error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">APP_MODE</small>
                <strong>{{ $appMode }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">SYNC_ENABLED</small>
                <strong>{{ $syncEnabled ? 'true' : 'false' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">SYNC_ROLE</small>
                <strong>{{ $syncRole }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Target</small>
                <strong>{{ $target }}</strong>
            </div>
            <div class="col-md-12">
                <small class="text-muted d-block">Cloud Push URL</small>
                <code>{{ $pushUrl ?: '-' }}</code>
            </div>
            <div class="col-md-12">
                <small class="text-muted d-block">Cloud Bootstrap URL</small>
                <code>{{ $bootstrapUrl ?: '-' }}</code>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="mb-2">Last Push Result</h6>
                @php $push = $lastPushResult ?? null; @endphp
                <div><strong>Status:</strong> {{ ($push['ok'] ?? false) ? 'OK' : 'FAIL' }}</div>
                <div><strong>Waktu:</strong> {{ $push['at'] ?? '-' }}</div>
                <div><strong>Rows:</strong> {{ $push['sent_rows'] ?? 0 }}</div>
                <div><strong>Pesan:</strong> <span class="{{ ($push['ok'] ?? false) ? 'text-success' : 'text-danger' }}">{{ $push['message'] ?? '-' }}</span></div>
            </div>
            <div class="col-md-6">
                <h6 class="mb-2">Last Bootstrap Result</h6>
                @php $boot = $lastBootstrapResult ?? null; @endphp
                <div><strong>Status:</strong> {{ ($boot['ok'] ?? false) ? 'OK' : 'FAIL' }}</div>
                <div><strong>Waktu:</strong> {{ $boot['at'] ?? '-' }}</div>
                <div><strong>Applied Rows:</strong> {{ $boot['applied_rows'] ?? 0 }}</div>
                <div><strong>Pesan:</strong> <span class="{{ ($boot['ok'] ?? false) ? 'text-success' : 'text-danger' }}">{{ $boot['message'] ?? '-' }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="mb-1">Manual Push Sync</h5>
            <small class="text-muted">
                Gunakan tombol ini untuk push sinkronisasi manual dari node cabang ke cloud.
            </small>
        </div>
        <form method="POST" action="{{ route('sync-control.manual-push') }}">
            @csrf
            <button
                type="submit"
                class="btn btn-primary"
                @disabled(!$syncEnabled || !in_array($syncRole, ['sender', 'both'], true))
            >
                Sinkronkan Sekarang
            </button>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div>
                <h5 class="mb-1">Bootstrap Master Data Dari Cloud</h5>
                <small class="text-muted">
                    Dipakai untuk inisialisasi cabang baru (master/studio/produk/paket/metode bayar).
                </small>
            </div>
        </div>
        <form method="POST" action="{{ route('sync-control.manual-bootstrap') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    @foreach(($cabangTersedia ?? collect()) as $cabang)
                        <option value="{{ $cabang->id }}" @selected((int) old('cabang_id', $activeCabangId) === (int) $cabang->id)>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <button
                    type="submit"
                    class="btn btn-outline-primary"
                    @disabled(!$syncEnabled || !in_array($syncRole, ['sender', 'both'], true))
                >
                    Tarik Master Dari Cloud
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Statistik Promo Local</h5>
        <small class="text-muted d-block mb-2">Gunakan ini untuk cek apakah data promo benar-benar sudah masuk ke local.</small>
        <div class="row g-3">
            <div class="col-md-3"><strong>Voucher:</strong> {{ $promoStats['voucher_total'] ?? 0 }}</div>
            <div class="col-md-3"><strong>Voucher-Cabang:</strong> {{ $promoStats['voucher_map_total'] ?? 0 }}</div>
            <div class="col-md-3"><strong>Diskon Otomatis:</strong> {{ $promoStats['diskon_total'] ?? 0 }}</div>
            <div class="col-md-3"><strong>Diskon-Cabang:</strong> {{ $promoStats['diskon_map_total'] ?? 0 }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-light"><strong>Status Cursor Dataset</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Dataset</th>
                        <th>Table</th>
                        <th>Cursor Updated At</th>
                        <th>Cursor PK</th>
                        <th>Last Sent Rows</th>
                        <th>Last Success</th>
                        <th>Last Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datasets as $datasetName => $def)
                        @php
                            $cursor = $cursors[$datasetName] ?? null;
                        @endphp
                        <tr>
                            <td><strong>{{ $datasetName }}</strong></td>
                            <td><code>{{ $def['table'] ?? '-' }}</code></td>
                            <td>{{ optional($cursor?->last_updated_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td>{{ $cursor?->last_pk ?? 0 }}</td>
                            <td>{{ $cursor?->last_sent_rows ?? 0 }}</td>
                            <td>{{ optional($cursor?->last_success_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td class="text-danger">{{ $cursor?->last_error ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada dataset sync.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
