    <style>
        @page {
            size: A4 portrait;
            margin: 3cm 3cm 3cm 4cm;
        }

        @page landscape {
            size: A4 landscape;
            margin: 2cm 2cm 2cm 2cm;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('/fonts/Bookman Old Style Bold.TTF') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('/fonts/Bookman Old Style Bold.TTF') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Bookman Old Style', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #000;
            orphans: 3;
            widows: 3;
            margin: 2cm 2.5cm 2cm 2.5cm;
        }

        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .title {
            font-size: 13pt;
        }

        .nomor {
            font-size: 11pt;
        }

        .content {
            text-align: justify;
            margin: 20px 0;
        }

        .indent {
            text-indent: 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }

        .landscape-page {
            page: landscape;
            page-break-before: always;
        }

        table th,
        table td {
            border: 1px solid #000;
            vertical-align: middle;
        }

        table th {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        table td.left {
            text-align: left;
        }

        table td.right {
            text-align: right;
        }

        .signature-section {
            margin-top: 40px;
        }

        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            vertical-align: top;
        }

        .signature-left {
            float: left;
        }

        .signature-right {
            float: right;
        }

        .signature-name {
            margin-top: 80px;
            font-weight: bold;
        }

        .signature-nip {
            margin-top: 5px;
        }

        ul {
            margin: 10px 0;
            padding-left: 40px;
        }

        li {
            margin: 5px 0;
        }

        /* Lampiran formatting */
        .lampiran-meta {
            float: right;
            width: 40%;
            text-align: right;
            font-size: 10pt;
        }

        .lampiran-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 6px;
        }

        .lampiran-subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 6px;
        }

        .lampiran-table {
            margin-top: 6px;
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }

        .lampiran-table th {
            background-color: #f0f0f0;
            padding: 6px 8px;
            text-align: center;
            vertical-align: middle;
        }

        .lampiran-table td {
            padding: 6px 8px;
            vertical-align: top;
        }

        .lampiran-totals {
            font-weight: bold;
        }
    </style>
    </head>

    <body>
        @if(!($render_lampiran ?? false))
        <div class="header">
            <div class="title">BERITA ACARA SERAH TERIMA</div>
            <div class="title">HASIL PEKERJAAN PETUGAS PENDATAAN LAPANGAN</div>
            <div class="title">{{ strtoupper($nama_kegiatan) }}</div>
            <div class="title">BULAN {{ strtoupper($bulan_label ?? '') }} TAHUN {{ $tahun ?? date('Y') }}</div>
            <div class="title">PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
        </div>
        <div class="nomor" style="margin-top: 15px; text-align: center;">Nomor: {{ $nomor_bast }}</div>

        <div class="content">
            <p>
                Pada hari ini {{ $hari ?? 'Jum\'at' }}, tanggal {{ $tanggal_bast ?? '-' }}, bulan {{ $bulan_label ?? '-' }}, tahun {{ $tahun ?? date('Y') }}, bertempat di Kantor Badan Pusat Statistik Kota Sawahlunto, kami yang bertanda tangan di bawah ini:
            </p>

            <table style="border: none;">
                <tr style="border: none;">
                    <td style="width: 5%; border: none; vertical-align: top;">1.</td>
                    <td style="width: 20%; border: none; vertical-align: top;">Nama</td>
                    <td style="width: 1%; border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top;">{{ $nama_ppk }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"></td>
                    <td style="border: none; vertical-align: top;">NIP</td>
                    <td style="border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top;">{{ $nip_ppk ?? '-' }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"></td>
                    <td style="border: none; vertical-align: top;">Jabatan</td>
                    <td style="border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top; text-align: justify;">{{ $jabatan_ppk ?? 'Pejabat Pembuat Komitmen Badan Pusat Statistik Kota Sawahlunto untuk Program Penyediaan dan Pelayanan Informasi Statistik' }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"></td>
                    <td style="border: none; vertical-align: top;">Unit Kerja</td>
                    <td style="border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top;">Badan Pusat Statistik Kota Sawahlunto</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"></td>
                    <td style="border: none; vertical-align: top;">Alamat Unit Kerja</td>
                    <td style="border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top; text-align: justify;">Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto</td>
                </tr>
            </table>

            <span>bertindak untuk dan atas nama BPS Kota Sawahlunto, selanjutnya disebut sebagai <strong>PIHAK PERTAMA.</strong></span>

            <table style="border: none; margin: 5px 0 20px 0;">
                <tr style="border: none;">
                    <td style="width: 5%; border: none; vertical-align: top;">2.</td>
                    <td style="width: 20%; border: none; vertical-align: top;">Nama</td>
                    <td style="width: 1%; border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top;">{{ $nama_ketua_tim }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"></td>
                    <td style="border: none; vertical-align: top;">NIP</td>
                    <td style="border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top;">{{ $nip_ketua_tim ?? '-' }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"></td>
                    <td style="border: none; vertical-align: top;">Jabatan</td>
                    <td style="border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top; text-align: justify;">Ketua Tim Kegiatan {{ $nama_kegiatan }} Tahun {{ $tahun ?? date('Y') }}</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none;"></td>
                    <td style="border: none; vertical-align: top;">Alamat</td>
                    <td style="border: none; vertical-align: top;">:</td>
                    <td style="border: none; vertical-align: top; text-align: justify;">Jl. Bagindo Aziz Chan, Kel. Aur Mulyo, Kec. Lembah Segar, Kota Sawahlunto</td>
                </tr>
            </table>
            <span style="page-break-inside: avoid;">bertindak sebagai Ketua Tim {{$nama_kegiatan}} Tahun {{ $tahun ?? date('Y') }} serta atas nama petugas kegiatan yang selanjutnya disebut sebagai <strong>PARA PETUGAS</strong> sebagaimana terlampir dalam Lampiran Berita Acara ini, selanjutnya disebut <strong>PIHAK KEDUA</strong></span>


            <p style="margin-top: 10px; text-align: justify;">
                Dengan ini menyatakan:
            </p>

            <ol style="margin: 10px 0 0 20px;">
                <li style="margin: 6px 0; text-align: justify;">
                    <strong>PIHAK KEDUA</strong> selaku Ketua Tim {{$nama_kegiatan}} Tahun {{$tahun}} telah melakukan fungsi pengendalian dan pengawasan atas pelaksanaan kegiatan {{$nama_kegiatan}} Tahun {{$tahun}} pelaksanaan bulan {{ $bulan_label ?? '' }} Tahun {{ $tahun }} yang dilaksanakan oleh petugas, sesuai dengan tugas dan kewenangan yang diberikan oleh <strong>PIHAK PERTAMA</strong>.
                </li>
                <li style="margin: 6px 0; text-align: justify;">
                    <strong>PIHAK KEDUA</strong> menyatakan bahwa pelaksanaan kegiatan sebagaimana dimaksud pada angka 1 telah diselesaikan sesuai dengan ketentuan dalam Perjanjian Kerja yang ditandatangani oleh <strong>PIHAK PERTAMA</strong> dan <strong>PARA PETUGAS</strong>, dan hasil kegiatan tersebut sudah diverifikasi sesuai prosedur yang berlaku.
                </li>
                <li style="margin: 6px 0; text-align: justify;">
                    Berdasarkan ketentuan pada angka 2 tersebut di atas, <strong>PIHAK KEDUA</strong> menyerahkan hasil pekerjaan kepada <strong>PIHAK PERTAMA</strong>, dan <strong>PIHAK PERTAMA</strong> menerima hasil pekerjaan tersebut.
                </li>
                <li style="margin: 6px 0; text-align: justify;">
                    Hasil pekerjaan berupa jenis dokumen, jumlah dan rincian hasil kegiatan sebagaimana dimaksud pada angka 3 tercantum dalam Lampiran Berita Acara ini sebagai bagian yang tidak terpisahkan dari Berita Acara ini.
                </li>
                @if($menggunakan_fasih)
                <li style="margin: 6px 0; text-align: justify;">
                    <strong>PIHAK KEDUA</strong> menyatakan bahwa sudah menyaksikan <strong>PARA PETUGAS</strong> dalam menghapus Aplikasi FASIH dan data hasil Pendataan Lapangan pada perangkat handphone <strong>PARA PETUGAS.</strong>
                </li>
                @endif
                <li style="margin: 6px 0; text-align: justify;">
                    Dalam hal terdapat hasil pendataan lapangan yang memerlukan pemeriksaan lanjutan, maka akan dilakukan pengecekan, perbaikan, dan/atau kunjungan kembali ke lapangan sesuai dengan ketentuan yang berlaku dengan tetap berpedoman pada ketentuan dalam Perjanjain Kerja yang telah ditandatangani oleh <strong>PIHAK PERTAMA</strong> dan <strong>PARA PETUGAS</strong>.
                </li>
            </ol>

            <div style="page-break-inside: avoid;">
                <p style="margin-top: 20px; text-align: justify;">
                    Demikian Berita Acara ini dibuat dengan sebenarnya dalam rangkap 2 (dua) dan menjadi sah berlaku setelah ditandatangani oleh <strong>PARA PIHAK</strong>.
                </p>
                <div style="height: 10px;"></div>
                <div class="signature-section">
                    <div class="signature-box signature-left">
                        <div>PIHAK KEDUA,</div>
                        <div style="height:10px;"></div>
                        <div class="signature-name">{{ strtoupper($nama_ketua_tim) }}</div>
                    </div>
                    <div class="signature-box signature-right">
                        <div>PIHAK PERTAMA,</div>
                        <div style="height:10px;"></div>
                        <div class="signature-name">{{ strtoupper($nama_ppk) }}</div>
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>

            @endif

            <?php
            $has_any_table = false;
            if (!empty($dokumen_rekap) && is_array($dokumen_rekap) && count($dokumen_rekap) > 0) {
                $has_any_table = true;
            } elseif (!empty($petugas) && is_array($petugas)) {
                foreach ($petugas as $p) {
                    if ((isset($p['hasil_listing']) && $p['hasil_listing'] !== null && $p['hasil_listing'] !== '')
                        || (isset($p['hasil_pendataan_lapangan']) && $p['hasil_pendataan_lapangan'] !== null && $p['hasil_pendataan_lapangan'] !== '')
                        || (isset($p['hasil_pengolahan']) && $p['hasil_pengolahan'] !== null && $p['hasil_pengolahan'] !== '')
                    ) {
                        $has_any_table = true;
                        break;
                    }
                }
            }
            ?>

            @if(($render_lampiran ?? true) && ($has_any_table || (!empty($dokumen_rekap) && is_array($dokumen_rekap))))
                @php
                    // Define role groups used for lampiran filtering
                    $pendataanRoles = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];
                    $pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
                @endphp
                @if(!empty($dokumen_rekap) && is_array($dokumen_rekap))
                <div style="page-break-before: always; margin-top: 20px;">
                    <div style="float: right; width:45%; text-align:left;">
                        <div>Lampiran</div>
                        <div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
                    </div>
                    <div style="clear: both;"></div>
                    <div style="text-align: center; margin-bottom: 8px; font-weight: bold;">{{ strtoupper($nama_kegiatan) }} <br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }} <br> PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>

                    <div style="margin-top: 6px;">Rekapitulasi Pendataan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}:</div>
                <table class="lampiran-table" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th style="width:5%;">No</th>
                            <th>Nama Dokumen</th>
                            <th style="width:18%;">Jumlah Dokumen yang didata</th>
                            <th style="width:18%;">Jumlah Dokumen yang Non Respon</th>
                            <th style="width:18%;">Keterangan</th>
                        </tr>
                        <tr class="column-numbers">
                            <th style="width:5%;">(1)</th>
                            <th>(2)</th>
                            <th style="width:18%;">(3)</th>
                            <th style="width:18%;">(4)</th>
                            <th style="width:18%;">(5)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dokumen_rekap as $i => $row)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td class="left">{{ $row['kode'] ?? $row['nama'] ?? '-' }}</td>
                            <td class="right">{{ isset($row['didata']) ? number_format($row['didata'],0,',','.') : '-' }}</td>
                            <td class="right">{{ isset($row['non_respon']) ? number_format($row['non_respon'],0,',','.') : '-' }}</td>
                            <td class="left">{{ $row['keterangan'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="signature-section">
                    <div class="signature-box signature-left">
                        <div>PIHAK KEDUA,</div>
                        <div style="height:10px;"></div>
                        <div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
                    </div>
                    <div class="signature-box signature-right">
                        <div>PIHAK PERTAMA,</div>
                        <div style="height:10px;"></div>
                        <div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
                    </div>
                    <div style="clear: both;"></div>
                </div>

                @else
                <?php
                $is_pemeriksaan = $is_pemeriksaan ?? false;
                $has_listing = false;
                $has_pendataan = false;
                $has_pengolahan_listing = false;
                $has_pengolahan_pendataan = false;
                $first_satuan_listing = null;
                $first_satuan_pendataan = null;
                if (!empty($petugas) && is_array($petugas)) {
                    foreach ($petugas as $p) {
                        $peran = $p['peran'] ?? null;
                        if (isset($p['hasil_listing']) && $p['hasil_listing'] !== null && $p['hasil_listing'] !== '') {
                            $has_listing = true;
                        }
                        if ((isset($p['hasil_pendataan_lapangan']) && $p['hasil_pendataan_lapangan'] !== null && $p['hasil_pendataan_lapangan'] !== '') && in_array($peran, $pendataanRoles, true)) {
                            $has_pendataan = true;
                        }
                        if ((isset($p['hasil_pengolahan']) && $p['hasil_pengolahan'] !== null && $p['hasil_pengolahan'] !== '') && in_array($peran, $pengolahanRoles, true)) {
                            $has_pengolahan_listing = true;
                        }
                        if ((isset($p['hasil_pengolahan']) && $p['hasil_pengolahan'] !== null && $p['hasil_pengolahan'] !== '') && (isset($p['hasil_pendataan_lapangan']) && $p['hasil_pendataan_lapangan'] !== null && $p['hasil_pendataan_lapangan'] !== '') && in_array($peran, $pengolahanRoles, true) && in_array($peran, $pendataanRoles, true)) {
                            $has_pengolahan_pendataan = true;
                        }
                        if (empty($first_satuan_listing) && !empty($p['satuan_listing'])) {
                            $first_satuan_listing = $p['satuan_listing'];
                        }
                        if (empty($first_satuan_pendataan) && !empty($p['satuan_pendataan_lapangan'])) {
                            $first_satuan_pendataan = $p['satuan_pendataan_lapangan'];
                        }
                    }
                }
                $first_satuan_listing = $first_satuan_listing ?? '';
                $first_satuan_pendataan = $first_satuan_pendataan ?? '';
                ?>

                @if($has_listing)
                <div class="landscape-page">
                    <div style="float: right; width:45%; text-align:left;">
                        <div>Lampiran</div>
                        <div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
                    </div>
                    <div style="clear: both;"></div>
                    <div style="text-align: center; margin-bottom: 8px; font-weight: bold;">{{ strtoupper($nama_kegiatan) }} <br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }} <br> PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
                    <div style="margin-top: 6px;">Rekapitulasi Pendataan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}:</div>
                    <?php
                    $title_amount = $is_pemeriksaan
                        ? (trim($first_satuan_listing) !== '' ? "Jumlah {$first_satuan_listing} yang dilakukan pemeriksaan pemutakhiran/listing" : 'Jumlah yang dilakukan pemeriksaan pemutakhiran/listing')
                        : (trim($first_satuan_listing) !== '' ? "Jumlah {$first_satuan_listing} yang dilakukan pemutakhiran/listing" : 'Jumlah yang dilakukan pemutakhiran/listing');
                    ?>
                    <div style="margin-top:12px; font-weight:bold;">Listing</div>
                    <table class="lampiran-table">
                        <thead>
                            <tr>
                                <th style="width:5%; text-align:left;">No</th>
                                <th style="text-align:left;">Nama Petugas</th>
                                <th style="text-align:left;">Nomor SPK</th>
                                <th style="text-align:left;">Instrumen</th>
                                <th style="text-align:right;">{{ $title_amount }}</th>
                                <th style="text-align:left;">Keterangan</th>
                            </tr>
                            <tr class="column-numbers">
                                <th style="width:5%; text-align:left;">(1)</th>
                                <th style="text-align:left;">(2)</th>
                                <th style="text-align:left;">(3)</th>
                                <th style="text-align:left;">(4)</th>
                                <th style="text-align:right;">(5)</th>
                                <th style="text-align:left;">(6)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($petugas as $i => $p)
                            @if((!empty($p['hasil_listing']) || $p['hasil_listing'] === 0))
                            <tr>
                                <td style="vertical-align:top;">{{ $i+1 }}</td>
                                <td class="left">{{ $p['nama_petugas'] ?? '-' }}</td>
                                <td class="left">{{ $p['nomor_spk'] ?? '-' }}</td>
                                <td class="left">{{ $instrumen_listing ?? $p['instrumen_listing'] ?? '-' }}</td>
                                <td class="right">{{ isset($p['hasil_listing']) ? number_format($p['hasil_listing'],0,',','.') : '-' }}</td>
                                <td class="left">{{ $p['catatan'] ?? '-' }}</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                    <div style="text-align: justify;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{$nama_kegiatan}} {{$tahun}} sesuai dengan ketentuan yang sudah ditetapkan pada Perjanjian Kerja sesuai informasi di atas.</div>

                    <div class="signature-section">
                        <div class="signature-box signature-left">
                            <div>Yang melakukan pemeriksaan,</div>
                            <div>Penangung Jawab Teknis</div>
                            <div>BPS Kota Sawahlunto</div>
                            <div style="height:10px;"></div>
                            <div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
                        </div>
                        <div class="signature-box signature-right">
                            <div>Mengetahui,</div>
                            <div>Kepala Badan Pusat Statistik</div>
                            <div>Kota Sawahlunto</div>
                            <div style="height:10px;"></div>
                            <div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>

                </div>

                @if($has_pendataan)
                <div class="landscape-page">
                    <div style="float: right; width:45%; text-align:left;">
                        <div>Lampiran</div>
                        <div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
                    </div>
                    <div style="clear: both;"></div>
                    <div style="text-align: center; margin-bottom: 8px; font-weight: bold;">{{ strtoupper($nama_kegiatan) }} <br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }} <br> PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
                    <div style="margin-top: 6px;">Rekapitulasi Pendataan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}:</div>
                    <?php
                    $title_amount = $is_pemeriksaan
                        ? (trim($first_satuan_pendataan) !== '' ? "Jumlah {$first_satuan_pendataan} yang dilakukan pemeriksaan pendataan" : 'Jumlah yang dilakukan pemeriksaan pendataan')
                        : (trim($first_satuan_pendataan) !== '' ? "Jumlah {$first_satuan_pendataan} yang dilakukan pendataan" : 'Jumlah yang dilakukan pendataan');
                    ?>
                    <div style="margin-top:12px; font-weight:bold;">Pendataan Lapangan</div>
                    <table class="lampiran-table">
                        <thead>
                            <tr>
                                <th style="width:5%; text-align:left;">No</th>
                                <th style="text-align:left;">Nama Petugas</th>
                                <th style="text-align:left;">Nomor SPK</th>
                                <th style="text-align:left;">Instrumen</th>
                                <th style="text-align:right;">{{ $title_amount }}</th>
                                <th style="text-align:left;">Keterangan</th>
                            </tr>
                            <tr class="column-numbers">
                                <th style="width:5%; text-align:left;">(1)</th>
                                <th style="text-align:left;">(2)</th>
                                <th style="text-align:left;">(3)</th>
                                <th style="text-align:left;">(4)</th>
                                <th style="text-align:right;">(5)</th>
                                <th style="text-align:left;">(6)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($petugas as $i => $p)
                            @if(((!empty($p['hasil_pendataan_lapangan']) || $p['hasil_pendataan_lapangan'] === 0) && in_array($p['peran'] ?? null, $pendataanRoles, true)))
                            <tr>
                                <td style="vertical-align:top;">{{ $i+1 }}</td>
                                <td class="left">{{ $p['nama_petugas'] ?? '-' }}</td>
                                <td class="left">{{ $p['nomor_spk'] ?? '-' }}</td>
                                <td class="left">{{ $instrumen_pendataan_lapangan ?? $p['instrumen_pendataan_lapangan'] ?? '-' }}</td>
                                <td class="right">{{ isset($p['hasil_pendataan_lapangan']) ? number_format($p['hasil_pendataan_lapangan'],0,',','.') : '-' }}</td>
                                <td class="left">{{ $p['catatan'] ?? '-' }}</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>

                    <div style="text-align: justify;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{$nama_kegiatan}} {{$tahun}} sesuai dengan ketentuan yang sudah ditetapkan pada Perjanjian Kerja sesuai informasi di atas.</div>

                    <div class="signature-section">
                        <div class="signature-box signature-left">
                            <div>Yang melakukan pemeriksaan,</div>
                            <div>Penangung Jawab Teknis</div>
                            <div>BPS Kota Sawahlunto</div>
                            <div style="height:10px;"></div>
                            <div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
                        </div>
                        <div class="signature-box signature-right">
                            <div>Mengetahui,</div>
                            <div>Kepala Badan Pusat Statistik</div>
                            <div>Kota Sawahlunto</div>
                            <div style="height:10px;"></div>
                            <div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>

                    @endif
                </div>

                @if($has_pengolahan_listing)
                <div class="landscape-page">
                    <div style="float: right; width:45%; text-align:left;">
                        <div>Lampiran</div>
                        <div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
                    </div>
                    <div style="text-align: center; margin-bottom: 8px; font-weight: bold;">{{ strtoupper($nama_kegiatan) }} <br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }} <br> PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
                    <div style="clear: both;"></div>
                    <div style="margin-top: 6px;">Rekapitulasi Pendataan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}:</div>
                    <?php
                    $title_amount = $is_pemeriksaan
                        ? 'Jumlah Dokumen Pemutakhiran yang sudah diperiksa'
                        : 'Jumlah Dokumen Pemutakhiran yang sudah diolah';
                    ?>
                    <div style="margin-top:12px; font-weight:bold;">Pengolahan (Listing)</div>
                    <table class="lampiran-table">
                        <thead>
                            <tr>
                                <th style="width:5%; text-align:left;">No</th>
                                <th style="text-align:left;">Nama Petugas</th>
                                <th style="text-align:left;">Nomor SPK</th>
                                <th style="text-align:left;">Instrumen</th>
                                <th style="text-align:right;">{{ $title_amount }}</th>
                                <th style="text-align:left;">Keterangan</th>
                            </tr>
                            <tr class="column-numbers">
                                <th style="width:5%; text-align:left;">(1)</th>
                                <th style="text-align:left;">(2)</th>
                                <th style="text-align:left;">(3)</th>
                                <th style="text-align:left;">(4)</th>
                                <th style="text-align:right;">(5)</th>
                                <th style="text-align:left;">(6)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($petugas as $i => $p)
                            @if(((!empty($p['hasil_pengolahan']) || $p['hasil_pengolahan'] === 0) && in_array($p['peran'] ?? null, $pengolahanRoles, true)))
                            <tr>
                                <td style="vertical-align:top;">{{ $i+1 }}</td>
                                <td class="left">{{ $p['nama_petugas'] ?? '-' }}</td>
                                <td class="left">{{ $p['nomor_spk'] ?? '-' }}</td>
                                <td class="left">{{ $instrumen_listing ?? $p['instrumen_listing'] ?? '-' }}</td>
                                <td class="right">{{ isset($p['hasil_pengolahan']) ? number_format($p['hasil_pengolahan'],0,',','.') : '-' }}</td>
                                <td class="left">{{ $p['catatan'] ?? '-' }}</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                    <div style="text-align: justify;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{$nama_kegiatan}} {{$tahun}} sesuai dengan ketentuan yang sudah ditetapkan pada Perjanjian Kerja sesuai informasi di atas.</div>

                    <div class="signature-section">
                        <div class="signature-box signature-left">
                            <div>Yang melakukan pemeriksaan,</div>
                            <div>Penangung Jawab Teknis</div>
                            <div>BPS Kota Sawahlunto</div>
                            <div style="height:10px;"></div>
                            <div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
                        </div>
                        <div class="signature-box signature-right">
                            <div>Mengetahui,</div>
                            <div>Kepala Badan Pusat Statistik</div>
                            <div>Kota Sawahlunto</div>
                            <div style="height:10px;"></div>
                            <div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>

                    @endif
                </div>

                @if($has_pengolahan_pendataan)
                <div class="landscape-page">
                    <h3>Lampiran:</h3>
                    <div style="text-align: center; margin-bottom: 8px; font-weight: bold;">{{ strtoupper($nama_kegiatan) }} <br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }} <br> PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
                    <div style="margin-top: 6px;">Rekapitulasi Pendataan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}:</div>
                    <?php
                    $title_amount = $is_pemeriksaan
                        ? 'Jumlah dokumen Pendataan lapangan yang sudah diperiksa'
                        : 'Jumlah dokumen Pendataan lapangan yang sudah diolah';
                    ?>
                    <div style="margin-top:12px; font-weight:bold;">Pengolahan (Pendataan Lapangan)</div>
                    <table class="lampiran-table">
                        <thead>
                            <tr>
                                <th style="width:5%; text-align:left;">No</th>
                                <th style="text-align:left;">Nama Petugas</th>
                                <th style="text-align:left;">Nomor SPK</th>
                                <th style="text-align:left;">Instrumen</th>
                                <th style="text-align:right;">{{ $title_amount }}</th>
                                <th style="text-align:left;">Keterangan</th>
                            </tr>
                            <tr class="column-numbers">
                                <th style="width:5%; text-align:left;">(1)</th>
                                <th style="text-align:left;">(2)</th>
                                <th style="text-align:left;">(3)</th>
                                <th style="text-align:left;">(4)</th>
                                <th style="text-align:right;">(5)</th>
                                <th style="text-align:left;">(6)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($petugas as $i => $p)
                            @if(((!empty($p['hasil_pengolahan']) || $p['hasil_pengolahan'] === 0) && (!empty($p['hasil_pendataan_lapangan']) || $p['hasil_pendataan_lapangan'] === 0) && in_array($p['peran'] ?? null, $pengolahanRoles, true) && in_array($p['peran'] ?? null, $pendataanRoles, true)))
                            <tr>
                                <td style="vertical-align:top;">{{ $i+1 }}</td>
                                <td class="left">{{ $p['nama_petugas'] ?? '-' }}</td>
                                <td class="left">{{ $p['nomor_spk'] ?? '-' }}</td>
                                <td class="left">{{ $instrumen_pendataan_lapangan ?? $p['instrumen_pendataan_lapangan'] ?? '-' }}</td>
                                <td class="right">{{ isset($p['hasil_pengolahan']) ? number_format($p['hasil_pengolahan'],0,',','.') : '-' }}</td>
                                <td class="left">{{ $p['catatan'] ?? '-' }}</td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                    <div style="text-align: justify;">Petugas yang bersangkutan telah menyelesaikan pekerjaan {{$nama_kegiatan}} {{$tahun}} sesuai dengan ketentuan yang sudah ditetapkan pada Perjanjian Kerja sesuai informasi di atas.</div>
                    <div class="signature-section">
                        <div class="signature-box signature-left">
                            <div>Yang melakukan pemeriksaan,</div>
                            <div>Penangung Jawab Teknis</div>
                            <div>BPS Kota Sawahlunto</div>
                            <div style="height:10px;"></div>
                            <div class="signature-name">{{ strtoupper($nama_ketua_tim ?? '') }}</div>
                        </div>
                        <div class="signature-box signature-right">
                            <div>Mengetahui,</div>
                            <div>Kepala Badan Pusat Statistik</div>
                            <div>Kota Sawahlunto</div>
                            <div style="height:10px;"></div>
                            <div class="signature-name">{{ strtoupper($kepalaBps ?? $kepalaBPS ?? '') }}</div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                    @endif
                </div>


                @endif


            </div>
            @endif
            @endif

        </div>
    </body>

    </html>