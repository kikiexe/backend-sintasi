# SINTASI - Backend Service (Laravel)

REST API Backend untuk sistem informasi manajemen pasien dan kunjungan klinik SINTASI.

---

## 1. Fitur Utama

- **Database MySQL Support**: Dibuat menggunakan Laravel Migration standar yang kompatibel penuh dengan MySQL (sesuai spesifikasi soal) dan menyertakan fallback SQLite untuk pengujian zero-setup yang cepat.
- **Autentikasi Aman**: Login berbasis token menggunakan Laravel Sanctum.
- **Security Test Payload Encryption**: Mendukung enkripsi AES-256-CBC pada payload `username`/`email` dan `password` saat dikirim dari client (didekripsi otomatis via Middleware).
- **Security Test Database Encryption**: Enkripsi kolom `nik` dan `email` di database menggunakan fitur Laravel Eloquent Encrypted Casts.
- **Pendaftaran Pasien**: Pembuatan Nomor Rekam Medik otomatis berurutan dengan format `RM0001`, `RM0002`, dst.
- **Pembuatan Kunjungan**: Cukup memasukkan Nomor Rekam Medik pasien, riwayat biodata otomatis terhubung dan jumlah kunjungan terakumulasi.

---

## 2. Persyaratan Sistem

- PHP >= 8.2 (ekstensi `openssl`, `mbstring`, serta `pdo_mysql` atau `pdo_sqlite`)
- Composer >= 2.0
- MySQL Server >= 5.7 / 8.0 (atau SQLite untuk zero-setup lokal)

---

## 3. Cara Menjalankan Backend

1. **Install Dependencies**:
   ```bash
   composer install
   ```

2. **Setup File Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   * **Opsi A (MySQL - Sesuai Spesifikasi)**:
     Sesuaikan kredensial di file `.env`:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=sintasi_db
     DB_USERNAME=root
     DB_PASSWORD=
     ```
   * **Opsi B (SQLite - Instant Zero-Setup tanpa Server DB)**:
     ```env
     DB_CONNECTION=sqlite
     ```

3. **Jalankan Migrasi & Seeder**:
   ```bash
   php artisan migrate --seed
   ```

4. **Jalankan Server Development**:
   ```bash
   php artisan serve --port=8000
   ```
   API akan berjalan di `http://127.0.0.1:8000`.

---

## 4. Akun Login Default (Seeder)

Data pengguna telah disediakan otomatis via Seeder:
- **Email**: `admin@sintasi.com`
- **Password**: `password123`

Akun kedua (petugas):
- **Email**: `petugas@sintasi.com`
- **Password**: `password123`

---

## 5. Postman Collection

File Postman Collection v2.1 telah disertakan di root repositori:
- **Path File**: `./postman_collection.json`

### Cara Import ke Postman:
1. Buka aplikasi Postman.
2. Klik tombol **Import** di kiri atas.
3. Pilih atau drag-and-drop file `postman_collection.json`.
4. Seluruh endpoint autentikasi, registrasi pasien, dan pembuatan kunjungan siap diuji.

---

## 6. Menjalankan Automated Tests

Seluruh skenario fungsional dan keamanan diuji dengan PHPUnit:
```bash
php artisan test
```
Semua test menguji validasi login plaintext, login terenkripsi AES-256-CBC, auto format RM000X, dan akumulasi kunjungan.
