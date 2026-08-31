@extends('layouts.app')

@section('title', 'Hak Akses Tracking Jabatan')

@section('content')
@php
    $canUpdateJabatan = auth()->user()?->hasPermission('konfigurasi.jabatan.update') ?? false;
@endphp
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('konfigurasi.jabatan') }}">Jabatan</a></li>
                <li class="breadcrumb-item active">Hak Akses Tracking</li>
            </ol>
        </nav>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div><strong>Jabatan:</strong> {{ $jabatan->nama }}</div>
        <div class="text-muted small">Pilih tracking item dan tracking KO yang boleh diupdate oleh jabatan ini.</div>
    </div>
</div>

<form method="POST" action="{{ route('konfigurasi.jabatan.tracking-ko.update', $jabatan) }}">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-header bg-light">
            <strong>Tracking Item (Kategori Produk)</strong>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @foreach(($trackingItem ?? collect()) as $tracking)
                    <div class="col-md-4">
                        <label class="border rounded p-2 d-flex align-items-center gap-2 w-100">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="tracking_ids[]"
                                   value="{{ $tracking->id }}"
                                   {{ in_array((int) $tracking->id, $selectedTrackingIds ?? [], true) ? 'checked' : '' }}>
                            <span class="fw-semibold">{{ $tracking->nama }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light">
            <strong>Tracking KO</strong>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @foreach(($trackingKo ?? collect()) as $tracking)
                    <div class="col-md-4">
                        <label class="border rounded p-2 d-flex align-items-center gap-2 w-100">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="tracking_ids[]"
                                   value="{{ $tracking->id }}"
                                   {{ in_array((int) $tracking->id, $selectedTrackingIds ?? [], true) ? 'checked' : '' }}>
                            <span class="fw-semibold">{{ $tracking->nama }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        @if($canUpdateJabatan)
            <button class="btn btn-primary">Simpan Hak Akses Tracking</button>
        @endif
        <a href="{{ route('konfigurasi.jabatan') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
</form>
@endsection
