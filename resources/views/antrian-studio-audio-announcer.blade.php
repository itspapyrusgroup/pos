<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audio Announcer Antrian Studio</title>
    <link href="{{ asset('assets/ltr/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/ltr/assets/css/main.css') }}" rel="stylesheet">
    <style>
        :root {
            --rx-bg-1: #071529;
            --rx-bg-2: #0e2646;
            --rx-panel: rgba(15, 37, 67, 0.9);
            --rx-soft: rgba(21, 49, 85, 0.95);
            --rx-line: rgba(176, 203, 238, 0.24);
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
            min-height: 100vh;
            font-family: "Noto Sans", sans-serif;
            color: var(--rx-text);
            background:
                radial-gradient(circle at 12% 12%, rgba(89, 166, 255, 0.22), transparent 30%),
                radial-gradient(circle at 88% 14%, rgba(87, 209, 141, 0.14), transparent 35%),
                linear-gradient(145deg, var(--rx-bg-1), var(--rx-bg-2));
            min-height: 100vh;
            padding: 1rem;
        }

        .wrap {
            max-width: 940px;
            margin: 0 auto;
            display: grid;
            gap: 0.9rem;
            color: var(--rx-text);
        }

        .card {
            background: var(--rx-panel);
            border: 1px solid var(--rx-line);
            border-radius: 12px;
            padding: 1rem;
            backdrop-filter: blur(5px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.24);
            color: var(--rx-text);
        }

        .card h1,
        .card strong,
        .card span,
        .card div {
            color: inherit;
        }

        .title {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 0 0.4rem;
            color: #fff;
        }

        .muted {
            color: var(--rx-muted);
        }

        .muted strong {
            color: #dfeeff;
        }

        .btn {
            appearance: none;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            background: linear-gradient(135deg, #3d8fff, #2d7ce8);
            color: #fff;
            font-weight: 600;
            padding: 0.58rem 0.9rem;
            cursor: pointer;
            transition: all .2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn.btn-soft {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--rx-danger);
        }

        .dot.online {
            background: var(--rx-ok);
        }

        .dot.warn {
            background: var(--rx-warn);
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .control-card {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
        }

        .status-chip {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            background: var(--rx-soft);
            border: 1px solid var(--rx-line);
            border-radius: 10px;
            padding: 0.62rem 0.7rem;
            min-height: 44px;
        }

        .status-chip span:last-child {
            color: #d7e8ff;
            font-size: 0.92rem;
            line-height: 1.2;
        }

        .action-row {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            justify-content: flex-end;
        }

        .queue-head {
            margin-bottom: 0.7rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px dashed var(--rx-line);
        }

        .list {
            display: grid;
            gap: 0.55rem;
            max-height: 360px;
            overflow: auto;
        }

        .item {
            border: 1px solid var(--rx-line);
            border-radius: 8px;
            padding: 0.6rem 0.72rem;
            background: var(--rx-soft);
            transition: border-color .2s ease, transform .2s ease;
        }

        .item:hover {
            border-color: rgba(89, 166, 255, 0.55);
            transform: translateY(-1px);
        }

        .small {
            font-size: 0.9rem;
        }

        @media (max-width: 860px) {
            .control-card {
                grid-template-columns: 1fr;
            }

            .status-grid {
                grid-template-columns: 1fr;
            }

            .action-row {
                justify-content: stretch;
            }

            .action-row .btn {
                flex: 1 1 auto;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <h1 class="title">Audio Panggilan Studio</h1>
            <div class="muted">Cabang: <strong>{{ $activeCabang?->nama ?? '-' }}</strong></div>
            <div class="muted">Tanggal antrian: <strong>{{ $tanggalAntrian }}</strong></div>
        </div>

        <div class="card control-card">
            <div class="status-grid">
                <div class="status-chip">
                    <span class="status">
                        <span class="dot" id="dot_audio"></span>
                    </span>
                    <span id="status_audio">Audio belum aktif</span>
                </div>
                <div class="status-chip">
                    <span class="status">
                        <span class="dot" id="dot_stream"></span>
                    </span>
                    <span id="status_stream">Menyambung ulang realtime...</span>
                </div>
            </div>
            <div class="action-row">
                <button class="btn btn-soft" id="btn_test_audio" type="button">Tes Suara</button>
                <button class="btn" id="btn_enable_audio" type="button">Aktifkan Audio</button>
            </div>
        </div>

        <div class="card">
            <div class="row queue-head">
                <strong>Antrian Suara</strong>
                <span class="small muted">Pending: <span id="pending_count">0</span></span>
            </div>
            <div class="list" id="voice_queue"></div>
        </div>
    </div>

    <script>
        (function () {
            const URL_BOARD = @json(route('antrian-studio.audio-announcer.board'));
            const URL_STREAM = @json(route('antrian-studio.audio-announcer.stream'));
            const cabangId = @json($displayCabangId ?? 0);
            const tanggalAntrian = @json($tanggalAntrian);
            const storageKey = `studio_announcer_seen_${String(cabangId || 0)}_${String(tanggalAntrian || '')}`;

            let eventSource = null;
            let fallbackPollId = null;
            let queue = [];
            let speaking = false;
            let audioEnabled = false;
            let seenMap = {};
            let offlineTimer = null;

            function loadSeen() {
                try {
                    const raw = localStorage.getItem(storageKey);
                    seenMap = raw ? JSON.parse(raw) : {};
                } catch (error) {
                    seenMap = {};
                }
            }

            function saveSeen() {
                const compact = Object.entries(seenMap).slice(-200);
                const obj = {};
                compact.forEach(([k, v]) => { obj[k] = v; });
                localStorage.setItem(storageKey, JSON.stringify(obj));
            }

            function setAudioStatus(kind, text) {
                const dot = document.getElementById('dot_audio');
                const label = document.getElementById('status_audio');
                if (!dot || !label) {
                    return;
                }
                dot.classList.remove('online', 'warn');
                if (kind === 'online') {
                    dot.classList.add('online');
                } else if (kind === 'warn') {
                    dot.classList.add('warn');
                }
                label.textContent = text;
            }

            function setStreamStatus(mode) {
                const dot = document.getElementById('dot_stream');
                const label = document.getElementById('status_stream');
                if (!dot || !label) {
                    return;
                }

                if (mode === 'online') {
                    dot.classList.add('online');
                    label.textContent = 'Realtime terhubung';
                    return;
                }
                if (mode === 'polling') {
                    dot.classList.add('online');
                    label.textContent = 'Sinkron via polling';
                    return;
                }
                if (mode === 'reconnecting') {
                    dot.classList.add('online');
                    label.textContent = 'Menyambung ulang realtime...';
                    return;
                }

                dot.classList.remove('online');
                label.textContent = 'Koneksi terputus';
            }

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            function digitToWord(ch) {
                const map = {
                    '0': 'nol',
                    '1': 'satu',
                    '2': 'dua',
                    '3': 'tiga',
                    '4': 'empat',
                    '5': 'lima',
                    '6': 'enam',
                    '7': 'tujuh',
                    '8': 'delapan',
                    '9': 'sembilan',
                };
                return map[ch] || ch;
            }

            function spellTokenByToken(value) {
                const raw = String(value ?? '').trim();
                if (!raw || raw === '-') {
                    return '-';
                }

                const parts = [];
                for (const ch of raw) {
                    if (/\d/.test(ch)) {
                        parts.push(digitToWord(ch));
                        continue;
                    }
                    if (/[a-z]/i.test(ch)) {
                        parts.push(ch.toUpperCase());
                        continue;
                    }
                    if (ch === '-') {
                        parts.push('strip');
                        continue;
                    }
                    if (ch === '/') {
                        parts.push('garis miring');
                        continue;
                    }
                    if (ch === '.') {
                        parts.push('titik');
                    }
                }

                return parts.join(' ').replace(/\s+/g, ' ').trim() || '-';
            }

            function announceText(item) {
                const rawKo = String(item.no_ko ?? '').trim();
                const koNumberPart = rawKo.replace(/^KO[\s\-:]*/i, '') || rawKo;
                const spelledKo = spellTokenByToken(koNumberPart);
                const spokenKo = spelledKo === '-' ? 'tidak diketahui' : spelledKo;
                return `Mohon perhatian. Nomor Order ${spokenKo}, atas nama ${item.nama_pelanggan || '-'}, silakan menuju ${item.studio_nama || 'studio'}.`;
            }

            function eventKey(item) {
                if (!item || !item.called_at) {
                    return '';
                }
                return `${String(item.id || '')}:${String(item.called_at || '')}`;
            }

            function trySpeak(text, onEnd, onError) {
                if (!('speechSynthesis' in window)) {
                    if (typeof onError === 'function') {
                        onError(new Error('speechSynthesis not supported'));
                    }
                    return;
                }

                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.95;
                utterance.pitch = 1;
                utterance.volume = 1;

                const voices = window.speechSynthesis.getVoices();
                const preferredFemaleIdVoice = voices.find((v) => {
                    const lang = (v.lang || '').toLowerCase();
                    const name = (v.name || '').toLowerCase();
                    return lang.startsWith('id') && (name.includes('gadis') || name.includes('female'));
                });
                const fallbackIdVoice = voices.find((v) => (v.lang || '').toLowerCase().startsWith('id'));
                const selectedVoice = preferredFemaleIdVoice || fallbackIdVoice;
                if (selectedVoice) {
                    utterance.voice = selectedVoice;
                }

                utterance.onend = function () {
                    if (typeof onEnd === 'function') {
                        onEnd();
                    }
                };
                utterance.onerror = function (e) {
                    if (typeof onError === 'function') {
                        onError(e);
                    }
                };

                window.speechSynthesis.speak(utterance);
            }

            function renderQueue() {
                const list = document.getElementById('voice_queue');
                const count = document.getElementById('pending_count');
                if (count) {
                    count.textContent = String(queue.length);
                }
                if (!list) {
                    return;
                }

                if (!queue.length) {
                    list.innerHTML = '<div class="item muted">Tidak ada antrian suara.</div>';
                    return;
                }

                list.innerHTML = queue.slice(0, 15).map((item) => `
                    <div class="item">
                        <strong>KO ${escapeHtml(item.no_ko || '-')}</strong>
                        <div class="small">${escapeHtml(item.nama_pelanggan || '-')} - ${escapeHtml(item.studio_nama || '-')}</div>
                        <div class="small muted">${escapeHtml(item.called_time || '')}</div>
                    </div>
                `).join('');
            }

            function speakNext() {
                if (!audioEnabled || speaking || !queue.length || !('speechSynthesis' in window)) {
                    return;
                }

                const item = queue.shift();
                renderQueue();
                speaking = true;

                setAudioStatus('online', `Memutar: KO ${item.no_ko || '-'}`);
                trySpeak(
                    announceText(item),
                    function () {
                        speaking = false;
                        setAudioStatus('online', 'Audio aktif');
                        setTimeout(speakNext, 250);
                    },
                    function () {
                        speaking = false;
                        setAudioStatus('warn', 'Audio error, cek output speaker/browser');
                        setTimeout(speakNext, 500);
                    }
                );
            }

            function enqueueFromBoard(board) {
                const recent = Array.isArray(board?.recent) ? board.recent : [];
                const sorted = recent
                    .filter((item) => !!item?.called_at)
                    .slice()
                    .sort((a, b) => {
                        const aTime = new Date(a.called_at || 0).getTime();
                        const bTime = new Date(b.called_at || 0).getTime();
                        if (aTime === bTime) {
                            return (a.id || 0) - (b.id || 0);
                        }
                        return aTime - bTime;
                    });

                sorted.forEach((item) => {
                    const key = eventKey(item);
                    if (!key || seenMap[key]) {
                        return;
                    }
                    seenMap[key] = true;
                    queue.push(item);
                });

                saveSeen();
                renderQueue();
                speakNext();
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
                        enqueueFromBoard(response?.board || {});
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
                        enqueueFromBoard(payload?.board || {});
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

            document.getElementById('btn_enable_audio')?.addEventListener('click', function () {
                audioEnabled = true;
                if (!('speechSynthesis' in window)) {
                    setAudioStatus('warn', 'Browser tidak mendukung speech synthesis');
                    return;
                }

                trySpeak(
                    'Audio Antrian aktif.',
                    function () {
                        setAudioStatus('online', 'Audio aktif');
                        speakNext();
                    },
                    function () {
                        setAudioStatus('warn', 'Gagal memutar suara. Cek output audio browser.');
                    }
                );
            });

            document.getElementById('btn_test_audio')?.addEventListener('click', function () {
                trySpeak(
                    'Tes suara antrian studio',
                    function () {
                        setAudioStatus('online', 'Tes suara berhasil');
                    },
                    function () {
                        setAudioStatus('warn', 'Tes suara gagal. Cek output speaker/browser.');
                    }
                );
            });

            loadSeen();
            renderQueue();
            const initialBoard = @json($customerBoard ?? []);
            (Array.isArray(initialBoard?.recent) ? initialBoard.recent : []).forEach((item) => {
                const key = eventKey(item);
                if (key) {
                    seenMap[key] = true;
                }
            });
            saveSeen();
            setStreamStatus('reconnecting');
            connectStream();
        })();
    </script>
</body>

</html>
