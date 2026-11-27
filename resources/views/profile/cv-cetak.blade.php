<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV - {{ $pegawai['nama'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0F172A;
            background: #F1F5F9;
            padding: 24px 15px;
            font-size: 12.5px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .no-print-bar {
            max-width: 1000px;
            margin: 0 auto 16px auto;
            background: #ffffff;
            border: 1px solid #CBD5E1;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .btn { padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; }
        .btn-print { background: #0D2C6E; color: #fff; }
        .btn-back { background: #F1F5F9; color: #334155; border-color: #CBD5E1; }
 
        .sheet { max-width: 1000px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); overflow: hidden; }
 
        .cv-header { position: relative; background: linear-gradient(120deg, #0D2C6E 0%, #1E5FBF 60%, #3B82F6 100%); padding: 28px 32px; color: #fff; display: flex; gap: 22px; align-items: center; overflow: hidden; }
        .cv-header .wave { position:absolute; right:-40px; top:-40px; width:260px; height:260px; background: rgba(255,255,255,0.08); border-radius: 50%; }
        .cv-photo-slot { width: 90px; height: 110px; border-radius: 8px; background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.4); flex-shrink: 0; display:flex; align-items:center; justify-content:center; font-size: 11px; color: rgba(255,255,255,0.8); text-align:center; }
        .cv-header-text { position: relative; z-index: 1; flex: 1; }
        .cv-header-text .label { font-size: 11px; letter-spacing: 2px; font-weight: 700; opacity: 0.85; }
        .cv-header-text h1 { font-size: 24px; margin: 2px 0 4px 0; }
        .cv-header-text .jabatan { font-weight: 600; font-size: 13.5px; }
        .cv-header-text .unit { font-size: 12.5px; opacity: 0.9; margin-bottom: 8px; }
        .cv-header-meta { font-size: 12px; display: flex; flex-direction: column; gap: 3px; }
        .cv-header-meta span.k { display:inline-block; width: 80px; opacity: 0.85; }
        .cv-logo { position: relative; z-index: 1; text-align: right; }
        .cv-logo img { height: 46px; }
        .cv-logo .logo-fallback { font-weight: 800; font-size: 15px; }
 
        .cv-body { padding: 24px 32px 32px 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 0 28px; }
        .cv-section { margin-bottom: 18px; grid-column: span 2; }
        .cv-section.half { grid-column: span 1; }
        .cv-section-title { display:flex; align-items:center; gap:8px; background:#EFF6FF; color:#1E40AF; font-weight:700; font-size:12.5px; padding:7px 12px; border-radius:6px; margin-bottom:10px; }
        .cv-kv { display:grid; grid-template-columns: 130px 10px 1fr; gap: 4px 0; font-size:12.5px; margin-bottom:3px; }
        .cv-table { width:100%; border-collapse: collapse; font-size:12.5px; }
        .cv-table th { text-align:left; font-weight:700; color:#334155; padding: 4px 8px 6px 0; border-bottom: 1px solid #E2E8F0; }
        .cv-table td { padding: 5px 8px 5px 0; vertical-align: top; }
        .cv-list { list-style: disc; padding-left: 18px; }
        .cv-list li { margin-bottom: 4px; }
        .cv-empty { color:#94A3B8; font-style: italic; }
 
        @media print {
            body { background: #fff; padding: 0; }
            .no-print-bar { display: none !important; }
            .sheet { box-shadow: none; border-radius: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
 
    <div class="no-print-bar">
        <div>
            <strong>Curriculum Vitae</strong> &bull;
            <span style="color:#64748B;">{{ $pegawai['nama'] }} (NIK: {{ $pegawai['nik'] }})</span>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('profile.show') }}" class="btn btn-back">&larr; Kembali</a>
            <button type="button" class="btn btn-print" onclick="window.print()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>
 
    <div class="sheet">
        <div class="cv-header">
            <div class="wave"></div>
            @if (!empty($pegawai['foto_url']))
                <div class="cv-photo-slot" style="padding:0; overflow:hidden;">
                    <img src="{{ $pegawai['foto_url'] }}" alt="Foto {{ $pegawai['nama'] }}" style="width:100%; height:100%; object-fit:cover;">
                </div>
            @else
                <div class="cv-photo-slot">Foto 3x4</div>
            @endif
            @php
                $badgeLabel = [
                    'PT' => 'Pegawai Tetap', 'DI' => 'Direksi', 'CP' => 'Calon Pegawai',
                    'PH' => 'Honorer', 'TK' => 'Tenaga Kontrak', 'PN' => 'Pensiun',
                ];
                $statusTeks = $badgeLabel[$pegawai['status_peg'] ?? ''] ?? ($pegawai['status_peg'] ?? '-');
                $latestGol = $pegawai['golongan'][0]['golongan'] ?? ($pegawai['golongan_utama'] ?? '-');
            @endphp
            <div class="cv-header-text">
                <div class="label">CURRICULUM VITAE</div>
                <h1>{{ strtoupper($pegawai['nama']) }}{{ !empty($pegawai['gelar']) && !str_contains($pegawai['nama'], $pegawai['gelar']) ? ', ' . $pegawai['gelar'] : '' }}</h1>
                <div class="jabatan">{{ $pegawai['jabatan'] }}</div>
                <div class="unit">{{ $pegawai['unit_kerja'] ?? 'Kantor Pusat' }}</div>
                <div class="cv-header-meta">
                    <div><span class="k">NIK</span>: {{ $pegawai['nik'] }}</div>
                    <div><span class="k">Golongan</span>: {{ $latestGol ?: '-' }}</div>
                    <div><span class="k">Status</span>: {{ $statusTeks }}</div>
                    <div><span class="k">Tgl. Masuk</span>: {{ formatTglIndo($pegawai['tgl_masuk'] ?? null) }}</div>
                </div>
            </div>
            <div class="cv-logo">
                <img src="{{ asset('logo-pdam.png') }}" alt="PDAM" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div class="logo-fallback" style="display:none;">PDAM<br><span style="font-weight:500; font-size:10px;">KAB. INDRAMAYU</span></div>
            </div>
        </div>
 
        <div class="cv-body">
            {{-- 1. Data Pribadi --}}
            <div class="cv-section">
                <div class="cv-section-title">1. DATA PRIBADI</div>
                @php
                    $bio = $pegawai['biodata'] ?? [];
                    $tempatLahir = !empty($bio['tempat_lahir']) ? $bio['tempat_lahir'] : '-';
                    $tglLahirFmt = !empty($bio['tgl_lahir']) ? formatTglIndo($bio['tgl_lahir']) : '-';
                    $ttlGabung = ($tempatLahir !== '-' && $tglLahirFmt !== '-')
                        ? ($tempatLahir . ', ' . $tglLahirFmt)
                        : ($tempatLahir !== '-' ? $tempatLahir : $tglLahirFmt);
                @endphp
                <div class="cv-kv"><div>Tempat, Tanggal Lahir</div><div>:</div><div>{{ $ttlGabung }}</div></div>
                <div class="cv-kv"><div>Jenis Kelamin</div><div>:</div><div>{{ !empty($bio['jenis_kelamin']) ? $bio['jenis_kelamin'] : '-' }}</div></div>
                <div class="cv-kv"><div>Alamat</div><div>:</div><div>{{ !empty($bio['alamat']) ? $bio['alamat'] : ($pegawai['alamat'] ?? '-') }}</div></div>
                <div class="cv-kv"><div>No. Telepon</div><div>:</div><div>{{ !empty($bio['telp']) ? $bio['telp'] : ($pegawai['telp'] ?? '-') }}</div></div>
                <div class="cv-kv"><div>Email</div><div>:</div><div>{{ !empty($bio['email']) ? $bio['email'] : ($pegawai['email'] ?? '-') }}</div></div>
                <div class="cv-kv"><div>Status Perkawinan</div><div>:</div><div>{{ !empty($bio['status_kawin']) ? $bio['status_kawin'] : '-' }}</div></div>
            </div>
 
            {{-- 2. Riwayat Pendidikan --}}
            <div class="cv-section">
                <div class="cv-section-title">2. RIWAYAT PENDIDIKAN</div>
                @if (count($pegawai['pendidikan'] ?? []))
                    <table class="cv-table">
                        <thead><tr><th>Tahun</th><th>Pendidikan</th><th>Institusi</th></tr></thead>
                        <tbody>
                            @foreach ($pegawai['pendidikan'] as $p)
                                <tr><td>{{ $p['tahun_lulus'] ?? '-' }}</td><td>{{ $p['jenjang'] ?? '-' }}{{ !empty($p['jurusan']) ? ' - ' . $p['jurusan'] : '' }}</td><td>{{ $p['institusi'] ?? ($p['nama_sekolah'] ?? '-') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="cv-empty">Belum ada data pendidikan.</div>
                @endif
            </div>
 
            {{-- 3. Riwayat Jabatan --}}
            <div class="cv-section">
                <div class="cv-section-title">3. RIWAYAT JABATAN</div>
                @if (count($pegawai['jabatan_riwayat'] ?? []))
                    <table class="cv-table">
                        <thead><tr><th>TMT</th><th>Jabatan</th><th>Unit Kerja</th></tr></thead>
                        <tbody>
                            @foreach ($pegawai['jabatan_riwayat'] as $j)
                                <tr><td>{{ formatTglIndo($j['tmt'] ?? null) }}</td><td>{{ $j['jabatan'] ?? '-' }}</td><td>{{ $j['unit_kerja'] ?? '-' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="cv-empty">Belum ada data riwayat jabatan.</div>
                @endif
            </div>

            {{-- 4. Riwayat Golongan / Pangkat --}}
            <div class="cv-section">
                <div class="cv-section-title">4. RIWAYAT GOLONGAN / PANGKAT</div>
                @if (count($pegawai['golongan'] ?? []))
                    <table class="cv-table">
                        <thead><tr><th>TMT</th><th>Golongan / Pangkat</th><th>No. SK</th></tr></thead>
                        <tbody>
                            @foreach ($pegawai['golongan'] as $g)
                                <tr>
                                    <td>{{ formatTglIndo($g['tmt'] ?? null) }}</td>
                                    <td>{{ $g['golongan'] ?? '-' }}{{ !empty($g['pangkat']) && ($g['pangkat'] !== ($g['golongan'] ?? '')) ? ' (' . $g['pangkat'] . ')' : '' }}</td>
                                    <td>{{ $g['no_sk'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif (!empty($pegawai['golongan_utama']))
                    <table class="cv-table">
                        <thead><tr><th>TMT</th><th>Golongan / Pangkat</th><th>Keterangan</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>-</td>
                                <td>{{ $pegawai['golongan_utama'] }}{{ !empty($pegawai['golongan_detail']) ? ' (' . $pegawai['golongan_detail'] . ')' : '' }}</td>
                                <td>Golongan Aktif</td>
                            </tr>
                        </tbody>
                    </table>
                @else
                    <div class="cv-empty">Belum ada data riwayat golongan.</div>
                @endif
            </div>
 
            {{-- 5. Diklat & Pelatihan --}}
            <div class="cv-section half">
                <div class="cv-section-title">5. DIKLAT &amp; PELATIHAN</div>
                @if (count($pegawai['diklat'] ?? []))
                    <table class="cv-table">
                        <thead><tr><th>Nama Pelatihan / Diklat</th><th>Penyelenggara</th><th>Tahun</th></tr></thead>
                        <tbody>
                            @foreach ($pegawai['diklat'] as $d)
                                <tr><td>{{ $d['nama'] }}</td><td>{{ $d['penyelenggara'] }}</td><td>{{ $d['tahun'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="cv-empty">Belum ada data diklat/pelatihan.</div>
                @endif
            </div>
 
            {{-- 6. Sertifikasi --}}
            <div class="cv-section half">
                <div class="cv-section-title">6. SERTIFIKASI</div>
                @if (count($pegawai['sertifikasi'] ?? []))
                    <table class="cv-table">
                        <thead><tr><th>Nama Sertifikat</th><th>Penyelenggara</th><th>Tahun</th></tr></thead>
                        <tbody>
                            @foreach ($pegawai['sertifikasi'] as $s)
                                <tr><td>{{ $s['nama'] }}</td><td>{{ $s['penyelenggara'] }}</td><td>{{ $s['tahun'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="cv-empty">Belum ada data sertifikasi.</div>
                @endif
            </div>
 
            {{-- 7. Kompetensi --}}
            <div class="cv-section half">
                <div class="cv-section-title">7. KOMPETENSI / KEAHLIAN</div>
                @if (count($pegawai['kompetensi'] ?? []))
                    <ul class="cv-list">
                        @foreach ($pegawai['kompetensi'] as $k)
                            <li>{{ $k }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="cv-empty">Belum ada data kompetensi.</div>
                @endif
            </div>
 
            {{-- 8. Prestasi --}}
            <div class="cv-section half">
                <div class="cv-section-title">8. PRESTASI / PENGHARGAAN</div>
                @if (count($pegawai['prestasi'] ?? []))
                    <table class="cv-table">
                        <thead><tr><th>Nama Penghargaan / Prestasi</th><th>Tahun</th></tr></thead>
                        <tbody>
                            @foreach ($pegawai['prestasi'] as $pr)
                                <tr>
                                    <td>
                                        <div>{{ $pr['judul'] ?? '-' }}</div>
                                        @if (!empty($pr['keterangan']) && $pr['keterangan'] !== '-')
                                            <div style="font-size:11px; color:#64748B;">{{ $pr['keterangan'] }}</div>
                                        @endif
                                    </td>
                                    <td>{{ formatTglIndo($pr['tanggal'] ?? null, 'Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="cv-empty">Belum ada data prestasi.</div>
                @endif
            </div>

            {{-- 9. Data Keluarga --}}
            <div class="cv-section">
                <div class="cv-section-title">9. DATA KELUARGA</div>
                @if (count($pegawai['keluarga'] ?? []))
                    <table class="cv-table">
                        <thead><tr><th>Nama Anggota Keluarga</th><th>Hubungan</th><th>Tanggal Lahir</th><th>Keterangan</th></tr></thead>
                        <tbody>
                            @foreach ($pegawai['keluarga'] as $kel)
                                <tr>
                                    <td>{{ $kel['nama'] ?? '-' }}</td>
                                    <td>{{ $kel['hubungan'] ?? '-' }}</td>
                                    <td>{{ formatTglIndo($kel['tgl_lahir'] ?? ($kel['tanggal_lahir'] ?? null)) }}</td>
                                    <td>{{ $kel['keterangan'] ?? ($kel['pekerjaan'] ?? '-') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="cv-empty">Belum ada data keluarga.</div>
                @endif
            </div>
        </div>
    </div>

    @if (request()->boolean('print'))
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 300);
            });
        </script>
    @endif
</body>
</html>
 