# Bima Pratama - System Informasi Manajemen (SIM)

Sistem Informasi Manajemen untuk usaha peternakan dan perdagangan yang mencakup manajemen stok, transaksi, piutang, hutang, dan laporan keuangan.

## 📋 Daftar Isi

- [Struktur Teknologi](#struktur-teknologi)
- [Fitur Utama](#fitur-utama)
- [Alur Per Fitur Secara Lengkap](#-alur-per-fitur-secara-lengkap)
- [Role & Akses](#role--akses)
- [Database](#database)
- [Instalasi](#instalasi)
- [Struktur Kategori Keuangan](#struktur-kategori-keuangan)
- [Struktur Data Awal](#struktur-data-awal)
- [Panduan Penggunaan](#panduan-penggunaan)

---

## Struktur Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Backend | Laravel 12 + PHP 8.2+ |
| Frontend | Livewire Volt + Mary UI + Tailwind CSS |
| Database | MySQL |
| Build Tool | Vite + Alpine.js |
| Reporting | Maatwebsite Excel |

---

## Fitur Utama

### Manajemen Stok
- **Stok Telur**: Input stok telur dengan detail bentes, ceplok, jumbo, rusak
- **Stok Pakan**: Input stok pakan (sentrat, kucing, curah)
- **Stok Obat**: Input stok obat-obatan ternak
- **Stok Tray**: Input stok egg tray
- **Penambahan Stok**: Input batch stok baru dengan harga beli

### Transaksi
- **Pembelian**: Telur masuk, pakan masuk, obat masuk, tray masuk
- **Penjualan**: Telur keluar, pakan keluar, obat keluar, tray keluar
- **Retur**: Retur pembelian dan penjualan untuk semua kategori
- **Kas**: Transaksi kas tunai, kas transfer, kas Deby
- **Piutang & Hutang**: Transaksi bon, piutang, dan hutang

### Laporan Keuangan
- **Laporan Laba Rugi**: Pendapatan dan pengeluaran harian/bulanan/tahunan
- **Neraca Saldo**: Ringkasan keuangan berdasarkan kategori akun
- **Laporan Aset**: Ringkasan aset dan liabilitas
- **Laporan Pakan Curah**: Analisis khusus pakan curah
- **Laporan Stok**: Laporan stok telur dengan detail kotor, bentes, ceplok, jumbo
- **Laporan Kas**: Laporan kas harian dengan perubahan saldo

---

## 🔄 Alur Per Fitur Secara Lengkap

### 1. Alur Transaksi Pembelian (Barang Masuk)
1. **Penerimaan Barang & Input Transaksi**:
   - User memilih jenis barang masuk (**Telur Masuk**, **Sentrat Masuk**, **Obat Masuk**, atau **Tray Masuk**).
   - Pengguna memilih **Supplier / Peternak**, menentukan barang, jumlah (kg/sak/pcs), dan harga beli per unit.
2. **Pencatatan Stok & Batch**:
   - Sistem secara otomatis mencatat penambahan stok barang di `stok_batches` beserta harga belinya.
3. **Pencatatan Keuangan (Hutang / Kas)**:
   - Jika pembayaran **Pembelian Tunai/Transfer**, saldo Kas / Bank berkurang sebesar total transaksi.
   - Jika pembayaran **Kredit**, sistem mencatat **Hutang** atas nama supplier/peternak terkait.
4. **Retur Pembelian (Barang Kembali)**:
   - Jika barang yang dibeli rusak/dikembalikan, user menginput menu **Kembali** (Telur/Sentrat/Obat/Tray Kembali).
   - Sistem akan mengembalikan stok dan menyesuaikan nilai hutang atau kas.

---

### 2. Alur Transaksi Penjualan (Barang Keluar)
1. **Input Transaksi Penjualan**:
   - User memilih menu barang keluar (**Telur Keluar**, **Sentrat Keluar**, **Obat Keluar**, atau **Tray Keluar**).
   - Memilih **Pelanggan / Pedagang**, memilih item barang, qty/berat, dan harga jual.
2. **Pengurangan Stok & Kalkulasi HPP**:
   - Sistem mengurangi stok barang berdasarkan batch yang tersedia (`stok_keluar_batches`).
   - HPP (Harga Pokok Penjualan) dihitung dari harga beli batch tersebut.
3. **Pencatatan Keuangan (Piutang / Kas)**:
   - Jika pembayaran **Tunai/Transfer**, transaksi masuk ke saldo Kas Tunai / Bank Transfer.
   - Jika pembayaran **Kredit (Bon)**, sistem menambahkan catatan **Piutang** pelanggan.
4. **Retur Penjualan (Return)**:
   - Jika ada pengembalian barang dari pedagang, user menginput **Return** (Telur/Sentrat/Obat/Tray Return).
   - Stok barang akan bertambah kembali dan piutang/kas dipotong sesuai nominal retur.

---

### 3. Alur Manajemen Stok & Opname
1. **Pencatatan Batch Stok (`stok_batches`)**:
   - Setiap masuknya stok baru disimpan per batch dengan informasi qty awal, sisa qty, dan harga beli per unit.
2. **Tracking Kondisi Stok Telur**:
   - Khusus telur, sistem mencatat kondisi fisik secara komprehensif (Utuh/Horn, Kotor, Bentes, Ceplok, Jumbo, Prok).
3. **Monitoring & Rekonsiliasi**:
   - Sistem menampilkan laporan stok real-time (Stok Telur, Pakan, Obat, Tray) yang menyandingkan stok awal, barang masuk, barang keluar, retur, dan stok akhir.

---

### 4. Alur Manajemen Kas & Bank
1. **Kas Tunai (`/tunai`)**:
   - Pencatatan penerimaan dan pengeluaran uang tunai fisik harian usaha.
2. **Kas Bank / Transfer (`/transfer`)**:
   - Pencatatan pembayaran via rekening bank (BCA, BRI, BNI).
3. **Kas Deby (`/deby`)**:
   - Pengelolaan dan pencatatan aliran transaksi kas khusus Deby.
4. **Pendapatan Lainnya & Beban Operasional**:
   - **Beban (`/beban`)**: Input pengeluaran operasional (BBM, servis kendaraan, gaji karyawan, konsumsi, ZIS/sedekah).
   - **Lainnya (`/lainnya`)**: Input pendapatan non-utama (penjualan telur reject, jasa transport, dll).

---

### 5. Alur Piutang & Hutang (Pelunasan Bon)
1. **Terbentuknya Piutang & Hutang**:
   - Otomatis dari transaksi penjualan kredit (Piutang) atau pembelian kredit (Hutang).
2. **Pencatatan Pelunasan**:
   - Pembayaran piutang dari pedagang dicatat di menu **Piutang**, memilih transaksi yang akan dilunasi dan metode pembayaran (Tunai/Transfer).
   - Pembayaran hutang ke supplier/peternak dicatat di menu **Hutang**.
3. **Sirkulasi Tray (Piutang & Hutang Tray)**:
   - Melacak jumlah pinjaman egg tray fisik dengan pedagang atau peternak hingga terjadi pengembalian tray.

---

### 6. Alur Pelaporan Keuangan & Akuntansi
1. **Laporan Laba Rugi**:
   - **Pendapatan** (Penjualan Telur, Pakan, Obat, Tray + Pendapatan Lain) dikurangi **HPP** dan **Beban Operasional/Produksi**.
   - Menghasilkan nilai Laba/Rugi Bersih secara real-time berdasarkan range tanggal yang dipilih.
2. **Neraca Saldo**:
   - Menyajikan saldo seluruh debit & kredit dari masing-masing kategori akun (Pendapatan, Beban, Aset, Liabilitas, Ekuitas).
3. **Laporan Aset**:
   - Menampilkan total Aset Lancar (Kas, Bank, Piutang Usaha, Nilai Persediaan Stok) dan Liabilitas (Total Hutang).
4. **Export Excel**:
   - Setiap laporan dapat diunduh dalam format `.xlsx` untuk kebutuhan audit dan arsip.

---

## Role & Akses

| ID | Role | Akses |
|----|------|-------|
| 1 | Admin | Semua fitur |
| 2 | Kasir | Master data, stok, transaksi dasar |
| 3 | Pembelian Telur | Telur masuk, telur kembali, laporan telur, tray |
| 4 | Pakan & Obat | Pakan masuk/keluar, obat masuk/keluar, laporan |
| 5 | Kas Tunai | Transaksi kas tunai, bank transfer |
| 6 | Kas Bank | Transfer, penjualan telur/tray, pembelian |
| 7 | Akuntansi | Piutang, hutang, laporan akhir |
| 8 | Developer/Fix | Data fixing, akses penuh |

---

## Database

### Tabel Utama
| Tabel | Keterangan |
|-------|------------|
| `users` | Data pengguna sistem |
| `roles` | Data role/user group |
| `barangs` | Data barang master |
| `jenis_barangs` | Klasifikasi jenis barang |
| `kategoris` | Kategori transaksi (penjualan, pembelian, aset, dll) |
| `detail_kategoris` | Tipe kategori (Pendapatan, Pengeluaran, Aset, Liabilitas, Ekuitas) |
| `clients` | Data klien/pelanggan/supplier |
| `transaksis` | Header transaksi utama |
| `detail_transaksis` | Detail transaksi per kategori |
| `stoks` | Log stok barang (deprecated) |
| `stok_batches` | Batch stok dengan harga beli |
| `stok_keluar_batches` | Log pengeluaran batch stok |

---

## Instalasi

### Prasyarat
- PHP 8.2+
- MySQL 5.7+
- Composer
- NPM / Node.js

### Langkah Instalasi

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd SIM_Bima_Pratama
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Configuration**
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. **Database Setup**
   ```bash
   # Edit .env untuk konfigurasi database
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bimapratama
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Migrate & Seed Database**
   ```bash
   php artisan migrate --seed
   ```

6. **Build Assets**
   ```bash
   npm run build
   ```

7. **Jalankan Aplikasi**
   ```bash
   php artisan serve
   # Atau gunakan perintah dev untuk development
   composer dev
   ```

---

## Struktur Kategori Keuangan

### 1. Pendapatan (9 kategori utama)
- Penjualan Telur (Horn, Bebek, Puyuh, Arab, Asin)
- Penjualan Pakan (Sentrat, Kucing, Curah)
- Penjualan Obat-Obatan
- Penjualan EggTray
- Pendapatan Perlengkapan (Triplex, Terpal, Ban, Sak, Tali)
- Pendapatan Non Penjualan (Telur Reject, Transport)
- Penjualan Lain-Lain
- Pendapatan Truk
- Pendapatan Pengadaan

### 2. Pengeluaran (18 kategori utama)
- Beban Transport (BBM, Servis)
- Beban Bunga & Pajak
- Beban Operasional (Gaji, Kantor, Konsumsi, TAL)
- Beban Produksi (Telur Bentes, Ceplok, Prok, Kotor, Jumbo, Tray)
- Beban Lain-Lain
- Beban Sedekah (ZIS)
- HPP (Harga Pokok Penjualan)
- Pengeluaran Truk & Pengadaan

### 3. Aset (25+ kategori)
- Piutang Pihak Lain (Peternak, Karyawan, Pedagang)
- Piutang Supplier (Bp.Supriyadi)
- Piutang Tray (Diamond, Super Buah, Random)
- Piutang Obat & Pakan
- Stok (Telur, Pakan, Obat, Tray, Return)
- Kas (Tunai, Deby)
- Bank (BCA, BRI, BNI)

### 4. Liabilitas (15+ kategori)
- Hutang Pihak Lain
- Hutang Supplier
- Hutang Tray
- Hutang Obat & Pakan

### 5. Ekuitas
- Modal

---

## API Endpoints

| Endpoint | Method | Keterangan |
|----------|--------|------------|
| `/api/transaksi/pembelian` | GET | Data pembelian (telur, pakan, obat, tray) |
| `/api/transaksi/penjualan` | GET | Data penjualan |
| `/api/transaksi/retur` | GET | Data retur |
| `/api/keuangan/hutang` | GET | Data hutang |
| `/api/keuangan/piutang` | GET | Data piutang |
| `/api/keuangan/beban` | GET | Data beban pengeluaran |
| `/api/keuangan/pendapatan-lainnya` | GET | Data pendapatan non utama |
| `/api/master/barang` | GET | Data barang |
| `/api/master/jenis-barang` | GET | Data jenis barang |
| `/api/master/client` | GET | Data client |

**Auth**: Gunakan header `X-API-KEY` atau query param `api_key` = api_bimapratama

---

## Struktur Data Awal

### Client Types
- **Peternak**: 170+ peternak dengan warna (Merah, Kuning, Elf, Rumah, Kandang, Pocok, dll)
- **Pedagang**: 70+ pedagang
- **Karyawan**: 50+ karyawan (Keliling, Lain, Sopir)
- **Supplier**: Tray suppliers, Obat suppliers, Pakan suppliers
- **Truk**: 6 truk operasional

### Barang Categories
- **Telur**: Horn, Bebek, Puyuh, Arab, Asin
- **Tray**: Diamond, Super Buah, Random, PMS, CPL
- **Obat**: 100+ varian obat dan vitamin (NEOBRO, VITACHICK, TURBO, dll)
- **Pakan Sentrat**: 144, 124P, CFR, BP 104, Starter, Grower, dll
- **Pakan Curah**: Jagung OC, Katul, Sekam Giling, Karak, Kebi
- **Pakan Kucing**: CAT CHOIZE, EXCEL, FELIBITE, BOLT

### Kategori Stok
- Stok Telur, Stok Pakan, Stok Obat, Stok Tray

---

## Panduan Penggunaan

### Login
- Admin: admin@gmail.com / password
- Manager: manager@gmail.com / password
- Role lainnya: [nama-role]@gmail.com / password

###Navigasi Utama
1. **Dashboard** - Monitoring pendapatan, pengeluaran, stok real-time
2. **Master Data** - Manajemen barang, jenis, kategori, client
3. **Manage Stok** - Input stok baru dan cek stok harian
4. **Transaksi** - Input pembelian, penjualan, retur, kas
5. **Laporan** - Laba rugi, neraca saldo, aset, pakan curah, kas

### Export Data
Semua laporan dapat di-export ke Excel dengan tombol "Export Excel"

---

## Kontribusi

1. Buat branch fitur baru
2. Commit perubahan
3. Push ke branch
4. Buat Pull Request

---

## Lisensi

Proprietary - Bima Pratama

---

## Kontak

Untuk pertanyaan teknis, hubungi tim pengembang.
