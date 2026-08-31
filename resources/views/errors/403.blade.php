<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden</title>
    <link href="{{ asset('assets/ltr/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/ltr/assets/css/main.css') }}" rel="stylesheet">
</head>
<body style="min-height:100vh;background:linear-gradient(140deg,#edf4ff,#dce9ff);">
<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height:80vh;">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <h1 class="display-3 fw-bold text-primary mb-2">403</h1>
                    <h5 class="mb-3">Akses Ditolak</h5>
                    <p class="text-secondary mb-4">Anda tidak memiliki permission untuk membuka halaman ini.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary px-4">Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
