@extends('layouts.app')

@section('title', 'Pengaturan Pejabat Penandatangan Laporan')

@section('content')
<style>
.ttd-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.ttd-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F2A3D;
    margin: 0 0 4px 0;
}
.ttd-subtitle {
    font-size: 13.5px;
    color: #64748B;
    margin: 0;
}

.ttd-config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.ttd-config-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15, 42, 61, 0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    display: flex;
    flex-direction: column;
}
.ttd-config-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(15, 42, 61, 0.08);
}
.ttd-card-header {
    padding: 16px 20px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ttd-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #0F2A3D;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ttd-card-body {
    padding: 20px;
    flex: 1;
}

/* Signature Preview Box */
.ttd-preview-box {
    background: #FFFFFF;
    border: 1px dashed #CBD5E1;
    border-radius: 12px;
    padding: 18px 14px;
    margin-bottom: 16px;
}
.ttd-preview-city {
    text-align: right;
    font-size: 12px;
    color: #475569;
    margin-bottom: 14px;
}
.ttd-preview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    text-align: center;
}
.ttd-preview-col {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 140px;
}
.ttd-role-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    letter-spacing: 0.3px;
}
.ttd-role-title {
    font-size: 11.5px;
    font-weight: 700;
    color: #0F2A3D;
    line-height: 1.3;
    margin-top: 2px;
}
.ttd-person-name {
    font-size: 12px;
    font-weight: 700;
    text-decoration: underline;
    color: #0F2A3D;
    line-height: 1.3;
}
.ttd-person-nik {
    font-size: 11px;
    color: #64748B;
    margin-top: 2px;
}
.ttd-card-footer {
    padding: 12px 20px;
    background: #F8FAFC;
    border-top: 1px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan / Pejabat Penandatangan Laporan</div>
    <div class="ttd-header">
        <div>
            <h1 class="ttd-title">PENGATURAN PEJABAT PENANDATANGAN LAPORAN (tbl_ttd)</h1>
            <p class="ttd-subtitle">
                Atur susunan nama, NIK, dan jabatan pejabat penandatangan tanda tangan dinamis untuk seluruh laporan resmi kepegawaian & keuangan (Laporan Penggajian, Potongan, THR, Gaji 13, dll).
            </p>
        </div>
    </div>
</div>

<div class="ttd-config-grid">
    @forelse ($ttdList as $ttd)
        <div class="ttd-config-card">
            <div class="ttd-card-header">
                <div class="ttd-card-title">
                    <svg width="18" height="18" fill="none" stroke="#0284C7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>{{ $ttd->judul }}</span>
                </div>
                <span class="badge" style="background:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE; font-size:11px; font-weight:700;">
                    Kode: {{ $ttd->kode }}
                </span>
            </div>

            <div class="ttd-card-body">
                <div style="font-size:12px; font-weight:600; color:#64748B; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">
                    Preview Lembar Pengesahan:
                </div>

                <div class="ttd-preview-box">
                    <div class="ttd-preview-city">
                        {{ $ttd->kota ?? 'Indramayu' }}, {{ date('d F Y') }}
                    </div>

                    <div class="ttd-preview-grid">
                        <!-- Pejabat 1 (Mengetahui) -->
                        <div class="ttd-preview-col">
                            <div>
                                <div class="ttd-role-label">{{ $ttd->label1 ?? 'MENGETAHUI' }}</div>
                                <div class="ttd-role-title">{{ $ttd->jabatan1 }}</div>
                            </div>
                            <div style="margin-top:50px;">
                                <div class="ttd-person-name">{{ $ttd->nama1 }}</div>
                                <div class="ttd-person-nik">NIK. {{ $ttd->nik1 }}</div>
                            </div>
                        </div>

                        <!-- Pejabat 2 (Menyetujui) -->
                        <div class="ttd-preview-col">
                            <div>
                                <div class="ttd-role-label">{{ $ttd->label2 ?? 'DISETUJUI' }}</div>
                                <div class="ttd-role-title">{{ $ttd->jabatan2 }}</div>
                            </div>
                            <div style="margin-top:50px;">
                                <div class="ttd-person-name">{{ $ttd->nama2 }}</div>
                                <div class="ttd-person-nik">NIK. {{ $ttd->nik2 }}</div>
                            </div>
                        </div>

                        <!-- Pejabat 3 (Pembuat / Manajer) -->
                        <div class="ttd-preview-col">
                            <div>
                                <div class="ttd-role-label">{{ $ttd->label3 ?? 'DIBUAT OLEH' }}</div>
                                <div class="ttd-role-title">{{ $ttd->jabatan3 }}</div>
                            </div>
                            <div style="margin-top:50px;">
                                <div class="ttd-person-name">{{ $ttd->nama3 }}</div>
                                <div class="ttd-person-nik">NIK. {{ $ttd->nik3 }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="font-size:12px; color:#64748B; line-height:1.4;">
                    Digunakan secara otomatis saat mencetak laporan berkode <code>{{ $ttd->kode }}</code>.
                </div>
            </div>

            <div class="ttd-card-footer">
                <span style="font-size:11.5px; color:#94A3B8;">
                    Terakhir diubah: {{ $ttd->updated_at ? \Carbon\Carbon::parse($ttd->updated_at)->translatedFormat('d M Y H:i') : '-' }}
                </span>
                <a href="{{ route('pejabat-ttd.edit', $ttd->id) }}" class="btn btn-primary" style="padding:6px 14px; font-size:12.5px; display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Ubah Pejabat Penandatangan
                </a>
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; padding:40px; text-align:center; background:#ffffff; border-radius:14px; border:1px solid #E2E8F0;">
            <p style="color:#64748B;">Belum ada konfigurasi penandatangan yang tersimpan.</p>
        </div>
    @endforelse
</div>
@endsection

