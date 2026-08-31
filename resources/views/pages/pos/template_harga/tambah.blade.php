@extends('layouts.app')

@section('title', 'Detail Template Harga')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('template.harga') }}">Template Harga</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detail</li>
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

@php
    $productGroups = $produkList->groupBy(fn ($produk) => (string) ($produk->kategori_produk_kode ?: '__NO_CATEGORY__'));
    $packageGroups = $paketList->groupBy(fn ($paket) => (string) ($paket->kategori_paket_id ?: '__NO_CATEGORY__'));
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1">{{ $template->nama }}</h5>
                <div class="text-muted small">{{ $template->kode }}{{ $template->keterangan ? ' - ' . $template->keterangan : '' }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label form-label-sm mb-1">Copy dari Template</label>
                    <select id="copy-source-template" class="form-select form-select-sm">
                        <option value="">Pilih template sumber...</option>
                        @foreach($templateSumberList as $item)
                            <option value="{{ $item->id }}">{{ $item->kode }} - {{ $item->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="btn-copy-source-produk" class="btn btn-sm btn-outline-primary">Copy Harga Produk</button>
                <button type="button" id="btn-copy-default-produk" class="btn btn-sm btn-outline-secondary">Copy Default Produk</button>
                <button type="button" id="btn-copy-default-paket" class="btn btn-sm btn-outline-secondary">Copy Default Paket</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('template.harga.detail.simpan', $template) }}" id="form-template-harga-detail">
            @csrf
            <input type="hidden" name="items_payload" id="items-payload">

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-root-barang-jasa" type="button" role="tab">
                        Barang & Jasa
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-root-paket" type="button" role="tab">
                        Paket
                    </button>
                </li>
            </ul>

            <div class="tab-content mb-4">
                <div class="tab-pane fade show active" id="tab-root-barang-jasa" role="tabpanel">
                    <h6 class="mb-2">Harga Barang & Jasa (Per Golongan)</h6>
                    @if($productGroups->isEmpty())
                        <div class="alert alert-light border text-muted">Belum ada produk.</div>
                    @else
                        <ul class="nav nav-pills mb-3 flex-wrap gap-2" role="tablist">
                            @foreach($productGroups as $kode => $items)
                                @php
                                    $first = $items->first();
                                    $kodeLabel = $first?->kategoriProduk?->kode ?: ($kode !== '__NO_CATEGORY__' ? $kode : '');
                                    $namaLabel = $first?->kategoriProduk?->nama ?: 'Tanpa Golongan';
                                    $tabId = 'produk-group-' . $loop->index;
                                @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}" type="button" role="tab">
                                        {{ trim(($kodeLabel ? $kodeLabel . ' - ' : '') . $namaLabel) }} ({{ $items->count() }})
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content mb-4">
                            @php $productIndex = 0; @endphp
                            @foreach($productGroups as $kode => $items)
                                @php $tabId = 'produk-group-' . $loop->index; @endphp
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tabId }}" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead>
                                                <tr>
                                                    <th width="140">Kode</th>
                                                    <th>Produk</th>
                                                    <th width="140">Harga Default</th>
                                                    <th width="160">Harga Template</th>
                                                    <th width="90">Aktif</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $produk)
                                                    @php
                                                        $key = 'PRODUK-' . $produk->id;
                                                        $current = $itemHarga[$key] ?? null;
                                                    @endphp
                                                    <tr data-item-row="1" data-item-type="PRODUK" data-key="{{ $key }}">
                                                        <td>{{ $produk->kode ?: '-' }}</td>
                                                        <td>
                                                            {{ $produk->nama }}
                                                            <input type="hidden" name="items[{{ $productIndex }}][jenis_item]" value="PRODUK">
                                                            <input type="hidden" name="items[{{ $productIndex }}][item_id]" value="{{ $produk->id }}">
                                                        </td>
                                                        <td>{{ number_format((float) $produk->harga_default, 0, ',', '.') }}</td>
                                                        <td>
                                                            <input
                                                                type="text"
                                                                inputmode="decimal"
                                                                name="items[{{ $productIndex }}][harga]"
                                                                class="form-control harga-input harga-produk"
                                                                value="{{ $current->harga ?? $produk->harga_default }}"
                                                                data-default-price="{{ (float) $produk->harga_default }}"
                                                            >
                                                        </td>
                                                        <td>
                                                            <input type="hidden" name="items[{{ $productIndex }}][status]" value="0">
                                                            <input type="checkbox" name="items[{{ $productIndex }}][status]" value="1" class="status-input" {{ ($current ? $current->status : true) ? 'checked' : '' }}>
                                                        </td>
                                                    </tr>
                                                    @php $productIndex++; @endphp
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="tab-root-paket" role="tabpanel">
                    <h6 class="mb-2">Harga Paket (Per Kategori Paket)</h6>
                    @if($packageGroups->isEmpty())
                        <div class="alert alert-light border text-muted">Belum ada paket.</div>
                    @else
                        <ul class="nav nav-pills mb-3 flex-wrap gap-2" role="tablist">
                            @foreach($packageGroups as $kategoriId => $items)
                                @php
                                    $first = $items->first();
                                    $kategoriNama = $first?->kategoriPaket?->nama ?: 'Tanpa Kategori';
                                    $tabId = 'paket-group-' . $loop->index;
                                @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}" type="button" role="tab">
                                        {{ $kategoriNama }} ({{ $items->count() }})
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content mb-4">
                            @php $packageIndex = 10000; @endphp
                            @foreach($packageGroups as $kategoriId => $items)
                                @php $tabId = 'paket-group-' . $loop->index; @endphp
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tabId }}" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead>
                                                <tr>
                                                    <th width="140">Kode</th>
                                                    <th>Paket</th>
                                                    <th width="160">Harga Default</th>
                                                    <th width="160">Harga Template</th>
                                                    <th width="90">Aktif</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $paket)
                                                    @php
                                                        $key = 'PAKET-' . $paket->id;
                                                        $current = $itemHarga[$key] ?? null;
                                                    @endphp
                                                    <tr data-item-row="1" data-item-type="PAKET" data-key="{{ $key }}">
                                                        <td>{{ $paket->kode ?: '-' }}</td>
                                                        <td>
                                                            {{ $paket->nama }}
                                                            <input type="hidden" name="items[{{ $packageIndex }}][jenis_item]" value="PAKET">
                                                            <input type="hidden" name="items[{{ $packageIndex }}][item_id]" value="{{ $paket->id }}">
                                                        </td>
                                                        <td>{{ number_format((float) $paket->harga_default, 0, ',', '.') }}</td>
                                                        <td><input type="text" inputmode="decimal" name="items[{{ $packageIndex }}][harga]" class="form-control harga-input harga-paket" value="{{ $current->harga ?? $paket->harga_default }}" data-default-price="{{ (float) $paket->harga_default }}"></td>
                                                        <td>
                                                            <input type="hidden" name="items[{{ $packageIndex }}][status]" value="0">
                                                            <input type="checkbox" name="items[{{ $packageIndex }}][status]" value="1" class="status-input" {{ ($current ? $current->status : true) ? 'checked' : '' }}>
                                                        </td>
                                                    </tr>
                                                    @php $packageIndex++; @endphp
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <h6 class="mb-2">Harga Add On</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Add On</th>
                            <th width="160">Harga Template</th>
                            <th width="90">Aktif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $addonIndex = 20000; @endphp
                        @foreach($addonList as $addon)
                            @php
                                $key = 'ADDON-' . $addon->id;
                                $current = $itemHarga[$key] ?? null;
                            @endphp
                            <tr data-item-row="1" data-item-type="ADDON" data-key="{{ $key }}">
                                <td>
                                    {{ $addon->nama }}
                                    <input type="hidden" name="items[{{ $addonIndex }}][jenis_item]" value="ADDON">
                                    <input type="hidden" name="items[{{ $addonIndex }}][item_id]" value="{{ $addon->id }}">
                                </td>
                                <td><input type="text" inputmode="decimal" name="items[{{ $addonIndex }}][harga]" class="form-control harga-input" value="{{ $current->harga ?? 0 }}"></td>
                                <td>
                                    <input type="hidden" name="items[{{ $addonIndex }}][status]" value="0">
                                    <input type="checkbox" name="items[{{ $addonIndex }}][status]" value="1" class="status-input" {{ ($current ? $current->status : true) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            @php $addonIndex++; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary">Simpan Perubahan Harga</button>
            <a href="{{ route('template.harga') }}" class="btn btn-outline-secondary">Kembali</a>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const URL_COPY_SOURCE = "{{ route('template.harga.copy-source', $template) }}";

        function notify(type, title, text) {
            if (window.Swal) {
                window.Swal.fire(title, text, type);
            }
        }
        function parseCurrencyInput(value) {
            const raw = String(value ?? '').trim().replace(/[^\d,.\-]/g, '');
            if (!raw || raw === '-' || raw === ',' || raw === '.') return 0;
            const negative = raw.startsWith('-');
            const unsigned = raw.replace(/-/g, '');
            const lastComma = unsigned.lastIndexOf(',');
            const lastDot = unsigned.lastIndexOf('.');
            const decimalIndex = Math.max(lastComma, lastDot);
            const hasBothSeparator = lastComma !== -1 && lastDot !== -1;
            const digitsAfterLastSeparator = decimalIndex >= 0 ? (unsigned.length - decimalIndex - 1) : 0;
            const useDecimalSeparator = decimalIndex >= 0 && (
                hasBothSeparator ||
                (digitsAfterLastSeparator > 0 && digitsAfterLastSeparator <= 2)
            );

            let normalized = '';
            if (useDecimalSeparator) {
                const intPart = unsigned.slice(0, decimalIndex).replace(/[.,]/g, '');
                const fracPart = unsigned.slice(decimalIndex + 1).replace(/[.,]/g, '');
                normalized = intPart + (fracPart ? '.' + fracPart : '');
            } else {
                normalized = unsigned.replace(/[.,]/g, '');
            }

            const parsed = parseFloat((negative ? '-' : '') + normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }
        function toPlainCurrencyString(value) {
            const num = parseCurrencyInput(value);
            if (Math.abs(num - Math.round(num)) < 0.0000001) {
                return String(Math.round(num));
            }
            return num.toFixed(2).replace(/\.?0+$/, '');
        }
        function formatCurrencyInput(value) {
            const num = parseCurrencyInput(value);
            const isInteger = Math.abs(num - Math.round(num)) < 0.0000001;
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: isInteger ? 0 : 2,
                maximumFractionDigits: 2
            }).format(num);
        }
        function formatAllHargaInputs(scope) {
            const root = scope || document;
            root.querySelectorAll('input.harga-input').forEach(function (input) {
                input.value = formatCurrencyInput(input.value);
            });
        }

        function copyDefaultProduk() {
            document.querySelectorAll('tr[data-item-type="PRODUK"]').forEach(function (row) {
                const input = row.querySelector('input.harga-produk');
                if (!input) return;
                input.value = input.getAttribute('data-default-price') || '0';
            });
            formatAllHargaInputs();
            notify('success', 'Berhasil', 'Harga produk berhasil disalin dari harga default.');
        }
        function copyDefaultPaket() {
            document.querySelectorAll('tr[data-item-type="PAKET"]').forEach(function (row) {
                const input = row.querySelector('input.harga-paket');
                if (!input) return;
                input.value = input.getAttribute('data-default-price') || '0';
            });
            formatAllHargaInputs();
            notify('success', 'Berhasil', 'Harga paket berhasil disalin dari harga default.');
        }

        function copyProdukDariTemplateLain() {
            const sourceTemplateId = (document.getElementById('copy-source-template')?.value || '').trim();
            if (!sourceTemplateId) {
                notify('warning', 'Validasi', 'Pilih template sumber terlebih dahulu.');
                return;
            }

            const url = `${URL_COPY_SOURCE}?source_template_id=${encodeURIComponent(sourceTemplateId)}`;
            fetch(url, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(async function (response) {
                    const data = await response.json().catch(function () { return {}; });
                    if (!response.ok) {
                        throw new Error(data.message || 'Gagal mengambil data template sumber.');
                    }
                    return data;
                })
                .then(function (data) {
                    const items = data.items || {};
                    document.querySelectorAll('tr[data-item-type="PRODUK"]').forEach(function (row) {
                        const key = row.getAttribute('data-key') || '';
                        if (!key || !Object.prototype.hasOwnProperty.call(items, key)) return;
                        const source = items[key] || {};
                        const hargaInput = row.querySelector('input.harga-produk');
                        const statusCheckbox = row.querySelector('input.status-input[type="checkbox"]');
                        if (hargaInput && source.harga !== undefined) {
                            hargaInput.value = source.harga;
                        }
                        if (statusCheckbox && source.status !== undefined) {
                            statusCheckbox.checked = !!source.status;
                        }
                    });
                    formatAllHargaInputs();
                    notify('success', 'Berhasil', 'Harga produk berhasil disalin dari template sumber.');
                })
                .catch(function (error) {
                    notify('error', 'Gagal', error.message || 'Terjadi kesalahan.');
                });
        }

        document.getElementById('btn-copy-default-produk')?.addEventListener('click', copyDefaultProduk);
        document.getElementById('btn-copy-default-paket')?.addEventListener('click', copyDefaultPaket);
        document.getElementById('btn-copy-source-produk')?.addEventListener('click', copyProdukDariTemplateLain);
        document.addEventListener('focusin', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.classList.contains('harga-input')) return;
            target.value = toPlainCurrencyString(target.value);
        });
        document.addEventListener('focusout', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.classList.contains('harga-input')) return;
            target.value = formatCurrencyInput(target.value);
        });
        document.addEventListener('input', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !target.classList.contains('harga-input')) return;
            target.value = target.value.replace(/[^\d,.\-]/g, '');
        });

        document.getElementById('form-template-harga-detail')?.addEventListener('submit', function () {
            const items = [];
            document.querySelectorAll('tr[data-item-row="1"]').forEach(function (row) {
                const type = row.getAttribute('data-item-type') || '';
                const key = row.getAttribute('data-key') || '';
                const itemIdText = key.includes('-') ? key.split('-')[1] : '';
                const itemId = parseInt(itemIdText, 10);
                const hargaInput = row.querySelector('input.harga-input');
                const statusCheckbox = row.querySelector('input.status-input[type="checkbox"]');
                const harga = parseCurrencyInput(hargaInput?.value || '0');

                if (!type || !Number.isFinite(itemId) || itemId <= 0) return;
                items.push({
                    jenis_item: type,
                    item_id: itemId,
                    harga: Number.isFinite(harga) ? harga : 0,
                    status: statusCheckbox?.checked ? 1 : 0,
                });
            });

            const payloadInput = document.getElementById('items-payload');
            if (payloadInput) {
                payloadInput.value = JSON.stringify(items);
            }

            document.querySelectorAll('[name^="items["]').forEach(function (el) {
                el.disabled = true;
            });
        });

        formatAllHargaInputs();
    })();
</script>
@endpush
