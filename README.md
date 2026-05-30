```markdown
# 🛒 Lapakku Backend - Multi-Tenant SaaS E-Commerce Platform

Selamat datang di repositori utama **Lapakku Backend**. Proyek ini dirancang sebagai platform **Software-as-a-Service (SaaS) Multi-Tenant E-Commerce** berbasis `store_id` untuk membedakan data antar-tenant secara dinamis, dibangun menggunakan ekosistem **Laravel 11**, **Laravel Sanctum**, dan database NoSQL **MongoDB**.

Aplikasi backend ini dikembangkan oleh **Izdihar Izzan Wibowo** sebagai core engine Tugas Akhir Semester 4.

---

## ⚡ Alur Arsitektur Sistem (SaaS Workflow)

Sistem ini memisahkan pengguna menjadi dua entitas utama dengan jalur akses yang terisolasi dengan ketat:

### 1. Alur Tenant (Penjual / Pemilik Toko) -> *Wajib Bearer Token*
* **Registrasi & Login:** Pengguna mendaftarkan akun utama dan melakukan login untuk mendapatkan Bearer Token keamanan via Laravel Sanctum.
* **Setup Lapak (2 Langkah):**
  * **Step 1:** Mengisi data profil dasar (Nama Toko, Alamat, Deskripsi) beserta pengajuan domain unik (`gratis` atau `custom`).
  * **Step 2:** Memilih durasi berlangganan layanan SaaS (1-5 tahun), menentukan metode pembayaran, dan mengunggah berkas fisik bukti transfer. Status toko otomatis terekam sebagai `pending` menunggu verifikasi admin.
* **Manajemen Etalase Dashboard:** Tenant yang terautentikasi dan memiliki toko dapat mengelola konten marketing (Hero Slider Banner), dropdown kategori (Kategori + Foto Ikon), serta melakukan CRUD penuh pada Produk. Setiap data yang dimasukkan otomatis mengunci `toko_id` milik mereka di latar belakang.

### 2. Alur Pembeli / Pelanggan Umum -> *Public Access (Tanpa Token)*
* **Storefront Consumption:** Pembeli umum dapat mengakses etalase depan toko milik siapa saja secara bebas murni memanfaatkan parameter `toko_id` di URL API (untuk memuat Hero Banner, Dropdown Kategori, dan Daftar Produk Toko terkait).
* **Guest Checkout:** Pembeli umum dapat melakukan transaksi pembelian secara langsung tanpa perlu mendaftarkan akun (*Guest Checkout*). Sistem hanya meminta masukan Email, Nama, dan Alamat Pengiriman pada payload pesanan. Begitu checkout sukses, database NoSQL otomatis mengurangi jumlah stok produk terkait secara *real-time*.

---

## 🛠️ Panduan Instalasi & Konfigurasi Awal (Dari Nol)

Ikuti langkah-langkah berikut untuk menjalankan engine API Lapakku di komputer lokal baru:

### 1. Persyaratan Sistem (Prerequisites)
* PHP >= 8.2 (Disarankan menggunakan web server bundle **Laragon** di Windows).
* **Composer** terinstal global di sistem.
* Akun atau Kluster **MongoDB Atlas** aktif (Atau MongoDB Community Server Lokal).

### 2. Langkah Kloning & Instalasi Vendor
Buka terminal/command prompt, masuk ke folder root server kamu (misal `C:\laragon\www\`), lalu jalankan perintah:
```bash
# Kloning repositori ini
git clone [https://github.com/username-kamu/lapakku-backend.git](https://github.com/username-kamu/lapakku-backend.git)
cd lapakku-backend

# Install seluruh dependency package vendor php via composer
composer install

```

### 3. Pengaturan Environment (`.env`)

Duplikat file `.env.example` menjadi `.env`, lalu cari dan sesuaikan baris konfigurasi database menuju MongoDB Atlas URI kamu:

```env
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=lapakku
DB_URI=mongodb+srv://username_atlas:password_atlas@cluster0.xxxx.mongodb.net/lapakku?retryWrites=true&w=majority

```

### 4. Membersihkan & Menyalakan Mesin Server

Gunakan rangkaian perintah ini untuk memicu pembersihan cache RAM Laravel dan menyalakan server lokal:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear

# Jalankan server lokal
php artisan serve

```

API kini dapat diakses penuh melalui alamat lokal default: `http://127.0.0.1:8000/api/`

---

## 📑 Kamus Dokumentasi API Endpoint (Untuk Frontend)

Semua request yang mengarah ke rute **Dashboard Tenant Area** wajib menyertakan:

* **Headers:** `Accept: application/json`
* **Authorization:** `Bearer <TOKEN_HASIL_LOGIN>`

### 🔓 1. Endpoint Autentikasi & Setup SaaS (Tenant Area)

* **Register Akun Penjual:** `POST /api/register`
* *Payload (JSON):* `nama_lengkap`, `email`, `password`


* **Login Akun Penjual:** `POST /api/login`
* *Payload (JSON):* `email`, `password`
* *Response:* Mengembalikan objek data akun serta status boolean `has_shop` untuk penentuan rute di Frontend.


* **Setup Toko Step 1 (Profil & Domain):** `POST /api/store/setup/step1`
* *Payload (JSON):* `nama_toko`, `deskripsi`, `alamat`, `tipe_domain` (`gratis`/`custom`), `nama_domain`.


* **Setup Toko Step 2 (Billing & Upload Bukti):** `POST /api/store/setup/step2`
* *Payload (⚠️ Wajib `form-data`):* `durasi_layanan` (integer 1-5), `metode_pembayaran` (`QRIS`/`transfer_bank`), `bukti_pembayaran` (File Gambar).



### 🔒 2. Endpoint Manajemen Dashboard (Tenant Area)

* **Tambah Dropdown Kategori:** `POST /api/kategori` (Body: `form-data` -> `nama_kategori`, `foto_icon` [File]).
* **Tambah Slider Banner Hero:** `POST /api/hero` (Body: `form-data` -> `gambar_hero` [File], `judul`, `deskripsi`, `link_tujuan` [Opsional]).
* **Tambah Galeri Multi-Foto Produk:** `POST /api/gallery` (Body: `form-data` -> `produk_id`, `foto_path` [File]).
* **CRUD Utama Produk:**
* **Create:** `POST /api/produk` (Body: `form-data` -> `kategori_id`, `nama_produk`, `deskripsi`, `harga`, `stok`, `gambar_produk` [File]).
* **Read All:** `GET /api/produk`
* **Read Single Detail:** `GET /api/produk/{id}`
* **Update:** `POST /api/produk/{id}` (Body: `form-data` -> masukkan seluruh field kembali, unggah `gambar_produk` baru jika ingin mengganti thumbnail).
* **Delete:** `DELETE /api/produk/{id}`


* **Manajemen Pesanan Masuk:**
* **Melihat Daftar Orderan:** `GET /api/store/orders`
* **Ubah Status Pesanan:** `POST /api/store/orders/{id}/status` (Body JSON: `status_pesanan` -> pilihan: `pending`, `diproses`, `selesai`, `dibatalkan`).



### 🌐 3. Endpoint Storefront Pelanggan Umum (Public Area - Tanpa Token)

* **Ambil Banner Slider Toko:** `GET /api/store/{toko_id}/hero`
* **Ambil Semua Dropdown Kategori Toko:** `GET /api/store/{toko_id}/kategori`
* **Ambil Semua Produk Etalase Toko:** `GET /api/store/{toko_id}/produk`
* **Ambil Detail Multi-Foto Galeri 1 Produk:** `GET /api/produk/{produk_id}/gallery`
* **Proses Checkout Belanja (Guest Checkout):** `POST /api/checkout`
* *Payload (JSON):*



```json
    {
        "toko_id": "ID_TOKO_YANG_BERSANGKUTAN",
        "nama_pembeli": "John Doe",
        "email_pembeli": "johndoe@gmail.com",
        "alamat_pengiriman": "Jl. Raya Bogor No. 12",
        "items": [
            { "produk_id": "ID_PRODUK_A", "qty": 2 },
            { "produk_id": "ID_PRODUK_B", "qty": 1 }
        ]
    }
    ```

---

## 🤖 INSTRUKSI KHUSUS PENGEMBANGAN FRONTEND (FE)

Dear Frontend Developer partner! Backend untuk sistem SaaS Lapakku sudah rampung sempurna dan diuji coba stabil. Seluruh aset gambar hasil upload langsung diarahkan ke direktori `public/uploads/` sehingga kamu bisa memanggil file visual secara langsung tanpa memerlukan konfigurasi symlink yang rawan terputus di lokal.

Untuk mempercepat pengerjaan UI/UX, konsumsi komponen, manajemen state, hingga konektivitas Axios/Fetch di sisi klien, kamu bisa menyalin instruksi prompt AI di bawah ini ke asisten kecerdasan buatan pilihanmu (ChatGPT/Claude/Gemini) untuk memandu langkah kodingmu secara instan.

### 📋 COPY-PASTE PROMPT BERIKUT KE AI KAMU:
```text
Halo AI! Saya sedang membangun sisi Frontend untuk proyek aplikasi bernama "Lapakku", sebuah platform SaaS E-Commerce Multi-Tenant berbasis arsitektur Laravel Sanctum dan MongoDB. Mitra Backend saya telah merampungkan seluruh API Endpoint-nya dan sistem disimpan secara lokal di alamat base URL: [http://127.0.0.1:8000/api/](http://127.0.0.1:8000/api/).

Tolong bertindak sebagai Arsitek Senior Frontend Ekspert. Bantu saya menyusun komponen UI, fungsi integrasi API (misalnya menggunakan Axios), manajemen state, serta penanganan rute navigasi berdasarkan aturan main backend berikut:

1. AUTENTIKASI & ALUR TENANT (DASHBOARD):
   - Pasca login (/api/login), backend mengembalikan data akun dan sebuah nilai boolean 'has_shop'.
   - Jika 'has_shop' berstatus FALSE, arahkan pengguna secara otomatis ke halaman Setup Toko 2 Langkah (/store/setup/step1 lalu step2).
   - Jika 'has_shop' berstatus TRUE, izinkan pengguna langsung melenggang masuk ke Dashboard Utama Tenant.
   - Ingat, rute Step 2 (/api/store/setup/step2), penambahan kategori, penambahan hero, galeri, serta penambahan/pembaruan produk WAJIB dikirim menggunakan tipe format 'form-data' karena melibatkan proses upload file fisik gambar. Jangan mengirimkan tipe raw JSON untuk rute-rute tersebut.
   - Sediakan fungsi interceptor token Axios untuk menyuntikkan 'Bearer <token>' ke dalam Headers 'Authorization' di setiap rute dashboard tenant.

2. ALUR STOREFRONT PUBLIK (PELANGGAN):
   - Halaman utama toko pembeli bersandar sepenuhnya pada parameter string 'toko_id'.
   - Buat komponen etalase publik yang memicu fungsi 'GET' secara paralel tanpa token (No Auth) saat halaman dimuat: menarik data slider banner via /api/store/{toko_id}/hero, memuat daftar kategori via /api/store/{toko_id}/kategori, dan menarik daftar produk via /api/store/{toko_id}/produk.
   - Buat skema keranjang belanja lokal (bisa memanfaatkan LocalStorage) untuk menampung array dari produk_id dan kuantitas (qty) barang yang dipilih pembeli.
   - Pada halaman formulir Checkout Pembeli, buat masukan teks biasa (Nama, Email, Alamat Pengiriman) untuk dieksekusi menggunakan metode Guest Checkout. Kirim data tersebut bersama isi array keranjang belanja sebagai payload mentah JSON menuju endpoint /api/checkout.

Sekarang, tolong buatkan saya draf struktur folder proyek frontend yang rapi, contoh fungsi manajemen otentikasi login lengkap dengan penanganan kondisi 'has_shop', serta file service Axios terisolasi untuk menangani request form-data upload produk dan checkout pesanan pelanggan secara detail!

```


Develop with:
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
