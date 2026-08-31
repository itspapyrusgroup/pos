@extends('layouts.app')

@section('title', 'Tambah Pemasok')

@section('content')

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a>
                </li>
                <li class="breadcrumb-item"><a href="{{ route('pemasok.index') }}">Pemasok</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Form Tambah Pemasok</h5>
        <hr>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('pemasok.store') }}">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nama" class="form-label">Nama Pemasok <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama" name="nama" value="{{ old('nama') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="kode" class="form-label">Kode Pemasok</label>
                    <input type="text" class="form-control" id="kode" name="kode" value="{{ old('kode') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="credit_terms" class="form-label">Credit Terms (Hari)</label>
                    <input type="number" class="form-control" id="credit_terms" name="credit_terms" value="{{ old('credit_terms', 0) }}">
                </div>
                <div class="col-md-6">
                    <label for="kategori" class="form-label">Kategori</label>
                    <select class="form-select" id="kategori" name="kategori">
                        <option value="Default" {{ old('kategori', 'Default') === 'Default' ? 'selected' : '' }}>Default</option>
                        <!-- Tambahkan kategori lain jika diperlukan -->
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2">{{ old('alamat') }}</textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="kontak" class="form-label">Kontak Person</label>
                    <input type="text" class="form-control" id="kontak" name="kontak" value="{{ old('kontak') }}">
                </div>
                <div class="col-md-6">
                    <label for="telepon" class="form-label">Telepon</label>
                    <input type="text" class="form-control" id="telepon" name="telepon" value="{{ old('telepon') }}">
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="status" name="status" {{ old('status', 'on') ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">Aktif</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('pemasok.index') }}" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan Pemasok</button>
            </div>
        </form>
    </div>
</div>

@endsection
