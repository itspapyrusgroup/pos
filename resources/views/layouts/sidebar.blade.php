@php
  use App\Models\ShiftKasir;

  $user = auth()->user();
  $can = static fn(string $permission): bool => $user?->hasPermission($permission) ?? false;
  $canAny = static function (array $permissions) use ($can): bool {
    foreach ($permissions as $permission) {
      if ($can($permission)) {
        return true;
      }
    }

    return false;
  };
  $isLocalBranch = (string) config('sync.app_mode') === 'local_branch';
  $blockedPatterns = $isLocalBranch ? (array) config('sync.local_blocked_route_names', []) : [];
  $isRouteBlocked = static function (string $routeName) use ($blockedPatterns): bool {
    foreach ($blockedPatterns as $pattern) {
      $pattern = trim((string) $pattern);
      if ($pattern === '') {
        continue;
      }

      if (str_ends_with($pattern, '*')) {
        $prefix = substr($pattern, 0, -1);
        if ($prefix !== '' && str_starts_with($routeName, $prefix)) {
          return true;
        }
        continue;
      }

      if ($routeName === $pattern) {
        return true;
      }
    }

    return false;
  };
  $canRoute = static fn(string $permission, string $routeName): bool => $can($permission) && !$isRouteBlocked($routeName);
  $canAnyRoute = static function (array $items) use ($canRoute): bool {
    foreach ($items as [$permission, $routeName]) {
      if ($canRoute($permission, $routeName)) {
        return true;
      }
    }

    return false;
  };

  $showDashboard = $can('dashboard.view');

  $showPos = $canAny([
    'pos.antrian.view',
    'pos.input_order.read',
    'pos.transaksi.read',
    'pos.riwayat.read',
    'pos.void_otp.read',
    'pos.tutup_kasir.view',
    'tracking_order.view',
  ]);

  $showMaster = $canAnyRoute([
    ['konfigurasi.perusahaan.view', 'perusahaan.index'],
    ['konfigurasi.cabang.view', 'cabang.index'],
    ['konfigurasi.divisi.view', 'konfigurasi.divisi'],
    ['konfigurasi.jabatan.view', 'konfigurasi.jabatan'],
    ['konfigurasi.tracking.view', 'konfigurasi.tracking'],
    ['konfigurasi.studio.view', 'konfigurasi.studio'],
    ['konfigurasi.karyawan.read', 'konfigurasi.karyawan'],
    ['konfigurasi.role_user.read', 'konfigurasi.role-karyawan'],
    ['paket.master.read', 'paket.list'],
    ['paket.kategori.read', 'paket.kategori'],
    ['paket.addon.read', 'paket.addon'],
    ['paket.kategori_addon.read', 'paket.kategori-addon'],
    ['template.harga.read', 'template.harga'],
    ['sales_mode.read', 'sales-mode'],
    ['promosi.read', 'promosi'],
    ['persediaan.barang_jasa.read', 'persediaan.barang-jasa'],
    ['persediaan.satuan.read', 'persediaan.satuan'],
    ['persediaan.golongan.read', 'persediaan.golongan'],
    ['persediaan.stok.read', 'persediaan.stok'],
    ['permintaan_barang.read', 'permintaan-barang.index'],
    ['pembelian.pesanan.read', 'pembelian.pesanan'],
    ['pembelian.penerimaan.read', 'pembelian.penerimaan'],
    ['pembelian.faktur.read', 'pembelian.faktur'],
    ['pembelian.pembayaran.read', 'pembelian.pembayaran'],
    ['pembelian.retur.read', 'pembelian.retur'],
    ['pemasok.read', 'pemasok.index'],
    ['pembelian.kategori_pemasok.view', 'pembelian.kategori-pemasok'],
  ]);

  $showKonfigurasi = $canAnyRoute([
    ['konfigurasi.perusahaan.view', 'perusahaan.index'],
    ['konfigurasi.cabang.view', 'cabang.index'],
    ['konfigurasi.divisi.view', 'konfigurasi.divisi'],
    ['konfigurasi.jabatan.view', 'konfigurasi.jabatan'],
    ['konfigurasi.tracking.view', 'konfigurasi.tracking'],
    ['konfigurasi.studio.view', 'konfigurasi.studio'],
    ['konfigurasi.karyawan.read', 'konfigurasi.karyawan'],
    ['konfigurasi.role_user.read', 'konfigurasi.role-karyawan'],
  ]);

  $showPaket = $canAnyRoute([
    ['paket.master.read', 'paket.list'],
    ['paket.kategori.read', 'paket.kategori'],
    ['paket.addon.read', 'paket.addon'],
    ['paket.kategori_addon.read', 'paket.kategori-addon'],
    ['template.harga.read', 'template.harga'],
  ]);

  $showPersediaan = $canAnyRoute([
    ['persediaan.barang_jasa.read', 'persediaan.barang-jasa'],
    ['persediaan.satuan.read', 'persediaan.satuan'],
    ['persediaan.golongan.read', 'persediaan.golongan'],
    ['persediaan.stok.read', 'persediaan.stok'],
    ['permintaan_barang.read', 'permintaan-barang.index'],
  ]);

  $showPembelian = $canAnyRoute([
    ['pembelian.pesanan.read', 'pembelian.pesanan'],
    ['pembelian.penerimaan.read', 'pembelian.penerimaan'],
    ['pembelian.faktur.read', 'pembelian.faktur'],
    ['pembelian.pembayaran.read', 'pembelian.pembayaran'],
    ['pembelian.retur.read', 'pembelian.retur'],
    ['pemasok.read', 'pemasok.index'],
    ['pembelian.kategori_pemasok.view', 'pembelian.kategori-pemasok'],
  ]);

  $showLaporanItems = $canAny([
    'laporan.penjualan.view',
    'laporan.penjualan_paket.view',
    'laporan.penjualan_barang_jasa.view',
    'laporan.booking.view',
    'laporan.promosi.view',
    'laporan.pembayaran.view',
    'laporan.kasir.view',
    'laporan.tutup_kasir.view',
    'laporan.performa_karyawan.view',
    'laporan.pembelian_pesanan.view',
    'laporan.pembelian_penerimaan.view',
    'laporan.pembelian_faktur.view',
    'laporan.pembelian_pembayaran.view',
    'laporan.pembelian_retur.view',
  ]);
  $showLaporan = $can('laporan.menu.view') && $showLaporanItems;

  $showFinance = $canAnyRoute([
    ['finance.coa.view', 'coa'],
    ['finance.tax.view', 'tax'],
    ['finance.metode_pembayaran.view', 'metode-pembayaran'],
  ]);

  $showProduksi = $canAny([
    'produksi.pekerjaan_dg.view',
    'produksi.daftar_qc.view',
  ]);

  $showStudio = $canAny([
    'studio.antrian.view',
    'studio.display_customer.view',
    'studio.audio_announcer.view',
  ]);
  $showCs = $canAny([
    'cs.konfirmasi.view',
    'cs.documentation.view',
    'cs.support.view',
  ]);
  $isCloudCenter = (string) config('sync.app_mode') === 'cloud_center';
  $showSystemSync = $can('dashboard.view') && !$isCloudCenter;

  $kasirMenuLabel = 'Tutup Kasir';
  if ($user && $can('pos.tutup_kasir.view') && $can('pos.transaksi.read')) {
    $accessibleCabangIds = $user->cabang()->pluck('cabang.id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    $activeCabangId = (int) session('active_cabang_id');
    if (!empty($accessibleCabangIds)) {
      if (!$activeCabangId || !in_array($activeCabangId, $accessibleCabangIds, true)) {
        $activeCabangId = (int) ($accessibleCabangIds[0] ?? 0);
      }
    }

    $hasOpenShift = ShiftKasir::query()
      ->when($activeCabangId > 0, fn ($q) => $q->where('cabang_id', $activeCabangId))
      ->where('user_id', (int) $user->id)
      ->where('status', 'OPEN')
      ->exists();

    $kasirMenuLabel = $hasOpenShift ? 'Tutup Kasir' : 'Buka Kasir';
  }
@endphp

<!--start sidebar -->
<aside class="sidebar-wrapper">
  <div class="sidebar-header">
    <div class="logo-icon">
      <img src="{{ asset('assets/images/Papyrus Logo.png') }}" class="logo-img" alt="Papyrus POS logo">
    </div>
    <div class="logo-name flex-grow-1">
      <h5 class="mb-0">Papyrus</h5>
      <small class="text-secondary">POS</small>
    </div>
    <div class="sidebar-close">
      <i class="bi bi-x-lg"></i>
    </div>
  </div>
  <div class="sidebar-nav" data-simplebar="true">
    <!--navigation-->
    <ul class="metismenu" id="menu">
      @if($showDashboard)
        <li class="{{ request()->is('dashboard') ? 'mm-active' : '' }}">
          <a href="{{ route('dashboard') }}">
            <div class="parent-icon"><i class="bi bi-speedometer2"></i></div>
            <div class="menu-title">Dashboard</div>
          </a>
        </li>
      @endif

      @if($showPos)
        <li class="menu-label">POS</li>

        @if($can('pos.antrian.view'))
          <li class="{{ request()->is('input-antrian') ? 'mm-active' : '' }}">
            <a href="{{ route('input-antrian') }}">
              <div class="parent-icon"><i class="bi bi-clipboard-plus"></i></div>
              <div class="menu-title">Input Antrian</div>
            </a>
          </li>
        @endif

        @if($can('pos.input_order.read') || $can('pos.transaksi.read'))
          <li class="{{ request()->is('input-order') ? 'mm-active' : '' }}">
            <a href="{{ route('input-order') }}">
              <div class="parent-icon"><i class="bi bi-cart-plus"></i></div>
              <div class="menu-title">Input Order</div>
            </a>
          </li>
        @endif

        @if($can('pos.transaksi.read'))
          <li class="{{ request()->is('transaksi-penjualan') ? 'mm-active' : '' }}">
            <a href="{{ route('transaksi-penjualan') }}">
              <div class="parent-icon"><i class="bi bi-cash-stack"></i></div>
              <div class="menu-title">Transaksi Penjualan</div>
            </a>
          </li>
        @endif

        @if($can('pos.riwayat.read'))
          <li class="{{ request()->is('riwayat-penjualan') ? 'mm-active' : '' }}">
            <a href="{{ route('riwayat-penjualan') }}">
              <div class="parent-icon"><i class="bi bi-receipt"></i></div>
              <div class="menu-title">Riwayat Penjualan</div>
            </a>
          </li>
        @endif

        @if($can('pos.void_otp.read'))
          <li class="{{ request()->is('pos/generate-otp-void*') ? 'mm-active' : '' }}">
            <a href="{{ route('pos.void-otp') }}">
              <div class="parent-icon"><i class="bi bi-shield-lock"></i></div>
              <div class="menu-title">Generate OTP Void/Remove</div>
            </a>
          </li>
        @endif

        @if($can('pos.tutup_kasir.view'))
          <li class="{{ request()->is('pos/tutup-kasir') ? 'mm-active' : '' }}">
            <a href="{{ route('pos.tutup-kasir') }}">
              <div class="parent-icon"><i class="bi bi-cash-coin"></i></div>
              <div class="menu-title">{{ $kasirMenuLabel }}</div>
            </a>
          </li>
        @endif

        @if($can('tracking_order.view'))
          <li class="{{ request()->is('tracking-order') ? 'mm-active' : '' }}">
            <a href="{{ route('tracking-order') }}">
              <div class="parent-icon"><i class="bi bi-geo-alt"></i></div>
              <div class="menu-title">Tracking Order</div>
            </a>
          </li>
        @endif
      @endif

      @if($showMaster)
        <li class="menu-label">Master</li>

        @if($showKonfigurasi)
          <li class="{{ request()->is('konfigurasi*') ? 'mm-active' : '' }}">
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="bi bi-gear"></i></div>
              <div class="menu-title">Konfigurasi</div>
            </a>
            <ul>
              @if($canRoute('konfigurasi.perusahaan.view', 'perusahaan.index'))
                <li class="{{ request()->is('konfigurasi/perusahaan') ? 'mm-active' : '' }}">
                  <a href="{{ route('perusahaan.index') }}"><i class="bi bi-circle"></i>&nbsp;Perusahaan</a>
                </li>
              @endif
              @if($canRoute('konfigurasi.cabang.view', 'cabang.index'))
                <li class="{{ request()->is('konfigurasi/cabang') ? 'mm-active' : '' }}">
                  <a href="{{ route('cabang.index') }}"><i class="bi bi-circle"></i>&nbsp;Cabang</a>
                </li>
              @endif
              @if($canRoute('konfigurasi.divisi.view', 'konfigurasi.divisi'))
                <li class="{{ request()->is('konfigurasi/divisi') ? 'mm-active' : '' }}">
                  <a href="{{ route('konfigurasi.divisi') }}"><i class="bi bi-circle"></i>&nbsp;Divisi</a>
                </li>
              @endif
              @if($canRoute('konfigurasi.jabatan.view', 'konfigurasi.jabatan'))
                <li class="{{ request()->is('konfigurasi/jabatan') ? 'mm-active' : '' }}">
                  <a href="{{ route('konfigurasi.jabatan') }}"><i class="bi bi-circle"></i>&nbsp;Jabatan</a>
                </li>
              @endif
              @if($canRoute('konfigurasi.tracking.view', 'konfigurasi.tracking'))
                <li class="{{ request()->is('konfigurasi/tracking') ? 'mm-active' : '' }}">
                  <a href="{{ route('konfigurasi.tracking') }}"><i class="bi bi-circle"></i>&nbsp;Tracking</a>
                </li>
              @endif
              @if($canRoute('konfigurasi.studio.view', 'konfigurasi.studio'))
                <li class="{{ request()->is('konfigurasi/studio') ? 'mm-active' : '' }}">
                  <a href="{{ route('konfigurasi.studio') }}"><i class="bi bi-circle"></i>&nbsp;Studio</a>
                </li>
              @endif
              @if($canRoute('konfigurasi.karyawan.read', 'konfigurasi.karyawan'))
                <li class="{{ request()->is('konfigurasi/karyawan') ? 'mm-active' : '' }}">
                  <a href="{{ route('konfigurasi.karyawan') }}"><i class="bi bi-circle"></i>&nbsp;Karyawan</a>
                </li>
              @endif
              @if($canRoute('konfigurasi.role_user.read', 'konfigurasi.role-karyawan'))
                <li class="{{ request()->is('konfigurasi/role-karyawan') ? 'mm-active' : '' }}">
                  <a href="{{ route('konfigurasi.role-karyawan') }}"><i class="bi bi-circle"></i>&nbsp;Role Karyawan</a>
                </li>
              @endif
            </ul>
          </li>
        @endif

        @if($showPaket)
          <li class="{{ request()->is('paket*') ? 'mm-active' : '' }}">
            <a href="javascript:;" class="has-arrow">
              <div class="parent-icon"><i class="bi bi-box-seam"></i></div>
              <div class="menu-title">Paket</div>
            </a>
            <ul>
              @if($canRoute('paket.master.read', 'paket.list'))
                <li class="{{ request()->is('paket/list') ? 'mm-active' : '' }}">
                  <a href="{{ route('paket.list') }}"><i class="bi bi-circle"></i>&nbsp;Paket</a>
                </li>
              @endif
              @if($canRoute('paket.kategori.read', 'paket.kategori'))
                <li class="{{ request()->is('paket/kategori') ? 'mm-active' : '' }}">
                  <a href="{{ route('paket.kategori') }}"><i class="bi bi-circle"></i>&nbsp;Kategori Paket</a>
                </li>
              @endif
              @if($canRoute('paket.addon.read', 'paket.addon'))
                <li class="{{ request()->is('paket/addon') ? 'mm-active' : '' }}">
                  <a href="{{ route('paket.addon') }}"><i class="bi bi-circle"></i>&nbsp;Add On</a>
                </li>
              @endif
              @if($canRoute('paket.kategori_addon.read', 'paket.kategori-addon'))
                <li class="{{ request()->is('paket/kategori-addon') ? 'mm-active' : '' }}">
                  <a href="{{ route('paket.kategori-addon') }}"><i class="bi bi-circle"></i>&nbsp;Kategori Add On</a>
                </li>
              @endif
              @if($canRoute('template.harga.read', 'template.harga'))
                <li class="{{ request()->is('paket/template') ? 'mm-active' : '' }}">
                  <a href="{{ route('template.harga') }}"><i class="bi bi-circle"></i>&nbsp;Template Harga</a>
                </li>
              @endif
            </ul>
          </li>
        @endif

        @if($canRoute('sales_mode.read', 'sales-mode'))
          <li class="{{ request()->is('sales-mode') ? 'mm-active' : '' }}">
            <a href="{{ route('sales-mode') }}">
              <div class="parent-icon"><i class="bi bi-megaphone"></i></div>
              <div class="menu-title">Sales Mode</div>
            </a>
          </li>
        @endif

        @if($canRoute('promosi.read', 'promosi'))
          <li class="{{ request()->is('promosi') ? 'mm-active' : '' }}">
            <a href="{{ route('promosi') }}">
              <div class="parent-icon"><i class="bi bi-megaphone"></i></div>
              <div class="menu-title">Promosi</div>
            </a>
          </li>
        @endif

        @if($showPersediaan)
          <li class="{{ request()->is('persediaan*') ? 'mm-active' : '' }}">
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="bi bi-boxes"></i></div>
              <div class="menu-title">Persediaan</div>
            </a>
            <ul>
              @if($canRoute('persediaan.barang_jasa.read', 'persediaan.barang-jasa'))
                <li class="{{ request()->is('persediaan/barang-jasa') ? 'mm-active' : '' }}">
                  <a href="{{ route('persediaan.barang-jasa') }}"><i class="bi bi-circle"></i>&nbsp;Barang & Jasa</a>
                </li>
              @endif
              @if($canRoute('persediaan.satuan.read', 'persediaan.satuan'))
                <li class="{{ request()->is('persediaan/satuan') ? 'mm-active' : '' }}">
                  <a href="{{ route('persediaan.satuan') }}"><i class="bi bi-circle"></i>&nbsp;Satuan Barang</a>
                </li>
              @endif
              @if($canRoute('persediaan.golongan.read', 'persediaan.golongan'))
                <li class="{{ request()->is('persediaan/golongan') ? 'mm-active' : '' }}">
                  <a href="{{ route('persediaan.golongan') }}"><i class="bi bi-circle"></i>&nbsp;Golongan</a>
                </li>
              @endif
              @if($canRoute('persediaan.stok.read', 'persediaan.stok'))
                <li class="{{ request()->is('persediaan/stok') ? 'mm-active' : '' }}">
                  <a href="{{ route('persediaan.stok') }}"><i class="bi bi-circle"></i>&nbsp;Stok Barang</a>
                </li>
                <li class="{{ request()->is('persediaan/stok/penyesuaian*') ? 'mm-active' : '' }}">
                  <a href="{{ route('persediaan.stok.penyesuaian') }}"><i class="bi bi-circle"></i>&nbsp;Penyesuaian Stok</a>
                </li>
              @endif
              @if($canRoute('permintaan_barang.read', 'permintaan-barang.index'))
                <li class="{{ request()->is('permintaan-barang*') ? 'mm-active' : '' }}">
                  <a href="{{ route('permintaan-barang.index') }}"><i class="bi bi-circle"></i>&nbsp;Permintaan Barang</a>
                </li>
              @endif
            </ul>
          </li>
        @endif

        @if($showPembelian)
          <li class="{{ request()->is('pembelian*') ? 'mm-active' : '' }}">
            <a class="has-arrow" href="javascript:;">
              <div class="parent-icon"><i class="bi bi-cart-plus"></i></div>
              <div class="menu-title">Pembelian</div>
            </a>
            <ul>
              @if($canRoute('pembelian.pesanan.read', 'pembelian.pesanan'))
                <li class="{{ request()->is('pembelian/pesanan') ? 'mm-active' : '' }}">
                  <a href="{{ route('pembelian.pesanan') }}"><i class="bi bi-circle"></i>&nbsp;Pesanan Pembelian</a>
                </li>
              @endif
              @if($canRoute('pembelian.penerimaan.read', 'pembelian.penerimaan'))
                <li class="{{ request()->is('pembelian/penerimaan') ? 'mm-active' : '' }}">
                  <a href="{{ route('pembelian.penerimaan') }}"><i class="bi bi-circle"></i>&nbsp;Penerimaan Barang</a>
                </li>
              @endif
              @if($canRoute('pembelian.faktur.read', 'pembelian.faktur'))
                <li class="{{ request()->is('pembelian/faktur') ? 'mm-active' : '' }}">
                  <a href="{{ route('pembelian.faktur') }}"><i class="bi bi-circle"></i>&nbsp;Faktur Pembelian</a>
                </li>
              @endif
              @if($canRoute('pembelian.pembayaran.read', 'pembelian.pembayaran'))
                <li class="{{ request()->is('pembelian/pembayaran') ? 'mm-active' : '' }}">
                  <a href="{{ route('pembelian.pembayaran') }}"><i class="bi bi-circle"></i>&nbsp;Pembayaran Pembelian</a>
                </li>
              @endif
              @if($canRoute('pembelian.retur.read', 'pembelian.retur'))
                <li class="{{ request()->is('pembelian/retur*') ? 'mm-active' : '' }}">
                  <a href="{{ route('pembelian.retur') }}"><i class="bi bi-circle"></i>&nbsp;Retur Pembelian</a>
                </li>
              @endif
              @if($canRoute('pemasok.read', 'pemasok.index'))
                <li class="{{ request()->is('pemasok') ? 'mm-active' : '' }}">
                  <a href="{{ route('pemasok.index') }}"><i class="bi bi-circle"></i>&nbsp;Pemasok</a>
                </li>
              @endif
              @if($canRoute('pembelian.kategori_pemasok.view', 'pembelian.kategori-pemasok'))
                <li class="{{ request()->is('pembelian/kategori-pemasok') ? 'mm-active' : '' }}">
                  <a href="{{ route('pembelian.kategori-pemasok') }}"><i class="bi bi-circle"></i>&nbsp;Kategori Pemasok</a>
                </li>
              @endif
            </ul>
          </li>
        @endif
      @endif

      @if($showStudio)
        <li class="menu-label">Studio</li>
        @if($can('studio.antrian.view'))
          <li class="{{ request()->is('antrian-studio') ? 'mm-active' : '' }}">
            <a href="{{ route('antrian-studio') }}">
              <div class="parent-icon"><i class="bi bi-people"></i>
              </div>
              <div class="menu-title">Daftar Antrian</div>
            </a>
          </li>
        @endif
        @if($can('studio.display_customer.view'))
          <li class="{{ request()->is('antrian-studio/display/customer*') ? 'mm-active' : '' }}">
            <a href="{{ route('antrian-studio.display.customer') }}" target="_blank">
              <div class="parent-icon"><i class="bi bi-display"></i></div>
              <div class=" menu-title">Display Customer
              </div>
            </a>
          </li>
        @endif
        @if($can('studio.audio_announcer.view'))
          <li class="{{ request()->is('antrian-studio/audio-announcer*') ? 'mm-active' : '' }}">
            <a href="{{ route('antrian-studio.audio-announcer') }}" target="_blank">
              <div class="parent-icon"><i class="bi bi-volume-up"></i></div>
              <div class="menu-title">Audio Announcer</div>
            </a>
          </li>
        @endif
      @endif



      @if($showFinance)
        <li class="menu-label">Finance & Accounting</li>

        {{-- @if($can('finance.coa.view'))
        <li class="{{ request()->is('coa') ? 'mm-active' : '' }}">
          <a href="{{ route('coa') }}">
            <div class="parent-icon"><i class="bi bi-journal-bookmark"></i></div>
            <div class="menu-title">Chart of Account</div>
          </a>
        </li>
        @endif --}}

        @if($canRoute('finance.tax.view', 'tax'))
          <li class="{{ request()->is('tax') ? 'mm-active' : '' }}">
            <a href="{{ route('tax') }}">
              <div class="parent-icon"><i class="bi bi-percent"></i></div>
              <div class="menu-title">Tax</div>
            </a>
          </li>
        @endif

        @if($canRoute('finance.metode_pembayaran.view', 'metode-pembayaran'))
          <li class="{{ request()->is('metode-pembayaran') ? 'mm-active' : '' }}">
            <a href="{{ route('metode-pembayaran') }}">
              <div class="parent-icon"><i class="bi bi-credit-card"></i></div>
              <div class="menu-title">Metode Pembayaran</div>
            </a>
          </li>
        @endif
      @endif

      @if($showProduksi)
        <li class="menu-label">Produksi</li>

        @if($can('produksi.pekerjaan_dg.view'))
          <li class="{{ request()->is('pekerjaan-dg') ? 'mm-active' : '' }}">
            <a href="{{ route('pekerjaan-dg') }}">
              <div class="parent-icon"><i class="bi bi-list-task"></i></div>
              <div class="menu-title">Daftar Pekerjaan DG</div>
            </a>
          </li>
        @endif

        @if($can('produksi.daftar_qc.view'))
          <li class="{{ request()->is('daftar-qc') ? 'mm-active' : '' }}">
            <a href="{{ route('daftar-qc') }}">
              <div class="parent-icon"><i class="bi bi-check-circle"></i></div>
              <div class="menu-title">Daftar QC</div>
            </a>
          </li>
        @endif
      @endif



      @if($showCs)
        <li class="menu-label">Customer Service</li>

        @if($can('cs.konfirmasi.view'))
          <li class="{{ request()->is('konfirmasi') ? 'mm-active' : '' }}">
            <a href="{{ route('konfirmasi') }}">
              <div class="parent-icon"><i class="bi bi-chat-square-text"></i></div>
              <div class="menu-title">Daftar Konfirmasi</div>
            </a>
          </li>
        @endif

        @if($can('cs.documentation.view'))
          <li class="{{ request()->is('documentation') ? 'mm-active' : '' }}">
            <a href="{{ route('documentation') }}">
              <div class="parent-icon"><i class="bi bi-file-earmark-text"></i></div>
              <div class="menu-title">Documentation</div>
            </a>
          </li>
        @endif

        @if($can('cs.support.view'))
          <li class="{{ request()->is('support') ? 'mm-active' : '' }}">
            <a href="{{ route('support') }}" target="_blank">
              <div class="parent-icon"><i class="bi bi-headset"></i></div>
              <div class="menu-title">Support</div>
            </a>
          </li>
        @endif
      @endif

      @if($showSystemSync)
        <li class="menu-label">System</li>
        <li class="{{ request()->is('sync-control') ? 'mm-active' : '' }}">
          <a href="{{ route('sync-control') }}">
            <div class="parent-icon"><i class="bi bi-cloud-arrow-up"></i></div>
            <div class="menu-title">Sinkronisasi Cloud</div>
          </a>
        </li>
      @endif

      @if($showLaporan)
        <li class="menu-label">Laporan</li>
        <li class="{{ request()->is('laporan') ? 'mm-active' : '' }}">
          <a href="{{ route('laporan.menu') }}">
            <div class="parent-icon"><i class="bi bi-grid-3x3-gap"></i></div>
            <div class="menu-title">Laporan</div>
          </a>
        </li>
      @endif

    </ul>
    <!--end navigation-->
  </div>
</aside>
<!--end sidebar -->
