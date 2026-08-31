@extends('layouts.app')

@section('title', 'Input Antrian Studio')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">POS</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item active" aria-current="page">Input Antrian Studio</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <strong>Cabang Aktif:</strong> {{ $activeCabang?->nama ?? '-' }}
        </div>
        @if(($cabangTersedia ?? collect())->count() > 1)
            <form method="POST" action="{{ route('active-cabang.update') }}" class="d-flex align-items-center gap-2">
                @csrf
                <label class="mb-0 small">Switch Cabang:</label>
                <select name="active_cabang_id" class="form-select form-select-sm">
                    @foreach($cabangTersedia as $cabangOption)
                        <option value="{{ $cabangOption->id }}" @selected((int) $cabangOption->id === (int) ($cabangDefaultId ?? 0))>
                            {{ $cabangOption->nama }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
            </form>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Antrian</label>
                            <input type="date" id="filter_tanggal_antrian" class="form-control"
                                value="{{ $tanggalAntrian }}">
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="button" id="btn_filter_tanggal" class="btn btn-outline-secondary btn-sm">
                                    Tampilkan
                                </button>
                                <button type="button" id="btn_filter_hari_ini" class="btn btn-outline-primary btn-sm">
                                    Hari Ini
                                </button>
                            </div>
                            {{-- <small class="text-muted d-block mt-1">Pilih tanggal lalu klik Tampilkan untuk melihat
                                antrian hari sebelumnya.</small> --}}
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Cari No KO</label>
                            <div class="input-group">
                                <input type="text" id="input_no_ko" class="form-control" placeholder="Masukkan No KO">
                                <button type="button" id="btn_show_ko" class="btn btn-primary">Show</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1" id="queue_board"></div>

    <div class="modal fade" id="modalKoInfo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Informasi KO & Input Antrian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No KO</label>
                            <input type="text" id="modal_no_ko" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <input type="text" id="modal_nama_pelanggan" class="form-control" readonly>
                        </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Antrian</label>
                        <input type="date" id="modal_tanggal_antrian" class="form-control">
                    </div>
                        <div class="col-md-6">
                            <label class="form-label">Pilih Studio (multiple)</label>
                            <select id="modal_studio_ids" class="form-select" multiple size="6"></select>
                            <small class="text-muted">Gunakan Ctrl/Command untuk pilih lebih dari satu studio.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Item KO</label>
                            <div class="border rounded p-2 bg-light" id="modal_ko_items">-</div>
                        </div>
                        <div class="col-12">
                            <div id="modal_queue_guard" class="alert alert-secondary mb-0 py-2 px-3 small">
                                Cek KO untuk melihat status kelayakan input antrian.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="btn_submit_antrian" class="btn btn-primary">Submit ke Antrian</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const SEARCH_URL = @json(route('input-antrian.ko-search'));
            const ENQUEUE_URL = @json(route('input-antrian.enqueue'));
            const REORDER_URL = @json(route('input-antrian.reorder'));
            const STUDIOS = @json(($studios ?? collect())->map(fn($s) => ['id' => (int) $s->id, 'nama' => $s->nama])->values());
            let boardData = @json($antrianByStudio ?? []);
            let selectedKo = null;
            let reorderInFlight = false;

            const modalEl = document.getElementById('modalKoInfo');
            const modal = new bootstrap.Modal(modalEl);

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }

        function todayJakarta() {
            return new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Jakarta',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }).format(new Date());
        }

        function applyTanggalAntrianMin() {
            const minDate = todayJakarta();
            $('#modal_tanggal_antrian').attr('min', minDate);
        }

            function renderQueueGuard(koData) {
                const guard = $('#modal_queue_guard');
                const submitBtn = $('#btn_submit_antrian');
                if (!koData) {
                    guard.removeClass('alert-danger alert-warning alert-success').addClass('alert-secondary')
                        .text('Cek KO untuk melihat status kelayakan input antrian.');
                    submitBtn.prop('disabled', true);
                    return;
                }

                const remaining = parseInt(koData.remaining_unchecked_tracking_items || 0, 10);
                const blocked = !!koData.is_queue_blocked;
                const hasCompleted = !!koData.has_completed_queue;

                if (blocked) {
                    guard.removeClass('alert-secondary alert-warning alert-success').addClass('alert-danger')
                        .text('KO sudah selesai difoto dan semua tracking paket sudah selesai. KO tidak bisa masuk antrian lagi.');
                    submitBtn.prop('disabled', true);
                    return;
                }

                if (hasCompleted && remaining > 0) {
                    guard.removeClass('alert-secondary alert-danger alert-success').addClass('alert-warning')
                        .text(`KO pernah selesai foto, tapi masih ada ${remaining} tracking item yang belum dicentang. KO masih bisa diantrikan.`);
                } else {
                    guard.removeClass('alert-secondary alert-danger alert-warning').addClass('alert-success')
                        .text('KO boleh diantrikan.');
                }
                submitBtn.prop('disabled', false);
            }

            function renderBoard(data) {
                const board = $('#queue_board');
                board.empty();

                (data || []).forEach((studio) => {
                    const html = `
                        <div class="col-xl-4 col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <strong>${escapeHtml(studio.studio_nama || '-')}</strong>
                                </div>
                                <div class="card-body p-2">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 64px;">No</th>
                                                    <th>No KO</th>
                                                    <th>Nama</th>
                                                    <th style="width: 64px;">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="queue-body" data-studio-id="${studio.studio_id}">
                                                ${((studio.items || []).length === 0) ? `
                                                    <tr class="queue-empty">
                                                        <td colspan="4" class="text-muted text-center py-3">Kosong - drop KO ke sini</td>
                                                    </tr>
                                                ` : ''}
                                                ${(studio.items || []).map((item) => `
                                                    <tr class="queue-row ${item.color === 'red' ? 'table-danger' : (item.color === 'green' ? 'table-success' : '')} ${item.is_locked ? 'queue-row-locked' : ''}" draggable="${item.is_locked ? 'false' : 'true'}" data-item-id="${item.id}" data-locked="${item.is_locked ? '1' : '0'}">
                                                        <td><span class="badge bg-secondary">${item.nomor_antrian}</span></td>
                                                        <td>${escapeHtml(item.no_ko || '-')}</td>
                                                        <td>${escapeHtml(item.nama_pelanggan || '-')}</td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-antrian" data-item-id="${item.id}" title="${item.is_locked ? 'Tidak bisa dihapus (sesi sudah berjalan/selesai)' : 'Hapus dari antrian'}" ${item.is_locked ? 'disabled' : ''}>
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    board.append(html);
                });

                bindDragEvents();
            }

            function collectQueuePayload() {
                const payload = [];

                document.querySelectorAll('.queue-body').forEach((body) => {
                    const studioId = parseInt(body.getAttribute('data-studio-id') || '0', 10);
                    if (!studioId) {
                        return;
                    }

                    const itemIds = Array.from(body.querySelectorAll('.queue-row'))
                        .map((row) => parseInt(row.getAttribute('data-item-id') || '0', 10))
                        .filter((id) => id > 0);

                    payload.push({
                        studio_id: studioId,
                        item_ids: itemIds,
                    });
                });

                return payload;
            }

            function getDragAfterElement(container, y) {
                const draggableElements = [...container.querySelectorAll('.queue-row:not(.dragging)')];
                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    if (offset < 0 && offset > closest.offset) {
                        return { offset: offset, element: child };
                    }
                    return closest;
                }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
            }

            function sendReorder() {
                if (reorderInFlight) {
                    return;
                }
                reorderInFlight = true;

                $.ajax({
                    url: REORDER_URL,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken() },
                    contentType: 'application/json',
                    data: JSON.stringify({
                        tanggal_antrian: $('#filter_tanggal_antrian').val(),
                        queues: collectQueuePayload(),
                    }),
                }).done((response) => {
                    boardData = response.board || [];
                    renderBoard(boardData);
                }).fail((xhr) => {
                    const message = xhr.responseJSON?.message || 'Gagal memperbarui urutan antrian.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                    renderBoard(boardData);
                }).always(() => {
                    reorderInFlight = false;
                });
            }

            function bindDragEvents() {
                const rows = document.querySelectorAll('.queue-row');
                const bodies = document.querySelectorAll('.queue-body');

                rows.forEach((row) => {
                    row.addEventListener('dragstart', () => {
                        if (row.getAttribute('data-locked') === '1') {
                            return;
                        }
                        row.classList.add('dragging');
                    });
                    row.addEventListener('dragend', () => {
                        row.classList.remove('dragging');
                        if (row.getAttribute('data-locked') === '1') {
                            return;
                        }
                        sendReorder();
                    });
                });

                bodies.forEach((body) => {
                    body.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        const dragging = document.querySelector('.queue-row.dragging');
                        if (!dragging) {
                            return;
                        }
                        if (dragging.getAttribute('data-locked') === '1') {
                            return;
                        }

                        const emptyRow = body.querySelector('.queue-empty');
                        if (emptyRow) {
                            emptyRow.remove();
                        }

                        const afterElement = getDragAfterElement(body, event.clientY);
                        if (!afterElement) {
                            body.appendChild(dragging);
                        } else {
                            body.insertBefore(dragging, afterElement);
                        }
                    });
                });
            }

        function loadKoDetail() {
            const noKo = ($('#input_no_ko').val() || '').trim();
            if (!noKo) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'No KO wajib diisi.' });
                return;
                }

                $.get(SEARCH_URL, { no_ko: noKo })
                    .done((response) => {
                        selectedKo = response.data || null;
                        if (!selectedKo) {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Data KO tidak ditemukan.' });
                            return;
                        }

                        $('#modal_no_ko').val(selectedKo.no_ko || '');
                        $('#modal_nama_pelanggan').val(selectedKo.nama_pelanggan || '-');
                        $('#modal_tanggal_antrian').val($('#filter_tanggal_antrian').val());

                        const studioSelect = $('#modal_studio_ids');
                        studioSelect.empty();
                        STUDIOS.forEach((studio) => {
                            studioSelect.append(`<option value="${studio.id}">${escapeHtml(studio.nama)}</option>`);
                        });

                    const items = selectedKo.items || [];
                    if (items.length === 0) {
                        $('#modal_ko_items').html('-');
                    } else {
                            const list = items.map((item) => {
                                return `<div>${escapeHtml(item.jenis_item)} - ${escapeHtml(item.nama_item)} (qty: ${item.qty})</div>`;
                            }).join('');
                        $('#modal_ko_items').html(list);
                    }

                    renderQueueGuard(selectedKo);
                    modal.show();
                })
                .fail((xhr) => {
                    selectedKo = null;
                    renderQueueGuard(null);
                    const message = xhr.responseJSON?.message || 'No KO tidak ditemukan.';
                    Swal.fire({ icon: 'error', title: 'Tidak Ditemukan', text: message });
                });
        }

        function submitAntrian() {
            if (!selectedKo) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih KO terlebih dahulu.' });
                return;
            }
            if (selectedKo.is_queue_blocked) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'KO ini sudah selesai difoto dan seluruh tracking paket selesai. Tidak bisa diantrikan lagi.' });
                return;
            }

            const studioIds = $('#modal_studio_ids').val() || [];
            const tanggalAntrian = $('#modal_tanggal_antrian').val();
            const today = todayJakarta();

            if (!tanggalAntrian) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tanggal antrian wajib diisi.' });
                return;
            }
            if (tanggalAntrian < today) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Tanggal antrian tidak boleh sebelum hari ini.' });
                return;
            }
            if (studioIds.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu studio.' });
                return;
            }

                $.ajax({
                    url: ENQUEUE_URL,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken() },
                    contentType: 'application/json',
                    data: JSON.stringify({
                        no_ko: selectedKo.no_ko,
                        tanggal_antrian: tanggalAntrian,
                        studio_ids: studioIds.map((id) => parseInt(id, 10)),
                    }),
                }).done((response) => {
                    modal.hide();
                    boardData = response.board || [];
                    renderBoard(boardData);

                    let text = response.message || 'Berhasil menambahkan antrian.';
                    if ((response.duplicates || []).length > 0) {
                        text += ' Studio duplikat: ' + response.duplicates.join(', ');
                    }
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: text });
                }).fail((xhr) => {
                    const errors = xhr.responseJSON?.errors || {};
                    const firstKey = Object.keys(errors)[0];
                    const firstError = firstKey ? errors[firstKey][0] : null;
                    const message = firstError || xhr.responseJSON?.message || 'Gagal menyimpan antrian.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                });
            }

            function hapusAntrian(itemId) {
                if (!itemId) {
                    return;
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus Antrian',
                    text: 'KO ini akan dihapus dari antrian studio. Lanjutkan?',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: `{{ url('/input-antrian') }}/${itemId}`,
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken() },
                        data: {
                            tanggal_antrian: $('#filter_tanggal_antrian').val(),
                        },
                    }).done((response) => {
                        boardData = response.board || [];
                        renderBoard(boardData);
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message || 'Antrian dihapus.' });
                    }).fail((xhr) => {
                        const message = xhr.responseJSON?.message || 'Gagal menghapus antrian.';
                        Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                    });
                });
            }

            $('#btn_show_ko').on('click', loadKoDetail);
            $('#input_no_ko').on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    loadKoDetail();
                }
            });
            $('#btn_submit_antrian').on('click', submitAntrian);
            $('#queue_board').on('click', '.btn-delete-antrian', function () {
                const itemId = parseInt($(this).data('item-id'), 10);
                hapusAntrian(itemId);
            });
            function goToTanggal(date) {
                if (!date) {
                    Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih tanggal antrian terlebih dahulu.' });
                    return;
                }
                const target = new URL(window.location.href);
                target.searchParams.set('tanggal_antrian', date);
                window.location.href = target.toString();
            }

            $('#btn_filter_tanggal').on('click', function () {
                goToTanggal($('#filter_tanggal_antrian').val());
            });
        $('#btn_filter_hari_ini').on('click', function () {
            const date = todayJakarta();
            $('#filter_tanggal_antrian').val(date);
            goToTanggal(date);
        });

        applyTanggalAntrianMin();
        renderQueueGuard(null);
        renderBoard(boardData);

        const urlParams = new URLSearchParams(window.location.search);
        const queryKo = (urlParams.get('no_ko') || '').trim();
        if (queryKo) {
            $('#input_no_ko').val(queryKo);
            setTimeout(() => {
                loadKoDetail();
            }, 200);
        }
    })();
</script>
@endpush
