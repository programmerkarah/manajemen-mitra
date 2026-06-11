<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petunjuk Penggunaan SIMANTIK</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18px 22px 22px 22px;
        }

        @page landscape {
            size: A4 landscape;
            margin: 14px 18px 18px 18px;
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
            letter-spacing: 0.02em;
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

        .intro {
            margin: 0 0 10px;
            padding: 10px 12px;
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            background: #f8fbff;
        }

        h2 {
            font-size: 12px;
            margin: 14px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #dbe4f0;
        }

        .summary-grid,
        .feature-grid,
        .feature-workflow-grid,
        .status-box,
        .screenshot-grid,
        .workflow-layout {
            display: grid;
            gap: 8px;
        }

        .summary-grid,
        .feature-grid,
        .feature-workflow-grid,
        .status-box,
        .screenshot-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .summary-card,
        .feature-item,
        .feature-workflow-card,
        .status-item,
        .screenshot-card,
        .flow-card {
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            padding: 9px 10px;
            background: #fff;
            break-inside: avoid;
        }

        .summary-card strong,
        .feature-item strong,
        .feature-workflow-card strong,
        .status-item strong,
        .screenshot-card strong,
        .flow-card strong {
            display: block;
            margin-bottom: 3px;
        }

        .summary-card p,
        .status-item p,
        .flow-card p {
            margin: 0;
        }

        .feature-item ul,
        .feature-workflow-card ul {
            margin: 0;
            padding-left: 16px;
        }

        .feature-item li,
        .feature-workflow-card li {
            margin-bottom: 2px;
        }

        .flow-head {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .flow-track {
            display: grid;
            gap: 6px;
        }

        .flow-step {
            display: grid;
            grid-template-columns: 28px 1fr;
            gap: 8px;
            align-items: start;
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            padding: 8px 10px;
            background: #f8fbff;
            break-inside: avoid;
        }

        .flow-number {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #0f766e;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .flow-terminal {
            justify-self: start;
            padding: 5px 12px;
            border-radius: 999px;
            background: #0f172a;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .flow-title {
            font-weight: 700;
            margin-bottom: 2px;
        }

        .flow-text {
            color: #334155;
        }

        .flow-arrow {
            text-align: center;
            color: #0f766e;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            margin: -1px 0;
        }

        .flow-note {
            font-size: 9px;
            color: #64748b;
            margin-top: 6px;
        }

        .landscape-page {
            page: landscape;
            page-break-before: always;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            margin-bottom: 8px;
        }

        .landscape-note {
            color: #475569;
            font-size: 10px;
        }

        .page-chip {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        .screenshot-image {
            display: block;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #dbe4f0;
            background: #f8fbff;
        }

        .screenshot-caption {
            margin-top: 6px;
            color: #475569;
            font-size: 9px;
        }

        .footer {
            margin-top: 12px;
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

        $screenshotDefinitions = [
            [
                'path' => public_path('manual-panduan/welcome-simantik.svg'),
                'title' => 'Login dan Beranda',
                'caption' => 'Tampilan awal untuk masuk aplikasi, membaca arahan, lalu menuju dashboard dan menu kerja.',
            ],
            [
                'path' => public_path('manual-panduan/dashboard-simantik.svg'),
                'title' => 'Dashboard SIMANTIK',
                'caption' => 'Ringkasan status, aksi cepat, dan jalur kerja utama setelah pengguna berhasil login.',
            ],
            [
                'path' => public_path('manual-panduan/sidebar-menu-simantik.svg'),
                'title' => 'Menu Utama',
                'caption' => 'Sidebar menunjukkan perpindahan antarfitur sehingga pengguna tidak kehilangan konteks kerja.',
            ],
            [
                'path' => public_path('manual-panduan/alokasi-create-simantik.svg'),
                'title' => 'Tambah Alokasi',
                'caption' => 'Form alokasi menonjolkan pemilihan kegiatan, daftar petugas, dan langkah simpan / lanjut.',
            ],
            [
                'path' => public_path('manual-panduan/sk-preview-simantik.svg'),
                'title' => 'Preview SK KPA',
                'caption' => 'Preview dokumen SK tampil seperti lembar PDF agar pengguna dapat memeriksa nomor, tanggal, dan dasar hukum.',
            ],
            [
                'path' => public_path('manual-panduan/spk-preview-simantik.svg'),
                'title' => 'Preview SPK',
                'caption' => 'Preview perjanjian kerja menampilkan header dokumen, identitas petugas, dan blok lampiran secara terbaca.',
            ],
            [
                'path' => public_path('manual-panduan/bast-preview-simantik.svg'),
                'title' => 'Preview BAST',
                'caption' => 'Preview BAST menampilkan status kegiatan, rincian lampiran, dan area tanda tangan sebelum unduh atau generate.',
            ],
            [
                'path' => public_path('manual-panduan/pulsa-review-simantik.svg'),
                'title' => 'Ajukan Pulsa dan Review Petugas',
                'caption' => 'Panel ini memperlihatkan alur pengajuan pulsa sekaligus pemeriksaan petugas agar proses kerja saling terhubung.',
            ],
            [
                'path' => public_path('manual-panduan/admin-dashboard-simantik.svg'),
                'title' => 'Dashboard Admin',
                'caption' => 'Admin memantau aktivitas, mengecek backup, dan menindaklanjuti proses verifikasi.',
            ],
        ];

        $screenshots = [];

        foreach ($screenshotDefinitions as $definition) {
            $imagePath = $definition['path'];
            $imageBase64 = file_exists($imagePath) ? base64_encode(file_get_contents($imagePath)) : null;

            $screenshots[] = [
                'title' => $definition['title'],
                'caption' => $definition['caption'],
                'image' => $imageBase64 ? 'data:image/svg+xml;base64,'.$imageBase64 : null,
            ];
        }
    @endphp

    <div class="cover">
        @if($logoBase64)
            <img class="logo" src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo SIMANTIK">
        @endif
        <h1>PETUNJUK PENGGUNAAN SIMANTIK</h1>
        <div class="subtitle">Panduan fitur utama SIMANTIK berdasarkan tampilan yang ada di project ini</div>
        <div class="meta">Laman: simantik.sawahlunto.io</div>
    </div>

    <div class="intro">
        Panduan ini ditulis ulang agar seluruh isi mengacu ke SIMANTIK, tanpa referensi SICAKEP, dan menampilkan alur kerja yang benar-benar dipakai pada tampilan aplikasi.
    </div>

    <h2>Fokus Panduan</h2>
    <div class="summary-grid">
        <div class="summary-card">
            <strong>Login dan dashboard</strong>
            <p>Pengguna masuk ke aplikasi, melihat ringkasan pekerjaan, lalu menentukan menu yang ingin dibuka.</p>
        </div>
        <div class="summary-card">
            <strong>Kelola kegiatan dan alokasi</strong>
            <p>Petugas, kegiatan, dan pembagian kerja diatur dari halaman yang menyediakan form, daftar, dan aksi cepat.</p>
        </div>
        <div class="summary-card">
            <strong>Dokumen administrasi</strong>
            <p>SK, SPK, dan BAST diproses melalui preview PDF-like sehingga pengguna dapat memeriksa isi sebelum generate atau unduh.</p>
        </div>
        <div class="summary-card">
            <strong>Monitoring dan review</strong>
            <p>Pengajuan pulsa, review petugas, dan dashboard admin digunakan untuk verifikasi serta tindak lanjut pekerjaan.</p>
        </div>
    </div>

    <h2>Latar Belakang</h2>
    <div class="feature-grid">
        <div class="feature-item">
            <strong>SIMANTIK dipakai sebagai pusat kerja</strong>
            <ul>
                <li>Semua langkah kerja utama berada dalam satu aplikasi.</li>
                <li>Pengguna berpindah antarfitur lewat dashboard dan sidebar.</li>
                <li>Dokumen dan status dapat dicek tanpa keluar dari alur kerja.</li>
            </ul>
        </div>
        <div class="feature-item">
            <strong>Panduan ini berorientasi pada tampilan asli</strong>
            <ul>
                <li>Isi manual mengikuti halaman yang benar-benar ada di project.</li>
                <li>Preview dokumen digambarkan seperti hasil PDF, bukan kartu statis.</li>
                <li>Screenshot disusun landscape agar area kerja terlihat lebih lengkap.</li>
            </ul>
        </div>
    </div>

    <h2>Diagram Alur SOP</h2>
    <div class="workflow-layout">
        <div class="flow-card">
            <div class="flow-head">SOP Utama Pengguna</div>
            <div class="flow-track">
                <div class="flow-step">
                    <div class="flow-terminal">Mulai</div>
                    <div>
                        <div class="flow-title">Buka SIMANTIK</div>
                        <div class="flow-text">Pengguna masuk ke halaman awal dan memastikan aplikasi sudah siap dipakai.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-number">1</div>
                    <div>
                        <div class="flow-title">Login</div>
                        <div class="flow-text">Masukkan akun yang valid untuk mengakses dashboard dan menu kerja.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-number">2</div>
                    <div>
                        <div class="flow-title">Baca ringkasan dashboard</div>
                        <div class="flow-text">Cek status, aksi cepat, dan petunjuk pekerjaan yang tersedia.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-number">3</div>
                    <div>
                        <div class="flow-title">Pilih modul</div>
                        <div class="flow-text">Pilih alokasi, kegiatan, SK, SPK, BAST, pulsa, atau review petugas sesuai kebutuhan.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-number">4</div>
                    <div>
                        <div class="flow-title">Simpan atau ajukan proses</div>
                        <div class="flow-text">Selesaikan isian lalu simpan, generate, preview, atau kirim untuk verifikasi.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-terminal">Selesai</div>
                    <div>
                        <div class="flow-title">Periksa hasil</div>
                        <div class="flow-text">Hasil kerja akan tampil di daftar, dashboard, atau file PDF sesuai fitur yang digunakan.</div>
                    </div>
                </div>
            </div>
            <div class="flow-note">SOP ini adalah alur dasar sebelum masuk ke modul yang lebih spesifik.</div>
        </div>

        <div class="flow-card">
            <div class="flow-head">SOP Dokumen dan Review</div>
            <div class="flow-track">
                <div class="flow-step">
                    <div class="flow-terminal">Mulai</div>
                    <div>
                        <div class="flow-title">Buka modul dokumen</div>
                        <div class="flow-text">Masuk ke halaman SK, SPK, BAST, atau pengajuan pulsa yang relevan.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-number">1</div>
                    <div>
                        <div class="flow-title">Lengkapi data</div>
                        <div class="flow-text">Isi nomor, tanggal, daftar petugas, atau status yang dibutuhkan dokumen.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-number">2</div>
                    <div>
                        <div class="flow-title">Preview PDF-like</div>
                        <div class="flow-text">Periksa hasil dokumen pada tampilan yang menyerupai lembar PDF sebelum diproses.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-number">3</div>
                    <div>
                        <div class="flow-title">Generate atau unduh</div>
                        <div class="flow-text">Unduh dokumen final jika isi sudah benar atau lanjut generate batch bila diperlukan.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-number">4</div>
                    <div>
                        <div class="flow-title">Review petugas dan admin</div>
                        <div class="flow-text">Gunakan halaman review atau dashboard admin untuk memeriksa hasil dan tindak lanjut.</div>
                    </div>
                </div>
                <div class="flow-arrow">↓</div>
                <div class="flow-step">
                    <div class="flow-terminal">Selesai</div>
                    <div>
                        <div class="flow-title">Status final tercatat</div>
                        <div class="flow-text">Dokumen dan status pekerjaan tersimpan sebagai hasil akhir alur kerja.</div>
                    </div>
                </div>
            </div>
            <div class="flow-note">Diagram ini mengikuti pola standar: mulai, aksi, keputusan, lalu hasil akhir.</div>
        </div>
    </div>

    <h2>Fitur Per Bagian</h2>
    <div class="feature-workflow-grid">
        <div class="feature-workflow-card">
            <strong>Login</strong>
            <ul>
                <li>Masuk melalui halaman awal SIMANTIK.</li>
                <li>Setelah login, pengguna diarahkan ke dashboard yang menampilkan ringkasan.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Dashboard</strong>
            <ul>
                <li>Menampilkan status kerja, aksi cepat, dan ringkasan proses.</li>
                <li>Menjadi titik awal untuk menuju fitur yang akan dikerjakan.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Kelola Kegiatan</strong>
            <ul>
                <li>Mengelola daftar kegiatan dan status pelaksanaannya.</li>
                <li>Menjadi dasar untuk alokasi petugas, dokumen, dan monitoring.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Tambah Alokasi</strong>
            <ul>
                <li>Memilih petugas, kegiatan, peran, dan data pendukung lain.</li>
                <li>Digunakan sebelum pembuatan SPK dan dokumen turunannya.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Cetak SK KPA</strong>
            <ul>
                <li>Nomor dan tanggal SK diisi sebelum preview atau generate.</li>
                <li>Hasil dokumen tampil seperti lembar PDF untuk memastikan isi benar.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Cetak SPK</strong>
            <ul>
                <li>Perjanjian kerja dibuat berdasarkan alokasi petugas dan periode kerja.</li>
                <li>Preview menampilkan identitas petugas, nomor SPK, dan lampiran.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Cetak BAST</strong>
            <ul>
                <li>BAST diproses setelah pekerjaan petugas dianggap siap diserahterimakan.</li>
                <li>Pengguna memeriksa daftar kegiatan, lampiran, dan tanda tangan sebelum final.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Ajukan Pulsa</strong>
            <ul>
                <li>Pengguna mengajukan kebutuhan pulsa dari halaman khusus pengajuan.</li>
                <li>Informasi pengajuan dapat dipantau pada grafik dan daftar status.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Review Petugas</strong>
            <ul>
                <li>Petugas yang sudah bekerja dapat diperiksa dari halaman review.</li>
                <li>Review membantu memastikan data, progres, dan hasil kerja selaras.</li>
            </ul>
        </div>
        <div class="feature-workflow-card">
            <strong>Dashboard Admin</strong>
            <ul>
                <li>Admin memantau aktivitas sistem, backup, dan status layanan.</li>
                <li>Bagian ini dipakai untuk tindak lanjut dan verifikasi operasional.</li>
            </ul>
        </div>
    </div>

    <h2>Ringkasan Status Proses</h2>
    <div class="status-box">
        <div class="status-item">
            <strong>Belum Dibuka</strong>
            Halaman atau menu belum dipilih.
        </div>
        <div class="status-item">
            <strong>Sedang Diisi</strong>
            Data sedang dilengkapi pada form yang aktif.
        </div>
        <div class="status-item">
            <strong>Sedang Dipreview</strong>
            Pengguna memeriksa hasil dokumen atau layar kerja sebelum lanjut.
        </div>
        <div class="status-item">
            <strong>Siap Diunduh</strong>
            Dokumen sudah terbentuk dan bisa disimpan.
        </div>
        <div class="status-item">
            <strong>Perlu Review</strong>
            Hasil kerja menunggu pemeriksaan petugas atau admin.
        </div>
        <div class="status-item">
            <strong>Selesai</strong>
            Alur kerja pada fitur tersebut sudah ditutup.
        </div>
    </div>

    <div class="landscape-page">
        <div class="page-heading">
            <div>
                <h2>Screenshot Tampilan dan Preview Dokumen</h2>
                <div class="landscape-note">Bagian ini disusun landscape agar screenshot, alur aksi, dan preview PDF-like dapat terlihat lebih luas.</div>
            </div>
            <div class="page-chip">Orientasi landscape</div>
        </div>

        <div class="screenshot-grid">
            @foreach($screenshots as $screenshot)
                <div class="screenshot-card">
                    <strong>{{ $screenshot['title'] }}</strong>
                    @if($screenshot['image'])
                        <img class="screenshot-image" src="{{ $screenshot['image'] }}" alt="{{ $screenshot['title'] }}">
                    @endif
                    <div class="screenshot-caption">{{ $screenshot['caption'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="footer">
            SIMANTIK | Panduan resmi penggunaan aplikasi | simantik.sawahlunto.io
        </div>
    </div>
</body>
</html>