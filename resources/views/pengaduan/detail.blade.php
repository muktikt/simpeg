@extends('layouts.app')

@section('title', 'Detail Pengaduan ' . ($pengaduan->nomor_pengaduan ?? ''))

@section('content')
<div class="page-head">
    <div class="breadcrumb">
        <a href="{{ route('pengaduan.index') }}" style="color:var(--text-muted); text-decoration:none;">Home / Pengaduan</a> / Detail
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0;">{{ $pengaduan->nomor_pengaduan ?? ('PGD-' . $pengaduan->id) }}</h1>
            <p style="margin:4px 0 0; font-size:13px; color:var(--text-muted);">
                Kategori: <strong>{{ $pengaduan->kategori ?? 'Umum' }}</strong> · Tanggal: {{ date('d F Y, H:i', strtotime($pengaduan->created_at ?? now())) }}
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <a href="{{ route('pengaduan.surat', $pengaduan->id) }}" target="_blank" 
               style="background:#0d2c6e; color:#ffffff; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 6px rgba(13,44,110,0.25);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Format Surat & Cetak PDF
            </a>
            <a href="{{ route('pengaduan.index') }}" style="background:#f1f5f9; color:#475569; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid #cbd5e1;">
                ← Kembali ke Daftar
            </a>
        </div>
    </div>
</div>

@php
    $statusName = $pengaduan->status ?? 'menungguKadiv';
    $statusLabels = [
        'menungguKadiv' => 'Menunggu Verifikasi Kadiv',
        'menungguVerifikasiKadiv' => 'Menunggu Verifikasi Kadiv',
        'reviewKspi' => 'Review KSPI (Awal)',
        'menungguReviewKspi' => 'Menunggu Review Hasil KSPI',
        'menungguDirutTahap1' => 'Menunggu Persetujuan Dirut (Tahap 1)',
        'menungguPilihEksekutor' => 'Menunggu Penunjukan Eksekutor KSPI',
        'investigasiBerjalan' => 'Investigasi Sedang Berjalan',
        'revisiInvestigasi' => 'Revisi Investigasi Diminta KSPI',
        'menungguDirutTahap2' => 'Menunggu Persetujuan Dirut (Tahap 2)',
        'tindakLanjutBerjalan' => 'Tindak Lanjut Sedang Berjalan',
        'menungguSdm' => 'Menunggu Eksekusi Administratif SDM',
        'selesai' => 'Selesai',
        'arsip' => 'Diarsipkan / Selesai Tanpa Tindak Lanjut',
        'ditolakDirektur' => 'Ditolak Direktur',
    ];
@endphp

<div style="display:grid; grid-template-columns: 1fr 380px; gap:20px; align-items:start;">
    
    {{-- KOLOM KIRI: DETAIL LAPORAN & BUKTI --}}
    <div>
        {{-- Modern 4-Stage Stepper Banner --}}
        @php
            $stage = match($statusName) {
                'menungguKadiv', 'menungguVerifikasiKadiv' => 1,
                'reviewKspi', 'menungguReviewKspi', 'menungguDirutTahap1', 'menungguPilihEksekutor' => 2,
                'investigasiBerjalan', 'revisiInvestigasi', 'menungguDirutTahap2', 'tindakLanjutBerjalan', 'menungguSdm' => 3,
                default => 4,
            };
            $isArsip = ($statusName === 'arsip');
            $stages = [
                1 => ['label' => 'Diajukan', 'desc' => 'Verifikasi Kadiv', 'icon' => '📤'],
                2 => ['label' => 'Diverifikasi', 'desc' => 'KSPI & Dirut', 'icon' => '🔍'],
                3 => ['label' => 'Diproses', 'desc' => 'Investigasi & Sanksi', 'icon' => '⚙️'],
                4 => ['label' => $isArsip ? 'Diarsipkan' : 'Selesai', 'desc' => $isArsip ? 'Tutup Kasus' : 'Selesai', 'icon' => $isArsip ? '📁' : '✅'],
            ];
        @endphp

        <div style="background:linear-gradient(135deg, #0d2c6e 0%, #1e40af 100%); color:#fff; padding:20px 22px; border-radius:14px; margin-bottom:20px; box-shadow:0 10px 15px -3px rgba(13,44,110,0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:10px;">
                <div>
                    <span style="background:rgba(255,255,255,0.18); padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                        Status Alur Saat Ini
                    </span>
                    <div style="font-size:19px; font-weight:800; margin-top:4px;">{{ $statusLabels[$statusName] ?? $statusName }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:13px; font-weight:700; background:rgba(255,255,255,0.15); padding:6px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.25);">
                        {{ $pengaduan->nomor_pengaduan }}
                    </div>
                </div>
            </div>

            <!-- Stepper Bar 4 Tahap -->
            <div style="display:flex; align-items:center; justify-content:space-between; position:relative;">
                @foreach ($stages as $num => $st)
                    @php
                        $isCompleted = ($num < $stage || ($num === 4 && $stage === 4));
                        $isCurrent = ($num === $stage && $stage < 4);
                        $circleBg = $isCompleted ? '#22c55e' : ($isCurrent ? '#38bdf8' : 'rgba(255,255,255,0.2)');
                        $circleColor = ($isCompleted || $isCurrent) ? '#fff' : 'rgba(255,255,255,0.6)';
                    @endphp
                    <div style="flex:1; display:flex; flex-direction:column; align-items:center; text-align:center; position:relative; z-index:2;">
                        <div style="width:34px; height:34px; border-radius:50%; background:{{ $circleBg }}; color:{{ $circleColor }}; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; margin-bottom:6px; box-shadow:0 2px 4px rgba(0,0,0,0.1); border:2px solid {{ $isCurrent ? '#fff' : 'transparent' }};">
                            @if ($isCompleted)
                                ✓
                            @else
                                {{ $st['icon'] }}
                            @endif
                        </div>
                        <div style="font-size:12px; font-weight:{{ $isCurrent || $isCompleted ? '700' : '500' }}; color:{{ $isCurrent || $isCompleted ? '#fff' : 'rgba(255,255,255,0.7)' }};">
                            {{ $st['label'] }}
                        </div>
                        <div style="font-size:10px; color:rgba(255,255,255,0.6); max-width:110px;">
                            {{ $st['desc'] }}
                        </div>
                    </div>
                    @if ($num < 4)
                        <div style="flex:1; height:3px; background:{{ $num < $stage ? '#22c55e' : 'rgba(255,255,255,0.25)' }}; margin-top:-22px; position:relative; z-index:1;"></div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Sorotan Catatan Terakhir --}}
        @php
            $latestNote = null;
            if (!empty($riwayat)) {
                foreach (array_reverse($riwayat->toArray()) as $rh) {
                    if (!empty($rh->keterangan) && trim($rh->keterangan) !== '') {
                        $latestNote = $rh;
                        break;
                    }
                }
            }
        @endphp
        @if ($latestNote)
            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-left:4px solid #2563eb; border-radius:8px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px;">
                <span style="font-size:18px;">📝</span>
                <div style="flex:1;">
                    <div style="font-size:11.5px; font-weight:700; color:#1e40af; text-transform:uppercase;">
                        Catatan / Disposisi Terakhir (Oleh: {{ $latestNote->oleh }} · {{ date('d M Y H:i', strtotime($latestNote->tanggal)) }})
                    </div>
                    <div style="font-size:13px; color:#1e293b; margin-top:3px; line-height:1.4;">
                        {{ $latestNote->keterangan }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Card Tim Eksekutor Terpilih (Jika Sudah Ditugaskan) --}}
        @if (!empty($tasks) && count($tasks) > 0)
            <div class="ribbon-card" style="margin-bottom:20px; border-left:4px solid #0284c7;">
                <div class="ribbon-head" style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="font-size:15px; color:#0369a1; margin:0;">🎯 Tim Eksekutor & Penugasan Investigasi</h2>
                    <span style="font-size:11.5px; background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:10px; font-weight:700;">{{ count($tasks) }} Petugas</span>
                </div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach ($tasks as $t)
                        @php
                            $tBadge = match($t->status) {
                                'Diproses' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                                'Selesai' => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                                default => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                            };
                        @endphp
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                            <div>
                                <strong style="font-size:13px; color:#0f172a;">{{ $t->nama_eksekutor ?? 'Eksekutor' }}</strong>
                                <span style="font-size:11.5px; color:#64748b;">(NIK: {{ $t->nik_eksekutor ?? '-' }} · {{ $t->jabatan_eksekutor ?? '-' }})</span>
                                @if (!empty($t->notes))
                                    <div style="font-size:12px; color:#334155; margin-top:3px;">
                                        <strong>Hasil:</strong> {{ $t->notes }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <span style="{{ $tBadge }} font-size:11px; font-weight:700; padding:2px 8px; border-radius:10px;">
                                    {{ $t->status }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Card Informasi Kasus --}}
        <div class="ribbon-card" style="margin-bottom:20px;">
            <div class="ribbon-head" style="margin-bottom:14px;">
                <h2 style="font-size:16px;">Informasi Pokok Pengaduan</h2>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:16px; background:#f8fafc; padding:14px; border-radius:8px; border:1px solid #e2e8f0;">
                <div>
                    <small style="color:var(--text-muted); display:block; font-size:11.5px;">Identitas Pelapor:</small>
                    @if (!empty($pengaduan->anonim))
                        <strong style="color:#dc2626;">🔒 Anonim (Dirahasiakan)</strong>
                    @else
                        <strong style="font-size:13.5px;">{{ $pengaduan->nama_pegawai ?? '-' }}</strong>
                        <div style="font-size:12px; color:#475569;">NIK: {{ $pengaduan->nik ?? '-' }} · {{ $pengaduan->cabang ?? '' }}</div>
                    @endif
                </div>
                <div>
                    <small style="color:var(--text-muted); display:block; font-size:11.5px;">Pihak yang Diadukan (Terlapor):</small>
                    <strong style="font-size:13.5px; color:#0f172a;">{{ $pengaduan->pihak_terlapor ?? '-' }}</strong>
                    <div style="font-size:12px; color:#475569;">
                        {{ !empty($pengaduan->nik_pelaku) ? 'NIK: ' . $pengaduan->nik_pelaku : '' }}
                        {{ !empty($pengaduan->jabatan_pelaku) ? '· ' . $pengaduan->jabatan_pelaku : '' }}
                    </div>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Judul / Pokok Masalah:</label>
                <div style="font-size:15px; font-weight:700; color:#1e293b;">{{ $pengaduan->judul }}</div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Uraian & Kronologi Kejadian:</label>
                <div style="background:#fff; border:1px solid #e2e8f0; padding:14px; border-radius:8px; font-size:13.5px; line-height:1.6; color:#334155; white-space:pre-line;">{{ $pengaduan->deskripsi }}</div>
            </div>

            {{-- Lampiran Bukti --}}
            @php
                $fotoBukti = !empty($pengaduan->foto_bukti) ? (is_array($pengaduan->foto_bukti) ? $pengaduan->foto_bukti : json_decode($pengaduan->foto_bukti, true)) : [];
                $dokumen = !empty($pengaduan->dokumen_pendukung) ? (is_array($pengaduan->dokumen_pendukung) ? $pengaduan->dokumen_pendukung : json_decode($pengaduan->dokumen_pendukung, true)) : [];
            @endphp

            @if (!empty($fotoBukti) || !empty($dokumen))
                <div style="margin-top:16px; border-top:1px solid #e2e8f0; padding-top:14px;">
                    <label style="font-size:12.5px; font-weight:700; color:#1e293b; display:block; margin-bottom:8px;">Berkas Bukti Terlampir:</label>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        @foreach ($fotoBukti as $f)
                            <a href="{{ $f }}" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1; padding:6px 12px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
                                📷 Lihat Foto Bukti
                            </a>
                        @endforeach
                        @foreach ($dokumen as $d)
                            <a href="{{ $d }}" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; color:#334155; padding:6px 12px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
                                📄 Unduh Dokumen
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Card Laporan Hasil Investigasi & Bukti Multimedia (Jika sudah ada) --}}
        @if (!empty($pengaduan->hasil_investigasi) || !empty($pengaduan->surat_rekomendasi))
            <div class="ribbon-card" style="margin-bottom:20px; border-left:4px solid #0284c7;">
                <div class="ribbon-head" style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <h2 style="font-size:16px; color:#0369a1; margin:0;">📋 Laporan Hasil Investigasi & Rekomendasi Sanksi</h2>
                    @if (!empty($pengaduan->kesimpulan_investigasi))
                        @if ($pengaduan->kesimpulan_investigasi === 'terbukti')
                            <span style="background:#dcfce7; color:#15803d; border:1.5px solid #86efac; padding:4px 12px; border-radius:20px; font-weight:800; font-size:12px; display:inline-flex; align-items:center; gap:5px;">
                                <span>⚖️</span> KESIMPULAN: TERBUKTI
                            </span>
                        @else
                            <span style="background:#fee2e2; color:#b91c1c; border:1.5px solid #fca5a5; padding:4px 12px; border-radius:20px; font-weight:800; font-size:12px; display:inline-flex; align-items:center; gap:5px;">
                                <span>🛡️</span> KESIMPULAN: TIDAK TERBUKTI
                            </span>
                        @endif
                    @endif
                </div>

                @if (!empty($pengaduan->petugas_investigasi))
                    <div style="font-size:12.5px; color:#475569; margin-bottom:12px; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px;">
                        Petugas Investigator: <strong>{{ $pengaduan->petugas_investigasi }}</strong>
                        ({{ $pengaduan->eksekutor === 'kadiv' ? 'Kadiv SPI' : 'Tim Eksekutor TPDPK' }})
                    </div>
                @endif

                @if (!empty($pengaduan->hasil_investigasi))
                    <div style="margin-bottom:12px;">
                        <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Hasil Temuan Fakta Investigasi:</label>
                        <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:12px; border-radius:8px; font-size:13px; color:#0c4a6e; white-space:pre-line;">{{ $pengaduan->hasil_investigasi }}</div>
                    </div>
                @endif

                @if (!empty($pengaduan->surat_rekomendasi))
                    <div style="margin-bottom:14px;">
                        <label style="font-size:12px; color:var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Surat / Poin Rekomendasi Sanksi Resmi:</label>
                        <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:12px; border-radius:8px; font-size:13px; color:#0c4a6e; white-space:pre-line;">{{ $pengaduan->surat_rekomendasi }}</div>
                    </div>
                @endif

                {{-- Bukti-Bukti Investigasi (Foto, Video, Voice Note, Dokumen) --}}
                @php
                    $invFoto = !empty($pengaduan->investigasi_foto) ? (is_array($pengaduan->investigasi_foto) ? $pengaduan->investigasi_foto : json_decode($pengaduan->investigasi_foto, true)) : [];
                    $invVideo = !empty($pengaduan->investigasi_video) ? (is_array($pengaduan->investigasi_video) ? $pengaduan->investigasi_video : json_decode($pengaduan->investigasi_video, true)) : [];
                    $invVoice = !empty($pengaduan->investigasi_voice) ? (is_array($pengaduan->investigasi_voice) ? $pengaduan->investigasi_voice : json_decode($pengaduan->investigasi_voice, true)) : [];
                    $invDok = !empty($pengaduan->investigasi_dokumen) ? (is_array($pengaduan->investigasi_dokumen) ? $pengaduan->investigasi_dokumen : json_decode($pengaduan->investigasi_dokumen, true)) : [];
                @endphp

                @if (!empty($invFoto) || !empty($invVideo) || !empty($invVoice) || !empty($invDok))
                    <div style="border-top:1px dashed #cbd5e1; padding-top:12px; margin-top:14px;">
                        <label style="font-size:12.5px; font-weight:700; color:#0f172a; display:block; margin-bottom:8px;">
                            Lampiran Berkas Bukti Hasil Pemeriksaan:
                        </label>

                        {{-- Bukti Foto --}}
                        @if (!empty($invFoto))
                            <div style="margin-bottom:10px;">
                                <div style="font-size:11.5px; font-weight:700; color:#0284c7; margin-bottom:4px;">📸 Foto Bukti Pemeriksaan:</div>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    @foreach ($invFoto as $idx => $f)
                                        <a href="{{ $f }}" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#e0f2fe; border:1px solid #bae6fd; color:#0369a1; padding:5px 10px; border-radius:6px; font-size:11.5px; text-decoration:none; font-weight:600;">
                                            <span>📷 Foto {{ $idx + 1 }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Bukti Video --}}
                        @if (!empty($invVideo))
                            <div style="margin-bottom:10px;">
                                <div style="font-size:11.5px; font-weight:700; color:#0284c7; margin-bottom:4px;">🎥 Video Bukti Pemeriksaan:</div>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    @foreach ($invVideo as $idx => $v)
                                        <div style="background:#0f172a; border-radius:8px; padding:6px; max-width:480px;">
                                            <video controls style="width:100%; max-height:260px; border-radius:6px; outline:none;">
                                                <source src="{{ $v }}" type="video/mp4">
                                                Browser Anda tidak mendukung tag video.
                                            </video>
                                            <div style="text-align:right; margin-top:4px;">
                                                <a href="{{ $v }}" target="_blank" style="color:#38bdf8; font-size:11px; text-decoration:none;">Buka di tab baru ↗</a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Bukti Rekaman Suara / Voice Note --}}
                        @if (!empty($invVoice))
                            <div style="margin-bottom:10px;">
                                <div style="font-size:11.5px; font-weight:700; color:#0284c7; margin-bottom:4px;">🎙️ Rekaman Suara / Audio Bukti:</div>
                                <div style="display:flex; flex-direction:column; gap:6px; max-width:480px;">
                                    @foreach ($invVoice as $idx => $vo)
                                        <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:8px 10px;">
                                            <div style="font-size:11px; font-weight:700; color:#0369a1; margin-bottom:4px;">Rekaman #{{ $idx + 1 }}</div>
                                            <audio controls style="width:100%; height:32px;">
                                                <source src="{{ $vo }}">
                                                Browser Anda tidak mendukung pemutar audio.
                                            </audio>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Dokumen BAP --}}
                        @if (!empty($invDok))
                            <div style="margin-bottom:4px;">
                                <div style="font-size:11.5px; font-weight:700; color:#0284c7; margin-bottom:4px;">📑 Berkas Dokumen & BAP Resmi:</div>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    @foreach ($invDok as $idx => $doc)
                                        <a href="{{ $doc }}" target="_blank" style="display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; color:#334155; padding:5px 12px; border-radius:6px; font-size:11.5px; text-decoration:none; font-weight:600;">
                                            📄 Unduh BAP / Berkas #{{ $idx + 1 }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- Card Surat Putusan Sanksi Resmi (Jika SDM sudah menerbitkan) --}}
        @if (!empty($pengaduan->nomor_surat_putusan))
            <div class="ribbon-card" style="margin-bottom:20px; border-left:4px solid #0369a1; background:#f0f9ff; border:1.5px solid #bae6fd;">
                <div class="ribbon-head" style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <h2 style="font-size:16px; color:#0369a1; margin:0;">🏛️ Surat Putusan Sanksi Resmi (SDM)</h2>
                    <span style="background:#0284c7; color:#fff; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:800;">
                        KASUS SELESAI
                    </span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:12px; background:#fff; border:1px solid #e0f2fe; padding:12px; border-radius:8px;">
                    <div>
                        <div style="font-size:11.5px; color:#64748b; font-weight:600;">Nomor Surat Putusan:</div>
                        <div style="font-size:13.5px; font-weight:800; color:#0f172a;">{{ $pengaduan->nomor_surat_putusan }}</div>
                    </div>
                    <div>
                        <div style="font-size:11.5px; color:#64748b; font-weight:600;">Jenis Sanksi Disiplin:</div>
                        <div style="font-size:13.5px; font-weight:800; color:#dc2626;">{{ $pengaduan->jenis_sanksi }}</div>
                    </div>
                    @if (!empty($pengaduan->tanggal_surat_putusan))
                        <div>
                            <div style="font-size:11.5px; color:#64748b; font-weight:600;">Tanggal Ditetapkan:</div>
                            <div style="font-size:13px; font-weight:700; color:#334155;">{{ date('d F Y', strtotime($pengaduan->tanggal_surat_putusan)) }}</div>
                        </div>
                    @endif
                </div>

                @if (!empty($pengaduan->catatan_sdm))
                    <div style="margin-bottom:12px;">
                        <label style="font-size:12px; color:#475569; font-weight:600; display:block; margin-bottom:4px;">Diktum / Catatan Putusan SDM:</label>
                        <div style="background:#fff; border:1px solid #e2e8f0; padding:10px 12px; border-radius:8px; font-size:12.5px; color:#334155; line-height:1.5;">
                            {{ $pengaduan->catatan_sdm }}
                        </div>
                    </div>
                @endif

                @if (!empty($pengaduan->file_surat_putusan))
                    <div style="margin-top:10px;">
                        <a href="{{ $pengaduan->file_surat_putusan }}" target="_blank" style="display:inline-flex; align-items:center; gap:8px; background:#0284c7; color:#fff; padding:8px 16px; border-radius:8px; font-size:12.5px; font-weight:700; text-decoration:none; box-shadow:0 2px 4px rgba(2,132,199,0.2);">
                            <span>📥 Unduh Berkas Surat Putusan Sanksi</span>
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- KOLOM KANAN: PANEL AKSI & TIMELINE RIWAYAT --}}
    <div>
        {{-- ========================================================= --}}
        {{-- 1. PANEL AKSI KADIV --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'kadiv' && in_array($statusName, ['menungguKadiv', 'menungguVerifikasiKadiv']))
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #16a085;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#0f766e;">⚡ Tindakan Kadiv</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Verifikasi pengaduan masuk dan teruskan ke KSPI untuk peninjauan lebih lanjut.
                </p>

                <form method="POST" action="{{ route('pengaduan.kadiv-verifikasi', $pengaduan->id) }}" style="margin-bottom:14px;">
                    @csrf
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Keputusan Verifikasi:</label>
                        <select name="keputusan" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="terima">✓ Terima (Diteruskan ke KSPI)</option>
                            <option value="tolak">✕ Tolak (Catat & Teruskan ke KSPI)</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Catatan Kadiv (Opsional):</label>
                        <textarea name="catatan" rows="2" placeholder="Tuliskan catatan verifikasi..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#0f766e; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Kirim Verifikasi ke KSPI
                    </button>
                </form>

                <hr style="border:none; border-top:1px solid #e2e8f0; margin:14px 0;">

                {{-- Form Alihkan Kategori --}}
                <form method="POST" action="{{ route('pengaduan.kadiv-alihkan', $pengaduan->id) }}">
                    @csrf
                    <label style="font-weight:600; font-size:12px; color:#475569; display:block; margin-bottom:4px;">Salah Kategori? Alihkan:</label>
                    <div style="display:flex; gap:6px;">
                        <select name="kategori_baru" style="flex:1; border:1px solid var(--border); border-radius:6px; padding:6px 8px; font-size:12px;">
                            <option value="Pelanggaran Administrasi" {{ $pengaduan->kategori === 'Pelanggaran Administrasi' ? 'selected' : '' }}>Pelanggaran Administrasi</option>
                            <option value="Pelanggaran Teknik" {{ $pengaduan->kategori === 'Pelanggaran Teknik' ? 'selected' : '' }}>Pelanggaran Teknik</option>
                        </select>
                        <button type="submit" style="padding:6px 12px; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; border-radius:6px; font-weight:600; font-size:12px; cursor:pointer;">
                            Alihkan
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 2. PANEL AKSI KSPI (REVIEW AWAL) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'kspi' && $statusName === 'reviewKspi')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #2e86ab;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#0369a1;">⚡ Tindakan KSPI (Review Awal)</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Hasil verifikasi dari Kadiv telah masuk. Teruskan ke Direktur Utama atau tolak/arsipkan jika tidak berdasar.
                </p>

                <form method="POST" action="{{ route('pengaduan.kspi-teruskan-dirut', $pengaduan->id) }}" style="margin-bottom:12px;">
                    @csrf
                    <div class="field" style="margin-bottom:8px;">
                        <label style="font-weight:600; font-size:12px;">Catatan Pengantar ke Dirut (Opsional):</label>
                        <textarea name="catatan" rows="2" placeholder="Catatan telaah awal..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#0284c7; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        ➔ Teruskan ke Direktur Utama
                    </button>
                </form>

                <form method="POST" action="{{ route('pengaduan.kspi-tolak', $pengaduan->id) }}">
                    @csrf
                    <div class="field" style="margin-bottom:8px;">
                        <label style="font-weight:600; font-size:12px; color:#b91c1c;">Alasan Penolakan (Wajib jika ditolak):</label>
                        <textarea name="catatan" required rows="2" placeholder="Tuliskan alasan penolakan aduan..." style="width:100%; border:1px solid #fca5a5; border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" onclick="return confirm('Yakin ingin menolak dan mengarsipkan pengaduan ini?')" style="width:100%; padding:8px; background:#ef4444; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:12px; cursor:pointer;">
                        ✕ Tolak & Arsipkan
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 3. PANEL AKSI DIRUT (PERSETUJUAN TAHAP 1) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'dirut' && $statusName === 'menungguDirutTahap1')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #e67e22;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#c2410c;">⚡ Persetujuan Dirut (Tahap 1)</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Tentukan kelayakan pengaduan ini untuk dilakukan proses investigasi resmi.
                </p>

                <form method="POST" action="{{ route('pengaduan.dirut-tahap1', $pengaduan->id) }}">
                    @csrf
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Keputusan Direktur Utama:</label>
                        <select name="keputusan" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="terima">✓ Setujui (Layak Diinvestigasi)</option>
                            <option value="tolak">✕ Tolak (Arsipkan Pengaduan)</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Arahan / Catatan Dirut:</label>
                        <textarea name="catatan" rows="2" placeholder="Tuliskan arahan atau pertimbangan..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#ea580c; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Simpan Keputusan Tahap 1
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 4. PANEL AKSI KSPI (PILIH EKSEKUTOR INVESTIGASI) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'kspi' && $statusName === 'menungguPilihEksekutor')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #0284c7; background:#ffffff; box-shadow:0 10px 25px -5px rgba(2,132,199,0.12);">
                <div class="ribbon-head" style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="font-size:15px; color:#0369a1; margin:0; display:flex; align-items:center; gap:8px;">
                        <span>⚡</span> Penunjukan Tim Eksekutor Investigasi
                    </h2>
                    <span style="font-size:11px; background:#e0f2fe; color:#0284c7; padding:3px 8px; border-radius:12px; font-weight:700;">
                        KSPI Task
                    </span>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px; line-height:1.5;">
                    Direktur Utama telah menyetujui pengaduan ini. Tentukan <strong>tim eksekutor investigasi resmi</strong> (Kadiv SPI atau TPDPK — Dodi Sudrajat) untuk melaksanakan investigasi lapangan.
                </p>

                <!-- Tombol Shortcut Pemilihan Cepat Eksekutor Resmi (TPDPK & Kadiv SPI) -->
                <div style="margin-bottom:14px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; padding:12px 14px;">
                    <div style="font-size:11px; font-weight:700; color:#0369a1; text-transform:uppercase; margin-bottom:8px; letter-spacing:0.3px; display:flex; align-items:center; gap:6px;">
                        <span>⚡</span> Eksekutor Resmi Investigasi (Klik untuk Memilih):
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:8px;">
                        <!-- Opsi 1: TPDPK (Dodi Sudrajat, S.E., M.M.) -->
                        <div onclick="quickToggleTpdpk()" id="btn-quick-tpdpk" style="background:#fff; border:1.5px solid #7dd3fc; border-radius:10px; padding:9px 11px; cursor:pointer; display:flex; align-items:center; gap:10px; transition:all 0.2s; user-select:none;">
                            <div id="check-icon-tpdpk" style="width:22px; height:22px; border-radius:6px; border:2px solid #cbd5e1; background:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:900; color:#fff; flex-shrink:0;"></div>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="font-size:12px; font-weight:800; color:#0f172a;">TPDPK</span>
                                    <span style="font-size:10px; font-weight:700; background:#e0f2fe; color:#0284c7; padding:1px 6px; border-radius:4px;">Resmi</span>
                                </div>
                                <div style="font-size:11.5px; font-weight:700; color:#0369a1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    Dodi Sudrajat, S.E., M.M.
                                </div>
                                <div style="font-size:10.5px; color:#64748b;">NIK: 1711161 · Kepala SPI</div>
                            </div>
                        </div>

                        <!-- Opsi 2: Kadiv SPI Teknik (Nurkhuliyah) -->
                        <div onclick="quickToggleKadiv('teknik')" id="btn-quick-kadiv-teknik" style="background:#fff; border:1.5px solid #7dd3fc; border-radius:10px; padding:9px 11px; cursor:pointer; display:flex; align-items:center; gap:10px; transition:all 0.2s; user-select:none;">
                            <div id="check-icon-kadiv-teknik" style="width:22px; height:22px; border-radius:6px; border:2px solid #cbd5e1; background:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:900; color:#fff; flex-shrink:0;"></div>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="font-size:12px; font-weight:800; color:#0f172a;">Kadiv SPI Teknik</span>
                                    <span style="font-size:10px; font-weight:700; background:#e0f2fe; color:#0284c7; padding:1px 6px; border-radius:4px;">Resmi</span>
                                </div>
                                <div style="font-size:11.5px; font-weight:700; color:#0369a1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    Nurkhuliyah
                                </div>
                                <div style="font-size:10.5px; color:#64748b;">NIK: 1711251 · Divisi Teknik</div>
                            </div>
                        </div>

                        <!-- Opsi 3: Kadiv SPI Administrasi (Ghani Rashid Ahmad, S.E.) -->
                        <div onclick="quickToggleKadiv('administrasi')" id="btn-quick-kadiv-admin" style="background:#fff; border:1.5px solid #7dd3fc; border-radius:10px; padding:9px 11px; cursor:pointer; display:flex; align-items:center; gap:10px; transition:all 0.2s; user-select:none;">
                            <div id="check-icon-kadiv-admin" style="width:22px; height:22px; border-radius:6px; border:2px solid #cbd5e1; background:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:900; color:#fff; flex-shrink:0;"></div>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="font-size:12px; font-weight:800; color:#0f172a;">Kadiv SPI Admin</span>
                                    <span style="font-size:10px; font-weight:700; background:#e0f2fe; color:#0284c7; padding:1px 6px; border-radius:4px;">Resmi</span>
                                </div>
                                <div style="font-size:11.5px; font-weight:700; color:#0369a1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    Ghani Rashid Ahmad, S.E.
                                </div>
                                <div style="font-size:10.5px; color:#64748b;">NIK: 1711571 · Divisi Admin</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" id="form-kspi-eksekutor" action="{{ route('pengaduan.kspi-pilih-eksekutor', $pengaduan->id) }}">
                    @csrf

                    <!-- Hidden fields yang diisi otomatis secara reaktif -->
                    <input type="hidden" name="eksekutor" id="input_eksekutor" value="tpdpk">
                    <input type="hidden" name="divisi_kadiv" id="input_divisi_kadiv" value="">
                    <input type="hidden" name="petugas_investigasi" id="input_petugas_investigasi" value="">
                    <div id="container-hidden-pegawai-ids"></div>

                    <!-- Area Kartu Eksekutor Terpilih -->
                    <div style="margin-bottom:14px;">
                        <label style="font-weight:700; font-size:12px; color:#0f172a; display:block; margin-bottom:6px;">
                            Daftar Eksekutor Investigasi Ditunjuk:
                        </label>

                        <!-- Box Kosong (Belum ada yang dipilih) -->
                        <div id="box-empty-eksekutor" onclick="openPegawaiPickerModal()" style="border:2px dashed #7dd3fc; background:#f0f9ff; border-radius:12px; padding:22px 16px; text-align:center; cursor:pointer; transition:all 0.2s;">
                            <div style="font-size:26px; margin-bottom:6px;">👥➕</div>
                            <div style="font-weight:700; color:#0284c7; font-size:13.5px;">+ Pilih Pegawai Eksekutor Investigasi</div>
                            <div style="font-size:11.5px; color:#64748b; margin-top:3px;">
                                Klik di sini untuk membuka katalog pegawai SIMPEG & filter Kadiv / Staf SPI
                            </div>
                        </div>

                        <!-- Box List Pegawai Terpilih -->
                        <div id="box-selected-eksekutor" style="display:none;">
                            <div id="list-selected-pegawai-cards" style="display:flex; flex-direction:column; gap:8px; margin-bottom:10px;"></div>
                            
                            <button type="button" onclick="openPegawaiPickerModal()" style="width:100%; background:#fff; border:1.5px dashed #0284c7; color:#0284c7; padding:8px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                                <span>➕</span> Tambah / Ubah Anggota Tim Eksekutor
                            </button>
                        </div>
                    </div>

                    <!-- Instruksi Catatan Penugasan -->
                    <div class="field" style="margin-bottom:14px;">
                        <label style="font-weight:600; font-size:12px; color:#374151; display:block; margin-bottom:4px;">
                            Catatan Penugasan & Instruksi Investigasi (Opsional):
                        </label>
                        <textarea name="catatan" rows="3" placeholder="Contoh: Lakukan pengecekan fisik lokasi meter dan konfirmasi kepada pelapor paling lambat 3 hari kerja..." style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:12.5px; line-height:1.5;"></textarea>
                    </div>

                    <button type="submit" id="btn-submit-penugasan" style="width:100%; padding:11px; background:#0284c7; color:#fff; border:none; border-radius:8px; font-weight:800; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 6px -1px rgba(2,132,199,0.25); transition:all 0.2s;">
                        <span>🚀</span> <span id="label-btn-submit">Tetapkan Eksekutor & Buat Tugas</span>
                    </button>
                </form>
            </div>

            {{-- ========================================================= --}}
            {{-- MODAL INTERAKTIF: MULTI-PICKER PEGAWAI EKSEKUTOR --}}
            {{-- ========================================================= --}}
            <div id="modal-picker-pegawai" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.65); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
                <div style="background:#ffffff; border-radius:20px; width:100%; max-width:680px; max-height:88vh; display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden; animation:modalPop 0.2s ease-out;">
                    
                    <!-- Header Modal -->
                    <div style="padding:16px 20px; background:linear-gradient(135deg, #0d2c6e 0%, #0284c7 100%); color:#ffffff; display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:20px;">
                                👥
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:16px; font-weight:800; color:#ffffff;">Pilih Tim Eksekutor Investigasi</h3>
                                <p style="margin:2px 0 0; font-size:12px; color:rgba(255,255,255,0.85);">
                                    Pilih 1 atau beberapa pegawai SIMPEG sebagai tim pemeriksa
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="closePegawaiPickerModal()" style="background:rgba(255,255,255,0.15); border:none; color:#ffffff; width:32px; height:32px; border-radius:50%; font-size:16px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                            ✕
                        </button>
                    </div>

                    <!-- Search Input & Filter Tabs -->
                    <div style="padding:14px 20px 10px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                        <!-- Search Box -->
                        <div style="position:relative; margin-bottom:10px;">
                            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:15px; color:#94a3b8;">🔍</span>
                            <input type="text" id="picker-search-input" oninput="onPickerSearchChange(this.value)" placeholder="Cari nama pegawai, NIK, jabatan, atau divisi..." style="width:100%; padding:9px 36px 9px 36px; border:1px solid #cbd5e1; border-radius:10px; font-size:13px; background:#ffffff; outline:none; transition:border 0.2s;">
                            <button type="button" id="picker-search-clear" onclick="clearPickerSearch()" style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); background:#e2e8f0; border:none; border-radius:50%; width:20px; height:20px; font-size:11px; cursor:pointer; align-items:center; justify-content:center; color:#475569;">✕</button>
                        </div>

                        <!-- Filter Chips / Tabs -->
                        <div style="display:flex; gap:6px; overflow-x:auto; padding-bottom:4px;">
                            <button type="button" class="tab-filter-btn active" id="tab-btn-ALL" onclick="setPickerFilter('ALL')">
                                Semua Pegawai (<span id="count-tab-all">0</span>)
                            </button>
                            <button type="button" class="tab-filter-btn" id="tab-btn-EKSEKUTOR" onclick="setPickerFilter('EKSEKUTOR')">
                                Eksekutor Resmi (<span id="count-tab-eksekutor">3</span>)
                            </button>
                            <button type="button" class="tab-filter-btn" id="tab-btn-TPDPK" onclick="setPickerFilter('TPDPK')">
                                TPDPK - Dodi Sudrajat (<span id="count-tab-tpdpk">1</span>)
                            </button>
                            <button type="button" class="tab-filter-btn" id="tab-btn-KADIV" onclick="setPickerFilter('KADIV')">
                                Kadiv SPI (<span id="count-tab-kadiv">2</span>)
                            </button>
                            <button type="button" class="tab-filter-btn" id="tab-btn-LAINNYA" onclick="setPickerFilter('LAINNYA')">
                                Pegawai Lainnya (<span id="count-tab-lainnya">0</span>)
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Pegawai List -->
                    <div id="picker-list-container" style="flex:1; overflow-y:auto; padding:12px 20px; min-height:280px; max-height:420px;">
                        <!-- Rendered by JavaScript -->
                    </div>

                    <!-- Footer Action Bar -->
                    <div style="padding:14px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span id="label-modal-selected-count" style="font-size:12.5px; font-weight:700; color:#0369a1; background:#e0f2fe; border:1px solid #bae6fd; padding:5px 12px; border-radius:20px;">
                                0 Pegawai Dipilih
                            </span>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button type="button" onclick="closePegawaiPickerModal()" style="background:#fff; border:1px solid #cbd5e1; color:#475569; padding:8px 16px; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer;">
                                Batal
                            </button>
                            <button type="button" onclick="applyPegawaiSelection()" style="background:#0284c7; border:none; color:#ffffff; padding:8px 18px; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; box-shadow:0 4px 6px -1px rgba(2,132,199,0.25);">
                                ✓ Terapkan Pilihan
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <style>
                .tab-filter-btn {
                    background: #ffffff;
                    border: 1px solid #cbd5e1;
                    color: #475569;
                    font-size: 11.5px;
                    font-weight: 600;
                    padding: 5px 12px;
                    border-radius: 20px;
                    cursor: pointer;
                    white-space: nowrap;
                    transition: all 0.2s;
                }
                .tab-filter-btn:hover {
                    border-color: #0284c7;
                    color: #0284c7;
                }
                .tab-filter-btn.active {
                    background: #0284c7;
                    border-color: #0284c7;
                    color: #ffffff;
                    box-shadow: 0 2px 4px rgba(2, 132, 199, 0.25);
                }
                .picker-item-row {
                    display: flex;
                    align-items: center;
                    padding: 10px 12px;
                    border-radius: 12px;
                    border: 1px solid #e2e8f0;
                    margin-bottom: 8px;
                    background: #ffffff;
                    cursor: pointer;
                    transition: all 0.15s ease-in-out;
                }
                .picker-item-row:hover {
                    border-color: #7dd3fc;
                    background: #f0f9ff;
                }
                .picker-item-row.selected {
                    border-color: #0284c7;
                    background: #f0f9ff;
                    box-shadow: 0 2px 5px rgba(2, 132, 199, 0.08);
                }
                @keyframes modalPop {
                    from { transform: scale(0.95); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
            </style>

            <script>
            // Master data pegawai dari database
            const rawPegawaiList = @json($daftarPegawai ?? []);

            // Klasifikasi role & divisi: TPDPK strictly Dodi Sudrajat, Kadiv SPI Teknik & Admin
            function classifyPegawai(p) {
                const role = (p.role || '').toLowerCase();
                const jabatan = (p.jabatan || '').toLowerCase();
                const nik = (p.nik || '').trim();
                const name = (p.name || '').toLowerCase();

                let category = 'LAINNYA';
                let categoryLabel = 'Pegawai PDAM';
                let badgeBg = '#f1f5f9';
                let badgeColor = '#475569';
                let isOfficialExecutor = false;

                // 1. TPDPK: HANYA Dodi Sudrajat (NIK 1711161, Kepala SPI)
                if (nik === '1711161' || role === 'kspi' || name.includes('dodi sudrajat')) {
                    category = 'TPDPK';
                    categoryLabel = 'TPDPK (Dodi Sudrajat)';
                    badgeBg = '#e0f2fe';
                    badgeColor = '#0284c7';
                    isOfficialExecutor = true;
                }
                // 2. Kadiv SPI: Nurkhuliyah (Teknik) & Ghani Rashid Ahmad (Admin)
                else if (nik === '1711251' || nik === '1711571' || role.includes('kadiv') || (jabatan.includes('kadiv') && jabatan.includes('spi')) || (jabatan.includes('kepala divisi') && jabatan.includes('spi'))) {
                    category = 'KADIV';
                    const isTeknik = (p.divisi_kadiv === 'teknik' || nik === '1711251' || jabatan.includes('teknik'));
                    categoryLabel = isTeknik ? 'Kadiv SPI Teknik' : 'Kadiv SPI Admin';
                    badgeBg = '#e0f2fe';
                    badgeColor = '#0369a1';
                    isOfficialExecutor = true;
                }
                // 3. Staf Divisi SPI
                else if (jabatan.includes('spi') || jabatan.includes('pengawasan umum') || jabatan.includes('pengawasan teknik')) {
                    category = 'STAF_SPI';
                    categoryLabel = 'Staf Divisi SPI';
                    badgeBg = '#f0f9ff';
                    badgeColor = '#0369a1';
                }

                const initials = (p.name || 'P')
                    .trim()
                    .split(/\s+/)
                    .slice(0, 2)
                    .map(w => w[0])
                    .join('')
                    .toUpperCase();

                return {
                    id: String(p.id),
                    nik: p.nik || '',
                    name: p.name || 'Pegawai',
                    jabatan: p.jabatan || '-',
                    unitKerja: p.unit_kerja || '-',
                    role: p.role,
                    divisiKadiv: p.divisi_kadiv,
                    fotoUrl: p.foto_url,
                    category,
                    categoryLabel,
                    badgeBg,
                    badgeColor,
                    initials,
                    isOfficialExecutor
                };
            }

            const parsedPegawaiList = rawPegawaiList.map(classifyPegawai);

            // State
            let selectedPegawaiMap = new Map(); // id -> pegawai object
            let modalDraftSelectedMap = new Map(); // id -> pegawai object
            let currentFilter = 'ALL';
            let currentSearchQuery = '';

            // Update badge counts on tabs
            document.addEventListener('DOMContentLoaded', () => {
                const countAll = parsedPegawaiList.length;
                const countEksekutor = parsedPegawaiList.filter(p => p.isOfficialExecutor).length;
                const countTpdpk = parsedPegawaiList.filter(p => p.category === 'TPDPK').length;
                const countKadiv = parsedPegawaiList.filter(p => p.category === 'KADIV').length;
                const countLainnya = parsedPegawaiList.filter(p => !p.isOfficialExecutor).length;

                const elAll = document.getElementById('count-tab-all');
                const elEks = document.getElementById('count-tab-eksekutor');
                const elTpd = document.getElementById('count-tab-tpdpk');
                const elKdv = document.getElementById('count-tab-kadiv');
                const elLai = document.getElementById('count-tab-lainnya');

                if (elAll) elAll.innerText = countAll;
                if (elEks) elEks.innerText = countEksekutor;
                if (elTpd) elTpd.innerText = countTpdpk;
                if (elKdv) elKdv.innerText = countKadiv;
                if (elLai) elLai.innerText = countLainnya;

                updateQuickToggleUi();
            });

            // Quick Toggle UI Synchronizer
            function updateQuickToggleUi() {
                const btnTpdpk = document.getElementById('btn-quick-tpdpk');
                const chkTpdpk = document.getElementById('check-icon-tpdpk');
                const btnKadivTeknik = document.getElementById('btn-quick-kadiv-teknik');
                const chkKadivTeknik = document.getElementById('check-icon-kadiv-teknik');
                const btnKadivAdmin = document.getElementById('btn-quick-kadiv-admin');
                const chkKadivAdmin = document.getElementById('check-icon-kadiv-admin');

                const pTpdpk = parsedPegawaiList.find(p => p.category === 'TPDPK' || p.nik === '1711161');
                const pKadivTeknik = parsedPegawaiList.find(p => p.category === 'KADIV' && (p.nik === '1711251' || p.divisiKadiv === 'teknik' || p.jabatan.toLowerCase().includes('teknik')));
                const pKadivAdmin = parsedPegawaiList.find(p => p.category === 'KADIV' && (p.nik === '1711571' || p.divisiKadiv === 'administrasi' || p.jabatan.toLowerCase().includes('umum') || p.jabatan.toLowerCase().includes('keuangan')));

                function applyBtnStyle(btn, chk, isSelected) {
                    if (!btn || !chk) return;
                    if (isSelected) {
                        btn.style.borderColor = '#0284c7';
                        btn.style.background = '#e0f2fe';
                        btn.style.boxShadow = '0 2px 6px rgba(2,132,199,0.18)';
                        chk.style.background = '#0284c7';
                        chk.style.borderColor = '#0284c7';
                        chk.innerText = '✓';
                    } else {
                        btn.style.borderColor = '#7dd3fc';
                        btn.style.background = '#ffffff';
                        btn.style.boxShadow = 'none';
                        chk.style.background = '#ffffff';
                        chk.style.borderColor = '#cbd5e1';
                        chk.innerText = '';
                    }
                }

                if (pTpdpk) applyBtnStyle(btnTpdpk, chkTpdpk, selectedPegawaiMap.has(pTpdpk.id));
                if (pKadivTeknik) applyBtnStyle(btnKadivTeknik, chkKadivTeknik, selectedPegawaiMap.has(pKadivTeknik.id));
                if (pKadivAdmin) applyBtnStyle(btnKadivAdmin, chkKadivAdmin, selectedPegawaiMap.has(pKadivAdmin.id));
            }

            // Quick Toggle TPDPK (Dodi Sudrajat)
            function quickToggleTpdpk() {
                const target = parsedPegawaiList.find(p => p.category === 'TPDPK' || p.nik === '1711161' || p.name.toLowerCase().includes('dodi sudrajat'));
                if (!target) return;
                if (selectedPegawaiMap.has(target.id)) {
                    selectedPegawaiMap.delete(target.id);
                } else {
                    selectedPegawaiMap.set(target.id, target);
                }
                renderSelectedCards();
            }

            // Quick Toggle Kadiv SPI Teknik / Administrasi
            function quickToggleKadiv(divisi) {
                const target = parsedPegawaiList.find(p => p.category === 'KADIV' && (
                    (divisi === 'teknik' && (p.divisiKadiv === 'teknik' || p.nik === '1711251' || p.jabatan.toLowerCase().includes('teknik'))) ||
                    (divisi === 'administrasi' && (p.divisiKadiv === 'administrasi' || p.nik === '1711571' || p.jabatan.toLowerCase().includes('umum') || p.jabatan.toLowerCase().includes('keuangan')))
                ));
                if (!target) return;
                if (selectedPegawaiMap.has(target.id)) {
                    selectedPegawaiMap.delete(target.id);
                } else {
                    selectedPegawaiMap.set(target.id, target);
                }
                renderSelectedCards();
            }

            // Modal Controls
            function openPegawaiPickerModal() {
                modalDraftSelectedMap = new Map(selectedPegawaiMap);
                document.getElementById('modal-picker-pegawai').style.display = 'flex';
                currentSearchQuery = '';
                document.getElementById('picker-search-input').value = '';
                document.getElementById('picker-search-clear').style.display = 'none';
                renderPickerList();
                updateModalCounter();
            }

            function closePegawaiPickerModal() {
                document.getElementById('modal-picker-pegawai').style.display = 'none';
            }

            function setPickerFilter(filterName) {
                currentFilter = filterName;
                document.querySelectorAll('.tab-filter-btn').forEach(btn => btn.classList.remove('active'));
                const targetBtn = document.getElementById('tab-btn-' + filterName);
                if (targetBtn) targetBtn.classList.add('active');
                renderPickerList();
            }

            function onPickerSearchChange(val) {
                currentSearchQuery = (val || '').trim().toLowerCase();
                const clearBtn = document.getElementById('picker-search-clear');
                if (clearBtn) clearBtn.style.display = currentSearchQuery ? 'flex' : 'none';
                renderPickerList();
            }

            function clearPickerSearch() {
                document.getElementById('picker-search-input').value = '';
                onPickerSearchChange('');
            }

            function toggleModalItem(id) {
                const target = parsedPegawaiList.find(p => p.id === id);
                if (!target) return;

                if (modalDraftSelectedMap.has(id)) {
                    modalDraftSelectedMap.delete(id);
                } else {
                    modalDraftSelectedMap.set(id, target);
                }

                // Update row styling without re-rendering entire list
                const rowEl = document.getElementById('picker-row-' + id);
                const chkEl = document.getElementById('picker-chk-' + id);
                const isSelected = modalDraftSelectedMap.has(id);

                if (rowEl) {
                    if (isSelected) rowEl.classList.add('selected');
                    else rowEl.classList.remove('selected');
                }
                if (chkEl) chkEl.checked = isSelected;

                updateModalCounter();
            }

            function updateModalCounter() {
                const count = modalDraftSelectedMap.size;
                const counterEl = document.getElementById('label-modal-selected-count');
                if (counterEl) {
                    counterEl.innerText = count + ' Pegawai Dipilih';
                }
            }

            function renderPickerList() {
                const container = document.getElementById('picker-list-container');
                if (!container) return;

                const filtered = parsedPegawaiList.filter(p => {
                    // Filter tab
                    if (currentFilter === 'EKSEKUTOR' && !p.isOfficialExecutor) return false;
                    if (currentFilter === 'TPDPK' && p.category !== 'TPDPK') return false;
                    if (currentFilter === 'KADIV' && p.category !== 'KADIV') return false;
                    if (currentFilter === 'LAINNYA' && p.isOfficialExecutor) return false;

                    // Filter search query
                    if (currentSearchQuery) {
                        const matchName = p.name.toLowerCase().includes(currentSearchQuery);
                        const matchNik = p.nik.toLowerCase().includes(currentSearchQuery);
                        const matchJabatan = p.jabatan.toLowerCase().includes(currentSearchQuery);
                        const matchUnit = p.unitKerja.toLowerCase().includes(currentSearchQuery);
                        if (!matchName && !matchNik && !matchJabatan && !matchUnit) return false;
                    }

                    return true;
                });

                if (filtered.length === 0) {
                    container.innerHTML = `
                        <div style="text-align:center; padding:32px 10px; color:#94a3b8;">
                            <div style="font-size:32px; margin-bottom:8px;">🔍</div>
                            <div style="font-size:13.5px; font-weight:600; color:#64748b;">Pegawai Tidak Ditemukan</div>
                            <div style="font-size:11.5px; margin-top:2px;">Coba gunakan kata kunci nama atau NIK yang lain.</div>
                        </div>
                    `;
                    return;
                }

                let html = '';
                filtered.forEach(p => {
                    const isSelected = modalDraftSelectedMap.has(p.id);
                    html += `
                        <div class="picker-item-row ${isSelected ? 'selected' : ''}" id="picker-row-${p.id}" onclick="toggleModalItem('${p.id}')">
                            <input type="checkbox" id="picker-chk-${p.id}" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleModalItem('${p.id}')" style="margin-right:12px; width:17px; height:17px; accent-color:#0284c7; cursor:pointer;">
                            
                            <div style="width:38px; height:38px; border-radius:50%; background:${isSelected ? '#0284c7' : '#0d2c6e'}; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; margin-right:12px; flex-shrink:0;">
                                ${p.initials}
                            </div>

                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <span style="font-weight:700; font-size:13.5px; color:#0f172a;">${p.name}</span>
                                    <span style="background:${p.badgeBg}; color:${p.badgeColor}; font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:6px;">
                                        ${p.categoryLabel}
                                    </span>
                                </div>
                                <div style="font-size:11.5px; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <span>${p.jabatan}</span>
                                    <span>•</span>
                                    <span>NIK: <strong>${p.nik}</strong></span>
                                    <span>•</span>
                                    <span>${p.unitKerja}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            }

            function applyPegawaiSelection() {
                selectedPegawaiMap = new Map(modalDraftSelectedMap);
                closePegawaiPickerModal();
                renderSelectedCards();
            }

            function removeExecutor(id) {
                selectedPegawaiMap.delete(id);
                renderSelectedCards();
            }

            function renderSelectedCards() {
                const emptyBox = document.getElementById('box-empty-eksekutor');
                const selectedBox = document.getElementById('box-selected-eksekutor');
                const cardsContainer = document.getElementById('list-selected-pegawai-cards');
                const hiddenContainer = document.getElementById('container-hidden-pegawai-ids');
                const submitBtn = document.getElementById('btn-submit-penugasan');
                const submitLabel = document.getElementById('label-btn-submit');

                const selectedArray = Array.from(selectedPegawaiMap.values());
                const count = selectedArray.length;

                // Sync hidden inputs
                hiddenContainer.innerHTML = '';
                selectedArray.forEach(p => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'pegawai_ids[]';
                    input.value = p.id;
                    hiddenContainer.appendChild(input);
                });

                // Tentukan auto metadata untuk form
                const hasKadiv = selectedArray.some(p => p.category === 'KADIV');
                const hasTpdpk = selectedArray.some(p => p.category === 'TPDPK');
                const inputEksekutor = document.getElementById('input_eksekutor');
                const inputDivisi = document.getElementById('input_divisi_kadiv');
                const inputPetugas = document.getElementById('input_petugas_investigasi');

                if (hasTpdpk) {
                    inputEksekutor.value = 'tpdpk';
                    inputDivisi.value = '';
                } else if (hasKadiv) {
                    inputEksekutor.value = 'kadiv';
                    const kadivItem = selectedArray.find(p => p.category === 'KADIV');
                    if (kadivItem && (kadivItem.divisiKadiv === 'teknik' || kadivItem.nik === '1711251' || kadivItem.jabatan.toLowerCase().includes('teknik'))) {
                        inputDivisi.value = 'teknik';
                    } else {
                        inputDivisi.value = 'administrasi';
                    }
                } else {
                    inputEksekutor.value = 'tpdpk';
                    inputDivisi.value = '';
                }

                inputPetugas.value = selectedArray.map(p => {
                    if (p.category === 'TPDPK') return p.name + ' (TPDPK)';
                    if (p.category === 'KADIV') return p.name + ' (' + p.categoryLabel + ')';
                    return p.name;
                }).join(', ');

                // Update quick toggle buttons UI state
                updateQuickToggleUi();

                if (count === 0) {
                    emptyBox.style.display = 'block';
                    selectedBox.style.display = 'none';
                    cardsContainer.innerHTML = '';
                    submitBtn.style.opacity = '0.6';
                    submitBtn.disabled = true;
                    submitLabel.innerText = 'Tetapkan Eksekutor & Buat Tugas';
                } else {
                    emptyBox.style.display = 'none';
                    selectedBox.style.display = 'block';
                    submitBtn.style.opacity = '1';
                    submitBtn.disabled = false;
                    submitLabel.innerText = `Tetapkan ${count} Eksekutor & Buat Tugas`;

                    let cardsHtml = '';
                    selectedArray.forEach(p => {
                        cardsHtml += `
                            <div style="display:flex; align-items:center; justify-content:space-between; background:#ffffff; border:1.5px solid #38bdf8; border-radius:10px; padding:10px 12px; box-shadow:0 2px 4px rgba(2,132,199,0.06);">
                                <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                                    <div style="width:36px; height:36px; border-radius:50%; background:#0284c7; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; flex-shrink:0;">
                                        ${p.initials}
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="font-weight:700; font-size:13px; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            ${p.name}
                                        </div>
                                        <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                            <span style="background:${p.badgeBg}; color:${p.badgeColor}; padding:1px 6px; border-radius:4px; font-weight:700; font-size:10px;">
                                                ${p.categoryLabel}
                                            </span>
                                            · NIK: ${p.nik} · ${p.jabatan}
                                        </div>
                                    </div>
                                </div>
                                <button type="button" onclick="removeExecutor('${p.id}')" title="Hapus dari tim" style="background:#fee2e2; border:none; color:#ef4444; width:28px; height:28px; border-radius:6px; cursor:pointer; font-weight:800; font-size:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-left:8px; transition:all 0.15s;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                                    ✕
                                </button>
                            </div>
                        `;
                    });
                    cardsContainer.innerHTML = cardsHtml;
                }
            }

            // Client side validation on submit
            document.getElementById('form-kspi-eksekutor').addEventListener('submit', function(e) {
                if (selectedPegawaiMap.size === 0) {
                    e.preventDefault();
                    alert('Harap pilih minimal 1 pegawai eksekutor investigasi sebelum menetapkan tugas.');
                    openPegawaiPickerModal();
                }
            });
            </script>
        @endif

        {{-- ========================================================= --}}
        {{-- 5. PANEL AKSI TPDPK / KADIV (INPUT HASIL INVESTIGASI) --}}
        {{-- ========================================================= --}}
        @php
            $isInvestigator = ($myRole === 'tpdpk') ||
                              ($myRole === 'kadiv') ||
                              ($myRole === 'kspi' && in_array($pengaduan->eksekutor, ['tpdpk', 'kspi'])) ||
                              ($ctx['nik'] === '1711161') ||
                              (!empty($pengaduan->executor_id) && $pengaduan->executor_id === $ctx['id']);
        @endphp
        @if ($isInvestigator && in_array($statusName, ['investigasiBerjalan', 'revisiInvestigasi']))
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #0284c7;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#0369a1;">⚡ Laporan Hasil Investigasi & Surat Rekomendasi ({{ $pengaduan->eksekutor === 'kadiv' ? 'Kadiv SPI' : 'TPDPK' }})</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Tentukan kesimpulan pemeriksaan (Terbukti / Tidak Terbukti), uraikan fakta temuan, lampirkan bukti multimedia, serta terbitkan Surat Rekomendasi langsung ke Direktur Utama.
                </p>

                <form method="POST" action="{{ route('pengaduan.tpdpk-hasil-investigasi', $pengaduan->id) }}" enctype="multipart/form-data">
                    @csrf

                    <!-- Pilihan Kesimpulan Investigasi: Terbukti vs Tidak Terbukti -->
                    <div style="margin-bottom:14px;">
                        <label style="font-weight:700; font-size:12px; color:#0f172a; display:block; margin-bottom:6px;">
                            Kesimpulan Hasil Investigasi *:
                        </label>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <label id="opt-kesimpulan-terbukti" style="border:2px solid #22c55e; background:#f0fdf4; border-radius:10px; padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s;">
                                <input type="radio" name="kesimpulan_investigasi" value="terbukti" required checked onchange="toggleKesimpulanStyle('terbukti')" style="accent-color:#16a34a; width:17px; height:17px;">
                                <div>
                                    <div style="font-weight:800; font-size:13px; color:#15803d;">⚖️ TERBUKTI</div>
                                    <div style="font-size:10.5px; color:#166534;">Pelanggaran terbukti sah</div>
                                </div>
                            </label>
                            <label id="opt-kesimpulan-tidak-terbukti" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:10px; padding:10px 12px; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s;">
                                <input type="radio" name="kesimpulan_investigasi" value="tidak_terbukti" required onchange="toggleKesimpulanStyle('tidak_terbukti')" style="accent-color:#dc2626; width:17px; height:17px;">
                                <div>
                                    <div style="font-weight:800; font-size:13px; color:#475569;">🛡️ TIDAK TERBUKTI</div>
                                    <div style="font-size:10.5px; color:#64748b;">Tidak terbukti / bukti nihil</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Uraian Temuan Fakta / Berita Acara -->
                    <div class="field" style="margin-bottom:12px;">
                        <label style="font-weight:600; font-size:12px; color:#0f172a; display:block; margin-bottom:4px;">
                            Uraian Temuan Fakta / Berita Acara Pemeriksaan *
                        </label>
                        <textarea name="hasil_investigasi" rows="4" required placeholder="Tuliskan kronologi dan fakta hasil pemeriksaan lapangan secara terperinci..." style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:12.5px; line-height:1.5;"></textarea>
                    </div>

                    <!-- Surat Rekomendasi Sanksi + Template Generator -->
                    <div class="field" style="margin-bottom:14px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <label style="font-weight:600; font-size:12px; color:#0f172a;">Surat Rekomendasi Sanksi Resmi *</label>
                            <button type="button" onclick="isiTemplateRekomendasi()" style="background:#e0f2fe; border:1px solid #7dd3fc; color:#0369a1; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                                <span>📋</span> Gunakan Template Surat
                            </button>
                        </div>
                        <textarea id="input_surat_rekomendasi" name="surat_rekomendasi" rows="4" required placeholder="Uraikan isi rekomendasi tindakan atau sanksi disiplin..." style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:12.5px; line-height:1.5;"></textarea>
                    </div>

                    <!-- Unggah Bukti Multimedia (Foto, Video, Voice Note, Dokumen) -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; margin-bottom:14px;">
                        <div style="font-weight:700; font-size:12px; color:#0f172a; margin-bottom:8px;">
                            📎 Unggah Lampiran Bukti Pemeriksaan (Opsional):
                        </div>
                        <div style="display:grid; grid-template-columns:1fr; gap:8px;">
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:2px;">📷 Foto Bukti (JPG/PNG):</label>
                                <input type="file" name="foto_bukti[]" multiple accept="image/*" style="font-size:11.5px; width:100%;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:2px;">🎥 Video Bukti (MP4/MOV/MKV/WebM):</label>
                                <input type="file" name="video_bukti[]" multiple accept="video/*" style="font-size:11.5px; width:100%;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:2px;">🎙️ Rekaman Suara / Audio (MP3/WAV/M4A):</label>
                                <input type="file" name="voice_bukti[]" multiple accept="audio/*" style="font-size:11.5px; width:100%;">
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:2px;">📑 Dokumen Berita Acara / BAP (PDF/DOC):</label>
                                <input type="file" name="dokumen_investigasi[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx" style="font-size:11.5px; width:100%;">
                            </div>
                        </div>
                    </div>

                    <button type="submit" style="width:100%; padding:11px; background:#0284c7; color:#fff; border:none; border-radius:8px; font-weight:800; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 6px -1px rgba(2,132,199,0.25);">
                        <span>🚀</span> Kirim Rekomendasi Langsung ke Direktur Utama
                    </button>
                </form>
            </div>

            <script>
            function toggleKesimpulanStyle(val) {
                const optTerbukti = document.getElementById('opt-kesimpulan-terbukti');
                const optTidak = document.getElementById('opt-kesimpulan-tidak-terbukti');
                if (val === 'terbukti') {
                    optTerbukti.style.borderColor = '#22c55e';
                    optTerbukti.style.background = '#f0fdf4';
                    optTidak.style.borderColor = '#cbd5e1';
                    optTidak.style.background = '#ffffff';
                } else {
                    optTidak.style.borderColor = '#ef4444';
                    optTidak.style.background = '#fef2f2';
                    optTerbukti.style.borderColor = '#cbd5e1';
                    optTerbukti.style.background = '#ffffff';
                }
            }

            function isiTemplateRekomendasi() {
                const radio = document.querySelector('input[name="kesimpulan_investigasi"]:checked');
                const val = radio ? radio.value : 'terbukti';
                const nomor = "{{ $pengaduan->nomor_pengaduan }}";
                const terlapor = "{{ $pengaduan->pihak_terlapor }}";
                const kategori = "{{ $pengaduan->kategori }}";
                const input = document.getElementById('input_surat_rekomendasi');

                if (val === 'terbukti') {
                    input.value = `SURAT REKOMENDASI HASIL INVESTIGASI\nNomor: REK/SPI/${new Date().getFullYear()}/${nomor.replace(/\\D/g, '').slice(-4) || '001'}\n\nBerdasarkan hasil investigasi dan pemeriksaan lapangan atas Pengaduan No. ${nomor} (${kategori}) mengenai terlapor Sdr/i ${terlapor}, Tim Pemeriksa menyimpulkan bahwa dugaan pelanggaran TERBUKTI secara sah dan meyakinkan.\n\nREKOMENDASI:\n1. Menjatuhkan sanksi disiplin kepada Sdr/i ${terlapor} sesuai ketentuan peraturan kepegawaian perusahaan yang berlaku.\n2. Meneruskan berkas perkara ke Bagian SDM untuk menerbitkan Surat Putusan Sanksi Resmi.\n3. Melakukan evaluasi SOP dan pembinaan berkala di unit kerja terkait guna mencegah pelanggaran serupa.`;
                } else {
                    input.value = `SURAT REKOMENDASI HASIL INVESTIGASI\nNomor: REK/SPI/${new Date().getFullYear()}/${nomor.replace(/\\D/g, '').slice(-4) || '001'}\n\nBerdasarkan hasil investigasi dan pemeriksaan lapangan atas Pengaduan No. ${nomor} (${kategori}) mengenai terlapor Sdr/i ${terlapor}, Tim Pemeriksa menyimpulkan bahwa dugaan pelanggaran TIDAK TERBUKTI dan tidak ditemukan bukti yang memadai.\n\nREKOMENDASI:\n1. Menghentikan proses pemeriksaan dan mengarsipkan pengaduan ini secara resmi.\n2. Merehabilitasi dan memulihkan nama baik Sdr/i ${terlapor}.\n3. Menjaga kerahasiaan proses pengaduan guna memelihara kondusifitas lingkungan kerja.`;
                }
            }
            </script>
        @endif

        {{-- ========================================================= --}}
        {{-- 6. PANEL AKSI KSPI (REVIEW HASIL INVESTIGASI - LEGACY) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'kspi' && $statusName === 'menungguReviewKspi')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #2e86ab;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#0369a1;">⚡ Review Hasil Investigasi</h2>
                </div>
                <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                    Periksa kelengkapan laporan investigasi. Jika sesuai, teruskan ke Direktur Utama untuk persetujuan akhir.
                </p>

                <form method="POST" action="{{ route('pengaduan.kspi-review-hasil', $pengaduan->id) }}">
                    @csrf
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Hasil Peninjauan KSPI:</label>
                        <select name="sesuai" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="1">✓ Laporan Sesuai & Lengkap (Teruskan ke Dirut)</option>
                            <option value="0">✕ Belum Lengkap (Kembalikan untuk Revisi)</option>
                        </select>
                    </div>
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px;">Catatan Review KSPI:</label>
                        <textarea name="catatan" rows="2" placeholder="Catatan kelengkapan..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:7px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#0284c7; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                        Kirim Hasil Review
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 7. PANEL AKSI DIRUT (PERSETUJUAN HASIL INVESTIGASI - TAHAP 2) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'dirut' && $statusName === 'menungguDirutTahap2')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #0284c7;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; color:#0369a1;">⚡ Keputusan Direktur Utama (Persetujuan Rekomendasi)</h2>
                </div>
                
                <!-- Status Kesimpulan Investigasi -->
                @if ($pengaduan->kesimpulan_investigasi === 'terbukti')
                    <div style="background:#f0fdf4; border:1.5px solid #86efac; border-radius:10px; padding:10px 14px; margin-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:18px;">⚖️</span>
                            <div>
                                <div style="font-weight:800; font-size:13.5px; color:#15803d;">Hasil Investigasi: TERBUKTI</div>
                                <div style="font-size:11.5px; color:#166534; margin-top:2px;">
                                    Jika Anda <strong>Terima</strong>, pengaduan ini akan diteruskan ke <strong>Bagian SDM</strong> untuk penetapan Surat Putusan Sanksi.
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div style="background:#fef2f2; border:1.5px solid #fecaca; border-radius:10px; padding:10px 14px; margin-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:18px;">🛡️</span>
                            <div>
                                <div style="font-weight:800; font-size:13.5px; color:#b91c1c;">Hasil Investigasi: TIDAK TERBUKTI</div>
                                <div style="font-size:11.5px; color:#991b1b; margin-top:2px;">
                                    Jika Anda <strong>Terima</strong>, pengaduan ini akan <strong>Diarsipkan</strong> dan nama baik pihak terlapor dipulihkan.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Terima Hasil Investigasi -->
                <form method="POST" action="{{ route('pengaduan.dirut-tahap2', $pengaduan->id) }}" style="margin-bottom:16px;">
                    @csrf
                    <input type="hidden" name="keputusan" value="terima">
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px; color:#0f172a;">Catatan Persetujuan Direktur Utama (Opsional):</label>
                        <textarea name="catatan" rows="2" placeholder="Catatan arahan persetujuan..." style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:10px; background:#16a34a; color:#fff; border:none; border-radius:8px; font-weight:800; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 2px 4px rgba(22,163,74,0.25);">
                        <span>✓</span> Terima Hasil & {{ $pengaduan->kesimpulan_investigasi === 'terbukti' ? 'Teruskan ke SDM' : 'Arsipkan Pengaduan' }}
                    </button>
                </form>

                <!-- Form Tinjau Ulang (Kembalikan ke KSPI) -->
                <form method="POST" action="{{ route('pengaduan.dirut-tahap2', $pengaduan->id) }}" style="border-top:1px dashed #cbd5e1; padding-top:14px;">
                    @csrf
                    <input type="hidden" name="keputusan" value="tinjau_ulang">
                    <div class="field" style="margin-bottom:10px;">
                        <label style="font-weight:600; font-size:12px; color:#b45309;">
                            Perlu Investigasi Ulang? Minta Tinjau Ulang ke KSPI:
                        </label>
                        <textarea name="catatan" required rows="2" placeholder="Tuliskan catatan poin investigasi yang perlu diperiksa ulang oleh tim eksekutor..." style="width:100%; border:1px solid #fde68a; border-radius:8px; padding:8px 10px; font-size:12px;"></textarea>
                    </div>
                    <button type="submit" style="width:100%; padding:9px; background:#d97706; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                        <span>↺</span> Kembalikan ke KSPI untuk Pilih Eksekutor Ulang
                    </button>
                </form>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- 8. PANEL AKSI SDM (PENERBITAN SURAT PUTUSAN SANKSI) --}}
        {{-- ========================================================= --}}
        @if ($myRole === 'sdm' && $statusName === 'menungguSdm')
            <div class="ribbon-card" style="margin-bottom:20px; border:2px solid #0284c7; background:#ffffff;">
                <div class="ribbon-head" style="margin-bottom:10px;">
                    <h2 style="font-size:15px; color:#0369a1;">🏛️ Penetapan Surat Putusan Sanksi (Bagian SDM)</h2>
                </div>
                <div style="background:#e0f2fe; border:1px solid #bae6fd; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12px; color:#0369a1; line-height:1.45;">
                    Pengaduan telah dinyatakan <strong>TERBUKTI</strong> dan disetujui Direktur Utama. Pihak SDM berwenang menerbitkan Surat Putusan Sanksi resmi dan melampirkan dokumen putusan.
                </div>

                <form method="POST" action="{{ route('pengaduan.sdm-putusan-sanksi', $pengaduan->id) }}" enctype="multipart/form-data">
                    @csrf

                    <!-- Nomor Surat Putusan Sanksi -->
                    <div class="field" style="margin-bottom:12px;">
                        <label style="font-weight:700; font-size:12px; color:#0f172a; display:block; margin-bottom:4px;">
                            Nomor Surat Putusan Sanksi *:
                        </label>
                        <input type="text" name="nomor_surat_putusan" required placeholder="Contoh: SP/SDM/{{ date('Y') }}/042" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:12.5px;">
                    </div>

                    <!-- Jenis Sanksi Disiplin -->
                    <div class="field" style="margin-bottom:12px;">
                        <label style="font-weight:700; font-size:12px; color:#0f172a; display:block; margin-bottom:4px;">
                            Jenis Sanksi Disiplin Yang Dikenakan *:
                        </label>
                        <select name="jenis_sanksi" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:12.5px; background:#fff;">
                            <option value="">-- Pilih Jenis Sanksi --</option>
                            <option value="Teguran Lisan / Peringatan I">Teguran Lisan / Peringatan I</option>
                            <option value="Teguran Tertulis / Surat Peringatan (SP)">Teguran Tertulis / Surat Peringatan (SP)</option>
                            <option value="Penurunan Gaji Berkala / Tunjangan">Penurunan Gaji Berkala / Tunjangan</option>
                            <option value="Penurunan Pangkat / Golongan 1 Tingkat">Penurunan Pangkat / Golongan 1 Tingkat</option>
                            <option value="Pemindahan Tugas / Demosi Jabatan">Pemindahan Tugas / Demosi Jabatan</option>
                            <option value="Pembebasan Sementara Dari Tugas (Skorsing)">Pembebasan Sementara Dari Tugas (Skorsing)</option>
                            <option value="Pemberhentian Tidak Dengan Hormat (PTDH)">Pemberhentian Tidak Dengan Hormat (PTDH)</option>
                            <option value="Sanksi Administratif Lainnya">Sanksi Administratif Lainnya</option>
                        </select>
                    </div>

                    <!-- Catatan / Diktum Putusan -->
                    <div class="field" style="margin-bottom:12px;">
                        <label style="font-weight:600; font-size:12px; color:#0f172a; display:block; margin-bottom:4px;">
                            Petikan Diktum / Catatan Putusan SDM (Opsional):
                        </label>
                        <textarea name="catatan_sdm" rows="3" placeholder="Tuliskan catatan pelaksanaan sanksi disiplin..." style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:12.5px; line-height:1.5;"></textarea>
                    </div>

                    <!-- Unggah Dokumen Surat Putusan Sanksi -->
                    <div class="field" style="margin-bottom:16px;">
                        <label style="font-weight:700; font-size:12px; color:#0f172a; display:block; margin-bottom:4px;">
                            Unggah Dokumen Berkas Surat Putusan (PDF/DOCX/JPG) *:
                        </label>
                        <input type="file" name="file_surat_putusan" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="font-size:12px; width:100%; border:1px dashed #cbd5e1; padding:10px; border-radius:8px; background:#f8fafc;">
                        <div style="font-size:11px; color:#64748b; margin-top:3px;">Format yang didukung: PDF, DOC, DOCX, JPG, PNG (Maks 20MB)</div>
                    </div>

                    <button type="submit" style="width:100%; padding:11px; background:#0284c7; color:#fff; border:none; border-radius:8px; font-weight:800; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 6px -1px rgba(2,132,199,0.25);">
                        <span>⚖️</span> Terbitkan Surat Putusan & Selesaikan Pengaduan
                    </button>
                </form>
            </div>
        @endif

        {{-- Card Timeline Riwayat Status Audit Trail --}}
        <div class="ribbon-card">
            <div class="ribbon-head" style="margin-bottom:14px;">
                <h2 style="font-size:15px;">Riwayat Alur Proses</h2>
            </div>

            <div style="position:relative; padding-left:20px; border-left:2px solid #e2e8f0; margin-left:10px;">
                @forelse ($riwayat as $r)
                    <div style="position:relative; margin-bottom:18px;">
                        {{-- Bullet Dot --}}
                        <div style="position:absolute; left:-27px; top:2px; width:12px; height:12px; border-radius:50%; background:#0d2c6e; border:2px solid #fff; box-shadow:0 0 0 2px #cbd5e1;"></div>
                        
                        <div style="font-size:13px; font-weight:700; color:#0f172a;">{{ $r->aksi ?? $r->status }}</div>
                        <div style="font-size:11.5px; color:var(--text-muted); margin-bottom:4px;">
                            Oleh: <strong>{{ $r->oleh }}</strong> ({{ $r->role ?? 'Sistem' }}) · {{ date('d M Y, H:i', strtotime($r->tanggal)) }}
                        </div>
                        @if (!empty($r->keterangan))
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:6px 10px; font-size:12px; color:#475569;">
                                {{ $r->keterangan }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="font-size:12px; color:var(--text-muted);">Belum ada riwayat tercatat.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
