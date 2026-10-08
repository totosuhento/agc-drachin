# AGC Drama YT

Script AGC (Auto Generated Content) PHP untuk situs drama pendek. Isinya diambil otomatis dari **channel YouTube resmi** lewat YouTube Data API, lalu diputar dengan **embed resmi YouTube**. Tidak ada video yang di-download atau di-host, sehingga risiko DMCA jauh lebih kecil dibanding situs streaming bajakan, dan lebih cocok untuk AdSense/Adsterra.

## Fitur

- Ambil video otomatis dari channel resmi (cukup isi `@handle`), termasuk backfill video lama sedikit demi sedikit
- Playlist channel otomatis jadi halaman **Serial** dengan episode berurutan + tombol episode berikutnya
- Filter otomatis: Shorts/teaser, video privat, video 18+, dan video yang tidak boleh di-embed disembunyikan
- Video yang dihapus pemiliknya otomatis hilang dari situs (tidak ada embed rusak)
- SEO: URL cantik, canonical, Open Graph, schema `VideoObject`, `BreadcrumbList`, `ItemList`, sitemap terpisah per 5.000 URL, robots.txt
- Deskripsi unik buatan AI (opsional; Anthropic atau API OpenAI-compatible)
- 6 slot iklan (head, top, below_player, in_list, sidebar, footer)
- Halaman About, Privacy Policy (sudah memuat klausul cookie iklan & YouTube API), Disclaimer, Contact: syarat approval AdSense
- Pemutar "klik untuk putar" (thumbnail dulu, iframe setelah diklik): halaman jauh lebih cepat
- Bahasa tampilan: Inggris (`en`) atau Indonesia (`id`)
- SQLite (tanpa MySQL), cache halaman, hemat kuota API (tidak memakai `search.list`)

> **Pakai hosting Docker/container (mis. Nusapod)?** Ikuti panduan **DOCKER.md**, bukan langkah VPS di bawah.

## Kebutuhan

- VPS Ubuntu 22.04/24.04, RAM 1 GB sudah cukup
- PHP 8.1+ dengan ekstensi `pdo_sqlite`, `curl`, `mbstring` (`intl` opsional, untuk slug yang lebih rapi)
- Nginx (atau Apache)
- Domain yang sudah diarahkan ke IP VPS
- YouTube Data API key (gratis)

## 1. Siapkan YouTube API key

1. Buka https://console.cloud.google.com → buat project baru.
2. **APIs & Services → Library** → cari **YouTube Data API v3** → **Enable**.
3. **APIs & Services → Credentials → Create credentials → API key**.
4. (Disarankan) Klik key tersebut → **API restrictions** → pilih *YouTube Data API v3* saja.

Kuota gratis 10.000 unit/hari. Script ini memakai sekitar 30–80 unit per jalan cron, jadi cron per jam aman.

## 2. Install di VPS

```bash
# Paket
sudo apt update
sudo apt install -y nginx php-fpm php-sqlite3 php-curl php-mbstring php-intl unzip

# Upload & ekstrak script
sudo mkdir -p /var/www/agc-drama-yt
sudo unzip agc-drama-yt.zip -d /var/www/
cd /var/www/agc-drama-yt

# Konfigurasi
sudo cp config.sample.php config.php
sudo nano config.php        # isi site.url, api_key, channels, iklan

# Izin folder storage (database, cache, log)
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

## 3. Konfigurasi Nginx

Buat `/etc/nginx/sites-available/agc-drama-yt` (ganti `domainanda.com` dan versi PHP sesuai `ls /run/php/`):

```nginx
server {
    listen 80;
    server_name domainanda.com www.domainanda.com;
    root /var/www/agc-drama-yt/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~* \.(css|js|png|jpg|svg|ico|woff2)$ {
        expires 30d;
        access_log off;
    }

    location ~ /\. { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/agc-drama-yt /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# HTTPS gratis
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d domainanda.com -d www.domainanda.com
```

Root web **harus** folder `public/`, supaya `config.php` dan database tidak bisa diakses dari luar.

## 4. Ambil konten pertama kali

```bash
cd /var/www/agc-drama-yt
sudo -u www-data php cron/fetch.php     # ambil video (beberapa menit)
sudo -u www-data php cron/status.php    # cek hasil
```

Buka domain Anda. Video dan serial sudah tampil.

## 5. Pasang cron (otomatis)

```bash
sudo crontab -u www-data -e
```

Tambahkan:

```
7 * * * *    php /var/www/agc-drama-yt/cron/fetch.php  > /dev/null 2>&1
*/30 * * * * php /var/www/agc-drama-yt/cron/enrich.php > /dev/null 2>&1
```

`enrich.php` hanya bekerja bila `ai.enabled = true`.

Log tersimpan di `storage/logs/cron.log`.

## Memilih channel

- Pakai hanya channel **resmi** milik studio/aplikasi (biasanya bercentang verifikasi, ditautkan dari website/aplikasi resminya).
- Channel reupload/kompilasi milik orang lain jangan dimasukkan, karena tetap berisiko klaim hak cipta.
- Contoh di `config.sample.php`: `@reelshortapp` dan `@dramaboxapp` (ditautkan dari deskripsi aplikasi resmi mereka). Cek dulu sebelum dipakai.
- Untuk target CPM tinggi, pilih channel berbahasa Inggris dan set `site.lang` ke `en`.

## Tips agar diterima AdSense & bertahan di Google

- **Aktifkan deskripsi AI** dan biarkan beberapa hari sampai sebagian besar video punya teks unik. Halaman yang isinya hanya embed video sering ditolak sebagai *low value content*.
- Mulai dengan 1–3 channel, jangan langsung puluhan ribu halaman. Google bisa menganggap lonjakan halaman otomatis sebagai *scaled content abuse*.
- Isi email kontak yang aktif dan tanggapi permintaan penghapusan konten.
- Submit `https://domainanda.com/sitemap.xml` di Google Search Console.

## Kepatuhan YouTube API

Script ini sudah menyesuaikan kebijakan YouTube API Services:
- Video diputar lewat embed player resmi (tidak di-download).
- Data video di-refresh terus; data yang tidak aktif dihapus setelah 30 hari.
- Privacy Policy memuat tautan ke YouTube Terms of Service & Google Privacy Policy.

## Struktur folder

```
app/            logika (fetcher, database, tampilan, bahasa)
cron/           fetch.php, enrich.php, status.php
public/         root web (index.php, assets/)
storage/        database.sqlite, cache/, logs/  (harus bisa ditulis www-data)
config.php      konfigurasi Anda
```

## Uji di komputer lokal

```bash
cp config.sample.php config.php   # isi api_key & channels, set site.url ke http://127.0.0.1:8080
php cron/fetch.php
php -S 127.0.0.1:8080 -t public public/index.php
```

## Masalah umum

| Gejala | Solusi |
|---|---|
| `Channel tidak ditemukan` | Cek ejaan handle (termasuk huruf besar/kecil) atau pakai ID channel `UC…` |
| `YouTube API 403 (forbidden)` | API belum di-enable atau key dibatasi ke API lain |
| `kuota habis` | Tunggu reset (tengah malam waktu Pasifik ≈ 14.00/15.00 WIB) atau kurangi `refresh_per_run` |
| Halaman 500 / database error | Folder `storage` belum bisa ditulis: `sudo chown -R www-data:www-data storage` |
| Video sudah tampil tapi tidak bisa diputar | Pemilik menonaktifkan embed; otomatis disembunyikan pada refresh berikutnya |
