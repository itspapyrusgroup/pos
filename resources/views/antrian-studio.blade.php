@extends('layouts.app')

@section('title', 'Antrian Studio')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Studio</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item active">Daftar Antrian Studio</li>
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

    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <strong>Studio Aktif:</strong>
            @php
                $studioAktif = ($studios ?? collect())->firstWhere('id', (int) ($selectedStudioId ?? 0));
            @endphp
            <span id="studio_aktif_label">{{ $studioAktif?->nama ?? '-' }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="mb-0 small">Pilih Studio:</label>
            <select class="form-select form-select-sm" id="filter_studio_id" style="min-width: 240px;">
                @foreach(($studios ?? collect()) as $studio)
                    <option value="{{ $studio->id }}" @selected((int) $studio->id === (int) ($selectedStudioId ?? 0))>
                        {{ $studio->nama }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Tanggal Antrian</label>
                    <input type="date" id="filter_tanggal_antrian" class="form-control" value="{{ $tanggalAntrian }}">
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-outline-secondary w-100" id="btn_refresh_board">Muat Data</button>
                </div>
                {{-- <div class="col-md-12">
                    <a href="{{ route('antrian-studio.display.customer', ['tanggal_antrian' => $tanggalAntrian, 'cabang_id' => $cabangDefaultId]) }}"
                        target="_blank" class="btn btn-dark w-100">
                        Buka Display Customer (Fullscreen)
                    </a>
                </div>
                <div class="col-md-12">
                    <a href="{{ route('antrian-studio.audio-announcer', ['tanggal_antrian' => $tanggalAntrian, 'cabang_id' => $cabangDefaultId]) }}"
                        target="_blank" class="btn btn-outline-dark w-100">
                        Buka Audio Announcer (PC SPV)
                    </a>
                </div> --}}
                <div class="col-md-12">
                    <small class="text-muted">
                        Warna baris: <span class="badge bg-danger">Merah</span> belum selesai,
                        <span class="badge bg-success">Hijau</span> selesai.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3" id="studio_board"></div>

    <div class="modal fade" id="modalTrackingKo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tracking Order Dari Studio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal_tracking_ko_body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const URL_BOARD = @json(route('antrian-studio.board'));
            const URL_STREAM = @json(route('antrian-studio.stream'));
            const URL_CALL = @json(url('/antrian-studio'));
            const URL_TRACKING_DETAIL_BASE = @json(url('/antrian-studio'));
            const URL_TRACKING_TOGGLE_BASE = @json(url('/antrian-studio'));
            let boardData = @json($boardData ?? []);
            let lastBoardSignature = '';
            let currentTrackingQueueId = null;
            let eventSource = null;
            let fallbackPollingId = null;
            let isSseConnected = false;
            const modalTrackingKo = new bootstrap.Modal(document.getElementById('modalTrackingKo'));

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            function currentTanggal() {
                return $('#filter_tanggal_antrian').val();
            }

            function currentStudioId() {
                return parseInt($('#filter_studio_id').val() || '0', 10);
            }

            function rowClass(color) {
                if (color === 'red') {
                    return 'table-danger';
                }
                if (color === 'green') {
                    return 'table-success';
                }
                return '';
            }

            function renderBoard(data) {
                const board = $('#studio_board');
                board.empty();

                (data || []).forEach((studio) => {
                    const rows = (studio.items || []).map((item) => `
                        <tr class="${rowClass(item.color)}">
                            <td>${item.nomor_antrian}</td>
                            <td><button type="button" class="btn btn-link btn-sm p-0 btn-open-tracking" data-id="${item.id}">${escapeHtml(item.no_ko || '-')}</button></td>
                            <td><button type="button" class="btn btn-link btn-sm p-0 text-start btn-open-tracking" data-id="${item.id}">${escapeHtml(item.nama_pelanggan || '-')}</button></td>
                            <td>${escapeHtml(item.photographer_name || '-')}</td>
                            <td><span class="queue-timer" data-seconds="${parseInt(item.duration_seconds || 0, 10)}" data-running="${item.start_at && !item.end_at ? '1' : '0'}">${formatSeconds(item.duration_seconds || 0)}</span></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    ${!item.start_at ? `<button type="button" class="btn btn-outline-primary btn-call" data-id="${item.id}">Panggil</button>` : ''}
                                    ${!item.start_at ? `<button type="button" class="btn btn-outline-warning btn-start" data-id="${item.id}">Start</button>` : ''}
                                    ${item.start_at && !item.end_at ? `<button type="button" class="btn btn-outline-success btn-end" data-id="${item.id}">End</button>` : ''}
                                </div>
                            </td>
                        </tr>
                    `).join('');

                    const html = `
                        <div class="col-xl-6">
                            <div class="card h-100">
                                <div class="card-header bg-light"><strong>${escapeHtml(studio.studio_nama)}</strong></div>
                                <div class="card-body p-2">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>No KO</th>
                                                    <th>Nama</th>
                                                    <th>Fotografer</th>
                                                    <th>Durasi</th>
                                                    <th class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${rows || `<tr><td colspan="6" class="text-center text-muted py-3">Belum ada antrian</td></tr>`}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    board.append(html);
                });

                updateVisibleTimers();
            }

            function formatSeconds(totalSeconds) {
                const sec = Math.max(0, parseInt(totalSeconds || 0, 10));
                const h = String(Math.floor(sec / 3600)).padStart(2, '0');
                const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
                const s = String(sec % 60).padStart(2, '0');
                return `${h}:${m}:${s}`;
            }

            function updateVisibleTimers() {
                $('.queue-timer').each(function () {
                    const $el = $(this);
                    let sec = parseInt($el.attr('data-seconds') || '0', 10);
                    if ($el.attr('data-running') === '1') {
                        sec += 1;
                        $el.attr('data-seconds', String(sec));
                    }
                    $el.text(formatSeconds(sec));
                });
            }

            function boardSignature(data) {
                return JSON.stringify((data || []).map((studio) => ({
                    studio_id: studio.studio_id,
                    items: (studio.items || []).map((item) => ({
                        id: item.id,
                        nomor_antrian: item.nomor_antrian,
                        status: item.status,
                        color: item.color,
                        start_at: item.start_at,
                        end_at: item.end_at,
                        task_summary: item.task_summary,
                    })),
                })));
            }

            function setBoardData(nextBoard, forceRender = false) {
                const next = nextBoard || [];
                const signature = boardSignature(next);
                if (!forceRender && signature === lastBoardSignature) {
                    return false;
                }

                boardData = next;
                lastBoardSignature = signature;
                renderBoard(boardData);
                return true;
            }

            function postAction(url, successCb) {
                $.ajax({
                    url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken() },
                    data: {
                        tanggal_antrian: currentTanggal(),
                        studio_id: currentStudioId(),
                    },
                }).done((response) => {
                    setBoardData(response.board || [], true);
                    if (typeof successCb === 'function') {
                        successCb(response);
                    } else {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message || 'Berhasil.' });
                    }
                }).fail((xhr) => {
                    const message = xhr.responseJSON?.message || 'Aksi gagal diproses.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                });
            }

            function refreshBoard() {
                $.get(URL_BOARD, { tanggal_antrian: currentTanggal() })
                    .done((response) => {
                        boardData = response.board || [];
                        renderBoard(boardData);
                    })
                    .fail(() => {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memuat board antrian.' });
                    });
            }

            function renderTrackingModal(payload) {
                const queue = payload?.queue || {};
                const groups = payload?.groups || [];

                const groupsHtml = groups.map((group, groupIndex) => {
                    const rows = (group.paket_items || []).map((item, idx) => `
                        <tr>
                            <td>${idx + 1}</td>
                            <td>${escapeHtml(item.nama || '-')}</td>
                            <td>${escapeHtml(item.kategori || '-')}</td>
                            <td>${escapeHtml(item.tracking_nama || '-')}</td>
                            <td>${item.qty || 0}</td>
                            <td>${item.total_qty || 0}</td>
                            <td>
                                ${item.can_update ? `
                                    <div class="form-check d-inline-flex align-items-center gap-2">
                                        <input class="form-check-input tracking-check"
                                               type="checkbox"
                                               data-antrian-id="${queue.antrian_studio_id}"
                                               data-order-item-id="${group.order_item_id}"
                                               data-produk-id="${item.produk_id}"
                                               ${item.is_checked ? 'checked' : ''}>
                                        <label class="form-check-label">Selesai</label>
                                    </div>
                                ` : `
                                    <div class="form-check d-inline-flex align-items-center gap-2">
                                        <input class="form-check-input" type="checkbox" disabled ${item.is_checked ? 'checked' : ''}>
                                        <label class="form-check-label text-muted">Read only</label>
                                    </div>
                                `}
                                ${item.checked_by ? `<div class="small text-muted mt-1">Oleh ${escapeHtml(item.checked_by)}${item.checked_at ? ' | ' + escapeHtml(item.checked_at) : ''}</div>` : ''}
                            </td>
                        </tr>
                    `).join('');

                    return `
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Paket ${groupIndex + 1}: ${escapeHtml(group.paket_nama || '-')}</strong>
                                <span class="text-muted ms-2">Qty Pesanan: ${group.order_qty || 0}</span>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead>
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
                                        ${rows || '<tr><td colspan="7" class="text-center text-muted">Tidak ada item paket.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }).join('');

                $('#modal_tracking_ko_body').html(`
                    <div class="mb-2"><strong>No KO:</strong> ${escapeHtml(queue.no_ko || '-')}</div>
                    <div class="mb-2"><strong>Nama:</strong> ${escapeHtml(queue.nama_pelanggan || '-')}</div>
                    <div class="mb-3"><strong>Studio:</strong> ${escapeHtml(queue.studio_nama || '-')}</div>
                    ${groupsHtml || '<div class="text-muted">Tidak ada item order untuk KO ini.</div>'}
                `);
            }

            function loadTrackingDetail(queueId, silentError = false) {
                currentTrackingQueueId = queueId;
                $('#modal_tracking_ko_body').html('<div class="text-muted">Memuat detail tracking...</div>');

                $.get(`${URL_TRACKING_DETAIL_BASE}/${queueId}/tracking-detail`)
                    .done((response) => {
                        renderTrackingModal(response);
                    })
                    .fail((xhr) => {
                        const message = xhr.responseJSON?.message || 'Gagal memuat detail tracking.';
                        if (!silentError) {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                        }
                        $('#modal_tracking_ko_body').html('<div class="text-danger">Gagal memuat detail tracking.</div>');
                    });
            }

            function refreshByFilter(showError = true) {
                $.get(URL_BOARD, { tanggal_antrian: currentTanggal(), studio_id: currentStudioId() })
                    .done((response) => {
                        setBoardData(response.board || [], false);
                    })
                    .fail(() => {
                        if (showError) {
                            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memuat board antrian.' });
                        }
                    });
            }

            function stopFallbackPolling() {
                if (fallbackPollingId) {
                    clearInterval(fallbackPollingId);
                    fallbackPollingId = null;
                }
            }

            function startFallbackPolling() {
                if (fallbackPollingId) {
                    return;
                }
                fallbackPollingId = setInterval(() => {
                    if (document.hidden) {
                        return;
                    }
                    refreshByFilter(false);
                }, 5000);
            }

            function disconnectStream() {
                if (eventSource) {
                    eventSource.close();
                    eventSource = null;
                }
                isSseConnected = false;
            }

            function connectStream() {
                disconnectStream();

                if (!window.EventSource) {
                    startFallbackPolling();
                    return;
                }

                const query = new URLSearchParams({
                    tanggal_antrian: currentTanggal() || '',
                    studio_id: String(currentStudioId() || 0),
                });
                eventSource = new EventSource(`${URL_STREAM}?${query.toString()}`);

                eventSource.addEventListener('board', (event) => {
                    isSseConnected = true;
                    stopFallbackPolling();
                    try {
                        const payload = JSON.parse(event.data || '{}');
                        setBoardData(payload.board || [], false);
                    } catch (e) {
                        // noop
                    }
                });

                eventSource.onerror = () => {
                    if (!isSseConnected) {
                        startFallbackPolling();
                    }
                    // Biarkan EventSource auto-reconnect.
                };
            }

            $('#btn_refresh_board').on('click', function () {
                refreshByFilter(true);
                connectStream();
            });
            $('#filter_studio_id, #filter_tanggal_antrian').on('change', function () {
                const studioText = $('#filter_studio_id option:selected').text() || '-';
                $('#studio_aktif_label').text(studioText);
                refreshByFilter(false);
                connectStream();
            });

            // Stopwatch stabil: update tampilan timer tiap detik tanpa re-render tabel.
            setInterval(() => {
                if (document.hidden) {
                    return;
                }
                updateVisibleTimers();
            }, 1000);

            $('#studio_board').on('click', '.btn-call', function () {
                const id = $(this).data('id');
                postAction(`${URL_CALL}/${id}/call`, (response) => {
                    Swal.fire({ icon: 'success', title: 'Dipanggil', text: response.message || 'Customer dipanggil.' });
                });
            });

            $('#studio_board').on('click', '.btn-start', function () {
                const id = $(this).data('id');
                postAction(`${URL_CALL}/${id}/start`);
            });

            $('#studio_board').on('click', '.btn-end', function () {
                const id = $(this).data('id');
                postAction(`${URL_CALL}/${id}/end`);
            });

            $('#studio_board').on('click', '.btn-open-tracking', function () {
                const id = $(this).data('id');
                loadTrackingDetail(id, false);
                modalTrackingKo.show();
            });

            $('#modal_tracking_ko_body').on('change', '.tracking-check', function () {
                const checkbox = $(this);
                const antrianId = parseInt(checkbox.data('antrian-id') || '0', 10);
                const orderItemId = parseInt(checkbox.data('order-item-id') || '0', 10);
                const produkId = parseInt(checkbox.data('produk-id') || '0', 10);
                const isChecked = checkbox.is(':checked') ? 1 : 0;

                $.ajax({
                    url: `${URL_TRACKING_TOGGLE_BASE}/${antrianId}/tracking-item-check`,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken() },
                    data: {
                        pesanan_penjualan_item_id: orderItemId,
                        produk_id: produkId,
                        is_checked: isChecked,
                    },
                }).done(() => {
                    loadTrackingDetail(antrianId, true);
                }).fail((xhr) => {
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    const message = xhr.responseJSON?.message || 'Gagal update checklist.';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: message });
                });
            });

            setBoardData(boardData, true);
            connectStream();

            window.addEventListener('beforeunload', () => {
                disconnectStream();
                stopFallbackPolling();
            });
        })();
    </script>
@endpush