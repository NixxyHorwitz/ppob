# 🚀 Admin Dashboard — PHP Modular Template

Template admin dashboard dengan Bootstrap 5, tema dark, Phosphor Icons, ApexCharts, Chart.js, dan DataTables.

## 📁 Struktur Folder

```
admin/
├── index.php              ← Halaman Dashboard utama
├── users.php              ← Contoh halaman Users
├── includes/
│   ├── header.php         ← Head HTML + Sidebar + Topbar (include di atas)
│   └── footer.php         ← Scripts + closing tags (include di bawah)
└── assets/
    └── style.css          ← Custom CSS dark theme
```

## ⚙️ Cara Pakai

### 1. Buat halaman baru

```php
<?php
$page_title  = 'Nama Halaman';    // Judul tab browser & topbar
$active_menu = 'key_menu';        // Key menu di sidebar (lihat daftar di bawah)
$base_path   = '';                 // '' jika di root, '../' jika di subfolder

require_once 'includes/header.php';
?>

<!-- Konten halaman di sini -->
<div class="page-header">
  <h1>Judul Halaman</h1>
</div>

<?php require_once 'includes/footer.php'; ?>
```

### 2. Daftar `$active_menu` yang tersedia

| Key               | Menu                    |
|-------------------|-------------------------|
| `dashboard`       | Dashboard               |
| `analytics`       | Analytics               |
| `reports`         | Reports                 |
| `users`           | Users                   |
| `products`        | Products (parent)       |
| `products-list`   | Product List            |
| `products-add`    | Add Product             |
| `products-category` | Categories            |
| `orders`          | Orders                  |
| `customers`       | Customers               |
| `blog`            | Blog / Posts            |
| `media`           | Media Library           |
| `settings`        | Settings                |
| `logs`            | Logs                    |

### 3. Inject script chart/JS ke halaman

Gunakan variabel `$extra_scripts` sebelum `require_once footer`:

```php
<?php
$extra_scripts = <<<'SCRIPT'
<script>
  // script ApexCharts, DataTable, dll
  $('#myTable').DataTable();
</script>
SCRIPT;

require_once 'includes/footer.php';
?>
```

## 🎨 Library yang Dipakai

| Library          | Versi  | Kegunaan                   |
|------------------|--------|----------------------------|
| Bootstrap        | 5.3.3  | Layout & komponen UI       |
| Phosphor Icons   | 2.1.1  | Ikon                       |
| ApexCharts       | 3.49   | Chart area, donut, bar     |
| Chart.js         | 4.4.3  | Chart alternatif           |
| DataTables       | 1.13.7 | Tabel interaktif           |
| jQuery           | 3.7.1  | DOM & DataTables           |

## 🖥️ Cara Jalankan

1. Tempatkan folder `admin/` di dalam web server (XAMPP/Laragon/dll)
2. Akses lewat browser: `http://localhost/admin/`
3. Pastikan ada koneksi internet untuk load CDN library

## 💡 Tips

- Ubah data user di `header.php` → variabel `$current_user` (atau ambil dari `$_SESSION`)
- Tambah menu baru di array `$nav_menus` dalam `header.php`
- Sesuaikan warna di `assets/style.css` bagian `:root { }`
- Untuk halaman di subfolder, set `$base_path = '../';`
