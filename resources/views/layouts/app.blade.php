<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="{{ asset('assets/images/Papyrus Logo.png') }}" type="image/png">
  <title>@yield('title', 'Papyrus POS')</title>

  <script>
    (function () {
      try {
        var themeKey = 'papyrus_pos_theme';
        var allowed = ['light', 'dark', 'semi-dark', 'minimal-theme', 'shadow-theme', 'light-theme', 'dark-theme'];
        var saved = localStorage.getItem(themeKey);
        if (!saved || allowed.indexOf(saved) === -1) return;
        var normalized = saved === 'light-theme' ? 'light' : (saved === 'dark-theme' ? 'dark' : saved);
        document.documentElement.setAttribute('data-bs-theme', normalized);
      } catch (e) {}
    })();
  </script>

  <link href="{{ asset('assets/ltr/assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet">

  <link href="{{ asset('assets/ltr/assets/css/pace.min.css') }}" rel="stylesheet">
  <script src="{{ asset('assets/ltr/assets/js/pace.min.js') }}"></script>
  <link href="{{ asset('assets/ltr/assets/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/main.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/select2-theme.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/dark-theme.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/semi-dark-theme.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/minimal-theme.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/shadow-theme.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/fonts/noto-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

  <style>
    .menu-label {
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      opacity: .75;
    }
    .top-right-menu {
      margin-left: auto;
      flex-direction: row !important;
      align-items: center;
    }
    .top-right-menu .profile-toggle {
      width: auto !important;
      height: auto !important;
      border: 0 !important;
      background: transparent !important;
      padding: 0 !important;
      display: block !important;
    }
    .top-right-menu .profile-toggle:hover,
    .top-right-menu .profile-toggle:focus {
      border: 0 !important;
      background: transparent !important;
      box-shadow: none !important;
    }
  </style>

  @stack('styles')
</head>
<body>
  @include('layouts.header')
  @include('layouts.sidebar')

  <main class="page-content">
    @yield('content')
  </main>

  <div class="overlay btn-toggle-menu"></div>

  <script src="{{ asset('assets/ltr/assets/js/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/plugins/chartjs/chart.min.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/plugins/apex/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/plugins/select2/js/select2.min.js') }}"></script>
  <script src="{{ asset('assets/js/form-select2.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

  @stack('scripts')

  <script>
    (function () {
      if (typeof jQuery !== 'undefined') {
        $('#menu').metisMenu();
      }

      document.addEventListener('click', function (event) {
        var toggle = event.target.closest('.btn-toggle-menu, .sidebar-close');
        if (!toggle) return;
        event.preventDefault();
        document.body.classList.toggle('toggled');
      });

      document.addEventListener('click', function (event) {
        var darkToggle = event.target.closest('.dark-mode');
        if (!darkToggle) return;
        event.preventDefault();
        event.stopPropagation();
        var html = document.documentElement;
        var nextTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', nextTheme);
        localStorage.setItem('papyrus_pos_theme', nextTheme);
      }, true);

      document.querySelectorAll('form[onsubmit]').forEach(function (form) {
        const onsubmit = form.getAttribute('onsubmit') || '';
        const match = onsubmit.match(/return\s+confirm\((['"])(.*?)\1\)/i);
        if (!match) return;
        form.dataset.swalMessage = match[2] || 'Yakin melanjutkan proses ini?';
        form.removeAttribute('onsubmit');
      });

      const flash = {
        success: @json(session('success')),
        error: @json(session('error')),
        warning: @json(session('warning')),
      };

      if (typeof Swal !== 'undefined') {
        if (flash.success) Swal.fire({ icon: 'success', title: 'Berhasil', text: flash.success, confirmButtonText: 'OK' });
        else if (flash.error) Swal.fire({ icon: 'error', title: 'Gagal', text: flash.error, confirmButtonText: 'OK' });
        else if (flash.warning) Swal.fire({ icon: 'warning', title: 'Perhatian', text: flash.warning, confirmButtonText: 'OK' });
      }

      document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form || form.tagName !== 'FORM') return;
        if (form.dataset.swalConfirmed === '1') return;
        const message = form.dataset.swalMessage;
        if (!message) return;

        event.preventDefault();
        const isDelete = (form.querySelector('input[name="_method"][value="DELETE"]') !== null) || /hapus/i.test(message);
        const proceed = function () {
          form.dataset.swalConfirmed = '1';
          form.submit();
        };

        if (typeof Swal === 'undefined') {
          proceed();
          return;
        }

        Swal.fire({
          title: isDelete ? 'Konfirmasi Hapus' : 'Konfirmasi',
          text: message,
          icon: isDelete ? 'warning' : 'question',
          showCancelButton: true,
          confirmButtonText: isDelete ? 'Ya, lanjutkan' : 'Ya',
          cancelButtonText: 'Batal',
          reverseButtons: true,
        }).then(function (result) {
          if (result.isConfirmed) proceed();
        });
      }, true);
    })();
  </script>
</body>
</html>
