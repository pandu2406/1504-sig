# Deployment Steps for Server

Ikuti langkah-langkah berikut untuk memperbaiki error "DivisionByZero" dan data kosong di server production.

## 1. Update File Code
Ada 2 file yang perlu diupdate. Anda bisa copy-paste code berikut atau upload file yang sudah diubah dari localhost.

### File 1: `app/Http/Controllers/UnitController.php`
Cari function `sipwVisualization` (sekitar baris 784).
Ganti logic parsing Excel agar variabel terinisialisasi dengan benar dan cache di-refresh.

**Poin Penting Perubahan:**
- Ubah cache key: `sipw_viz_data_v8` (supaya cache lama hilang).
- Pindahkan inisialisasi variabel `$idx...` ke **LUAR** loop `foreach ($worksheet->getRowIterator()`.
- Pastikan variabel `$idx...` mempertahankan nilainya saat iterasi baris data (index > 1).

### File 2: `resources/views/units/sipw_viz.blade.php`
Cari bagian calculation `$maxVal` (sekitar baris 76).
Tambahkan pengecekan pembagian dengan nol.

**Code Baru:**
```php
@php
    $maxVal = empty($stats['topSLS']) ? 1 : max($stats['topSLS']);
    if ($maxVal == 0) $maxVal = 1; // TAMBAHAN PENTING
@endphp
```

Juga hilangkan tulisan `(Kolom AC)` pada judul card Muatan Dominan (baris 109).

## 2. Analisis Masalah (Kenapa Localhost Benar tapi Server Salah?)
Masalah utamanya adalah **Variable Scope** pada loop Excel parser.
- **Sebelum fix:** Variabel `$idxUsaha` dideklarasikan di dalam blok `if ($index == 1)`. Pada scope PHP tertentu, jika loop berlanjut ke `$index == 2`, variabel ini bisa dianggap tidak terdefinisi (undefined) atau reset, sehingga script gagal membaca kolom 'usaha' dan menganggap nilainya 0.
- **Efeknya:** Semua data usaha jadi 0. Total data jadi 0.
- **Division Error:** Saat visualisasi mencoba menghitung bar width `($val / $maxVal)`, karena data 0 maka `$maxVal` jadi 0. Terjadilah error "Division by Obzero".

## 3. Jalankan Command di Terminal Server
Setelah file diupdate, **WAJIB** jalankan perintah ini di terminal server (SSH/Console) untuk membersihkan sisa-sisa cache error.

```bash
php artisan view:clear
php artisan cache:clear
```

Jika tidak bisa akses terminal, Anda bisa membuat route sementara di `routes/web.php` untuk menjalankannya:
```php
Route::get('/clear-cache', function() {
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    return "Cache cleared";
});
```
Lalu buka `domainanda.com/clear-cache`.

---
**Summary Checklist:**
- [ ] Update `UnitController.php` (Fix scope & Cache Key)
- [ ] Update `sipw_viz.blade.php` (Fix Zero Division & Judul)
- [ ] Run `php artisan optimize:clear` atau `cache:clear`
