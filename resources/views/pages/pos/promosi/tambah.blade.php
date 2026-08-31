@extends('layouts.app')

@section('title', 'Tambah Promosi - POS')

@section('content')

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">POS</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a>
                </li>
                <li class="breadcrumb-item"><a href="{{ route('promosi') }}">Promosi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Baru</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Tambah Promosi - Baru</h5>

        <form id="promotionForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="promotion_type" class="form-label">Tipe Promosi</label>
                    <select class="form-select" id="promotion_type" name="promotion_type" required>
                        <option value="">Pilih Tipe Promosi</option>
                        <option value="bill_discount_rp">Bill Discount (Rp)</option>
                        <option value="bill_discount_percent">Bill Discount (%)</option>
                        <option value="item_discount_rp">Discount Item (Rp)</option>
                        <option value="item_discount_percent">Discount Item (%)</option>
                        <option value="free_item">Free Item</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="promotion_time" class="form-label">Waktu Promosi</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="promotion_time" id="all_days" value="all_days" checked>
                        <label class="form-check-label" for="all_days">Setiap Hari</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="promotion_time" id="specific_days" value="specific_days">
                        <label class="form-check-label" for="specific_days">Hari Tertentu</label>
                    </div>
                </div>

                <div class="col-md-6" id="days_selection" style="display: none;">
                    <label class="form-label">Pilih Hari</label>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input day-checkbox" type="checkbox" id="monday" value="Monday">
                                <label class="form-check-label" for="monday">Senin</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input day-checkbox" type="checkbox" id="tuesday" value="Tuesday">
                                <label class="form-check-label" for="tuesday">Selasa</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input day-checkbox" type="checkbox" id="wednesday" value="Wednesday">
                                <label class="form-check-label" for="wednesday">Rabu</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input day-checkbox" type="checkbox" id="thursday" value="Thursday">
                                <label class="form-check-label" for="thursday">Kamis</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input day-checkbox" type="checkbox" id="friday" value="Friday">
                                <label class="form-check-label" for="friday">Jumat</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input day-checkbox" type="checkbox" id="saturday" value="Saturday">
                                <label class="form-check-label" for="saturday">Sabtu</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input day-checkbox" type="checkbox" id="sunday" value="Sunday">
                                <label class="form-check-label" for="sunday">Minggu</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="branches" class="form-label">Cabang</label>
                    <select class="form-select multiple-select" id="branches" name="branches[]" multiple required>
                        <option value="all">Semua Cabang</option>
                        <option value="1">Papyrus Bengawan</option>
                        <option value="2">Papyrus CCM</option>
                        <option value="3">Papyrus Margo</option>
                    </select>
                </div>


                <div class="col-md-6">
                    <label for="sales_mode" class="form-label">Sales Mode</label>
                    <select class="form-select multiple-select" id="sales_mode" name="sales_mode[]"multiple required>
                        <option value="">Pilih Sales Mode</option>
                        <option value="1">Toko</option>
                        <option value="2">Tokopedia</option>
                        <option value="3">Shopee</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="promotion_code" class="form-label">Kode Promosi</label>
                    <input type="text" class="form-control" id="promotion_code" name="promotion_code" required>
                </div>
                <div class="col-md-6">
                    <label for="promotion_master_code" class="form-label">Promotion Master Code</label>
                    <input type="text" class="form-control" id="promotion_master_code" name="promotion_master_code">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>
                <div class="col-md-6">
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="min_sales_price" class="form-label">Min. Harga Penjualan (Rp)</label>
                    <input type="number" class="form-control" id="min_sales_price" name="min_sales_price" min="0" value="0">
                </div>
                <div class="col-md-6">
                    <label for="discount_value" class="form-label">Nilai Diskon</label>
                    <input type="number" class="form-control" id="discount_value" name="discount_value" min="0">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="payment_method" class="form-label">Metode Pembayaran</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="">Pilih Metode Pembayaran</option>
                        <option value="cash">Tunai</option>
                        <option value="credit_card">Kartu Kredit</option>
                        <option value="debit_card">Kartu Debit</option>
                        <option value="e_wallet">E-Wallet</option>
                    </select>
                </div>
            </div>

            <div id="item_selection" class="mb-3" style="display: none;">
                <label class="form-label">Pilih Item</label>
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="item_type" id="item_type_package" value="package">
                                    <label class="form-check-label" for="item_type_package">Paket</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="item_type" id="item_type_product" value="product">
                                    <label class="form-check-label" for="item_type_product">Barang</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="item_type" id="item_type_service" value="service">
                                    <label class="form-check-label" for="item_type_service">Jasa</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3" id="item_list_container" style="display: none;">
                            <select class="form-select multiple-select" id="item_list" name="items[]" multiple>
                                <!-- Items will be loaded dynamically based on selection -->
                            </select>
                        </div>

                        <div class="mt-3" id="include_options" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_package_content" name="include_package_content">
                                <label class="form-check-label" for="include_package_content">Include Package Content</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_extra" name="include_extra">
                                <label class="form-check-label" for="include_extra">Include Extra</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="promotion_desc" class="form-label">Deskripsi Promosi</label>
                <textarea class="form-control" id="promotion_desc" name="promotion_desc" rows="2"></textarea>
            </div>

            <div class="mb-3">
                <label for="promotion_notes" class="form-label">Catatan Promosi</label>
                <textarea class="form-control" id="promotion_notes" name="promotion_notes" rows="2"></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize multiple select
        $('.multiple-select').select2({
            placeholder: "Pilih",
            allowClear: true
        });

        // Show/hide days selection based on promotion time
        $('input[name="promotion_time"]').change(function() {
            if ($(this).val() === 'specific_days') {
                $('#days_selection').show();
            } else {
                $('#days_selection').hide();
            }
        });

        // Show/hide item selection based on promotion type
        $('#promotion_type').change(function() {
            const type = $(this).val();
            if (type === 'item_discount_rp' || type === 'item_discount_percent' || type === 'free_item') {
                $('#item_selection').show();
            } else {
                $('#item_selection').hide();
            }
        });

        // Show item list when item type is selected
        $('input[name="item_type"]').change(function() {
            const itemType = $(this).val();
            if (itemType) {
                $('#item_list_container').show();
                $('#include_options').toggle(itemType === 'package');

                // Here you would typically load items via AJAX based on the selected type
                // For demo, we'll just add some dummy items
                let items = [];
                if (itemType === 'package') {
                    items = [
                        {id: 1, text: 'Paket Keluarga'},
                        {id: 2, text: 'Paket Pasangan'},
                        {id: 3, text: 'Paket Individu'}
                    ];
                } else if (itemType === 'product') {
                    items = [
                        {id: 4, text: 'Frame 24R'},
                        {id: 5, text: 'Frame 4R'},
                        {id: 6, text: 'Cetak 4R'}
                    ];
                } else if (itemType === 'service') {
                    items = [
                        {id: 7, text: 'Tambah Pose'},
                        {id: 8, text: 'Tambah File'},
                        {id: 9, text: 'Tambah Orang'}
                    ];
                }

                $('#item_list').empty().select2({
                    data: items,
                    placeholder: "Pilih " + (itemType === 'package' ? 'Paket' : itemType === 'product' ? 'Barang' : 'Jasa'),
                    allowClear: true
                });
            } else {
                $('#item_list_container').hide();
                $('#include_options').hide();
            }
        });

        // Handle form submission
        $('#promotionForm').on('submit', function(e) {
            e.preventDefault();
            // Form submission logic here
            Swal.fire({
                title: 'Berhasil!',
                text: 'Promosi berhasil ditambahkan',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = "{{ route('promosi') }}";
            });
        });
    });
</script>
@endpush

@push('styles')
<style>
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        padding: 0.375rem 0.75rem;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: white;
    }
</style>
@endpush
