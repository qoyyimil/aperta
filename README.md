# Aperta

Aplikasi pengelola klien untuk studio foto. Klien memesan jadwal sendiri, memantau status booking, memilih foto yang ingin diedit, lalu mengunduh hasil edit setelah lunas. Admin dan tim mengelola semuanya dari dashboard.

| Bagian        | Alamat     | Untuk                                               |
| ------------- | ---------- | --------------------------------------------------- |
| Form booking  | `/booking` | Klien memesan jadwal                                |
| Lacak booking | `/lacak`   | Klien melihat status, memilih foto, mengunduh hasil |
| Dashboard     | `/studio`  | Admin, fotografer, editor                           |

## Teknologi

- **Backend:** Laravel 13, PHP 8.5, PostgreSQL
- **Dashboard:** Filament 5 (Livewire 4)
- **Portal klien:** Inertia 2 + React, Vite 7, Tailwind CSS 4
- **Pengolahan foto:** Intervention Image 4 (driver GD), diproses lewat queue

## Daftar isi

1. [Kebutuhan](#1-kebutuhan)
2. [Siapkan database](#2-siapkan-database)
3. [Pasang proyek](#3-pasang-proyek)
4. [Atur `.env`](#4-atur-env)
5. [Migrasi dan akun admin](#5-migrasi-dan-akun-admin)
6. [Menjalankan](#6-menjalankan)
7. [Tes](#7-tes)
8. [Alur kerja dan aturan bisnis](#8-alur-kerja-dan-aturan-bisnis)
9. [Pemecahan masalah](#9-pemecahan-masalah)
10. [Struktur kode](#10-struktur-kode)

---

## 1. Kebutuhan

| Perangkat  | Versi                                    | Cek dengan       |
| ---------- | ---------------------------------------- | ---------------- |
| PHP        | 8.5 (Windows: build **x64 Thread Safe**) | `php -v`         |
| Composer   | 2.x                                      | `composer -V`    |
| Node.js    | 20 atau lebih baru                       | `node -v`        |
| PostgreSQL | 17 atau lebih baru                       | `psql --version` |

### Ekstensi PHP

Jalankan `php -m` dan pastikan semua ini ada:

```text
pdo_pgsql  pgsql  gd  intl  zip  mbstring  fileinfo  exif  openssl  curl
```

Kalau ada yang belum aktif, buka `php.ini` (lokasinya terlihat di `php --ini`), hapus tanda `;` di depan baris `extension=...` yang sesuai, lalu buka ulang terminal.

> **Penting (Windows):** semua file `.dll` di folder `ext` harus berasal dari paket PHP yang **sama persis** dengan `php.exe`. Mencampur `php_gd.dll` dari versi lain membuat PHP crash dengan error yang aneh (lihat [Pemecahan masalah](#9-pemecahan-masalah)).

### Batas upload

Fotografer mengunggah banyak foto sekaligus. Naikkan batas di `php.ini`:

```ini
upload_max_filesize = 50M
post_max_size = 512M
max_file_uploads = 200
memory_limit = 512M
```

---

## 2. Siapkan database

Masuk ke PostgreSQL sebagai `postgres`:

```bash
psql -U postgres -h 127.0.0.1
```

```sql
CREATE USER aperta WITH PASSWORD 'ganti_password_ini';
CREATE DATABASE aperta OWNER aperta;
CREATE DATABASE aperta_test OWNER aperta;
\q
```

- `aperta`: database untuk pemakaian sehari-hari.
- `aperta_test`: khusus untuk tes. **Tes menghapus seluruh isi database ini setiap kali dijalankan**, jadi jangan pernah menyamakannya dengan `aperta`.

---

## 3. Pasang proyek

```bash
git clone <url-repo> aperta
cd aperta

composer install
npm install

cp .env.example .env        # PowerShell: Copy-Item .env.example .env
php artisan key:generate
```

Referensi komponen UI (dipakai saat membuat tampilan, tidak ikut di repo):

```bash
git clone https://github.com/Deal-Tech/dealtech-ui-for-public-component ref/dealtech-ui
```

---

## 4. Atur `.env`

```env
APP_NAME=Aperta
APP_URL=http://127.0.0.1:8001
APP_LOCALE=id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aperta
DB_USERNAME=aperta
DB_PASSWORD=ganti_password_ini

QUEUE_CONNECTION=database
MAIL_MAILER=log

# Aturan bisnis (opsional, nilai default di config/aperta.php)
APERTA_MIN_DEPOSIT_PERCENT=50
APERTA_FULL_PAYMENT_DAYS_BEFORE=1
APERTA_SELECTION_DAYS=7
APERTA_PHOTO_DISK=local
```

- `APP_URL` **harus sama** dengan alamat yang dibuka di browser, termasuk port. Kalau berbeda, foto di galeri tidak tampil karena tautan bertanda tangan (signed URL) jadi tidak valid.
- `MAIL_MAILER=log` menulis email ke `storage/logs/laravel.log`, bukan mengirimnya. Ganti ke SMTP kalau ingin email benar-benar terkirim.
- Hapus baris `DB_CONNECTION=sqlite` bawaan Laravel kalau masih ada.

### Database untuk tes

Di `phpunit.xml`, bagian `<php>`, pastikan:

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="aperta_test"/>
```

---

## 5. Migrasi dan akun admin

```bash
php artisan config:clear
php artisan migrate
```

Buat akun pertama, lalu jadikan admin. Akun baru otomatis berperan **Fotografer**.

```bash
php artisan make:filament-user
php artisan aperta:admin EMAIL_TADI
```

Akun tim berikutnya dibuat admin dari menu **Pengguna** di dashboard.

### Watermark (opsional)

Ganti file PNG transparan di `resources/aperta.png`. Foto pratinjau untuk klien akan diberi watermark ini. Kalau file tidak ada, pratinjau tetap dibuat tanpa watermark. Hasil edit akhir tidak pernah diberi watermark.

---

## 6. Menjalankan

```bash
composer run dev
```

Perintah ini menyalakan tiga proses sekaligus:

| Nama     | Tugas                                                               |
| -------- | ------------------------------------------------------------------- |
| `server` | Web server di `http://127.0.0.1:8001`                               |
| `queue`  | Memproses foto yang diunggah (pratinjau, thumbnail, watermark)      |
| `vite`   | Membangun tampilan React dan memuat ulang otomatis saat file diubah |

Ketiganya wajib jalan. Tanpa `queue`, foto yang diunggah akan selamanya berstatus "diproses".

Pastikan skrip `dev` di `composer.json` seperti ini (tanpa `pail`, karena `pail` butuh ekstensi `pcntl` yang tidak ada di Windows):

```json
"dev": [
    "Composer\\Config::disableProcessTimeout",
    "npx concurrently -c \"#93c5fd,#c4b5fd,#fdba74\" \"php artisan serve --port=8001\" \"php artisan queue:listen --tries=1 --timeout=0\" \"npm run dev\" --names=server,queue,vite --kill-others"
]
```

### Langkah pertama di dashboard

Buka `http://127.0.0.1:8001/studio`, login sebagai admin, lalu isi:

1. **Paket**: harga, durasi sesi, kuota foto edit, harga foto tambahan.
2. **Add-on** (opsional).
3. **Voucher** (opsional).
4. **Tanggal libur**: tanggal yang tidak bisa dipesan.

Setelah itu form booking di `/booking` siap dipakai.

---

## 7. Tes

```bash
php artisan test
```

Tes memakai database `aperta_test`, bukan `aperta`. Akibatnya, **tes yang lolos tidak menjamin database `aperta` sudah lengkap**. Setelah menarik kode baru, selalu jalankan juga:

```bash
php artisan migrate
```

---

## 8. Alur kerja dan aturan bisnis

```text
Baru → Terkonfirmasi → Sesi Selesai → Memilih Foto → Editing → Selesai
  └──────────┴──→ Dibatalkan
```

| Tahap      | Siapa        | Apa yang terjadi                                                                                                                  |
| ---------- | ------------ | --------------------------------------------------------------------------------------------------------------------------------- |
| Booking    | Klien        | Pilih paket, tanggal, dan jam. Slot yang sudah terisi tidak bisa dipesan dua kali.                                                |
| DP         | Admin        | Catat pembayaran. Status otomatis jadi **Terkonfirmasi** saat DP ≥ 50%.                                                           |
| Pelunasan  | Admin        | Tagihan sesi (paket + add-on − diskon) wajib lunas paling lambat **H-1**. Sebelum lunas, tombol *Tandai sesi selesai* terkunci.   |
| Sesi foto  | Fotografer   | Tandai sesi selesai, unggah semua foto, lalu buka galeri untuk klien.                                                             |
| Pilih foto | Klien        | Pilih foto dan beri catatan edit. Pilihan di atas kuota ditagih sebagai foto tambahan.                                            |
| Editing    | Editor       | Unggah hasil edit. Nama file dicocokkan otomatis dengan foto pilihan klien.                                                       |
| Pengiriman | Editor/Admin | Kirim hasil. Klien bisa melihat semua hasil, tapi **unduhan baru terbuka setelah seluruh tagihan lunas**, termasuk foto tambahan. |

Semua angka aturan ada di `config/aperta.php` dan bisa diubah lewat `.env` tanpa menyentuh kode.

### Peran pengguna

| Peran      | Akses                                                                                   |
| ---------- | --------------------------------------------------------------------------------------- |
| Admin      | Semua menu dan semua booking                                                            |
| Fotografer | Hanya booking yang ditugaskan kepadanya; bisa mengunggah foto dan menandai sesi selesai |
| Editor     | Hanya booking yang ditugaskan kepadanya; bisa mengunggah dan mengirim hasil edit        |

---

## 9. Pemecahan masalah

| Gejala                                                                          | Penyebab                                                                   | Solusi                                                                             |
| ------------------------------------------------------------------------------- | -------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| `could not find driver`                                                         | Ekstensi PostgreSQL belum aktif                                            | Aktifkan `pdo_pgsql` dan `pgsql` di `php.ini`                                      |
| `Connection refused`                                                            | Layanan PostgreSQL mati                                                    | Windows: buka *Services*, nyalakan `postgresql-x64-17`                             |
| `password authentication failed`                                                | Isi `.env` tidak cocok dengan user database                                | Samakan dengan langkah 2, lalu `php artisan config:clear`                          |
| `permission denied for schema public`                                           | Database bukan milik user `aperta`                                           | Sebagai `postgres`: `ALTER DATABASE aperta OWNER TO aperta;`                           |
| `column "..." does not exist`                                                   | Ada migrasi yang belum dijalankan di database `aperta`                       | `php artisan migrate:status`, lalu `php artisan migrate`                           |
| Perubahan `.env` tidak terbaca                                                  | Konfigurasi tersimpan di cache                                             | `php artisan config:clear`                                                         |
| `Class "..." not found` setelah menambah file                                   | Autoload belum diperbarui                                                  | `composer dump-autoload`                                                           |
| Halaman portal klien putih kosong                                               | Vite tidak jalan, atau versi `@inertiajs/react` tidak cocok dengan backend | Pastikan `composer run dev` jalan; versi `@inertiajs/react` harus `~2.3`           |
| `Unable to locate file in Vite manifest`                                        | Vite belum jalan atau belum di-build                                       | Jalankan `npm run dev`, atau `npm run build`                                       |
| `npm ERESOLVE` saat install                                                     | Konflik versi plugin                                                       | `@vitejs/plugin-react` harus `~5.2`                                                |
| Foto tetap "diproses"                                                           | Queue tidak jalan                                                          | Pastikan proses `queue` di `composer run dev` aktif                                |
| Foto di galeri tidak tampil (gambar rusak)                                      | `APP_URL` beda dengan alamat di browser                                    | Samakan `APP_URL`, termasuk port `8001`                                            |
| PHP crash dengan pesan fungsi yang tidak berhubungan (mis. `numfmt_get_locale`) | File `.dll` ekstensi dari versi PHP lain, atau cache opcache basi          | Ganti `.dll` dengan yang satu paket dengan `php.exe`, lalu nyalakan ulang terminal |
| `pcntl` extension required                                                      | `php artisan pail` tidak didukung di Windows                               | Hapus `pail` dari skrip `dev` (lihat langkah 6)                                    |
| Upload gagal untuk file besar                                                   | Batas upload PHP terlalu kecil                                             | Naikkan nilai di `php.ini` (lihat langkah 1)                                       |

---

## 10. Struktur kode

| Lokasi                        | Isi                                                                                              |
| ----------------------------- | ------------------------------------------------------------------------------------------------ |
| `config/aperta.php`             | Angka aturan bisnis: DP, pelunasan, batas waktu, slot jam, kota                                  |
| `app/Enums/BookingStatus.php` | Daftar status dan perpindahan yang sah. Mengubah alur cukup di sini.                             |
| `app/Models/Booking.php`      | Pusat aturan booking: perpindahan status, harga, pembayaran, pilihan foto, hasil edit            |
| `app/Support/`                | Kelas kecil tanpa database: `PriceBreakdown`, `SlotGrid`, `BookingCode`, `ProofImages`, `Rupiah` |
| `app/Actions/`                | Satu kelas per proses: `PlaceBooking`, `AddProofs`, `AddResults`                                 |
| `app/Jobs/RenderPhoto.php`    | Membuat pratinjau dan thumbnail di queue, untuk foto mentah maupun hasil edit                    |
| `app/Http/Controllers/`       | Halaman portal klien                                                                             |
| `app/Http/Presenters/`        | Menyiapkan data untuk halaman React                                                              |
| `app/Filament/Resources/`     | Menu dashboard                                                                                   |
| `app/Policies/`               | Siapa boleh melakukan apa                                                                        |
| `resources/js/Pages/`         | Halaman React portal klien                                                                       |
| `tests/`                      | Tes aturan bisnis dan alur lengkap                                                               |

### Aturan menulis kode

- Status booking **hanya** berubah lewat `$booking->transitionTo(...)`, yang otomatis memeriksa aturan dan mencatat riwayat.
- Harga dan kuota paket disalin ke booking saat dibuat, jadi mengubah paket tidak memengaruhi booking lama.
- Uang disimpan sebagai bilangan bulat rupiah, tanpa desimal.
- Angka aturan bisnis ditaruh di `config/aperta.php`, bukan ditulis langsung di kode.
- Foto disimpan di disk privat dan hanya bisa diakses lewat tautan bertanda tangan yang kedaluwarsa.