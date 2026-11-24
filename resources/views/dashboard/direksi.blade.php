{{-- Dashboard Statistik Khusus Eksekutif Direksi (Role 7) --}}
<style>
.exec-header {
    background: linear-gradient(135deg, #0b253a 0%, #103b5c 50%, #164e63 100%);
    border-radius: 16px;
    padding: 26px 30px;
    color: #ffffff;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px -5px rgba(11, 37, 58, 0.25), 0 8px 10px -6px rgba(11, 37, 58, 0.2);
    position: relative;
    overflow: hidden;
}
.exec-header::after {
    content: '';
    position: absolute;
    right: -20px;
    top: -30px;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}
.exec-badge-gold {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.35);
    color: #fbbf24;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 9999px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.exec-badge-live {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
    font-size: 11.5px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 9999px;
}
.exec-badge-live::before {
    content: '';
    width: 7px;
    height: 7px;
    background: #34d399;
    border-radius: 50%;
    box-shadow: 0 0 8px #34d399;
}
.exec-grid-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}
.exec-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.exec-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.07);
}
.exec-card-title {
    font-size: 12.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.exec-card-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 26px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    margin-bottom: 8px;
}
.exec-card-sub {
    font-size: 12.5px;
    color: #64748b;
    line-height: 1.4;
}
.exec-card-pill {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}
.exec-charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 18px;
    margin-bottom: 24px;
}
@media (max-width: 992px) {
    .exec-charts-row {
        grid-template-columns: 1fr;
    }
}
.exec-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.exec-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.exec-panel-head h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f2a3d;
    display: flex;
    align-items: center;
    gap: 8px;
}
.exec-branch-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px dashed #e2e8f0;
    font-size: 13px;
}
.exec-branch-row:last-child {
    border-bottom: none;
}
.exec-progress-track {
    flex: 1;
    height: 8px;
    background: #f1f5f9;
    border-radius: 99px;
    overflow: hidden;
    margin: 0 14px;
}
.exec-progress-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #0284c7, #0ea5e9);
}
.exec-quick-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #0f2a3d;
    text-decoration: none;
    font-weight: 600;
    font-size: 13.5px;
    transition: all 0.2s ease;
}
.exec-quick-btn:hover {
    background: #f8fafc;
    border-color: #0284c7;
    color: #0284c7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08);
}
.exec-quick-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>

<div class="exec-header">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <span class="exec-badge-gold">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    Executive Dashboard
                </span>
                <span class="exec-badge-live">Realtime Data Supabase</span>
                @if (session('simpeg_user.userlevel') === '1')
                    <span style="font-size:11px; background:#38bdf8; color:#0c4a6e; padding:3px 8px; border-radius:99px; font-weight:700;">Pratinjau Mode Direksi</span>
                @endif
            </div>
            <h1 style="margin:0 0 6px 0; font-size:26px; font-weight:700; letter-spacing:-0.5px; color:#ffffff;">
                {{ $namaDirektur ?? session('simpeg_user.nama_peg', 'Direksi') }}
            </h1>
            <p style="margin:0; font-size:14px; color:#94a3b8; display:flex; align-items:center; gap:8px;">
                <span>{{ $jabatanDirektur ?? 'Direksi' }}</span>
                <span>•</span>
                <span>PERUMDAM Tirta Darma Ayu Kabupaten Indramayu</span>
                <span>•</span>
                <span>Periode: <strong>{{ $execData['payroll_summary']['periode_label'] }}</strong></span>
            </p>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">
            @if (session('simpeg_user.userlevel') === '1')
                <a href="{{ route('dashboard', ['view' => 'standard']) }}" class="btn" style="background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.3); color:#ffffff; font-size:12.5px; padding:7px 14px; border-radius:8px; text-decoration:none;">
                    &larr; Beranda Standar SDM
                </a>
            @endif
            <a href="{{ route('approval.index') }}" class="btn" style="background:#f59e0b; color:#ffffff; font-weight:700; font-size:13px; padding:8px 18px; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(245,158,11,0.35);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Approval Center ({{ $execData['approvals']['total_pending'] }})
            </a>
        </div>
    </div>
</div>

{{-- Top 4 KPI Cards --}}
<div class="exec-grid-cards">
    {{-- Card 1: Beban Penggajian --}}
    <div class="exec-card" style="border-left: 4px solid #0284c7;">
        <div>
            <div class="exec-card-title">
                <span>Realisasi Gaji ({{ $execData['payroll_summary']['periode_label'] }})</span>
                <span class="exec-card-pill" style="background:#e0f2fe; color:#0369a1;">
                    {{ $execData['payroll_summary']['pegawai_tergaji'] }} Pegawai
                </span>
            </div>
            <div class="exec-card-value">
                Rp {{ number_format($execData['payroll_summary']['total_bersih'], 0, ',', '.') }}
            </div>
            <div class="exec-card-sub">
                Gaji Bruto: <strong>Rp {{ number_format($execData['payroll_summary']['total_bruto'], 0, ',', '.') }}</strong><br>
                Total Potongan: <strong>Rp {{ number_format($execData['payroll_summary']['total_potongan'], 0, ',', '.') }}</strong>
            </div>
        </div>
        <div style="margin-top:14px; padding-top:10px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:11.5px; color:#64748b;">Status Otorisasi:</span>
            <span class="exec-card-pill" style="{{ $execData['payroll_summary']['status_otorisasi'] === 'Disetujui & Diterbitkan' ? 'background:#dcfce7; color:#15803d;' : 'background:#fef3c7; color:#b45309;' }}">
                {{ $execData['payroll_summary']['status_otorisasi'] }}
            </span>
        </div>
    </div>

    {{-- Card 2: Personel Headcount --}}
    <div class="exec-card" style="border-left: 4px solid #10b981;">
        <div>
            <div class="exec-card-title">
                <span>Total Kekuatan Personel</span>
                <span class="exec-card-pill" style="background:#dcfce7; color:#166534;">
                    {{ $execData['sdm']['rasio_tetap'] }}% Tetap
                </span>
            </div>
            <div class="exec-card-value">
                {{ number_format($execData['sdm']['total'], 0, ',', '.') }} <span style="font-size:16px; font-weight:500; color:#64748b;">Orang</span>
            </div>
            <div class="exec-card-sub">
                <strong>{{ $execData['sdm']['tetap'] }}</strong> Pegawai Tetap • <strong>{{ $execData['sdm']['pkwt'] }}</strong> Kontrak (PKWT)<br>
                <strong>{{ $execData['sdm']['direksi'] }}</strong> Jajaran Direksi • <strong>{{ $execData['sdm']['pensiun'] }}</strong> Pensiun
            </div>
        </div>
        <div style="margin-top:14px; padding-top:10px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:11.5px; color:#64748b;">Presensi Hari Ini:</span>
            <span style="font-size:12px; font-weight:700; color:#0f766e;">
                {{ $execData['sdm']['presensi_hari_ini'] }} hadir ({{ $execData['sdm']['persen_kehadiran'] }}%)
            </span>
        </div>
    </div>

    {{-- Card 3: Berkas Butuh Putusan Direksi --}}
    <div class="exec-card" style="border-left: 4px solid #f59e0b;">
        <div>
            <div class="exec-card-title">
                <span>Tindakan Menunggu Direksi</span>
                <span class="exec-card-pill" style="{{ $execData['approvals']['total_pending'] > 0 ? 'background:#fee2e2; color:#b91c1c;' : 'background:#f1f5f9; color:#64748b;' }}">
                    {{ $execData['approvals']['total_pending'] > 0 ? 'Butuh Tindakan' : 'Tuntas' }}
                </span>
            </div>
            <div class="exec-card-value" style="{{ $execData['approvals']['total_pending'] > 0 ? 'color:#b91c1c;' : '' }}">
                {{ $execData['approvals']['total_pending'] }} <span style="font-size:16px; font-weight:500; color:#64748b;">Berkas</span>
            </div>
            <div class="exec-card-sub">
                <strong>{{ $execData['approvals']['cuti_count'] }}</strong> Permohonan Cuti Dinas Menunggu<br>
                <strong>{{ $execData['approvals']['potongan_count'] }}</strong> Validasi Gaji/Potongan • <strong>{{ $execData['approvals']['wbs_count'] }}</strong> Disposisi WBS
            </div>
        </div>
        <div style="margin-top:14px; padding-top:10px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <a href="{{ route('approval.index') }}" style="color:#0284c7; text-decoration:none; font-size:12px; font-weight:700;">
                Buka Tindakan Cepat &rarr;
            </a>
        </div>
    </div>

    {{-- Card 4: Total Belanja Pegawai YTD --}}
    <div class="exec-card" style="border-left: 4px solid #6366f1;">
        <div>
            <div class="exec-card-title">
                <span>Belanja Pegawai Kumulatif (YTD)</span>
                <span class="exec-card-pill" style="background:#ede9fe; color:#5b21b6;">Tahun {{ $execData['payroll_summary']['tahun'] }}</span>
            </div>
            <div class="exec-card-value">
                Rp {{ number_format($execData['payroll_summary']['ytd_bersih'], 0, ',', '.') }}
            </div>
            <div class="exec-card-sub">
                Realisasi THR: <strong>Rp {{ number_format($execData['payroll_summary']['thr_total'], 0, ',', '.') }}</strong><br>
                Tunj. Pendidikan: <strong>Rp {{ number_format($execData['payroll_summary']['tunj_pendidikan_total'], 0, ',', '.') }}</strong>
            </div>
        </div>
        <div style="margin-top:14px; padding-top:10px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:11.5px; color:#64748b;">Akumulasi Bruto:</span>
            <span style="font-size:12px; font-weight:600; color:#475569;">
                Rp {{ number_format($execData['payroll_summary']['ytd_bruto'], 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>

{{-- Row 2: Charts (Tren Gaji & Komposisi Golongan) --}}
<div class="exec-charts-row">
    {{-- Chart 1: Tren Realisasi Gaji Bulanan --}}
    <div class="exec-panel">
        <div class="exec-panel-head">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Tren Belanja Gaji Pegawai Bulanan
            </h3>
            <span style="font-size:12px; color:#64748b;">Data Riwayat Penggajian Terbit</span>
        </div>
        <div style="position:relative; height: 260px; width: 100%;">
            <canvas id="chartPayrollTrend"></canvas>
        </div>
        <div style="display:flex; justify-content:center; gap:20px; margin-top:14px; font-size:12px; color:#64748b;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:12px; height:12px; background:#0284c7; border-radius:3px;"></span> Gaji Bruto
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:12px; height:12px; background:#10b981; border-radius:3px;"></span> Gaji Bersih (Net Pay)
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:12px; height:12px; background:#f43f5e; border-radius:3px;"></span> Total Potongan
            </div>
        </div>
    </div>

    {{-- Chart 2: Komposisi Golongan Kepangkatan --}}
    <div class="exec-panel">
        <div class="exec-panel-head">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                Jenjang Kepangkatan
            </h3>
            <span style="font-size:12px; color:#64748b;">Distribusi Golongan</span>
        </div>
        <div style="position:relative; height: 220px; width: 100%; display:flex; align-items:center; justify-content:center;">
            <canvas id="chartGolongan"></canvas>
        </div>
        <div style="margin-top:10px; font-size:11.5px; color:#475569; display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            <div>• Gol. D (Staf Madya): <strong>{{ $execData['golongan']['Golongan D'] }}</strong></div>
            <div>• Gol. C (Staf): <strong>{{ $execData['golongan']['Golongan C'] }}</strong></div>
            <div>• Gol. B (Pelaksana): <strong>{{ $execData['golongan']['Golongan B'] }}</strong></div>
            <div>• Gol. A (Peg. Dasar): <strong>{{ $execData['golongan']['Golongan A'] }}</strong></div>
        </div>
    </div>
</div>

{{-- Row 3: Sebaran Personel & Quick Actions --}}
<div class="exec-charts-row">
    {{-- Distribusi Unit Kerja Terbesar --}}
    <div class="exec-panel">
        <div class="exec-panel-head">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0f766e" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                Distribusi Ketenagakerjaan per Unit Kerja & Cabang
            </h3>
            <a href="{{ route('pegawai.index') }}" style="font-size:12px; color:#0284c7; text-decoration:none; font-weight:600;">Lihat Semua &rarr;</a>
        </div>
        <div>
            @foreach ($execData['unit_kerja'] as $uk)
                <div class="exec-branch-row">
                    <div style="width: 210px; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $uk['unit'] }}
                    </div>
                    <div class="exec-progress-track">
                        <div class="exec-progress-fill" style="width: {{ $uk['percent'] }}%;"></div>
                    </div>
                    <div style="width: 85px; text-align:right; font-weight:700; color:#0f172a;">
                        {{ $uk['count'] }} <span style="font-size:11px; font-weight:400; color:#64748b;">({{ $uk['percent'] }}%)</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Akses Cepat Dokumen & Disposisi Eksekutif --}}
    <div class="exec-panel">
        <div class="exec-panel-head">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Pusat Akses Dokumen Eksekutif
            </h3>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <a href="{{ route('approval.index') }}" class="exec-quick-btn">
                <div class="exec-quick-icon" style="background:#fef3c7; color:#b45309;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div style="flex:1;">
                    <div>Kotak Masuk Approval</div>
                    <div style="font-size:11.5px; font-weight:400; color:#64748b;">Otorisasi Cuti, Gaji, THR & Potongan</div>
                </div>
                <span class="exec-card-pill" style="background:#fef3c7; color:#b45309;">{{ $execData['approvals']['total_pending'] }}</span>
            </a>

            <a href="{{ route('gaji-laporan.buku-besar') }}" class="exec-quick-btn">
                <div class="exec-quick-icon" style="background:#e0f2fe; color:#0369a1;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </div>
                <div style="flex:1;">
                    <div>Buku Besar Gaji Eksekutif</div>
                    <div style="font-size:11.5px; font-weight:400; color:#64748b;">Rekap Ledger Payroll per Periode</div>
                </div>
                <span>&rarr;</span>
            </a>

            <a href="{{ route('thr.laporan-buku-besar') }}" class="exec-quick-btn">
                <div class="exec-quick-icon" style="background:#dcfce7; color:#15803d;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><line x1="12" y1="18" x2="12" y2="20"/><line x1="12" y1="4" x2="12" y2="6"/></svg>
                </div>
                <div style="flex:1;">
                    <div>Buku Besar THR</div>
                    <div style="font-size:11.5px; font-weight:400; color:#64748b;">Ledger Tunjangan Hari Raya</div>
                </div>
                <span>&rarr;</span>
            </a>

            <a href="{{ route('gaji-tigabelas.laporan-buku-besar') }}" class="exec-quick-btn">
                <div class="exec-quick-icon" style="background:#f3e8ff; color:#7e22ce;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <div style="flex:1;">
                    <div>Buku Besar Tunj. Pendidikan</div>
                    <div style="font-size:11.5px; font-weight:400; color:#64748b;">Realisasi Gaji 13 & Insentif</div>
                </div>
                <span>&rarr;</span>
            </a>

            <a href="{{ route('cuti.index') }}" class="exec-quick-btn">
                <div class="exec-quick-icon" style="background:#fee2e2; color:#b91c1c;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div style="flex:1;">
                    <div>Format Cetak Surat Cuti Dinas</div>
                    <div style="font-size:11.5px; font-weight:400; color:#64748b;">SK Cuti Fisik Resmi & Tanda Tangan</div>
                </div>
                <span>&rarr;</span>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart Payroll Trend
    const ctxTrend = document.getElementById('chartPayrollTrend');
    if (ctxTrend) {
        const trendData = @json($execData['trend_payroll']);
        const labels = trendData.map(d => d.label);
        const brutoValues = trendData.map(d => d.bruto);
        const bersihValues = trendData.map(d => d.bersih);
        const potonganValues = trendData.map(d => d.potongan);

        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: labels.length ? labels : ['Data Berjalan'],
                datasets: [
                    {
                        label: 'Gaji Bruto',
                        data: brutoValues.length ? brutoValues : [0],
                        backgroundColor: '#0284c7',
                        borderRadius: 6,
                    },
                    {
                        label: 'Gaji Bersih',
                        data: bersihValues.length ? bersihValues : [0],
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                    },
                    {
                        label: 'Potongan',
                        data: potonganValues.length ? potonganValues : [0],
                        backgroundColor: '#f43f5e',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                                }
                                return 'Rp ' + value;
                            },
                            font: { size: 11 }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    }

    // 2. Chart Golongan
    const ctxGol = document.getElementById('chartGolongan');
    if (ctxGol) {
        const golData = @json($execData['golongan']);
        new Chart(ctxGol, {
            type: 'doughnut',
            data: {
                labels: ['Gol. D', 'Gol. C', 'Gol. B', 'Gol. A', 'Lainnya'],
                datasets: [{
                    data: [
                        golData['Golongan D'] || 0,
                        golData['Golongan C'] || 0,
                        golData['Golongan B'] || 0,
                        golData['Golongan A'] || 0,
                        golData['Lainnya'] || 0
                    ],
                    backgroundColor: ['#8b5cf6', '#0284c7', '#0ea5e9', '#10b981', '#cbd5e1'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11 } }
                    }
                }
            }
        });
    }
});
</script>

