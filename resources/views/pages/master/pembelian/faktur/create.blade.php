@extends('layouts.app')

@section('title', 'Buat Faktur Pembelian')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('pembelian.faktur') }}">Faktur Pembelian</a></li>
                <li class="breadcrumb-item active" aria-current="page">Buat</li>
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
        <form method="POST" action="{{ route('pembelian.faktur.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">No Faktur</label>
                    <input type="text" class="form-control" value="{{ $nomorFaktur }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">No PO</label>
                    <select class="form-select" name="pesanan_pembelian_id" id="pesanan_pembelian_id" required>
                        <option value="">Pilih PO</option>
                        @foreach($poList as $po)
                            <option value="{{ $po->id }}">{{ $po->nomor_po }} - {{ $po->pemasok->nama ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Faktur</label>
                    <input type="date" class="form-control" name="tanggal_faktur" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jatuh Tempo</label>
                    <input type="date" class="form-control" name="jatuh_tempo">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" name="catatan" rows="2"></textarea>
                </div>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-bordered" id="tabel-item">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th width="120">Qty</th>
                            <th width="180">Harga</th>
                            <th width="180">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Simpan Faktur</button>
                <a href="{{ route('pembelian.faktur') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const poPayload = @json($poPayload);

    const poSelect = document.getElementById('pesanan_pembelian_id');
    const tbody = document.querySelector('#tabel-item tbody');

    function syncSubtotal(row) {
        const qty = parseFloat(row.querySelector('[name="qty[]"]').value || '0');
        const harga = parseFloat(row.querySelector('[name="harga[]"]').value || '0');
        row.querySelector('.subtotal-text').textContent = (qty * harga).toFixed(2);
    }

    poSelect.addEventListener('change', function () {
        tbody.innerHTML = '';
        const items = poPayload[this.value] || [];
        items.forEach((item) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    ${item.produk_nama}
                    <input type="hidden" name="produk_id[]" value="${item.produk_id}">
                </td>
                <td><input type="number" step="0.01" min="0.01" class="form-control" name="qty[]" value="${item.qty}"></td>
                <td><input type="number" step="0.01" min="0" class="form-control" name="harga[]" value="${item.harga}"></td>
                <td><span class="subtotal-text">0.00</span></td>
            `;
            tbody.appendChild(row);
            syncSubtotal(row);
        });
    });

    document.addEventListener('input', function (e) {
        if (e.target.name !== 'qty[]' && e.target.name !== 'harga[]') return;
        syncSubtotal(e.target.closest('tr'));
    });
</script>
@endpush
