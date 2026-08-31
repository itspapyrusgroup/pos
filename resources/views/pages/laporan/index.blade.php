@extends('layouts.app')

@section('title', 'Laporan')

@push('styles')
    <style>
        .laporan-shell {
            --lap-bg-a: #f7fbff;
            --lap-bg-b: #eaf2ff;
            --lap-line: #d7e4fb;
            --lap-ink: #0e2954;
            --lap-muted: #5f7494;
            --lap-primary: #1168d4;
            --lap-primary-soft: rgba(17, 104, 212, 0.08);
            background: linear-gradient(150deg, var(--lap-bg-a), var(--lap-bg-b));
            border: 1px solid var(--lap-line);
            border-radius: 18px;
            padding: 1.1rem;
        }

        .laporan-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .laporan-title {
            margin: 0;
            color: var(--lap-ink);
            font-weight: 800;
        }

        .laporan-subtitle {
            margin: 0.2rem 0 0;
            color: var(--lap-muted);
            font-size: 0.93rem;
        }

        .laporan-search {
            position: relative;
            min-width: 280px;
            flex: 1 1 360px;
            max-width: 480px;
        }

        .laporan-search input {
            border-radius: 12px;
            border: 1px solid var(--lap-line);
            padding-left: 2.25rem;
            height: 42px;
        }

        .laporan-search i {
            position: absolute;
            left: 0.78rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7890b4;
        }

        .laporan-section {
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid var(--lap-line);
            border-radius: 14px;
            padding: 0.95rem;
            margin-bottom: 0.9rem;
        }

        .laporan-section-head {
            margin-bottom: 0.8rem;
        }

        .laporan-section-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--lap-ink);
        }

        .laporan-section-desc {
            margin: 0.2rem 0 0;
            color: var(--lap-muted);
            font-size: 0.9rem;
        }

        .laporan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 0.7rem;
        }

        .laporan-card {
            display: block;
            text-decoration: none;
            color: inherit;
            background: #fff;
            border: 1px solid var(--lap-line);
            border-radius: 12px;
            padding: 0.9rem;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            min-height: 132px;
        }

        .laporan-card:hover {
            transform: translateY(-2px);
            border-color: #bcd3f7;
            box-shadow: 0 8px 18px rgba(16, 63, 126, 0.1);
        }

        .laporan-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--lap-primary-soft);
            color: var(--lap-primary);
            margin-bottom: 0.6rem;
            font-size: 1rem;
        }

        .laporan-card-title {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 700;
            color: #173761;
        }

        .laporan-card-desc {
            margin: 0.35rem 0 0;
            color: var(--lap-muted);
            font-size: 0.86rem;
            line-height: 1.35;
        }

        .laporan-empty {
            border: 1px dashed var(--lap-line);
            border-radius: 12px;
            color: var(--lap-muted);
            text-align: center;
            background: rgba(255, 255, 255, 0.7);
            padding: 1.3rem 1rem;
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Laporan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item active" aria-current="page">Daftar Menu Laporan</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="laporan-shell">
        <div class="laporan-toolbar">
            <div>
                <h5 class="laporan-title">Menu Laporan</h5>
                <p class="laporan-subtitle">Total {{ (int) $totalReportMenus }} menu laporan tersedia sesuai akses Anda.</p>
            </div>
            <div class="laporan-search">
                <i class="bi bi-search"></i>
                <input type="text" id="report_menu_search" class="form-control" placeholder="Cari menu laporan...">
            </div>
        </div>

        @if(empty($reportSections))
            <div class="alert alert-warning mb-0">Tidak ada menu laporan yang bisa diakses untuk akun Anda.</div>
        @else
            @foreach($reportSections as $section)
                <section class="laporan-section report-section" data-section="{{ strtolower($section['title']) }}">
                    <div class="laporan-section-head">
                        <h6 class="laporan-section-title">{{ $section['title'] }}</h6>
                        <p class="laporan-section-desc">{{ $section['description'] }}</p>
                    </div>
                    <div class="laporan-grid">
                        @foreach($section['items'] as $item)
                            <a href="{{ $item['url'] }}" class="laporan-card report-card" data-search="{{ $item['search_blob'] }}">
                                <span class="laporan-icon"><i class="{{ $item['icon'] }}"></i></span>
                                <p class="laporan-card-title">{{ $item['title'] }}</p>
                                <p class="laporan-card-desc">{{ $item['desc'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif

        <div class="laporan-empty" id="laporan_empty_state">
            Tidak ada menu laporan yang cocok dengan kata kunci pencarian.
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('report_menu_search');
            const cards = Array.from(document.querySelectorAll('.report-card'));
            const sections = Array.from(document.querySelectorAll('.report-section'));
            const emptyState = document.getElementById('laporan_empty_state');

            if (!input || !cards.length) {
                return;
            }

            function applyFilter() {
                const keyword = String(input.value || '').trim().toLowerCase();
                let visibleCards = 0;

                cards.forEach((card) => {
                    const haystack = String(card.getAttribute('data-search') || '');
                    const visible = keyword === '' || haystack.includes(keyword);
                    card.style.display = visible ? '' : 'none';
                    if (visible) {
                        visibleCards += 1;
                    }
                });

                sections.forEach((section) => {
                    const hasVisible = !!section.querySelector('.report-card:not([style*="display: none"])');
                    section.style.display = hasVisible ? '' : 'none';
                });

                if (emptyState) {
                    emptyState.style.display = visibleCards > 0 ? 'none' : 'block';
                }
            }

            input.addEventListener('input', applyFilter);
        })();
    </script>
@endpush
