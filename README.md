# WP Content Refresher

Plugin WordPress untuk memperbarui konten secara otomatis menggunakan cron job dan menampilkan daftar artikel terbaru yang diperbarui.

## 📋 Deskripsi

WP Content Refresher adalah plugin WordPress yang membantu Anda mengelola pembaruan konten website secara otomatis. Plugin ini menyediakan fitur untuk:

- Memperbarui konten post dan page secara otomatis setiap hari
- Menampilkan daftar artikel terkait berdasarkan ID post
- Menampilkan daftar artikel yang baru diperbarui dengan featured image
- Lazy loading untuk gambar
- Caching untuk performa yang lebih baik
- Schema markup untuk SEO
- Logging system untuk tracking pembaruan

## 🚀 Fitur

- **Pembaruan Otomatis**: Menggunakan WordPress Cron untuk memperbarui konten setiap hari
- **Shortcode [previous_posts]**: Menampilkan 5 post sebelumnya berdasarkan Post ID
- **Shortcode [recent_updated_posts]**: Menampilkan post yang baru diperbarui dengan featured image
- **Lazy Loading**: Optimasi loading gambar untuk performa yang lebih baik
- **Schema Markup**: Menambahkan schema.org markup untuk SEO
- **Sistem Cache**: Menggunakan WordPress Object Cache untuk performa optimal
- **Sistem Log**: Mencatat semua aktivitas pembaruan konten
- **Pengaturan Admin**: Interface admin yang mudah digunakan untuk konfigurasi plugin
- **Keamanan**: Implementasi nonce dan permission checking

## 📦 Instalasi

1. Upload folder `wp-content-refresher` ke direktori `/wp-content/plugins/`
2. Aktifkan plugin melalui menu 'Plugins' di WordPress
3. Atur konfigurasi plugin di menu Settings > Content Refresher

## 🔧 Penggunaan

### Shortcode untuk Menampilkan Post Sebelumnya

```php
[previous_posts]
```

Shortcode ini akan menampilkan 5 post sebelumnya berdasarkan Post ID.

### Shortcode untuk Menampilkan Post yang Baru Diperbarui

```php
[recent_updated_posts]
```

Atau dengan parameter offset:

```php
[recent_updated_posts start="5"]
```

### Pengaturan Admin

1. Buka menu Settings > Content Refresher
2. Atur jumlah post yang ditampilkan per shortcode
3. Pilih jam untuk pembaruan otomatis
4. Pilih tipe post yang akan diperbarui
5. Simpan pengaturan

## ⚙️ Konfigurasi

Plugin dapat dikonfigurasi melalui panel admin WordPress. Pengaturan yang tersedia:

- **Posts per Shortcode**: Jumlah post yang ditampilkan (default: 5)
- **Update Hour**: Jam ketika pembaruan otomatis dijalankan (default: 1 AM)
- **Post Types**: Tipe post yang akan diperbarui (default: post, page)

## 🔒 Keamanan

Plugin ini mengimplementasikan beberapa fitur keamanan:

- Nonce verification untuk setiap request
- Permission checking untuk akses admin
- Sanitasi input untuk shortcode attributes
- Validasi data sebelum update database

## 📝 Logging

Plugin menyimpan log pembaruan konten yang dapat diakses di database WordPress:

- Post ID yang diperbarui
- Waktu pembaruan
- Status pembaruan
- Pesan error (jika ada)

## 🛠 Hooks dan Filters

### Actions

```php
// Dijalankan sebelum konten diperbarui
do_action('wcr_before_content_update', $post_id);

// Dijalankan setelah konten diperbarui
do_action('wcr_after_content_update', $post_id);
```

### Filters

```php
// Filter untuk mengubah jumlah post yang ditampilkan
add_filter('wcr_posts_per_page', function($number) {
    return 10; // Ubah jumlah post
});

// Filter untuk mengubah query arguments
add_filter('wcr_query_args', function($args) {
    $args['post_type'] = array('post', 'page', 'custom_post_type');
    return $args;
});
```

## 📦 Requirements

- WordPress 5.2 atau lebih tinggi
- PHP 7.2 atau lebih tinggi

## 🐛 Troubleshooting

### Cron Job Tidak Berjalan

1. Pastikan WordPress Cron berjalan dengan benar
2. Cek pengaturan jam di panel admin
3. Periksa log untuk error message

### Cache Tidak Bekerja

1. Pastikan WordPress Object Cache aktif
2. Periksa konfigurasi cache di server
3. Clear cache dan coba lagi

## 📖 Changelog

### 1.0.0
- Rilis pertama
- Fitur pembaruan otomatis
- Shortcode untuk menampilkan post
- Sistem logging
- Panel admin

## 📝 License

Plugin ini dilisensikan di bawah GPL v2 atau yang lebih baru.

## 👥 Kontribusi

Kontribusi sangat diterima! Jika Anda ingin berkontribusi:

1. Fork repository
2. Buat branch untuk fitur baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 🤝 Support

Jika Anda menemukan bug atau memiliki saran, silakan buat issue di repository GitHub.