<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petunjuk Penggunaan SICAKEP</title>
    <style>
        @page {
            margin: 18px 22px 22px 22px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #111827;
            margin: 0;
            background: #ffffff;
        }

        .cover {
            text-align: center;
            padding: 6px 0 14px;
            border-bottom: 2px solid #dbe4f0;
            margin-bottom: 14px;
        }

        .logo {
            width: 96px;
            height: 96px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 16px;
            margin: 0;
            line-height: 1.25;
        }

        .subtitle {
            margin-top: 6px;
            color: #475569;
            font-size: 10px;
        }

        .meta {
            margin-top: 8px;
            font-size: 10px;
            color: #64748b;
        }

        h2 {
            font-size: 12px;
            margin: 14px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #dbe4f0;
        }

        .intro {
            margin: 0 0 10px;
            padding: 10px 12px;
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            background: #f8fbff;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-grid td {
            border: 1px solid #dbe4f0;
            padding: 8px 10px;
            vertical-align: top;
        }

        .info-grid td.label {
            width: 140px;
            font-weight: 700;
            background: #f8fbff;
        }

        .steps {
            margin: 0;
            padding-left: 18px;
        }

        .steps > li {
            margin-bottom: 10px;
            break-inside: avoid;
        }

        .step-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .step-list {
            margin: 0;
            padding-left: 18px;
        }

        .step-list li {
            margin-bottom: 3px;
        }

        .note {
            margin-top: 4px;
            padding: 8px 10px;
            border-left: 3px solid #3b82f6;
            background: #eff6ff;
            color: #1e3a8a;
        }

        .status-box {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .status-item {
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            padding: 8px 10px;
            background: #fff;
        }

        .status-item strong {
            display: block;
            margin-bottom: 3px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .feature-item {
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            padding: 9px 10px;
            background: #fff;
        }

        .feature-item strong {
            display: block;
            margin-bottom: 3px;
        }

        .feature-item ul {
            margin: 0;
            padding-left: 16px;
        }

        .feature-item li {
            margin-bottom: 2px;
        }

        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #dbe4f0;
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('icon.png');
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    @endphp

    <div class="cover">
        @if($logoBase64)
            <img class="logo" src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo SIMANTIK">
        @endif
        <h1>PETUNJUK PENGGUNAAN SIMANTIK</h1>
        <div class="subtitle">Panduan singkat pelaksanaan inovasi SICAKEP untuk kebutuhan administrasi pelaporan kegiatan PEKPPP</div>
        <div class="meta">Laman: simantik.sawahlunto.io</div>
    </div>

    <div class="intro">
        Panduan ini disusun sebagai versi singkat dari manual SICAKEP yang menyorot alur penggunaan CKP-T dan CKP-R pada aplikasi SIMANTIK untuk kebutuhan administrasi pelaporan kegiatan PEKPPP.
    </div>

    <h2>Latar Belakang</h2>
    <table class="info-grid">
        <tr>
            <td class="label">1</td>
            <td>Proses bisnis pembuatan CKP sebelumnya masih manual sehingga pegawai mengisi dokumen fisik atau file digital secara terpisah lalu dievaluasi oleh atasan.</td>
        </tr>
        <tr>
            <td class="label">2</td>
            <td>Perubahan pola hubungan kerja menuntut kolaborasi yang lebih baik antar anggota tim dan ketua tim.</td>
        </tr>
        <tr>
            <td class="label">3</td>
            <td>Digitalisasi proses bisnis menjadi strategi untuk mendukung reformasi birokrasi dan SPBE.</td>
        </tr>
    </table>

    <h2>Manfaat Aplikasi SICAKEP</h2>
    <div class="feature-grid">
        <div class="feature-item">
            <strong>Fungsi Utama</strong>
            <ul>
                <li>Pengisian data CKP-T dan CKP-R.</li>
                <li>Verifikasi atau persetujuan oleh pihak berwenang.</li>
                <li>Penilaian CKP-R yang melibatkan Ketua Tim yang bersesuaian.</li>
            </ul>
        </div>
        <div class="feature-item">
            <strong>Dukungan Administrasi</strong>
            <ul>
                <li>Monitoring CKP-T dan CKP-R.</li>
                <li>Pembuatan otomatis laporan CKP-T dan CKP-R.</li>
            </ul>
        </div>
    </div>

    <h2>SOP Pengajuan CKP-T</h2>
    <ol class="steps">
        <li>
            <div class="step-title">SOP pengajuan CKP-T oleh anggota tim</div>
            <ol class="step-list" type="a">
                <li>Login SSO pada aplikasi SICAKEP dan masuk ke tim kerja yang sesuai.</li>
                <li>Entri kegiatan CKP-T bulan berjalan.</li>
                <li>Kirim kegiatan CKP-T bulan berjalan kepada Ketua Tim.</li>
                <li>Menunggu informasi bahwa CKP-T sudah disetujui Ketua Tim.</li>
            </ol>
        </li>
        <li>
            <div class="step-title">SOP pengajuan CKP-T oleh ketua tim</div>
            <ol class="step-list" type="a">
                <li>Login SSO pada aplikasi SICAKEP dan memilih tim kerja yang dipimpin.</li>
                <li>Memeriksa kegiatan CKP-T bulan berjalan yang dikirim anggota tim.</li>
                <li>Jika ada perbaikan, kembalikan CKP-T untuk diperbaiki.</li>
                <li>Jika sudah sesuai, setujui kegiatan CKP-T bulan berjalan.</li>
            </ol>
        </li>
        <li>
            <div class="step-title">SOP pengajuan CKP-R oleh anggota tim</div>
            <ol class="step-list" type="a">
                <li>Login SSO pada aplikasi SICAKEP dan masuk ke tim kerja yang sesuai.</li>
                <li>Entri kegiatan CKP-R bulan berjalan.</li>
                <li>Kirim kegiatan CKP-R bulan berjalan kepada Ketua Tim.</li>
                <li>Menunggu informasi bahwa CKP-R sudah dinilai Ketua Tim.</li>
            </ol>
        </li>
        <li>
            <div class="step-title">SOP pengajuan CKP-R oleh ketua tim</div>
            <ol class="step-list" type="a">
                <li>Login SSO pada aplikasi SICAKEP dan memilih tim kerja.</li>
                <li>Memeriksa CKP-R bulan berjalan yang dikirim anggota tim.</li>
                <li>Jika ada perbaikan, kembalikan CKP-R untuk diperbaiki.</li>
                <li>Jika sudah sesuai, lakukan penilaian CKP-R bulan berjalan.</li>
            </ol>
        </li>
    </ol>

    <h2>Tampilan Aplikasi</h2>
    <div class="feature-grid">
        <div class="feature-item">
            <strong>Menu Login</strong>
            <ul>
                <li>Website SiCakep Online diakses melalui VPN/internal BPS.</li>
                <li>Login menggunakan SSO BPS.</li>
            </ul>
        </div>
        <div class="feature-item">
            <strong>Tampilan Dashboard</strong>
            <ul>
                <li>Menampilkan status CKP-T bulan berjalan.</li>
                <li>Menampilkan status CKP-R bulan berjalan.</li>
                <li>Menampilkan jumlah kegiatan pada CKP-R bulan berjalan.</li>
                <li>Menampilkan nilai CKP-R bulan berjalan.</li>
            </ul>
        </div>
        <div class="feature-item">
            <strong>Menu Tim Kerja</strong>
            <ul>
                <li>Tambah tim kerja.</li>
                <li>Pilih tim kerja yang aktif.</li>
                <li>Hapus tim kerja yang tidak diperlukan.</li>
            </ul>
        </div>
        <div class="feature-item">
            <strong>Menu Entri CKP</strong>
            <ul>
                <li>Pilih tahun CKP.</li>
                <li>Cek status CKP-T dan entri CKP-T bulanan.</li>
                <li>Cek status CKP-R dan entri CKP-R bulanan.</li>
                <li>Lihat nilai CKP-R dari periode berjalan.</li>
            </ul>
        </div>
        <div class="feature-item">
            <strong>Menu Unduh CKP</strong>
            <ul>
                <li>Pilih tahun CKP.</li>
                <li>Preview dan unduh CKP-T.</li>
                <li>Preview dan unduh CKP-R.</li>
                <li>Cek status CKP-T dan CKP-R dalam bentuk ringkas.</li>
            </ul>
        </div>
        <div class="feature-item">
            <strong>Ringkasan Status Pengajuan CKP</strong>
            <ul>
                <li>Belum dientri.</li>
                <li>Sedang dientri.</li>
                <li>Sedang diperiksa.</li>
                <li>Sudah disetujui.</li>
                <li>Sudah dinilai.</li>
            </ul>
        </div>
    </div>

    <h2>Langkah Penggunaan</h2>
    <ol class="steps">
        <li>
            <div class="step-title">Menampilkan dan mengisi CKP-T</div>
            <ol class="step-list" type="a">
                <li>Pilih tahun CKP pada menu entri.</li>
                <li>Buka CKP-T bulan berjalan dan isi kegiatan utama atau kegiatan tambahan.</li>
                <li>Simpan lalu kirim CKP-T kepada Ketua Tim.</li>
                <li>Jika diperlukan, salin kegiatan dari bulan sebelumnya untuk mempercepat pengisian.</li>
            </ol>
        </li>
        <li>
            <div class="step-title">Menampilkan dan mengisi CKP-R</div>
            <ol class="step-list" type="a">
                <li>Pilih tahun CKP pada menu entri.</li>
                <li>Buka CKP-R bulan berjalan untuk melihat daftar kegiatan utama dan tambahan.</li>
                <li>Isi kegiatan dan kirim ke Ketua Tim untuk dinilai.</li>
                <li>Periksa nilai CKP-R yang tampil setelah penilaian selesai.</li>
            </ol>
        </li>
        <li>
            <div class="step-title">Mengunduh CKP</div>
            <ol class="step-list" type="a">
                <li>Buka menu unduh CKP dan pilih tahun yang diperlukan.</li>
                <li>Gunakan preview untuk memeriksa isi sebelum diunduh.</li>
                <li>Unduh CKP-T atau CKP-R sesuai kebutuhan pelaporan.</li>
            </ol>
        </li>
        <li>
            <div class="step-title">Membaca ringkasan status pengajuan</div>
            <ol class="step-list" type="a">
                <li>Gunakan status belum dientri, sedang dientri, sedang diperiksa, sudah disetujui, dan sudah dinilai untuk memantau progres.</li>
                <li>Status tersebut membantu memastikan pengajuan CKP-T dan CKP-R tidak tertinggal.</li>
            </ol>
        </li>
    </ol>

    <h2>Keterangan Status</h2>
    <div class="status-box">
        <div class="status-item">
            <strong>Belum Dientri</strong>
            CKP-T atau CKP-R belum diisi.
        </div>
        <div class="status-item">
            <strong>Sedang Dientri</strong>
            CKP-T atau CKP-R sedang diisi.
        </div>
        <div class="status-item">
            <strong>Sedang Diperiksa</strong>
            CKP-T atau CKP-R sudah dikirim dan sedang diperiksa Ketua Tim.
        </div>
        <div class="status-item">
            <strong>Sudah Disetujui</strong>
            CKP-T telah disetujui oleh Ketua Tim.
        </div>
        <div class="status-item">
            <strong>Sudah Dinilai</strong>
            CKP-R telah selesai dinilai.
        </div>
    </div>

    <div class="footer">
        SICAKEP - SIMANTIK | Disusun mengikuti referensi manual pelaksanaan inovasi | simantik.sawahlunto.io
    </div>
</body>
</html>