@extends('layouts.app')

@section('title', 'Edit Permintaan Barang')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Transaksi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('permintaan-barang.index') }}">Permintaan Barang</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('permintaan-barang.update', $permintaan) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">No. Permintaan</label>
                    <input type="text" class="form-control" value="{{ $permintaan->nomor_permintaan }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Permintaan</label>
                    <input type="date" class="form-control" name="tanggal_permintaan" value="{{ old('tanggal_permintaan', $permintaan->tanggal_permintaan) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Dibutuhkan</label>
                    <input type="date" class="form-control" name="tanggal_dibutuhkan" value="{{ old('tanggal_dibutuhkan', $permintaan->tanggal_dibutuhkan) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cabang</label>
                    <select class="form-select" name="cabang_id" required>
                        @foreach($cabangList as $cabang)
                            <option value="{{ $cabang->id }}" {{ (string) $permintaan->cabang_id === (string) $cabang->id ? 'selected' : '' }}>
                                {{ $cabang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" required>
                        @foreach(['DRAFT','APPROVED','PROCESSED','CANCELLED'] as $status)
                            <option value="{{ $status }}" {{ $permintaan->status === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" name="catatan" rows="2">{{ old('catatan', $permintaan->catatan) }}</textarea>
                </div>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-bordered" id="tabel-item">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th width="120">Qty</th>
                            <th>Catatan</th>
                            <th width="60"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permintaan->items as $item)
                            <tr>
                                <td>
                                    <select class="form-select" name="produk_id[]" required>
                                        <option value="">Pilih Produk</option>
                                        @foreach($produkList as $produk)
                                            <option value="{{ $produk->id }}" {{ (string) $item->produk_id === (string) $produk->id ? 'selected' : '' }}>
                                                {{ $produk->kode }} - {{ $produk->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0.01" class="form-control" name="qty[]" value="{{ (float) $item->qty }}" required></td>
                                <td><input type="text" class="form-control" name="catatan_item[]" value="{{ $item->catatan }}"></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-item" {{ $loop->count === 1 ? 'disabled' : '' }}>Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-tambah-item">Tambah Item</button>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('permintaan-barang.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('btn-tambah-item').addEventListener('click', function () {
        const tbody = document.querySelector('#tabel-item tbody');
        const row = tbody.querySelector('tr').cloneNode(true);
        row.querySelectorAll('input').forEach((el) => el.value = '');
        row.querySelector('select').selectedIndex = 0;
        row.querySelector('.btn-hapus-item').disabled = false;
        tbody.appendChild(row);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('btn-hapus-item')) {
            return;
        }

        const tbody = document.querySelector('#tabel-item tbody');
        if (tbody.querySelectorAll('tr').length === 1) {
            return;
        }
        e.target.closest('tr').remove();
    });
</script>
@endpush
