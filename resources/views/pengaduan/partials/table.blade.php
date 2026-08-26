<table class="data-table">
    <thead>
        <tr>
            <th>No. Pengaduan</th>
            <th>Pelapor</th>
            <th>Kategori & Terlapor</th>
            <th>Judul Pengaduan</th>
            <th>Status Alur</th>
            <th style="text-align:center;">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $item)
            @php
                $statusName = $item->status ?? 'menungguKadiv';
                $statusLabels = [
                    'menungguKadiv' => 'Menunggu Kadiv',
                    'menungguVerifikasiKadiv' => 'Menunggu Verifikasi Kadiv',
                    'reviewKspi' => 'Review KSPI',
                    'menungguReviewKspi' => 'Menunggu Review KSPI',
                    'menungguDirutTahap1' => 'Menunggu Dirut (Tahap 1)',
                    'menungguPilihEksekutor' => 'Menunggu Pilih Eksekutor',
                    'investigasiBerjalan' => 'Investigasi Berjalan',
                    'revisiInvestigasi' => 'Revisi Investigasi',
                    'menungguDirutTahap2' => 'Menunggu Dirut (Tahap 2)',
                    'tindakLanjutBerjalan' => 'Tindak Lanjut Berjalan',
                    'menungguSdm' => 'Menunggu SDM',
                    'selesai' => 'Selesai',
                    'arsip' => 'Diarsipkan',
                    'ditolakDirektur' => 'Ditolak Direktur',
                ];
                $statusBadges = [
                    'menungguKadiv' => 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;',
                    'menungguVerifikasiKadiv' => 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;',
                    'reviewKspi' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                    'menungguReviewKspi' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                    'menungguDirutTahap1' => 'background:#ffedd5; color:#c2410c; border:1px solid #fed7aa;',
                    'menungguDirutTahap2' => 'background:#ffedd5; color:#c2410c; border:1px solid #fed7aa;',
                    'menungguPilihEksekutor' => 'background:#ede9fe; color:#6d28d9; border:1px solid #ddd6fe;',
                    'investigasiBerjalan' => 'background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe;',
                    'revisiInvestigasi' => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                    'tindakLanjutBerjalan' => 'background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe;',
                    'menungguSdm' => 'background:#ffedd5; color:#c2410c; border:1px solid #fed7aa;',
                    'selesai' => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                    'arsip' => 'background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1;',
                    'ditolakDirektur' => 'background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;',
                ];
                $badgeStyle = $statusBadges[$statusName] ?? 'background:#f1f5f9; color:#475569;';
                $badgeLabel = $statusLabels[$statusName] ?? $statusName;
            @endphp
            <tr>
                <td>
                    <strong style="color:#0d2c6e;">{{ $item->nomor_pengaduan ?? ('PGD-' . $item->id) }}</strong><br>
                    <small style="color:var(--text-muted);">{{ date('d M Y H:i', strtotime($item->created_at ?? now())) }}</small>
                </td>
                <td>
                    @if (!empty($item->anonim))
                        <span style="background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700;">🔒 Anonim</span>
                    @else
                        <strong>{{ $item->nama_pegawai ?? '-' }}</strong><br>
                        <small style="color:var(--text-muted);">NIK: {{ $item->nik ?? '-' }}</small>
                    @endif
                </td>
                <td>
                    <span style="font-size:11.5px; font-weight:600; color:#0369a1; background:#f0f9ff; padding:2px 6px; border-radius:4px;">{{ $item->kategori ?? 'Umum' }}</span><br>
                    <small style="color:#334155;">Terlapor: <strong>{{ $item->pihak_terlapor ?? '-' }}</strong></small>
                </td>
                <td style="max-width:240px;">
                    <div style="font-weight:600; color:#1e293b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $item->judul }}</div>
                    <small style="color:var(--text-muted); display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $item->deskripsi }}</small>
                </td>
                <td>
                    <span style="{{ $badgeStyle }} font-size:11.5px; font-weight:700; padding:4px 8px; border-radius:12px; display:inline-block;">
                        {{ $badgeLabel }}
                    </span>
                </td>
                <td style="text-align:center;">
                    <a href="{{ route('pengaduan.detail', $item->id) }}" 
                       style="background:#0d2c6e; color:#fff; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                        Lihat & Proses ➔
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <div class="table-empty" style="text-align:center; padding:30px 10px; color:var(--text-muted);">
                        Belum ada data pengaduan pada daftar ini.
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
