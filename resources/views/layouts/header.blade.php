@php
  $loggedUser = auth()->user();
  $homeRouteName = ($loggedUser && ($loggedUser->hasPermission('dashboard.view') ?? false)) ? 'dashboard' : 'home';
  $cabangTersedia = collect();
  $activeCabangId = (int) session('active_cabang_id');

  if ($loggedUser) {
    $cabangTersedia = $loggedUser->cabang()
      ->where('cabang.status', true)
      ->orderBy('cabang.nama')
      ->get(['cabang.id', 'cabang.nama', 'cabang.kode', 'cabang.warna_header']);

    if ($cabangTersedia->isNotEmpty() && !$cabangTersedia->pluck('id')->map(fn ($id) => (int) $id)->contains($activeCabangId)) {
      $activeCabangId = (int) ($cabangTersedia->first()->id ?? 0);
    }
  }

  $activeCabang = $cabangTersedia->firstWhere('id', $activeCabangId);
  if (!$activeCabang && $activeCabangId > 0) {
    $activeCabang = \App\Models\Cabang::query()->find($activeCabangId);
  }

  $headerBgColor = $activeCabang?->warna_header ? trim($activeCabang->warna_header) : null;
@endphp

@if($headerBgColor)
  <style>
    .top-header .navbar {
      background-color: {{ $headerBgColor }} !important;
      background: {{ $headerBgColor }} !important;
      border-bottom: 2px solid rgba(0, 0, 0, 0.15) !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12) !important;
    }
    .top-header .navbar .btn-toggle-menu {
      background-color: rgba(255, 255, 255, 0.25) !important;
      border-color: rgba(255, 255, 255, 0.4) !important;
      color: #ffffff !important;
    }
    .top-header .navbar .btn-toggle-menu:hover {
      background-color: rgba(255, 255, 255, 0.4) !important;
    }
    .top-header .navbar .app-brand-title {
      color: #ffffff !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }
    .top-header .navbar .active-branch-pill {
      background-color: rgba(255, 255, 255, 0.95) !important;
      color: #1e293b !important;
      border: 1px solid rgba(255, 255, 255, 0.6) !important;
      font-weight: 700 !important;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15) !important;
      letter-spacing: .02em;
    }
    .top-header .navbar .top-right-menu .nav-item .nav-link {
      background-color: rgba(255, 255, 255, 0.25) !important;
      border-color: rgba(255, 255, 255, 0.4) !important;
      color: #ffffff !important;
    }
    .top-header .navbar .top-right-menu .nav-item .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.4) !important;
    }
    .top-header .navbar .branch-select-label {
      color: rgba(255, 255, 255, 0.95) !important;
      font-weight: 600;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }
    .top-header .navbar .branch-select-input {
      background-color: rgba(255, 255, 255, 0.95) !important;
      border-color: rgba(255, 255, 255, 0.6) !important;
      color: #1e293b !important;
      font-weight: 600;
    }
    .top-header .navbar .profile-toggle p,
    .top-header .navbar .profile-toggle small {
      color: #ffffff !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }
    .top-header .navbar .profile-toggle small {
      opacity: 0.9;
    }
  </style>
@endif

<header class="top-header">
  <nav class="navbar navbar-expand justify-content-between flex-nowrap">
    <div class="d-flex align-items-center gap-2">
      <div class="btn-toggle-menu">
        <i class="bi bi-list fs-4"></i>
      </div>

      <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0 fw-semibold app-brand-title d-none d-lg-block">Papyrus POS</h6>
        @if($activeCabang)
          <span class="badge active-branch-pill d-flex align-items-center gap-1 py-1 px-2">
            <i class="bi bi-geo-alt-fill text-danger"></i>
            <span>{{ $activeCabang->nama }}</span>
          </span>
        @endif
      </div>
    </div>

    <ul class="navbar-nav top-right-menu gap-2 align-items-center">
      @if($cabangTersedia->isNotEmpty())
        <li class="nav-item d-none d-md-block">
          <form method="POST" action="{{ route('active-cabang.update') }}" class="d-flex align-items-center gap-2">
            @csrf
            <label class="small branch-select-label mb-0">Cabang</label>
            <select
              name="active_cabang_id"
              class="form-select form-select-sm branch-select-input"
              style="min-width: 190px;"
              onchange="this.form.submit()"
            >
              @foreach($cabangTersedia as $cabangOption)
                <option value="{{ $cabangOption->id }}" @selected((int) $cabangOption->id === $activeCabangId)>
                  {{ $cabangOption->nama }}
                </option>
              @endforeach
            </select>
          </form>
        </li>
      @endif

      <li class="nav-item dark-mode">
        <a class="nav-link dark-mode-icon" href="javascript:;">
          <i class="bi bi-moon-stars"></i>
        </a>
      </li>

      <li class="nav-item dropdown dropdown-user-setting">
        <a class="dropdown-toggle dropdown-toggle-nocaret p-0 profile-toggle" href="#" data-bs-toggle="dropdown">
          <div class="d-flex align-items-center gap-2">
            <img
              src="{{ $loggedUser?->foto_profil ? asset('storage/' . $loggedUser->foto_profil) : asset('assets/ltr/assets/images/avatars/01.png') }}"
              class="rounded-circle border"
              width="42"
              height="42"
              alt="Avatar"
            >
            <div class="d-none d-md-block text-start">
              <p class="mb-0 fw-semibold">{{ $loggedUser?->name ?? '-' }}</p>
              <small class="text-secondary">{{ $loggedUser?->role?->nama ?? 'User' }}</small>
            </div>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end mt-2">
          <li>
            <a class="dropdown-item" href="{{ route($homeRouteName) }}">
              <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form action="{{ route('logout') }}" method="POST">
              @csrf
              <button class="dropdown-item text-danger" type="submit">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
              </button>
            </form>
          </li>
        </ul>
      </li>
    </ul>
  </nav>
</header>

