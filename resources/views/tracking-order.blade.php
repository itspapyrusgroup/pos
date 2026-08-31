@extends('layouts.app')

@section('title', 'Tracking Order')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active">Tracking Order</li>
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

<div id="ajaxFlashContainer"></div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">No KO</label>
                <input type="text" name="no_ko" class="form-control" value="{{ $filters['no_ko'] ?? request('no_ko') }}" placeholder="Contoh: KO-20260227-0001">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cabang</label>
                <select name="cabang_id" class="form-select">
                    <option value="">Semua Cabang Akses</option>
                    @foreach(($cabangs ?? collect()) as $cabang)
                        <option value="{{ $cabang->id }}" @selected((string) ($filters['cabang_id'] ?? '') === (string) $cabang->id)>
                            {{ $cabang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary">Cari Tracking</button>
            </div>
        </form>
    </div>
</div>

@if(($overdueKoList ?? collect())->isNotEmpty())
    <div class="card mb-3 border-danger">
        <div class="card-header bg-danger text-white">
            <strong>KO Lewat Deadline dan Belum Selesai</strong>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>No KO</th>
                        <th>Deadline</th>
                        <th>Pelanggan</th>
                        <th>Sisa Step KO</th>
                        <th>Sisa Step Item</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($overdueKoList ?? collect()) as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>{{ $item['nomor_ko'] }}</td>
                            <td>{{ $item['tanggal_selesai']?->format('d-m-Y') ?? '-' }}</td>
                            <td>{{ $item['customer_name'] ?? '-' }}</td>
                            <td>{{ (int) ($item['unchecked_ko_steps'] ?? 0) }}</td>
                            <td>{{ (int) ($item['unchecked_item_steps'] ?? 0) }}</td>
                            <td>
                                <a href="{{ route('tracking-order', ['no_ko' => $item['nomor_ko'], 'cabang_id' => $filters['cabang_id'] ?? null]) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div id="trackingOrderResult">
    @if(($filters['no_ko'] ?? request('no_ko')) && !$ko?->pesananPenjualan)
        <div class="alert alert-warning">No KO tidak ditemukan atau tidak bisa diakses pada cabang filter Anda.</div>
    @endif

    @if($ko?->pesananPenjualan)
        <div class="card mb-3">
            <div class="card-body">
                <div><strong>No KO:</strong> {{ $ko->nomor_ko }}</div>
                <div><strong>Deadline:</strong> {{ $ko->tanggal_selesai?->format('d-m-Y') ?? '-' }}</div>
                <div>
                    <strong>Status Tracking:</strong>
                    @if(($selectedKoProgress['is_finished'] ?? false) === true)
                        <span class="badge bg-success">SELESAI</span>
                    @else
                        <span class="badge bg-warning text-dark">BELUM SELESAI</span>
                        <span class="small text-muted ms-2">
                            Sisa step KO: {{ (int) ($selectedKoProgress['unchecked_ko_steps'] ?? 0) }},
                            sisa step item: {{ (int) ($selectedKoProgress['unchecked_item_steps'] ?? 0) }}
                        </span>
                    @endif
                </div>
                <div><strong>Pelanggan:</strong> {{ $ko->pesananPenjualan->customer_name ?: ($ko->pesananPenjualan->pelanggan?->nama ?? '-') }}</div>
                <div><strong>Jabatan Login:</strong> {{ $user?->karyawan?->jabatan?->nama ?? '-' }}</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light">
                <strong>Tracking Level KO</strong>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Step KO</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($koStepChecks ?? collect()) as $idx => $step)
                            @php($isChecked = (bool) ($step['is_checked'] ?? false))
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $step['nama'] ?? '-' }}</td>
                                <td>
                                    @if($step['can_update'] ?? false)
                                        <form method="POST" action="{{ route('tracking-order.ko-check.update') }}" class="d-inline" data-ajax-tracking-toggle="1">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="no_ko" value="{{ $ko->nomor_ko }}">
                                            <input type="hidden" name="step_kode" value="{{ $step['kode'] }}">
                                            <input type="hidden" name="is_checked" value="0">
                                            <div class="form-check d-inline-flex align-items-center gap-2">
                                                <input class="form-check-input" type="checkbox" name="is_checked" value="1" data-tracking-toggle="1" {{ $isChecked ? 'checked' : '' }}>
                                                <label class="form-check-label">Selesai</label>
                                            </div>
                                        </form>
                                    @else
                                        <div class="form-check d-inline-flex align-items-center gap-2">
                                            <input class="form-check-input" type="checkbox" disabled {{ $isChecked ? 'checked' : '' }}>
                                            <label class="form-check-label text-muted">Read only</label>
                                        </div>
                                    @endif
                                    @if($step['checked_by'] ?? false)
                                        <div class="small text-muted mt-1">
                                            Oleh {{ $step['checked_by'] }}{{ $step['checked_at'] ? ' | ' . $step['checked_at']->format('d-m-Y H:i') : '' }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada step KO.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @forelse(($itemTrackingGroups ?? collect()) as $groupIndex => $group)
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>Paket {{ $groupIndex + 1 }}: {{ $group['paket_nama'] ?? '-' }}</strong>
                    <span class="text-muted ms-2">Qty Pesanan: {{ (float) ($group['order_qty'] ?? 0) }}</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Item Pekerjaan</th>
                                <th>Kategori</th>
                                <th>Tracking</th>
                                <th>Qty Paket</th>
                                <th>Qty Total</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(collect($group['paket_items'] ?? []) as $idx => $item)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $item['nama'] ?? '-' }}</td>
                                    <td>{{ $item['kategori'] ?? '-' }}</td>
                                    <td>{{ $item['tracking_nama'] ?? '-' }}</td>
                                    <td>{{ (float) ($item['qty'] ?? 0) }}</td>
                                    <td>{{ (float) ($item['total_qty'] ?? 0) }}</td>
                                    <td>
                                        @php($isChecked = (bool) ($item['is_checked'] ?? false))
                                        @if($item['can_update'] ?? false)
                                            <form method="POST" action="{{ route('tracking-order.item-check.update') }}" class="d-inline" data-ajax-tracking-toggle="1">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="no_ko" value="{{ $ko->nomor_ko }}">
                                                <input type="hidden" name="pesanan_penjualan_item_id" value="{{ $group['order_item_id'] }}">
                                                <input type="hidden" name="produk_id" value="{{ $item['produk_id'] }}">
                                                <input type="hidden" name="is_checked" value="0">
                                                <div class="form-check d-inline-flex align-items-center gap-2">
                                                    <input class="form-check-input" type="checkbox" name="is_checked" value="1" data-tracking-toggle="1" {{ $isChecked ? 'checked' : '' }}>
                                                    <label class="form-check-label">Selesai</label>
                                                </div>
                                            </form>
                                        @else
                                            <div class="form-check d-inline-flex align-items-center gap-2">
                                                <input class="form-check-input" type="checkbox" disabled {{ $isChecked ? 'checked' : '' }}>
                                                <label class="form-check-label text-muted">Read only</label>
                                            </div>
                                        @endif
                                        @if($item['checked_by'] ?? false)
                                            <div class="small text-muted mt-1">
                                                Oleh {{ $item['checked_by'] }}{{ $item['checked_at'] ? ' | ' . $item['checked_at']->format('d-m-Y H:i') : '' }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Tidak ada item paket.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="alert alert-info">Tidak ada item order untuk KO ini.</div>
        @endforelse
    @endif
</div>

@push('scripts')
<script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const flashContainer = document.getElementById('ajaxFlashContainer');

        function showFlash(message, type) {
            if (!flashContainer) return;
            flashContainer.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>';
        }

        async function refreshResultArea() {
            const response = await fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Gagal memuat ulang data tracking.');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newResult = doc.querySelector('#trackingOrderResult');
            const currentResult = document.querySelector('#trackingOrderResult');

            if (newResult && currentResult) {
                currentResult.outerHTML = newResult.outerHTML;
            }
        }

        document.addEventListener('change', function (event) {
            const checkbox = event.target;
            if (!(checkbox instanceof HTMLInputElement)) return;
            if (!checkbox.matches('input[type="checkbox"][data-tracking-toggle="1"]')) return;

            const form = checkbox.closest('form[data-ajax-tracking-toggle="1"]');
            if (!form) return;

            const originalChecked = !checkbox.checked;
            checkbox.disabled = true;

            const formData = new FormData(form);
            formData.set('is_checked', checkbox.checked ? '1' : '0');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            }).then(async function (response) {
                if (!response.ok) {
                    let message = 'Checklist gagal diperbarui.';
                    try {
                        const data = await response.json();
                        message = data.message || message;
                    } catch (e) {}
                    throw new Error(message);
                }

                const data = await response.json();
                await refreshResultArea();
                showFlash(data.message || 'Checklist berhasil diperbarui.', 'success');
            }).catch(function (error) {
                checkbox.checked = originalChecked;
                checkbox.disabled = false;
                showFlash(error.message || 'Terjadi kesalahan.', 'danger');
            });
        });
    })();
</script>
@endpush
@endsection
