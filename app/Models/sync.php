<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sync Mode
    |--------------------------------------------------------------------------
    |
    | APP_MODE:
    | - local_branch : node POS cabang (utama transaksi offline-first)
    | - cloud_center : node pusat konsolidasi
    |
    | SYNC_ROLE:
    | - sender   : kirim perubahan ke cloud
    | - receiver : terima perubahan dari branch
    | - both     : keduanya aktif
    | - none     : fitur sync dimatikan
    |
    */
    'app_mode' => env('APP_MODE', env('APP_MODEL', 'local_branch')),
    'enabled' => (bool) env('SYNC_ENABLED', false),
    'role' => env('SYNC_ROLE', 'none'),

    // URL endpoint cloud penerima sync (dipakai node sender/local).
    'cloud_push_url' => env('SYNC_CLOUD_PUSH_URL', ''),
    'cloud_bootstrap_url' => env('SYNC_CLOUD_BOOTSTRAP_URL', ''),
    'cloud_master_pull_url' => env('SYNC_CLOUD_MASTER_PULL_URL', ''),

    // Shared secret sederhana antar node sync.
    'api_key' => env('SYNC_API_KEY', ''),
    'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('SYNC_ALLOWED_IPS', ''))))),
    'rate_limit_per_minute' => (int) env('SYNC_RATE_LIMIT_PER_MINUTE', 60),

    'http_timeout_seconds' => (int) env('SYNC_HTTP_TIMEOUT', 20),
    'http_retry_attempts' => (int) env('SYNC_HTTP_RETRY_ATTEMPTS', 3),
    'http_retry_sleep_ms' => (int) env('SYNC_HTTP_RETRY_SLEEP_MS', 800),
    'batch_size_per_dataset' => (int) env('SYNC_BATCH_SIZE', 300),
    'target_name' => env('SYNC_TARGET_NAME', 'cloud'),
    'bootstrap_batch_size_per_dataset' => (int) env('SYNC_BOOTSTRAP_BATCH_SIZE', 2000),
    'pull_master_auto' => (bool) env('SYNC_PULL_MASTER_AUTO', false),
    'pull_master_mode' => env('SYNC_PULL_MASTER_MODE', 'incremental'),
    'pull_master_interval_minutes' => (int) env('SYNC_PULL_MASTER_INTERVAL_MINUTES', 5),
    'pull_master_cabang_id' => (int) env('SYNC_PULL_MASTER_CABANG_ID', 0),
    'pull_master_target_name' => env('SYNC_PULL_MASTER_TARGET_NAME', 'cloud_master'),
    'pull_batch_size_per_dataset' => (int) env('SYNC_PULL_BATCH_SIZE', 300),
    'pull_partial_max_retries' => (int) env('SYNC_PULL_PARTIAL_MAX_RETRIES', 3),

    // Route name patterns yang diblokir ketika APP_MODE=local_branch.
    'local_blocked_route_names' => [
        'perusahaan.index',
        'cabang.index',
        'konfigurasi.*',
        'paket.*',
        'template.harga*',
        'sales-mode*',
        'promosi*',
        'persediaan.*',
        'permintaan-barang.*',
        'pemasok.*',
        'pembelian.*',
        'metode-pembayaran*',
        'coa',
        'tax',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dataset Sync
    |--------------------------------------------------------------------------
    |
    | Semua tabel di bawah wajib punya kolom:
    | - primary key (default: id)
    | - updated_at
    |
    */
    'datasets' => [
        'pelanggan' => [
            'table' => 'pelanggan',
            'primary_key' => 'id',
        ],
        'pesanan_penjualan' => [
            'table' => 'pesanan_penjualan',
            'primary_key' => 'id',
        ],
        'pesanan_penjualan_item' => [
            'table' => 'pesanan_penjualan_item',
            'primary_key' => 'id',
        ],
        'pembayaran_penjualan' => [
            'table' => 'pembayaran_penjualan',
            'primary_key' => 'id',
        ],
        'penjualan_edit_logs' => [
            'table' => 'penjualan_edit_logs',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'booking_studio' => [
            'table' => 'booking_studio',
            'primary_key' => 'id',
        ],
        'antrian_studio' => [
            'table' => 'antrian_studio',
            'primary_key' => 'id',
        ],
        'antrian_studio_tugas' => [
            'table' => 'antrian_studio_tugas',
            'primary_key' => 'id',
        ],
        'kantong_order' => [
            'table' => 'kantong_order',
            'primary_key' => 'id',
        ],
        'ko_tracking_ko_checks' => [
            'table' => 'ko_tracking_ko_checks',
            'primary_key' => 'id',
        ],
        'ko_tracking_item_checks' => [
            'table' => 'ko_tracking_item_checks',
            'primary_key' => 'id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrap Master Datasets (cloud -> local)
    |--------------------------------------------------------------------------
    |
    | Gunakan untuk inisialisasi cabang baru (database local).
    |
    */
    'bootstrap_datasets' => [
        'perusahaan' => [
            'table' => 'perusahaan',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'cabang' => [
            'table' => 'cabang',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'divisi' => [
            'table' => 'divisi',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'jabatan' => [
            'table' => 'jabatan',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'roles' => [
            'table' => 'roles',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'permissions' => [
            'table' => 'permissions',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'users' => [
            'table' => 'users',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'karyawan' => [
            'table' => 'karyawan',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'tema_studio' => [
            'table' => 'tema_studio',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'studio' => [
            'table' => 'studio',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'kategori_produk' => [
            'table' => 'kategori_produk',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'satuan' => [
            'table' => 'satuan',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'produk' => [
            'table' => 'produk',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'kategori_paket' => [
            'table' => 'kategori_paket',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'kategori_addon' => [
            'table' => 'kategori_addon',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'addon' => [
            'table' => 'addon',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'paket' => [
            'table' => 'paket',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'paket_item' => [
            'table' => 'paket_item',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'paket_cabang' => [
            'table' => 'paket_cabang',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'metode_pembayaran' => [
            'table' => 'metode_pembayaran',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'cabang_metode_pembayaran' => [
            'table' => 'cabang_metode_pembayaran',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'sales_mode' => [
            'table' => 'sales_mode',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'cabang_sales_mode' => [
            'table' => 'cabang_sales_mode',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'template_harga' => [
            'table' => 'template_harga',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'template_harga_item' => [
            'table' => 'template_harga_item',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'tracking_templates' => [
            'table' => 'tracking_templates',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'tracking_template_steps' => [
            'table' => 'tracking_template_steps',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'tracking_references' => [
            'table' => 'tracking_references',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'voucher_promosi' => [
            'table' => 'voucher_promosi',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'voucher_promosi_cabang' => [
            'table' => 'voucher_promosi_cabang',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'diskon_otomatis' => [
            'table' => 'diskon_otomatis',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'diskon_otomatis_cabang' => [
            'table' => 'diskon_otomatis_cabang',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'stok_cabang' => [
            'table' => 'stok_cabang',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'stok_penyesuaian' => [
            'table' => 'stok_penyesuaian',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'stok_penyesuaian_item' => [
            'table' => 'stok_penyesuaian_item',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'permintaan_barang' => [
            'table' => 'permintaan_barang',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'permintaan_barang_item' => [
            'table' => 'permintaan_barang_item',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'permission_role' => [
            'table' => 'permission_role',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'cabang_user' => [
            'table' => 'cabang_user',
            'primary_key' => 'id',
            'branch_scoped' => true,
            'cabang_column' => 'cabang_id',
        ],
        'jabatan_tracking_permissions' => [
            'table' => 'jabatan_tracking_permissions',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'jabatan_tracking_references' => [
            'table' => 'jabatan_tracking_references',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
        'karyawan_tracking_divisi_access' => [
            'table' => 'karyawan_tracking_divisi_access',
            'primary_key' => 'id',
            'branch_scoped' => false,
        ],
    ],

    // Batasi kolom sensitif yang ikut ditarik saat bootstrap per dataset.
    'bootstrap_column_allowlist' => [
        'users' => [
            'id',
            'name',
            'email',
            'username',
            'no_wa',
            'foto_profil',
            'status',
            'role_id',
            'password',
            'created_at',
            'updated_at',
            'deleted_at',
        ],
    ],
];
