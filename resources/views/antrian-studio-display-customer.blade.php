<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Display Antrian Customer</title>
    <link href="{{ asset('assets/ltr/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/ltr/assets/css/main.css') }}" rel="stylesheet">
    <style>
        :root {
            --rx-bg-1: #071529;
            --rx-bg-2: #0c223d;
            --rx-card: rgba(14, 33, 61, 0.88);
            --rx-card-soft: rgba(25, 47, 80, 0.94);
            --rx-line: rgba(176, 203, 238, 0.22);
            --rx-text: #e9f2ff;
            --rx-muted: #9fb6d7;
            --rx-brand: #59a6ff;
            --rx-ok: #57d18d;
            --rx-warn: #ffd369;
            --rx-danger: #ff6b7f;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: "Noto Sans", sans-serif;
            color: var(--rx-text);
            background:
                radial-gradient(circle at 15% 12%, rgba(89, 166, 255, 0.23), transparent 34%),
                radial-gradient(circle at 86% 14%, rgba(87, 209, 141, 0.14), transparent 35%),
                linear-gradient(140deg, var(--rx-bg-1) 0%, var(--rx-bg-2) 65%, #0a1d34 100%);
        }

        .screen {
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-rows: auto 1fr auto;
            gap: clamp(0.8rem, 1.4vw, 1.35rem);
            padding: clamp(0.8rem, 1.2vw, 1.2rem);
        }

        .topbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            align-items: center;
            background: var(--rx-card);
            border: 1px solid var(--rx-line);
            border-radius: 14px;
            padding: 0.85rem 1.1rem;
            backdrop-filter: blur(6px);
        }

        .topbar-left {
            min-width: 0;
        }

        .topbar-center {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
        }

        .brand-logo {
            height: clamp(34px, 4.5vw, 56px);
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.28));
        }

        .topbar-right {
            justify-self: end;
        }

        .branch {
            font-size: 1.42rem;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .meta {
            color: var(--rx-muted);
            font-size: 0.95rem;
        }

        .clock {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--rx-brand);
            letter-spacing: 1px;
        }

        .main {
            display: grid;
            grid-template-rows: auto 1fr;
            gap: 1rem;
            min-height: 0;
        }

        .hero {
            background: linear-gradient(135deg, rgba(10, 31, 57, 0.96), rgba(17, 42, 73, 0.95));
            border: 1px solid var(--rx-line);
            border-radius: 16px;
            padding: clamp(1rem, 2.2vw, 1.8rem);
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .hero-label {
            color: var(--rx-warn);
            font-size: clamp(1rem, 1.6vw, 1.3rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.6px;
            margin-bottom: 0.75rem;
        }

        .hero-ko {
            font-size: clamp(2.5rem, 6.6vw, 6.8rem);
            font-weight: 800;
            line-height: 1;
            margin: 0 0 0.55rem;
            color: #fff;
        }

        .hero-name {
            font-size: clamp(1.3rem, 3.1vw, 2.8rem);
            font-weight: 700;
            margin: 0 0 0.3rem;
        }

        .hero-studio {
            font-size: clamp(1.8rem, 4.7vw, 3.7rem);
            color: var(--rx-brand);
            font-weight: 800;
            letter-spacing: 0.4px;
        }

        .empty {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--rx-muted);
        }

        .studio-panel {
            min-height: 0;
            background: var(--rx-card);
            border: 1px solid var(--rx-line);
            border-radius: 16px;
            padding: clamp(0.9rem, 1.8vw, 1.4rem);
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .studio-title {
            font-size: clamp(1rem, 1.6vw, 1.3rem);
            font-weight: 700;
            color: var(--rx-ok);
            text-transform: uppercase;
            letter-spacing: 1.1px;
            text-align: center;
        }

        .studio-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(clamp(220px, 14vw, 290px), 1fr));
            gap: clamp(0.65rem, 1vw, 1rem);
            min-height: 0;
            overflow: auto;
            align-content: start;
        }

        .studio-item {
            border: 1px solid var(--rx-line);
            background: var(--rx-card-soft);
            border-radius: 12px;
            padding: clamp(0.8rem, 1.2vw, 1.1rem);
            min-height: clamp(145px, 20vh, 230px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            gap: clamp(0.2rem, 0.45vw, 0.45rem);
        }

        .studio-item.active {
            border-color: rgba(87, 209, 141, 0.75);
            box-shadow: inset 0 0 0 1px rgba(87, 209, 141, 0.25);
        }

        .studio-name {
            font-size: clamp(1.15rem, 2vw, 2rem);
            font-weight: 800;
            color: #fff;
            margin: 0;
        }

        .studio-state {
            font-size: clamp(0.95rem, 1.35vw, 1.3rem);
            color: var(--rx-muted);
            margin: 0;
        }

        .studio-state.active {
            color: var(--rx-ok);
            font-weight: 700;
        }

        .studio-ko {
            font-size: clamp(1.2rem, 2.2vw, 2.4rem);
            font-weight: 800;
            line-height: 1.05;
        }

        .studio-customer {
            color: var(--rx-text);
            font-size: clamp(1rem, 1.6vw, 1.7rem);
            font-weight: 600;
            line-height: 1.22;
        }

        .studio-time {
            color: var(--rx-muted);
            font-size: clamp(0.88rem, 1.25vw, 1.18rem);
            margin: 0;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--rx-line);
            background: var(--rx-card);
            border-radius: 12px;
            padding: 0.65rem 1rem;
            color: var(--rx-muted);
            font-size: 0.9rem;
        }

        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            background: var(--rx-danger);
        }

        .status-dot.online {
            background: var(--rx-ok);
        }

        .flash {
            animation: highlight 0.9s ease;
        }

        @keyframes highlight {
            0% {
                box-shadow: 0 0 0 0 rgba(89, 166, 255, 0.55);
            }

            100% {
                box-shadow: 0 0 0 24px rgba(89, 166, 255, 0);
            }
        }

        @media (min-width: 1800px) {
            .studio-list {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        @media (max-width: 980px) {
            .studio-list {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 860px) {
            .studio-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .studio-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="screen">
        <div class="topbar">
            <div class="topbar-left">
                <div class="branch" id="branch_name">{{ $activeCabang?->nama ?? '-' }}</div>
                <div class="meta">
                    Tanggal antrian:
                    {{ \Carbon\Carbon::parse($tanggalAntrian)->locale('id')->isoFormat('D MMMM YYYY') }}
                </div>
            </div>
            <div class="topbar-center">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Papyrus" class="brand-logo">
            </div>
            <div class="topbar-right">
                <div class="clock" id="clock_now">--:--:--</div>
            </div>
        </div>

        <div class="main">
            <section class="hero" id="hero_call"></section>
            <section class="studio-panel">
                <div class="studio-title">Status Semua Studio</div>
                <div class="studio-list" id="studio_list"></div>
            </section>
        </div>

        <div class="footer">
            <div>
                <span class="status-dot" id="stream_dot"></span>
                <span id="stream_status">Offline</span>
            </div>
            <div>Total aktif: <strong id="total_active">0</strong></div>
        </div>
    </div>

    <script>
        (function () {
            const URL_BOARD = @json(route('antrian-studio.display.customer.board'));
            const URL_STREAM = @json(route('antrian-studio.display.customer.stream'));
            const cabangId = @json($displayCabangId ?? 0);
            const tanggalAntrian = @json($tanggalAntrian);

            let boardData = @json($customerBoard ?? []);
            let eventSource = null;
            let fallbackPollId = null;
            let lastSignature = '';
            let offlineTimer = null;

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            function hasMeaningfulValue(value) {
                const text = String(value ?? '').trim();
                if (!text) {
                    return false;
                }
                return text !== '-' && text !== '--';
            }

            function formatClock() {
                const now = new Date();
                const hh = String(now.getHours()).padStart(2, '0');
                const mm = String(now.getMinutes()).padStart(2, '0');
                const ss = String(now.getSeconds()).padStart(2, '0');
                return `${hh}:${mm}:${ss}`;
            }

            function tickClock() {
                const el = document.getElementById('clock_now');
                if (el) {
                    el.textContent = formatClock();
                }
            }

            function setStreamStatus(mode) {
                const dot = document.getElementById('stream_dot');
                const text = document.getElementById('stream_status');
                if (!dot || !text) {
                    return;
                }

                if (mode === 'online') {
                    dot.classList.add('online');
                    text.textContent = 'Realtime terhubung';
                    return;
                }
                if (mode === 'polling') {
                    dot.classList.add('online');
                    text.textContent = 'Sinkron via polling';
                    return;
                }
                if (mode === 'reconnecting') {
                    dot.classList.add('online');
                    text.textContent = 'Menyambung ulang realtime...';
                    return;
                }

                dot.classList.remove('online');
                text.textContent = 'Koneksi terputus';
            }

            function boardSignature(data) {
                return JSON.stringify({
                    active_call: data?.active_call?.id || 0,
                    called_at: data?.active_call?.called_at || '',
                    studio_rows: (data?.studio_statuses || []).map((row) => [
                        row.studio_id || 0,
                        row.is_active ? 1 : 0,
                        row.no_ko || '',
                        row.start_time || '',
                    ]),
                    total_active: data?.total_active || 0,
                });
            }

            function renderHero(activeCall) {
                const el = document.getElementById('hero_call');
                if (!el) {
                    return;
                }

                if (!activeCall) {
                    el.innerHTML = '<div class="empty">Belum ada customer yang dipanggil.</div>';
                    return;
                }

                el.innerHTML = `
                    <div class="hero-label">Sedang Dipanggil</div>
                    <h1 class="hero-ko">KO ${escapeHtml(activeCall.no_ko || '-')}</h1>
                    <p class="hero-name">${escapeHtml(activeCall.nama_pelanggan || '-')}</p>
                    <div class="hero-studio">Silahkan ke ${escapeHtml(activeCall.studio_nama || '-')}</div>
                `;
            }

            function renderStudios(studios) {
                const list = document.getElementById('studio_list');
                if (!list) {
                    return;
                }

                const rows = (studios || []);
                if (!rows.length) {
                    list.innerHTML = '<div class="studio-item"><div class="studio-customer">Belum ada data studio.</div></div>';
                    return;
                }

                list.innerHTML = rows.map((item) => `
                    <div class="studio-item ${item.is_active ? 'active' : ''}">
                        <div class="studio-name">${escapeHtml(item.studio_nama || '-')}</div>
                        <div class="studio-state ${item.is_active ? 'active' : ''}">${escapeHtml(item.status_label || '-')}</div>
                        ${hasMeaningfulValue(item.no_ko) ? `<div class="studio-ko">KO ${escapeHtml(item.no_ko)}</div>` : ''}
                        ${hasMeaningfulValue(item.nama_pelanggan) ? `<div class="studio-customer">${escapeHtml(item.nama_pelanggan)}</div>` : ''}
                        ${hasMeaningfulValue(item.start_time) ? `<div class="studio-time">Mulai: ${escapeHtml(item.start_time)}</div>` : ''}
                    </div>
                `).join('');
            }

            function renderBoard(nextData, animate = false) {
                boardData = nextData || {};
                renderHero(boardData.active_call || null);
                renderStudios(boardData.studio_statuses || []);

                const total = document.getElementById('total_active');
                if (total) {
                    total.textContent = String(parseInt(boardData.total_active || 0, 10));
                }

                if (animate) {
                    const hero = document.getElementById('hero_call');
                    hero?.classList.remove('flash');
                    // force reflow so animation can replay
                    void hero?.offsetWidth;
                    hero?.classList.add('flash');
                }
            }

            function refreshBoard() {
                const params = new URLSearchParams();
                if (tanggalAntrian) {
                    params.set('tanggal_antrian', String(tanggalAntrian));
                }
                if (parseInt(cabangId || 0, 10) > 0) {
                    params.set('cabang_id', String(cabangId));
                }

                fetch(`${URL_BOARD}?${params.toString()}`)
                    .then((r) => r.json())
                    .then((response) => {
                        const nextBoard = response?.board || {};
                        const signature = boardSignature(nextBoard);
                        if (signature !== lastSignature) {
                            renderBoard(nextBoard, true);
                            lastSignature = signature;
                        }
                        if (fallbackPollId) {
                            setStreamStatus('polling');
                        }
                    })
                    .catch(() => { });
            }

            function startPollingFallback() {
                if (fallbackPollId) {
                    return;
                }
                fallbackPollId = setInterval(refreshBoard, 4000);
                setStreamStatus('polling');
            }

            function stopPollingFallback() {
                if (fallbackPollId) {
                    clearInterval(fallbackPollId);
                    fallbackPollId = null;
                }
            }

            function connectStream() {
                if (!window.EventSource) {
                    startPollingFallback();
                    return;
                }

                if (eventSource) {
                    eventSource.close();
                }

                const query = new URLSearchParams();
                if (tanggalAntrian) {
                    query.set('tanggal_antrian', String(tanggalAntrian));
                }
                if (parseInt(cabangId || 0, 10) > 0) {
                    query.set('cabang_id', String(cabangId));
                }

                eventSource = new EventSource(`${URL_STREAM}?${query.toString()}`);

                eventSource.addEventListener('open', function () {
                    if (offlineTimer) {
                        clearTimeout(offlineTimer);
                        offlineTimer = null;
                    }
                    setStreamStatus('online');
                    stopPollingFallback();
                });

                eventSource.addEventListener('customer-board', function (event) {
                    try {
                        const payload = JSON.parse(event.data || '{}');
                        const nextBoard = payload?.board || {};
                        const signature = boardSignature(nextBoard);
                        if (signature !== lastSignature) {
                            renderBoard(nextBoard, true);
                            lastSignature = signature;
                        }
                    } catch (error) {
                        // ignore invalid payload
                    }
                });

                eventSource.addEventListener('error', function () {
                    setStreamStatus('reconnecting');
                    startPollingFallback();
                    if (offlineTimer) {
                        clearTimeout(offlineTimer);
                    }
                    offlineTimer = setTimeout(function () {
                        if (!fallbackPollId) {
                            setStreamStatus('offline');
                        }
                    }, 12000);
                });
            }

            renderBoard(boardData, false);
            lastSignature = boardSignature(boardData);
            tickClock();
            setInterval(tickClock, 1000);
            setStreamStatus('reconnecting');
            connectStream();
        })();
    </script>
</body>

</html>
