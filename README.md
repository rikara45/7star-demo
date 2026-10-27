# 7Star — demo publik

Repo ini adalah **versi demo** dari aplikasi 7Star (sistem penilaian kepemimpinan dosen,
Laravel 12). Aplikasi aslinya butuh tiga peran (admin, jurusan, dosen) plus penilai eksternal
untuk menjalankan satu workflow, jadi untuk ditampilkan sebagai portofolio dibuat versi yang
bisa dicoba tanpa registrasi.

Situs demo: **https://7star.rioeka.com**

## Bedanya dengan aplikasi asli

| Aspek | Aplikasi asli | Repo demo ini |
|---|---|---|
| Database | MySQL, diisi manual | SQLite, otomatis dibuat + di-reset tiap deploy |
| Masuk | Registrasi + verifikasi email, 3 peran | `/demo` → tombol "masuk sebagai" per peran, tanpa password |
| Data | Data nyata | Data contoh (nama & instansi fiktif), dosen dengan penilaian 360 lengkap |
| Narasi AI | Gemini 2.5 Flash (butuh `GEMINI_API_KEY`) | Disimulasikan: teks contoh, tidak memanggil API |
| Registrasi | Terbuka | Ditutup (403) |
| Aset front-end | `npm run build` | `public/build` sudah ter-commit |

Perubahan kode dibatasi pada beberapa tempat:

- `app/Http/Controllers/DemoController.php` + `resources/views/demo/index.blade.php` — halaman pilih peran.
- `database/seeders/DemoSeeder.php` — data contoh; skor dihitung dengan rumus yang sama seperti
  `UserDashboardController::calculateRealtimeScores()` supaya angka di halaman hasil konsisten.
- `UserDashboardController::generateResult()` — blok `if (config('app.demo_mode'))` yang mengisi
  narasi contoh sebelum panggilan Gemini.
- `config/app.php` (`demo_mode`), `routes/web.php`, `Auth/RegisteredUserController` — saklar demo.

Semua perubahan itu tidak aktif kalau `DEMO_MODE=false`, jadi alur aplikasi aslinya tetap utuh.

## Data contoh

- Admin: Rina Kusuma — `admin@demo.7star.test`
- Jurusan: Dr. Bagas Prasetyo — `jurusan@demo.7star.test`
- Dosen (lengkap): Dr. Ayu Lestari — `dosen@demo.7star.test`
- Dosen (portofolio menunggu verifikasi): Rangga Wijaya, M.Kom. — `dosen2@demo.7star.test`
- Dosen (state awal kosong): Sinta Maharani, M.T. — `dosen3@demo.7star.test`

Password semua akun `password`, tapi tidak diperlukan karena masuk lewat `/demo`.
Satu tautan penilai tanpa login juga disiapkan (token `demo-penilai-dosen2-superior-7star`)
dan ditampilkan di halaman `/demo`.

## Menjalankan lokal

```bash
cp .env.example .env
php artisan key:generate
touch database/demo.sqlite
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Atau lewat Docker (sama seperti yang dipakai di VPS):

```bash
docker build -t 7star-demo .
docker run -p 8080:80 7star-demo
```

Entrypoint container menjalankan `migrate:fresh --seed` setiap kali start, jadi demo selalu
kembali ke data contoh.

## Deploy

Dockerfile di root (php:8.3-apache + SQLite). Di Coolify: build pack **Dockerfile**,
port **80**, domain `https://7star.rioeka.com`. Tidak ada environment variable wajib —
default demo ada di `.env.example` yang disalin entrypoint.
