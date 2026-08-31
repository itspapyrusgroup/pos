@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="card radius-10 mb-3">
    <div class="card-body">
        <h5 class="mb-1">Selamat datang, {{ auth()->user()?->name ?? 'Karyawan' }}</h5>
        <p class="text-muted mb-0">Dashboard ringkasan utama tidak tersedia untuk role ini. Gunakan shortcut menu di bawah untuk membuka fitur yang masih bisa diakses.</p>
    </div>
</div>

<div class="card radius-10">
    <div class="card-body">
        <h6 class="mb-3">Shortcut Menu</h6>
        <div class="row g-2">
            @forelse($shortcutMenus ?? [] as $menu)
                <div class="col-12 col-md-6 col-xl-4">
                    <a href="{{ route($menu['route']) }}" class="btn btn-outline-primary w-100 text-start py-2">
                        <i class="{{ $menu['icon'] }} me-2"></i>{{ $menu['label'] }}
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning mb-0">Belum ada menu yang bisa diakses untuk role Anda. Hubungi admin untuk pengaturan permission.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
