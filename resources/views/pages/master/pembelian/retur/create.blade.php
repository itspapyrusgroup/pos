@extends('layouts.app')

@section('title', 'Buat Retur Pembelian')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('pembelian.retur') }}">Retur Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Buat Retur</li>
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

<form method="POST" action="{{ route('pembelian.retur.store') }}">
    @csrf
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Nomor Retur</label>
                    <input type="text" class="form-control" value="{{ $nomorRetur }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Retur</label>
                    <input type="date" name="tanggal_retur" class="form-control" value="{{ old('tanggal_retur', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="POSTED" {{ old('status', 'POSTED') === 'POSTED' ? 'selected' : '' }}>POSTED</option>
                        <option value="DRAFT" {{ old('status') === 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Penerimaan Barang</label>
                    <select name="penerimaan_barang_id" id="penerimaan_barang_id" class="form-select" required>
                        <option value="">Pilih Penerimaan</option>
                        @foreach($penerimaanList as $penerimaan)
                            <option value="{{ $penerimaan->id }}" {{ (string) old('penerimaan_barang_id') === (string) $penerimaan->id ? 'selected' : '' }}>
                                {{ $penerimaan->nomor_penerimaan }} - {{ $penerimaan->pesananPembelian->nomor_po ?? '-' }} - {{ $penerimaan->pesananPembelian->pemasok->nama ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2">{{ old('catatan') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle" id="retur-items-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Qty Diterima</th>
                        <th>Qty Sisa Retur</th>
                        <th>Qty Retur</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Pilih penerimaan barang terlebih dahulu.</td>
                    </tr>
                </tbody>
            </table>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('pembelian.retur') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Retur</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const penerimaanPayload = @json($penerimaanPayload);
    const selectPenerimaan = document.getElementById('penerimaan_barang_id');
    const tableBody = document.querySelector('#retur-items-table tbody');

    function renderRows() {
        const penerimaanId = selectPenerimaan.value;
        const items = penerimaanPayload[penerimaanId] || [];

        if (!items.length) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada item untuk diretur.</td></tr>';
            return;
        }

        tableBody.innerHTML = items.map((item, index) => `
            <tr>
                <td>
                    ${item.produk_nama}
                    <input type="hidden" name="penerimaan_barang_item_id[${index}]" value="${item.id}">
                </td>
                <td>${item.qty_terima}</td>
                <td>${item.qty_sisa_retur}</td>
                <td>
                    <input type="number" name="qty[${index}]" class="form-control" min="0" max="${item.qty_sisa_retur}" step="0.01" value="0">
                </td>
                <td>
                    <input type="text" name="alasan_retur[${index}]" class="form-control" maxlength="150">
                </td>
            </tr>
        `).join('');
    }

    selectPenerimaan.addEventListener('change', renderRows);
    if (selectPenerimaan.value) {
        renderRows();
    }
</script>
@endpush
