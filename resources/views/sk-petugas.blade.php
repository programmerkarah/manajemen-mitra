<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SK Petugas - {{ $kegiatan->nama }}</title>
    <style>
        @page {
            margin: 2cm 2.5cm;
        }
        
        body {
            font-family: 'Bookman Old Style', serif;
            font-size: 12pt;
            line-height: 1.5;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h3 {
            margin: 5px 0;
            font-weight: bold;
        }
        
        .title {
            text-align: center;
            margin: 20px 0;
            font-weight: bold;
        }
        
        .section {
            margin: 15px 0;
        }
        
        .section-label {
            display: inline-block;
            width: 120px;
            vertical-align: top;
        }
        
        .section-separator {
            display: inline-block;
            width: 20px;
            text-align: center;
            vertical-align: top;
        }
        
        .section-content {
            display: inline-block;
            width: calc(100% - 150px);
            vertical-align: top;
            text-align: justify;
        }
        
        .dasar-hukum-item {
            margin-bottom: 10px;
        }
        
        .dictum {
            margin: 15px 0;
        }
        
        .dictum-label {
            display: inline-block;
            width: 120px;
            vertical-align: top;
            font-weight: bold;
        }
        
        .dictum-separator {
            display: inline-block;
            width: 20px;
            text-align: center;
            vertical-align: top;
        }
        
        .dictum-content {
            display: inline-block;
            width: calc(100% - 150px);
            vertical-align: top;
            text-align: justify;
        }
        
        .signature {
            margin-top: 40px;
            text-align: right;
        }
        
        .signature-content {
            display: inline-block;
            text-align: center;
        }
        
        .tembusan {
            margin-top: 40px;
        }
        
        .tembusan ol {
            margin: 5px 0;
            padding-left: 20px;
        }
        
        .lampiran-header {
            margin-bottom: 20px;
        }
        
        table.petugas {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table.petugas th,
        table.petugas td {
            border: 1px solid black;
            padding: 8px;
        }
        
        table.petugas th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        table.petugas td {
            vertical-align: top;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Halaman Pertama: SK -->
    <div class="header">
        <h3>BADAN PUSAT STATISTIK KOTA SAWAHLUNTO</h3>
        <h3>{{ $kategoriKeputusan }} KEPALA BADAN PUSAT STATISTIK</h3>
        <h3>KOTA SAWAHLUNTO</h3>
        <h3>NOMOR : {{ $nomorSk }} TAHUN {{ $tahunSk }}</h3>
    </div>
    
    <div class="title">TENTANG</div>
    
    <div class="title">
        PETUGAS {{ strtoupper($kegiatan->nama) }}<br>
        BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
        TAHUN ANGGARAN {{ $periode->tahun }}
    </div>
    
    <div style="margin-top: 30px; text-align: center; font-weight: bold;">
        KEPALA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO,
    </div>
    
    <div class="section">
        <span class="section-label">Menimbang</span>
        <span class="section-separator">:</span>
        <span class="section-content">
            Bahwa untuk kelancaran pelaksanaan kegiatan Badan Pusat Statistik Tahun {{ $periode->tahun }} di wilayah Kota Sawahlunto,
            maka perlu menetapkan Petugas {{ $kegiatan->nama }} Badan Pusat Statistik Kota Sawahlunto Tahun Anggaran {{ $periode->tahun }}.
        </span>
    </div>
    
    <div class="section">
        <span class="section-label" style="vertical-align: top;">Mengingat</span>
        <span class="section-separator" style="vertical-align: top;">:</span>
        <span class="section-content">
            @foreach($dasarHukum as $index => $dh)
            <div class="dasar-hukum-item">
                {{ $index + 1 }}. {{ $dh->nama_lengkap }} tentang {{ $dh->tentang }}@if($dh->lembaran), {{ $dh->lembaran }}@endif;
            </div>
            @endforeach
        </span>
    </div>
    
    <div style="text-align: center; font-weight: bold; margin: 20px 0;">
        MEMUTUSKAN :
    </div>
    
    <div class="dictum">
        <span class="dictum-label">Menetapkan</span>
        <span class="dictum-separator">:</span>
        <span class="dictum-content">
            {{ strtoupper($kategoriKeputusan) }} KEPALA BADAN PUSAT STATISTIK KOTA SAWAHLUNTO TENTANG 
            PETUGAS {{ strtoupper($kegiatan->nama) }} BADAN PUSAT STATISTIK KOTA SAWAHLUNTO TAHUN ANGGARAN {{ $periode->tahun }}.
        </span>
    </div>
    
    <div class="dictum">
        <span class="dictum-label">PERTAMA</span>
        <span class="dictum-separator">:</span>
        <span class="dictum-content">
            Mengangkat yang namanya tersebut dalam kolom 2 Lampiran Keputusan ini sebagai Petugas {{ $kegiatan->nama }} 
            Badan Pusat Statistik Kota Sawahlunto Tahun Anggaran {{ $periode->tahun }}.
        </span>
    </div>
    
    <div class="dictum">
        <span class="dictum-label">KEDUA</span>
        <span class="dictum-separator">:</span>
        <span class="dictum-content">
            Petugas {{ $kegiatan->nama }} sebagaimana tersebut dalam diktum PERTAMA bertanggung jawab atas 
            pelaksanaan tugas sebagaimana tersebut dalam kolom 4 Lampiran Keputusan ini.
        </span>
    </div>
    
    <div class="dictum">
        <span class="dictum-label">KETIGA</span>
        <span class="dictum-separator">:</span>
        <span class="dictum-content">
            Segala biaya yang berhubungan dengan terbitnya keputusan ini dibebankan kepada DIPA BPS Kota Sawahlunto 
            SP {{ $dipa }} tanggal {{ $tanggalDipa }}.
        </span>
    </div>
    
    <div class="dictum">
        <span class="dictum-label">KEEMPAT</span>
        <span class="dictum-separator">:</span>
        <span class="dictum-content">
            Keputusan ini mulai berlaku pada tanggal ditetapkan, dengan ketentuan apabila dikemudian hari terdapat 
            kekeliruan akan diadakan perbaikan sebagaimana mestinya
        </span>
    </div>
    
    <div style="margin: 20px 0; margin-left: 140px;">
        Petikan Keputusan ini disampaikan kepada yang bersangkutan untuk dipergunakan sebagaimana mestinya.
    </div>
    
    <div class="signature">
        <div class="signature-content">
            <div>Ditetapkan di : Sawahlunto</div>
            <div>Pada tanggal : {{ \Carbon\Carbon::parse($tanggalSk)->isoFormat('D MMMM Y') }}</div>
            <div style="margin-top: 10px; font-weight: bold;">
                KEPALA BADAN PUSAT STATISTIK<br>
                KOTA SAWAHLUNTO,
            </div>
            <div style="margin-top: 80px; font-weight: bold; text-decoration: underline;">
                {{ $kepalaBps }}
            </div>
        </div>
    </div>
    
    <div class="tembusan">
        <strong>Tembusan :</strong>
        <ol>
            <li>Yth. Kepala BPS Provinsi Sumatera Barat di Padang;</li>
            <li>Yth. Kepala Kantor Wilayah III Direktorat Jenderal Perbendaharaan Padang di Padang;</li>
            <li>Yth. Kepala Kantor Pelayanan Perbendaharaan Negara Sijunjung di Muaro Sijunjung.</li>
        </ol>
    </div>
    
    <div class="page-break"></div>
    
    <!-- Halaman Kedua: Lampiran -->
    <div class="lampiran-header">
        <div>Lampiran 1. Keputusan Kepala Badan Pusat Statistik</div>
        <div style="margin-left: 100px;">Kota Sawahlunto</div>
        <div>Nomor <span style="margin-left: 30px;">: {{ $nomorSk }} Tahun {{ $tahunSk }}</span></div>
        <div>Tanggal <span style="margin-left: 20px;">: {{ \Carbon\Carbon::parse($tanggalSk)->isoFormat('D MMMM Y') }}</span></div>
    </div>
    
    <div class="title">
        PETUGAS {{ strtoupper($kegiatan->nama) }}<br>
        BADAN PUSAT STATISTIK KOTA SAWAHLUNTO<br>
        TAHUN ANGGARAN {{ $periode->tahun }}
    </div>
    
    <table class="petugas">
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th style="width: 30%;">Nama/Jabatan</th>
                <th style="width: 20%;">NIP/Golongan</th>
                <th style="width: 25%;">Ditetapkan Sebagai</th>
                <th style="width: 20%;">Biaya Satuan (Rp)</th>
            </tr>
            <tr>
                <th>(1)</th>
                <th>(2)</th>
                <th>(3)</th>
                <th>(4)</th>
                <th>(5)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alokasiList as $index => $alokasi)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}.</td>
                <td>
                    {{ $alokasi->petugas->nama }}<br>
                    <em>{{ $alokasi->jabatan }}</em>
                </td>
                <td style="text-align: center;">
                    @if($alokasi->petugas->nip)
                        {{ $alokasi->petugas->nip }}<br>
                        {{ $alokasi->petugas->golongan }}
                    @else
                        Non PNS
                    @endif
                </td>
                <td>
                    @foreach($alokasi->details as $detail)
                        {{ $detail->role_name }}<br>
                    @endforeach
                </td>
                <td style="text-align: right;">
                    @foreach($alokasi->details as $detail)
                        {{ number_format($detail->rate, 0, ',', '.') }},-/{{ $detail->satuan }}<br>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="signature" style="margin-top: 60px;">
        <div class="signature-content">
            <div style="font-weight: bold;">
                KEPALA BADAN PUSAT STATISTIK<br>
                KOTA SAWAHLUNTO,
            </div>
            <div style="margin-top: 80px; font-weight: bold; text-decoration: underline;">
                {{ $kepalaBps }}
            </div>
        </div>
    </div>
</body>
</html>
