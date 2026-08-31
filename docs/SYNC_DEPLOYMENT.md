# Sync Deployment Guide (Local Branch + Cloud Center)

Panduan ini untuk skenario:
- POS tetap berjalan di cabang walau internet down.
- Data transaksi cabang tetap bisa dipantau online di cloud.
- Cabang baru bisa bootstrap master data dari cloud.

## 1. Konsep Arsitektur

- `local_branch`:
  - Dipakai operasional POS harian.
  - Menjadi `sender` sinkronisasi transaksi ke cloud.
  - Bisa manual push dari menu `Sinkronisasi Cloud`.
- `cloud_center`:
  - Menjadi penerima sinkronisasi (`receiver`).
  - Menyediakan endpoint bootstrap master data untuk cabang baru.

## 2. WAJIB: Migrasi Database

Ya, local maupun cloud **wajib** migrasi dulu.

```bash
php artisan migrate
```

Alasan:
- fitur sync butuh tabel `sync_push_cursor`
- struktur tabel harus kompatibel agar upsert sync aman

## 3. Konfigurasi ENV Cloud

Contoh `.env` cloud:

```env
APP_MODE=cloud_center
SYNC_ENABLED=true
SYNC_ROLE=receiver
SYNC_API_KEY=ISI_KEY_RAHASIA_SAMA_DENGAN_LOCAL
```

Catatan:
- Cloud tidak perlu `SYNC_CLOUD_PUSH_URL`.
- Endpoint receiver yang harus hidup:
  - `POST /api/sync/push`
  - `GET /api/sync/bootstrap/master`

## 4. Konfigurasi ENV Local Cabang

Contoh `.env` local cabang:

```env
APP_MODE=local_branch
SYNC_ENABLED=true
SYNC_ROLE=sender
SYNC_TARGET_NAME=cloud
SYNC_CLOUD_PUSH_URL=https://cloud-domain.com/api/sync/push
SYNC_CLOUD_BOOTSTRAP_URL=https://cloud-domain.com/api/sync/bootstrap/master
SYNC_API_KEY=ISI_KEY_RAHASIA_SAMA_DENGAN_CLOUD
SYNC_HTTP_TIMEOUT=20
SYNC_HTTP_RETRY_ATTEMPTS=3
SYNC_HTTP_RETRY_SLEEP_MS=800
SYNC_BATCH_SIZE=300
SYNC_BOOTSTRAP_BATCH_SIZE=2000
SYNC_PULL_MASTER_AUTO=true
SYNC_PULL_MASTER_INTERVAL_MINUTES=5
SYNC_PULL_MASTER_CABANG_ID=1
```

## 5. Cara Generate `SYNC_API_KEY`

Jalankan:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Pakai value yang sama persis di cloud dan di local cabang.

## 6. Jalankan Scheduler (Local Cabang)

Sinkronisasi auto push dijadwalkan tiap menit (`sync:push-cloud`).

Jalankan scheduler worker:

```bash
php artisan schedule:work
```

Atau pakai cron/supervisor sesuai server masing-masing.

## 7. Manual Sync dari UI

Di aplikasi local:
- buka menu `System > Sinkronisasi Cloud` (`/sync-control`)
- tombol:
  - `Sinkronkan Sekarang` -> push incremental transaksi ke cloud
  - `Tarik Master Dari Cloud` -> bootstrap master data dari cloud

Catatan:
- Data promosi (`voucher_promosi`, `diskon_otomatis` + relasi cabang) ikut di bootstrap master.
- Jika ingin otomatis, aktifkan `SYNC_PULL_MASTER_AUTO=true` di local.

## 8. Bootstrap Cabang Baru

Urutan yang direkomendasikan:

1. Deploy app di cabang baru.
2. Set `.env` local cabang (mode sender).
3. Jalankan `php artisan migrate`.
4. Tarik master data dari cloud:
   - via UI: `Tarik Master Dari Cloud`
   - atau CLI:
   ```bash
   php artisan sync:bootstrap-pull --cabang_id=1
   ```
5. Verifikasi data master (cabang/studio/produk/paket/metode bayar).
6. Mulai operasional POS.

Untuk update master berkala (termasuk promosi):
- set `SYNC_PULL_MASTER_AUTO=true`
- set `SYNC_PULL_MASTER_CABANG_ID=<id_cabang_local>`
- scheduler akan menjalankan pull tiap `SYNC_PULL_MASTER_INTERVAL_MINUTES`.

## 9. Verifikasi Cepat

Di local cabang:

```bash
php artisan list --raw | findstr sync:
php artisan route:list --path=sync
```

Harus muncul:
- `sync:push-cloud`
- `sync:bootstrap-pull`
- route `/sync-control`
- route API `/api/sync/push` dan `/api/sync/bootstrap/master`

## 10. Troubleshooting

### A. `Sinkronkan Sekarang` gagal 401 Unauthorized
- Penyebab: `SYNC_API_KEY` local != cloud.
- Solusi: samakan key di kedua node, lalu retry.

### B. Gagal connect cloud / timeout
- Penyebab: URL salah, DNS/firewall, SSL.
- Solusi:
  - cek `SYNC_CLOUD_PUSH_URL`
  - test akses endpoint cloud dari server local
  - naikkan `SYNC_HTTP_TIMEOUT`

### C. Status sync sukses tapi rows 0 terus
- Penyebab: memang belum ada perubahan baru.
- Cek tabel `sync_push_cursor` untuk cursor timestamp.

### D. Bootstrap master tidak masuk
- Penyebab umum:
  - `SYNC_CLOUD_BOOTSTRAP_URL` kosong/salah
  - role cloud bukan `receiver/both`
  - key tidak cocok
- Solusi: perbaiki env dan ulangi bootstrap.

### E. Scheduler tidak jalan
- Penyebab: `schedule:work`/cron tidak aktif.
- Solusi: aktifkan scheduler di service manager.

### F. Error schema saat ingest/upsert
- Penyebab: migrasi local/cloud beda versi.
- Solusi:
  - jalankan `php artisan migrate` di semua node
  - samakan versi code app

## 11. Catatan Operasional

- Sync saat ini fokus pada incremental transaksi dan bootstrap master.
- Hard delete tidak disarankan untuk data transaksi lintas node.
- Lakukan backup DB rutin di cloud dan local.

## 12. Guard Akses Master Data (Local Branch)

Mulai patch keamanan terbaru, local branch menggunakan guard berlapis:

- `Route middleware`:
  - `permission.route` tetap memverifikasi RBAC per route name.
  - `local.master.restrict` memblokir route tertentu saat `APP_MODE=local_branch`.
- `Sidebar filter`:
  - Menu yang route-nya masuk `sync.local_blocked_route_names` tidak ditampilkan.
  - Tujuan: user tidak melihat menu yang pasti akan ditolak.

### Dampak ke user

- Saat `APP_MODE=local_branch`, role superadmin tetap bisa login, tetapi route master yang diblokir tetap `403`.
- Saat `APP_MODE=cloud_center`, blokir local branch tidak berlaku.

### Sumber aturan blokir

Aturan route yang diblokir didefinisikan di:

- `config/sync.php` -> `local_blocked_route_names`

Contoh pattern:

- `konfigurasi.*`
- `paket.*`
- `template.harga*`
- `sales-mode*`
- `promosi*`
- `persediaan.*`
- `permintaan-barang.*`
- `pemasok.*`
- `pembelian.*`
- `metode-pembayaran*`
- `coa`
- `tax`

### Jika menu masih muncul di local branch

Checklist:

1. Pastikan `APP_MODE=local_branch`.
2. Jalankan:
   - `php artisan config:clear`
   - `php artisan view:clear`
3. Cek route name menu yang dimaksud, lalu pastikan route tersebut tercakup pattern di `local_blocked_route_names`.
