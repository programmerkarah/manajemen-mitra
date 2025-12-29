@php
    $pendataanRoles = ['pcl_ppl', 'pml', 'pcl', 'ppl', 'lapangan'];
    $pengolahanRoles = ['pengolahan', 'pengawas_pengolahan', 'pemeriksa_pengolahan'];
@endphp

@if($has_pendataan)
    <div class="landscape-page">
        <div style="float: right; width:45%; text-align:left;">
            <div>Lampiran</div>
            <div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
        </div>
        <div style="clear: both;"></div>
        <div style="text-align: center; margin-bottom: 8px; font-weight: bold;">{{ strtoupper($nama_kegiatan) }} <br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }} <br> PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
        <div style="margin-top: 6px;">Rekapitulasi Pendataan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}:</div>
        @php
            $title_amount = isset($is_pemeriksaan) && $is_pemeriksaan
                ? (isset($first_satuan_pendataan) && trim($first_satuan_pendataan) !== '' ? "Jumlah {$first_satuan_pendataan} yang dilakukan pemeriksaan pendataan" : 'Jumlah yang dilakukan pemeriksaan pendataan')
                : (isset($first_satuan_pendataan) && trim($first_satuan_pendataan) !== '' ? "Jumlah {$first_satuan_pendataan} yang dilakukan pendataan" : 'Jumlah yang dilakukan pendataan');
        @endphp
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
    </div>
@endif

@if($has_pengolahan_listing)
    <div class="landscape-page">
        <div style="float: right; width:45%; text-align:left;">
            <div>Lampiran</div>
            <div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
        </div>
        <div style="text-align: center; margin-bottom: 8px; font-weight: bold;">{{ strtoupper($nama_kegiatan) }} <br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }} <br> PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
        <div style="clear: both;"></div>
        <div style="margin-top: 6px;">Rekapitulasi Pengolahan (Listing) {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}:</div>
        @php
            $title_amount = isset($is_pemeriksaan) && $is_pemeriksaan
                ? 'Jumlah Dokumen Pemutakhiran yang sudah diperiksa'
                : 'Jumlah Dokumen Pemutakhiran yang sudah diolah';
        @endphp
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
    </div>
@endif

@if($has_pengolahan_pendataan)
    <div class="landscape-page">
        <div style="float: right; width:45%; text-align:left;">
            <div>Lampiran</div>
            <div>Berita Acara Serah Terima Hasil Pekerjaan Petugas Pendataan Lapangan {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}</div>
        </div>
        <div style="text-align: center; margin-bottom: 8px; font-weight: bold;">{{ strtoupper($nama_kegiatan) }} <br>BULAN {{ strtoupper($bulan_label) }} TAHUN {{ $tahun ?? '-' }} <br> PADA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</div>
        <div style="clear: both;"></div>
        <div style="margin-top: 6px;">Rekapitulasi Pengolahan (Pendataan Lapangan) {{$nama_kegiatan}} Bulan {{ $bulan_label }} Tahun {{ $tahun ?? '-' }}:</div>
        @php
            $title_amount = isset($is_pemeriksaan) && $is_pemeriksaan
                ? 'Jumlah dokumen Pendataan lapangan yang sudah diperiksa'
                : 'Jumlah dokumen Pendataan lapangan yang sudah diolah';
        @endphp
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
    </div>
@endif