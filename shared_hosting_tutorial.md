# Tutorial: Menampilkan Data Excel di Shared Hosting

Jika data Excel tidak muncul di shared hosting, biasanya masalahnya ada pada **lokasi file** atau **cache**. Ikuti panduan ini langkah demi langkah.

## 1. Pastikan Lokasi File Excel Tepat
Di server lokal, file `export_sipw.xlsx` ada di root folder project. Di shared hosting, struktur folder seringkali berbeda (folder `public` dipisah).

*   **Standard Laravel:** File harus ada di folder root aplikasi (sejajar dengan `app`, `config`, `.env`).
*   **Cek Posisi:** Upload file `export_sipw.xlsx` ke folder utama project Laravel Anda di hosting (biasanya di luar folder `public_html` jika Anda memisahkan core Laravel).

## 2. Script Pengecekan Server (Wajib Coba)
Jangan menebak-nebak. Buat satu file PHP test di folder `public` atau `public_html` Anda untuk mengecek apakah server bisa membaca file tersebut.

1.  Buat file baru bernama `cek_excel.php` di folder `public_html` (atau folder public akses web Anda).
2.  Isi dengan code berikut:

```php
<?php
// Letakkan file ini di folder public_html
// Sesuaikan path ke autoload.php dan file excel

// A. Jika struktur folder standard (semua di public_html)
// $base_path = __DIR__ . '/..';

// B. Jika folder public dipisah (public_html & project_laravel)
// Gunakan path relatif naik ke atas. Sesuaikan "../gcsbr" dengan nama folder project Anda.
$base_path = realpath(__DIR__ . '/../'); 

require $base_path . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file_name = 'export_sipw.xlsx';
$path = $base_path . '/' . $file_name;

echo "<h1>Diagnosa File Excel</h1>";
echo "Current Dir: " . __DIR__ . "<br>";
echo "Target Base Path: " . $base_path . "<br>";
echo "Target File Path: " . $path . "<br>";

if (file_exists($path)) {
    echo "<h3 style='color:green'>✅ FILE DITEMUKAN!</h3>";
    try {
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $row = $worksheet->getRowIterator()->current();
        echo "Berhasil membaca file Excel menggunakan library PhpSpreadsheet.<br>";
        echo "Jumlah Sheet: " . $spreadsheet->getSheetCount();
    } catch (Exception $e) {
        echo "<h3 style='color:red'>❌ FILE ADA TAPI ERROR DIBACA</h3>";
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "<h3 style='color:red'>❌ FILE TIDAK DITEMUKAN</h3>";
    echo "Pastikan Anda mengupload 'export_sipw.xlsx' ke folder:<br><strong>$base_path</strong>";
}
```

3.  Buka di browser: `domainanda.com/cek_excel.php`.
4.  Jika **MERAH (File Tidak Ditemukan)**, pindahkan file `export_sipw.xlsx` ke path yang tertulis di layar.

## 3. Clear Cache di Shared Hosting
Di shared hosting, kita sering tidak punya akses terminal SSH. Anda bisa menggunakan Route khusus untuk membersihkan cache.

1.  Buka file `routes/web.php`.
2.  Tambahkan route sementara di paling bawah:

```php
Route::get('/bersih-bersih', function() {
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    return "Cache, View, dan Config berhasil dibersihkan! Silakan cek halaman visualisasi lagi.";
});
```
3.  Upload `routes/web.php` yang baru.
4.  Buka browser: `domainanda.com/bersih-bersih`.
5.  Setelah muncul pesan sukses, buka kembali halaman visualisasi.

## 4. Pastikan Update Code Terupload
Pastikan 2 file yang kita perbaiki tadi sudah terupload dengan benar ke hosting:
1.  `app/Http/Controllers/UnitController.php` (Pastikan function `sipwVisualization` sudah pakai kode terbaru).
2.  `resources/views/units/sipw_viz.blade.php`.

## 5. Solusi Error "Class 'PhpOffice\PhpSpreadsheet\IOFactory' Not Found"
Jika muncul error ini, artinya library Excel belum terinstall/terupload di server.

### Opsi A: Jika Punya Akses Terminal (SSH)
Masuk ke folder project, lalu jalankan:
```bash
composer require phpoffice/phpspreadsheet
```

### Opsi B: Jika Tidak Ada Terminal (Upload Manual)
1.  Di komputer lokal Anda, buka folder `vendor`.
2.  Cari folder `phpoffice`.
3.  Upload folder `phpoffice` tersebut ke folder `vendor` di server.
4.  Upload juga file `vendor/composer/autoload_psr4.php` dan `vendor/composer/installed.json` dari lokal ke server (overwrite yang lama).
5.  Atau amannya: **Upload ulang seluruh folder `vendor` dari lokal ke server.**

## Ringkasan Solusi
1.  **Upload Code Fix**: Pastikan `UnitController.php` dan `sipw_viz.blade.php` di hosting adalah versi terbaru.
2.  **Upload Excel**: Pastikan `export_sipw.xlsx` ada di folder root project (bukan di dalam public).
3.  **Hapus Cache**: Jalankan route `/bersih-bersih`.
4.  **Cek Vendor**: Pastikan library `phpoffice` ada di dalam folder `vendor` server.
