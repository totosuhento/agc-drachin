# Install di hosting Docker (Nusapod, dll.)

Hosting container seperti Nusapod tidak memberi akses VPS biasa. Script dijalankan sebagai **image Docker**.
Image ini sudah berisi PHP + Apache + cron internal, jadi Anda tidak perlu menginstal apa pun di server.

Alurnya: **file script → GitHub → image Docker (dibuat otomatis) → dijalankan di Nusapod**.

---

## Langkah 1 — Upload script ke GitHub (sekali saja)

1. Daftar/login di https://github.com → klik **New repository**.
   - Nama: `agc-drama-yt`
   - Pilih **Private** atau **Public** (bebas) → **Create repository**.
2. Klik **uploading an existing file**, lalu seret **seluruh isi** folder `agc-drama-yt` hasil ekstrak zip.
   - Pastikan folder **`.github`** ikut ter-upload. Di Mac, folder berawalan titik tersembunyi: tekan `Cmd + Shift + .` di Finder untuk menampilkannya.
3. Klik **Commit changes**.

## Langkah 2 — Image Docker dibuat otomatis

1. Buka tab **Actions** di repository. Workflow **Build Docker image** akan berjalan sendiri (±3 menit) dan berubah hijau ✅.
   - Kalau tidak jalan: klik **Build Docker image → Run workflow**.
2. Image Anda sekarang ada di: `ghcr.io/USERNAME-GITHUB/agc-drama-yt:latest` (huruf kecil semua).
3. Agar Nusapod bisa mengunduhnya tanpa login:
   - Buka profil GitHub → tab **Packages** → `agc-drama-yt` → **Package settings** → **Change visibility** → **Public**.
   - (Jika Nusapod mendukung *private registry*, image boleh tetap privat: isi username GitHub + *Personal Access Token* dengan izin `read:packages`.)

> Alternatif tanpa GitHub (perlu Docker Desktop di komputer):
> ```bash
> docker build -t USERNAME_DOCKERHUB/agc-drama-yt:latest .
> docker push USERNAME_DOCKERHUB/agc-drama-yt:latest
> ```

## Langkah 3 — Jalankan di Nusapod

Di panel Nusapod, buat container/aplikasi baru dengan pengaturan berikut (nama menu bisa sedikit berbeda):

| Pengaturan | Isi |
|---|---|
| Image | `ghcr.io/USERNAME-GITHUB/agc-drama-yt:latest` |
| Port container | `80` |
| **Persistent volume / storage** | mount ke **`/var/www/html/storage`** (min. 1 GB) |
| RAM | 512 MB disarankan (256 MB masih bisa) |
| Domain | domain Anda + aktifkan SSL/HTTPS |

⚠️ **Volume wajib.** Tanpa persistent volume, database ikut hilang setiap container di-restart/di-deploy ulang, dan semua video harus diambil ulang (boros kuota API).

### Environment variables

Isi di menu *Environment / Variables*:

| Nama | Wajib | Contoh / keterangan |
|---|---|---|
| `SITE_URL` | ✅ | `https://domainanda.com` |
| `YOUTUBE_API_KEY` | ✅ | API key YouTube Data API v3 |
| `CONTACT_EMAIL` | ✅ | `admin@domainanda.com` |
| `SITE_NAME` | | `ShortDrama Hub` |
| `SITE_TAGLINE` | | kosong = otomatis sesuai niche & bahasa |
| `SITE_LANG` | | `en` (US/UK), `de` (Jerman) atau `id` |
| `SITE_NICHE` | | topik situs (jamak), mis. `Software-Tutorials`. Kosong = short dramas |
| `SITE_ITEM` | | satu konten, mis. `Tutorial` |
| `YOUTUBE_INCLUDE` | | hanya ambil video yang judulnya memuat salah satu kata ini, dipisah koma: `tutorial,anleitung,tipps` |
| `YOUTUBE_EXCLUDE` | | buang video yang judulnya memuat kata ini: `trailer,livestream,#shorts` |
| `LEGAL_NAME` | wajib utk `de` | nama untuk Impressum |
| `LEGAL_ADDRESS` | wajib utk `de` | alamat Impressum, baris dipisah ` \| `: `Musterstr. 1 \| 10115 Berlin \| Deutschland` |
| `YOUTUBE_CHANNELS` | | dipisah koma: `@reelshortapp,@dramaboxapp` |
| `MIN_DURATION` | | `120` (detik; sembunyikan Shorts) |
| `AI_ENABLED` | | `true` untuk deskripsi unik |
| `AI_PROVIDER` | | `anthropic` atau `openai` |
| `AI_API_KEY` | | API key AI |
| `AI_MODEL` | | `claude-haiku-5-5` |
| `ADS_HEAD`, `ADS_TOP`, `ADS_BELOW_PLAYER`, `ADS_IN_LIST`, `ADS_SIDEBAR`, `ADS_FOOTER` | | kode iklan (HTML) |
| `ANALYTICS` | | kode Google Analytics |
| `FETCH_INTERVAL` | | `3600` (detik antar pengambilan video) |
| `PORT` | | isi hanya jika Nusapod mewajibkan port selain 80 |

Kode iklan yang panjang juga bisa disimpan sebagai file di volume: `storage/ads/top.html`, `storage/ads/below_player.html`, dst. (jika Nusapod punya file manager/console).

Ingin mengatur semuanya lewat file? Salin `config.sample.php` ke `storage/config.php` di volume; file itu akan dipakai dan environment variables diabaikan.

## Langkah 4 — Cek hasil

- Buka **Logs** container. Sekitar 20 detik setelah start akan muncul:
  `Channel diperbarui: ...` lalu `Selesai. Unit API terpakai ...`
- Buka domain Anda — video & serial sudah tampil.
- Ambil video berjalan otomatis setiap jam (cron internal), tidak perlu setting crontab.
- Jika ada console: `php /var/www/html/cron/status.php` untuk ringkasan.

## Situs kedua dari image yang sama (contoh: tutorial software berbahasa Jerman)

Satu image bisa dipakai untuk banyak situs. Buat **container baru** di Nusapod dengan image yang sama, **volume baru** (jangan berbagi volume dengan situs drama), lalu isi environment berbeda:

```
SITE_URL=https://domain-jerman-anda.de
SITE_NAME=Tutorial Hafen
SITE_LANG=de
SITE_NICHE=Software-Tutorials
SITE_ITEM=Tutorial
YOUTUBE_API_KEY=...            (boleh key yang sama; kuota dibagi)
YOUTUBE_CHANNELS=@handle1,@handle2,@handle3
YOUTUBE_INCLUDE=tutorial,anleitung,so geht,erklärt,tipps
YOUTUBE_EXCLUDE=trailer,livestream,werbung
CONTACT_EMAIL=...
LEGAL_NAME=...
LEGAL_ADDRESS=Straße 1 | PLZ Stadt | Land
```

Situs berbahasa Jerman otomatis menampilkan **Impressum** dan **Datenschutz** versi Jerman. Teks tersebut hanya template, bukan nasihat hukum; cek ulang (misalnya dengan generator Datenschutz/Impressum Jerman) sebelum daftar AdSense. Untuk AdSense di Uni Eropa, aktifkan juga pesan persetujuan cookie (CMP) di menu **Privasi & pesan** pada akun AdSense.

## Update script nanti

Upload file baru ke GitHub → Actions membuat image baru → di Nusapod klik **Redeploy / Pull latest image**. Data di volume tetap aman.

## Masalah umum

| Gejala | Solusi |
|---|---|
| Nusapod gagal pull image | Visibility package belum **Public**, atau nama image tidak huruf kecil semua |
| Situs tampil tapi kosong | Lihat logs; biasanya `YOUTUBE_API_KEY` salah / API belum di-enable |
| Isi hilang setelah redeploy | Volume belum di-mount ke `/var/www/html/storage` |
| Link/canonical mengarah ke `localhost` | `SITE_URL` belum diisi |
| 502 / tidak bisa diakses | Port container di panel tidak sama dengan port Apache (default 80; atau isi `PORT`) |
