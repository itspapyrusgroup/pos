@extends('layouts.app')

@section('title', 'Bill of Material')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Bill of Material</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBom">+ Tambah BOM</button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="nama" class="form-control" placeholder="Cari nama BOM..." value="{{ request('nama') }}">
            </div>
            <div class="col-md-3">
                <select name="tipe" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="PAKET" {{ request('tipe') === 'PAKET' ? 'selected' : '' }}>PAKET</option>
                    <option value="ADDON" {{ request('tipe') === 'ADDON' ? 'selected' : '' }}>ADDON</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non Aktif</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('paket.bom') }}" class="btn btn-outline-secondary">Reset</a>
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
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Jumlah Item</th>
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bomList as $index => $bom)
                    <tr>
                        <td>{{ $bomList->firstItem() + $index }}</td>
                        <td>{{ $bom->kode }}</td>
                        <td>{{ $bom->nama }}</td>
                        <td>{{ $bom->tipe }}</td>
                        <td>{{ $bom->items_count }}</td>
                        <td>{!! $bom->status ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non Aktif</span>' !!}</td>
                        <td class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetailBom{{ $bom->id }}">Detail</button>
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditBom{{ $bom->id }}">Edit</button>
                            <form method="POST" action="{{ route('paket.bom.destroy', $bom) }}" data-swal-message="Hapus BOM ini?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data BOM.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $bomList->links() }}
    </div>
</div>

<div class="modal fade" id="modalTambahBom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('paket.bom.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah BOM</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Nama BOM</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipe</label>
                            <select name="tipe" class="form-select" required>
                                <option value="PAKET">PAKET</option>
                                <option value="ADDON">ADDON</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Non Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th width="180">Qty</th>
                                    <th width="80">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyCreateBom">
                                <tr class="bom-row">
                                    <td>
                                        <select name="item_produk_id[]" class="form-select select-produk" required>
                                            <option value="">Cari kode / nama produk...</option>
                                            @foreach($produkList as $produk)
                                                <option value="{{ $produk->id }}">{{ $produk->kode }} - {{ $produk->nama }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" min="0.01" name="item_qty[]" class="form-control" required></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-add-row" data-target="tbodyCreateBom">+ Tambah Item</button>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($bomList as $bom)
    <div class="modal fade" id="modalDetailBom{{ $bom->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail BOM - {{ $bom->kode }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><strong>Nama:</strong> {{ $bom->nama }}</div>
                    <div class="mb-2"><strong>Tipe:</strong> {{ $bom->tipe }}</div>
                    <div class="mb-3"><strong>Status:</strong> {{ $bom->status ? 'Aktif' : 'Non Aktif' }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produk</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bom->items as $i => $item)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $item->produk->nama ?? '-' }}</td>
                                        <td>{{ rtrim(rtrim(number_format($item->qty, 2, '.', ''), '0'), '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada item.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditBom{{ $bom->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('paket.bom.update', $bom) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit BOM - {{ $bom->kode }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <label class="form-label">Nama BOM</label>
                                <input type="text" name="nama" class="form-control" value="{{ $bom->nama }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipe</label>
                                <select name="tipe" class="form-select" required>
                                    <option value="PAKET" {{ $bom->tipe === 'PAKET' ? 'selected' : '' }}>PAKET</option>
                                    <option value="ADDON" {{ $bom->tipe === 'ADDON' ? 'selected' : '' }}>ADDON</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="1" {{ $bom->status ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$bom->status ? 'selected' : '' }}>Non Aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th width="180">Qty</th>
                                        <th width="80">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyEditBom{{ $bom->id }}">
                                    @forelse($bom->items as $item)
                                        <tr class="bom-row">
                                            <td>
                                                <select name="item_produk_id[]" class="form-select select-produk" required>
                                                    <option value="">Cari kode / nama produk...</option>
                                                    @foreach($produkList as $produk)
                                                        <option value="{{ $produk->id }}" {{ (int) $item->produk_id === (int) $produk->id ? 'selected' : '' }}>{{ $produk->kode }} - {{ $produk->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" min="0.01" name="item_qty[]" class="form-control" value="{{ $item->qty }}" required></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button></td>
                                        </tr>
                                    @empty
                                        <tr class="bom-row">
                                            <td>
                                                <select name="item_produk_id[]" class="form-select select-produk" required>
                                                    <option value="">Cari kode / nama produk...</option>
                                                    @foreach($produkList as $produk)
                                                        <option value="{{ $produk->id }}">{{ $produk->kode }} - {{ $produk->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" min="0.01" name="item_qty[]" class="form-control" required></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">x</button></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-add-row" data-target="tbodyEditBom{{ $bom->id }}">+ Tambah Item</button>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-warning">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    (function () {
        const produkOptions = @json(
            $produkList->map(function ($produk) {
                return [
                    'id' => (int) $produk->id,
                    'text' => trim(($produk->kode ? ($produk->kode . ' - ') : '') . $produk->nama),
                ];
            })->values()
        );

        function buildProdukSelectElement() {
            const select = document.createElement('select');
            select.name = 'item_produk_id[]';
            select.className = 'form-select select-produk';
            select.required = true;

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Cari kode / nama produk...';
            select.appendChild(placeholder);

            produkOptions.forEach(function (item) {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.text;
                select.appendChild(option);
            });

            return select;
        }

        function initProdukSelect(scope) {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
            const $scope = window.jQuery(scope || document);
            $scope.find('select.select-produk').each(function () {
                const $el = window.jQuery(this);
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Cari kode / nama produk...',
                    allowClear: true,
                    dropdownParent: $el.closest('.modal'),
                });
            });
        }

        function bindAddRow(btn) {
            btn.addEventListener('click', function () {
                const targetId = btn.getAttribute('data-target');
                const tbody = document.getElementById(targetId);
                if (!tbody) return;

                const tr = document.createElement('tr');
                tr.className = 'bom-row';
                const tdProduk = document.createElement('td');
                tdProduk.appendChild(buildProdukSelectElement());

                const tdQty = document.createElement('td');
                const qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.step = '0.01';
                qtyInput.min = '0.01';
                qtyInput.name = 'item_qty[]';
                qtyInput.className = 'form-control';
                qtyInput.required = true;
                tdQty.appendChild(qtyInput);

                const tdAksi = document.createElement('td');
                tdAksi.className = 'text-center';
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger btn-remove-row';
                removeBtn.textContent = 'x';
                tdAksi.appendChild(removeBtn);

                tr.appendChild(tdProduk);
                tr.appendChild(tdQty);
                tr.appendChild(tdAksi);

                tbody.appendChild(tr);
                initProdukSelect(tr);
            });
        }

        document.querySelectorAll('.btn-add-row').forEach(bindAddRow);
        initProdukSelect(document);
        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                initProdukSelect(modal);
            });
        });

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('.btn-remove-row');
            if (!btn) return;
            const tbody = btn.closest('tbody');
            if (!tbody) return;
            const rows = tbody.querySelectorAll('.bom-row');
            if (rows.length <= 1) {
                const row = btn.closest('.bom-row');
                if (!row) return;
                row.querySelectorAll('input').forEach(function (el) {
                    el.value = '';
                });
                const select = row.querySelector('select.select-produk');
                if (select) {
                    if (window.jQuery && window.jQuery(select).data('select2')) {
                        window.jQuery(select).val('').trigger('change');
                    } else {
                        select.value = '';
                    }
                }
                return;
            }
            const row = btn.closest('.bom-row');
            if (row) row.remove();
        });
    })();
</script>
@endpush
