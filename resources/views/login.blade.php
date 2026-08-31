<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="{{ asset('assets/images/Papyrus Logo.png') }}" type="image/png">
  <title>Login - Papyrus POS</title>

  <link href="{{ asset('assets/ltr/assets/css/pace.min.css') }}" rel="stylesheet">
  <script src="{{ asset('assets/ltr/assets/js/pace.min.js') }}"></script>
  <link href="{{ asset('assets/ltr/assets/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/ltr/assets/css/main.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/fonts/noto-sans.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">

  <style>
    body {
      min-height: 100vh;
      font-family: "Noto Sans", sans-serif;
      background: linear-gradient(130deg, #eef4ff 0%, #d8e9ff 45%, #f2f8ff 100%);
    }
    .auth-shell {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .auth-card {
      width: 100%;
      max-width: 460px;
      border: 1px solid #d9e2f2;
      border-radius: 18px;
      box-shadow: 0 24px 60px rgba(16, 48, 94, .14);
      background: #fff;
      padding: 30px;
    }
    .logo-wrap img {
      width: 84px;
      height: 84px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #e8f0ff;
    }
  </style>
</head>
<body>
  <main class="auth-shell">
    <section class="auth-card">
      <div class="logo-wrap text-center mb-3">
        <img src="{{ asset('storage/logo/logo-papyrus-bulat.png') }}" alt="Logo Papyrus">
      </div>

      <h4 class="text-center fw-bold mb-1">Papyrus POS</h4>
      <p class="text-center text-secondary mb-4">Masuk ke sistem kasir dan operasional.</p>

      @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('login.submit') }}">
        @csrf
        <div class="mb-3">
          <label for="inputLogin" class="form-label">Email / Username</label>
          <div class="position-relative">
            <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-secondary">
              <i class="bi bi-envelope-fill"></i>
            </span>
            <input type="text" name="login" value="{{ old('login') }}" class="form-control ps-5" id="inputLogin" placeholder="Email atau Username" required>
          </div>
        </div>

        <div class="mb-3">
          <label for="inputPassword" class="form-label">Password</label>
          <div class="position-relative">
            <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-secondary">
              <i class="bi bi-lock-fill"></i>
            </span>
            <input type="password" name="password" class="form-control ps-5" id="inputPassword" placeholder="Password" required>
          </div>
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" name="remember" value="1" type="checkbox" id="rememberMe">
          <label class="form-check-label" for="rememberMe">Remember Me</label>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary">Sign In</button>
        </div>
      </form>
    </section>
  </main>

  <script src="{{ asset('assets/ltr/assets/js/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/ltr/assets/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
