@extends('layouts.app')

@section('title', 'Laporan Customer')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('laporan.menu') }}">Daftar Menu Laporan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Laporan Customer</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Laporan Customer</h5>

            <form method="get" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="text" name="name" value="{{ old('name', $filters['name'] ?? '') }}" class="form-control" placeholder="Nama customer">
                </div>
                <div class="col-md-2">
                    <input type="text" name="phone" value="{{ old('phone', $filters['phone'] ?? '') }}" class="form-control" placeholder="No. HP">
                </div>
                <div class="col-md-3">
                    <input type="text" name="email" value="{{ old('email', $filters['email'] ?? '') }}" class="form-control" placeholder="Email">
                </div>
                <div class="col-md-2">
                    <select name="cabang_id" class="form-select">
                        <option value="">Semua Cabang</option>
                        @foreach($cabangs as $c)
                            <option value="{{ $c->id }}" {{ (int)($filters['cabang_id'] ?? 0) === $c->id ? 'selected' : '' }}>{{ $c->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-primary">Filter</button>
                </div>
                <div class="col-12">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Dari</label>
                            <input type="date" name="date_from" value="{{ old('date_from', $filters['date_from'] ?? '') }}" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Sampai</label>
                            <input type="date" name="date_to" value="{{ old('date_to', $filters['date_to'] ?? '') }}" class="form-control">
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                @php
                    $currentSort = request('sort_by', 'last_transaction_at');
                    $currentDir = request('sort_dir', 'desc');
                    $baseParams = request()->except(['page']);
                    $sortLink = function ($col) use ($baseParams, $currentSort, $currentDir) {
                        $dir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
                        return route('laporan-customer', array_merge($baseParams, ['sort_by' => $col, 'sort_dir' => $dir]));
                    };
                @endphp

                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th><a href="{{ $sortLink('nama') }}">Nama @if($currentSort==='nama') @if($currentDir==='asc') <i class="bi bi-chevron-up"></i> @else <i class="bi bi-chevron-down"></i> @endif @endif</a></th>
                            <th><a href="{{ $sortLink('no_hp') }}">No HP @if($currentSort==='no_hp') @if($currentDir==='asc') <i class="bi bi-chevron-up"></i> @else <i class="bi bi-chevron-down"></i> @endif @endif</a></th>
                            <th><a href="{{ $sortLink('email') }}">Email @if($currentSort==='email') @if($currentDir==='asc') <i class="bi bi-chevron-up"></i> @else <i class="bi bi-chevron-down"></i> @endif @endif</a></th>
                            <th class="text-end"><a href="{{ $sortLink('transaksi_count') }}">Jumlah Transaksi @if($currentSort==='transaksi_count') @if($currentDir==='asc') <i class="bi bi-chevron-up"></i> @else <i class="bi bi-chevron-down"></i> @endif @endif</a></th>
                            <th class="text-end"><a href="{{ $sortLink('total_spending') }}">Total Spending @if($currentSort==='total_spending') @if($currentDir==='asc') <i class="bi bi-chevron-up"></i> @else <i class="bi bi-chevron-down"></i> @endif @endif</a></th>
                            <th><a href="{{ $sortLink('last_transaction_at') }}">Tgl Terakhir @if($currentSort==='last_transaction_at') @if($currentDir==='asc') <i class="bi bi-chevron-up"></i> @else <i class="bi bi-chevron-down"></i> @endif @endif</a></th>
                            <th>Cabang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->nama ?? '-' }}</td>
                                <td>{{ $row->no_hp ?? '-' }}</td>
                                <td>{{ $row->email ?? '-' }}</td>
                                <td class="text-end">{{ (int) $row->transaksi_count }}</td>
                                <td class="text-end">Rp {{ number_format((float) $row->total_spending, 0, ',', '.') }}</td>
                                <td>{{ $row->last_transaction_at ? \Carbon\Carbon::parse($row->last_transaction_at)->format('Y-m-d H:i') : '-' }}</td>
                                <td>{{ $row->cabangs ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
@endsection
