@extends('layouts.app')

@section('title', 'Surat Izin Cuti - ' . $cuti['nama'])

@section('content')
<style>
/* Tombol Toolbar Layar */
.surat-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 800px;
    margin: 0 auto 20px auto;
    padding: 12px 18px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}

/* Lembar Kertas Fisik Resmi (A4 Portrait) */
.surat-kertas {
    background: #ffffff;
    width: 210mm;
    min-height: 297mm;
    padding: 20mm 22mm 20mm 25mm;
    margin: 0 auto 40px auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    box-sizing: border-box;
    font-family: 'Times New Roman', Times, serif;
    color: #000000;
    line-height: 1.45;
    position: relative;
}

/* Kop Surat Dinas */
.kop-surat {
    display: flex;
    align-items: center;
    border-bottom: 2px solid #000000;
    padding-bottom: 8px;
    margin-bottom: 2px;
}
.kop-logo {
    width: 75px;
    height: 75px;
    margin-right: 18px;
    flex-shrink: 0;
}
.kop-text {
    flex: 1;
    text-align: center;
}
.kop-instansi-1 {
    font-size: 13pt;
    font-weight: bold;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.kop-instansi-2 {
    font-size: 15pt;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin: 1px 0;
}
.kop-alamat {
    font-size: 9pt;
    line-height: 1.35;
}
.kop-garis-bawah {
    border-bottom: 1px solid #000000;
    margin-bottom: 22px;
}

/* Judul Surat */
.surat-judul-box {
    text-align: center;
    margin-bottom: 24px;
}
.surat-judul {
    font-size: 13pt;
    font-weight: bold;
    text-decoration: underline;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.surat-nomor {
    font-size: 10.5pt;
    margin-top: 3px;
}

/* Isi Surat */
.surat-paragraf {
    font-size: 11pt;
    text-align: justify;
    text-indent: 35px;
    margin-bottom: 12px;
}
.surat-table-data {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0 16px 20px;
    font-size: 11pt;
}
.surat-table-data td {
    padding: 3px 6px;
    vertical-align: top;
}

/* Diktum Ketentuan */
.diktum-list {
    margin: 8px 0 16px 25px;
    padding-left: 15px;
    font-size: 10.5pt;
    text-align: justify;
}
.diktum-list li {
    margin-bottom: 5px;
}

/* Penandatangan 3 Kolom */
.surat-ttd-container {
    width: 100%;
    margin-top: 28px;
    page-break-inside: avoid;
}
.surat-ttd-table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
    font-size: 10.5pt;
}
.surat-ttd-table td {
    vertical-align: top;
    width: 33.33%;
    padding: 0 4px;
}

/* Tembusan */
.surat-tembusan {
    margin-top: 35px;
    font-size: 9.5pt;
    page-break-inside: avoid;
}
.surat-tembusan ol {
    margin: 3px 0 0 18px;
    padding: 0;
}

/* CSS Khusus Print Fisik */
@media print {
    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .sidebar, .topbar, .surat-toolbar, .btn, .breadcrumb, .page-head {
        display: none !important;
    }
    .content-area, .page-body {
        margin: 0 !important;
        padding: 0 !important;
    }
    .surat-kertas {
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 10mm 15mm !important;
        box-shadow: none !important;
    }
    @page {
        size: A4 portrait;
        margin: 10mm 15mm;
    }
}
</style>

<!-- Toolbar Aksi Layar -->
<div class="surat-toolbar btn-no-print">
    <div>
        <a href="{{ route('cuti.index') }}" class="btn btn-outline" style="display:inline-flex; align-items:center; gap:6px;">
            &larr; Kembali ke Daftar Cuti
        </a>
    </div>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-primary" onclick="window.print()" style="display:inline-flex; align-items:center; gap:6px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Fisik Surat Izin Cuti (Print)
        </button>
    </div>
</div>

<!-- Lembar Fisik Surat Izin Cuti Resmi (Dinas) -->
<div class="surat-kertas">
    <!-- Kop Surat Resmi PERUMDAM Tirta Darma Ayu -->
    <div class="kop-surat">
        <img src="{{ asset('logo-pdam.png') }}" alt="Logo PDAM" class="kop-logo">
        <div class="kop-text">
            <div class="kop-instansi-1">Pemerintah Kabupaten Indramayu</div>
            <div class="kop-instansi-2">PERUSAHAAN UMUM DAERAH AIR MINUM TIRTA DARMA AYU</div>
            <div class="kop-alamat">
                Jalan Letnan Jenderal Suprapto No. 25/E Indramayu - Jawa Barat 45212<br>
                Telepon: (0234) 272111, 274111 &bull; Faksimile: (0234) 274112 &bull; Email: <u>pdam.tda@gmail.com</u>
            </div>
        </div>
    </div>
    <div class="kop-garis-bawah"></div>

    <!-- Judul & Nomor Surat -->
    <div class="surat-judul-box">
        <div class="surat-judul">SURAT IZIN {{ strtoupper($cuti['jenis']) }}</div>
        <div class="surat-nomor">Nomor: {{ $cuti['no_surat'] }}</div>
    </div>

    <!-- Paragraf Pembuka -->
    <div class="surat-paragraf">
        Berdasarkan Peraturan Direksi PERUMDAM Tirta Darma Ayu Kabupaten Indramayu tentang Manajemen Sumber Daya Manusia serta permohonan izin cuti yang bersangkutan, dengan ini Direksi / Bagian Sumber Daya Manusia memberikan Izin Cuti kepada Pegawai:
    </div>

    <!-- Tabel Data Pegawai -->
    <table class="surat-table-data">
        <tr>
            <td style="width:28%;">Nama Pegawai</td>
            <td style="width:2%;">:</td>
            <td style="width:70%;"><strong>{{ $cuti['nama'] }}</strong></td>
        </tr>
        <tr>
            <td>Nomor Induk Karyawan (NIK)</td>
            <td>:</td>
            <td><strong>{{ $cuti['nik'] }}</strong></td>
        </tr>
        <tr>
            <td>Pangkat / Golongan</td>
            <td>:</td>
            <td>{{ $cuti['golongan'] ?? 'Pegawai Tetap' }}</td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td>{{ $cuti['jabatan'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>Unit Kerja / Bagian</td>
            <td>:</td>
            <td>{{ $cuti['unit_kerja'] ?? '-' }}</td>
        </tr>
    </table>

    <!-- Rincian Izin Cuti -->
    <div class="surat-paragraf">
        Untuk menjalankan <strong>{{ $cuti['jenis'] }}</strong> dengan ketentuan sebagai berikut:
    </div>

    <table class="surat-table-data">
        <tr>
            <td style="width:28%;">Lamanya Cuti</td>
            <td style="width:2%;">:</td>
            <td style="width:70%;"><strong>{{ $cuti['durasi_hari'] }} hari kerja</strong></td>
        </tr>
        <tr>
            <td>Terhitung Mulai Tanggal</td>
            <td>:</td>
            <td><strong>{{ formatTglIndo($cuti['tanggal_mulai']) }}</strong> s/d <strong>{{ formatTglIndo($cuti['tanggal_selesai']) }}</strong></td>
        </tr>
        <tr>
            <td>Alasan / Keperluan</td>
            <td>:</td>
            <td>{{ $cuti['alasan'] ?: 'Keperluan Pribadi / Keluarga' }}</td>
        </tr>
        <tr>
            <td>Alamat Selama Cuti</td>
            <td>:</td>
            <td>{{ $cuti['alamat_cuti'] ?: 'Indramayu' }}</td>
        </tr>
        <tr>
            <td>Nomor Telepon / HP</td>
            <td>:</td>
            <td>{{ $cuti['no_telp'] ?: '-' }}</td>
        </tr>
    </table>

    <!-- Diktum Ketentuan Kedinasan -->
    <div style="font-size:10.5pt; font-weight:bold; margin-top:12px;">Ketentuan Selama Menjalankan Cuti:</div>
    <ol class="diktum-list">
        <li>Sebelum menjalankan cuti, yang bersangkutan wajib menyelesaikan pekerjaan mendesak serta menyerahkan tugas dan wewenang kepada atasan langsung atau pejabat/rekan kerja pengganti sementara yang ditunjuk.</li>
        <li>Setelah masa cuti berakhir, yang bersangkutan diwajibkan melapor kembali kepada Pimpinan Unit Kerja dan melaksanakan tugas kedinasan sebagaimana mestinya.</li>
        <li>Apabila terdapat kepentingan dinas mendesak yang membutuhkan kehadiran yang bersangkutan, perusahaan berhak memanggil kembali sebelum masa cuti berakhir.</li>
    </ol>

    <div class="surat-paragraf" style="margin-top:10px;">
        Demikian Surat Izin Cuti ini dibuat dan diberikan untuk dipergunakan sebagaimana mestinya.
    </div>

    <!-- Blok Pengesahan Tanda Tangan 3 Kolom -->
    <div class="surat-ttd-container">
        <div style="text-align:right; font-size:10.5pt; margin-bottom:14px; padding-right:10px;">
            {{ $pejabatTtd['kota'] ?? 'Indramayu' }}, {{ formatTglIndo($cuti['tanggal_surat'] ?? now()) }}
        </div>

        <table class="surat-ttd-table">
            <tr>
                <td>
                    <div>PEMOHON CUTI,</div>
                    <div style="font-size:9.5pt; color:#444;">Pegawai Yang Bersangkutan</div>
                    <div style="height:65px;"></div>
                    <div><strong><u>{{ $cuti['nama'] }}</u></strong></div>
                    <div style="font-size:9.5pt;">NIK. {{ $cuti['nik'] }}</div>
                </td>
                <td>
                    <div>MENYETUJUI,</div>
                    <div style="font-size:9.5pt; color:#444;">Atasan Langsung / Pimpinan Unit</div>
                    <div style="height:65px;"></div>
                    <div><strong><u>( .................................................. )</u></strong></div>
                    <div style="font-size:9.5pt;">NIP / NIK. .....................................</div>
                </td>
                <td>
                    <div>{{ $pejabatTtd['pembuat']['label'] ?? 'MENGETAHUI / MENGESAHKAN,' }}</div>
                    <div style="font-size:9.5pt; color:#444;">{{ $pejabatTtd['pembuat']['jabatan'] ?? 'Manajer Bidang SDM' }}</div>
                    <div style="height:65px;"></div>
                    <div><strong><u>{{ $pejabatTtd['pembuat']['nama'] ?? 'HEDDY KELANA, S.H.' }}</u></strong></div>
                    <div style="font-size:9.5pt;">NIK. {{ $pejabatTtd['pembuat']['nik'] ?? '17 11 100' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tembusan Surat Dinas Resmi -->
    <div class="surat-tembusan">
        <strong><u>Tembusan disampaikan Kepada Yth:</u></strong>
        <ol>
            <li>Direktur Utama PERUMDAM Tirta Darma Ayu Kabupaten Indramayu;</li>
            <li>Direktur Umum PERUMDAM Tirta Darma Ayu Kabupaten Indramayu;</li>
            <li>Manajer / Kepala {{ $cuti['unit_kerja'] }};</li>
            <li>Pertinggal / Arsip Bagian Sumber Daya Manusia.</li>
        </ol>
    </div>
</div>
@endsection

