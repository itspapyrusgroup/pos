@extends('layouts.app')

@section('title', 'Pesanan Pembelian')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pembelian</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Pesanan Pembelian</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('pembelian.pesanan.create') }}" class="btn btn-primary">+ Buat PO</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" class="form-control" name="nomor_po" placeholder="Cari nomor PO..." value="{{ request('nomor_po') }}">
            </div>
            <div class="col-md-3">
                <select class="form-select js-select2" name="pemasok_id" data-placeholder="Semua Pemasok">
                    <option value="">Semua Pemasok</option>
                    @foreach($pemasokList as $pemasok)
                        <option value="{{ $pemasok->id }}" {{ (string) request('pemasok_id') === (string) $pemasok->id ? 'selected' : '' }}>
                            {{ $pemasok->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select js-select2" name="status" data-placeholder="Semua Status">
                    <option value="">Semua Status</option>
                    @foreach(['DRAFT','ORDERED','PARTIAL_RECEIVED','RECEIVED','CLOSED'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>No PO</th>
                    <th>Tgl PO</th>
                    <th>Pemasok</th>
                    <th>Cabang</th>
                    <th>Dari Permintaan</th>
                    <th>Status</th>
                    <th width="320">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pesananList as $index => $po)
                    <tr>
                        <td>{{ $pesananList->firstItem() + $index }}</td>
                        <td>{{ $po->nomor_po }}</td>
                        <td>{{ \Carbon\Carbon::parse($po->tanggal_po)->format('d-m-Y') }}</td>
                        <td>{{ $po->pemasok->nama ?? '-' }}</td>
                        <td>{{ $po->cabang->nama ?? '-' }}</td>
                        <td>{{ $po->permintaanBarang->nomor_permintaan ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $po->status }}</span></td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('pembelian.pesanan.show', $po) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            <a href="{{ route('pembelian.pesanan.pdf', $po) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                            @if($po->penerimaan_count === 0 && $po->faktur_count === 0 && $po->status !== 'CLOSED')
                                <a href="{{ route('pembelian.pesanan.edit', $po) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                <form method="POST" action="{{ route('pembelian.pesanan.destroy', $po) }}" class="js-delete-po-form">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            @endif

                            @if(in_array($po->status, ['ORDERED', 'PARTIAL_RECEIVED']) && $po->status !== 'CLOSED' && (float) $po->outstanding_qty > 0)
                                <form method="POST" action="{{ route('pembelian.pesanan.close', $po) }}" data-swal-message="Tutup PO ini? Sisa outstanding tidak akan ditagih penerimaan lagi.">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Tutup PO</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada data pesanan pembelian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $pesananList->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        if (typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.select2 !== 'undefined') {
            window.jQuery('.js-select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: function () {
                    return window.jQuery(this).data('placeholder') || 'Pilih';
                }
            });
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form.classList.contains('js-delete-po-form')) return;
            if (form.dataset.swalConfirmed === '1') return;

            event.preventDefault();

            if (typeof Swal === 'undefined') {
                form.dataset.swalConfirmed = '1';
                form.submit();
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Hapus PO ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then(function (result) {
                if (!result.isConfirmed) return;
                form.dataset.swalConfirmed = '1';
                form.submit();
            });
        }, true);
    })();
</script>
@endpush
