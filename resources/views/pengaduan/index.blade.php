@extends('layouts.app')

@section('title', 'Pengaduan Pegawai (Whistleblowing)')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaduan</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0;">Pengaduan Pegawai</h1>
            <p style="margin:4px 0 0; font-size:13px; color:var(--text-muted);">
                Sistem Penanganan Pelanggaran Disiplin Pegawai & Whistleblowing
            </p>
        </div>
        <div>
            @php
                $roleLabel = match($myRole) {
                    'kadiv' => 'Kadiv (' . ucfirst($divisiKadiv ?? 'Kategori') . ')',
                    'kspi' => 'KSPI (Pengawas Internal)',
                    'dirut' => 'Direktur Utama (DIRUT)',
                    'tpdpk' => 'TPDPK (Tim Penegak Disiplin)',
                    'sdm' => 'SDM (Pelapor)',
                    default => 'Pegawai (Pelapor)',
                };
                $roleColor = match($myRole) {
                    'dirut' => '#e67e22',
                    'kspi' => '#0284c7',
                    'tpdpk' => '#0284c7',
                    'kadiv' => '#0369a1',
                    default => '#34495e',
                };
            @endphp
            <span style="background:{{ $roleColor }}; color:#fff; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#fff;"></span>
                Role: {{ $roleLabel }}
            </span>
        </div>
    </div>
</div>

@php
    $isPrivileged = in_array($myRole, ['kadiv', 'kspi', 'dirut', 'tpdpk']);
@endphp

{{-- TAB NAVIGASI PENGADUAN --}}
    <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:10px; flex-wrap:wrap; align-items:center;">
        @if ($isPrivileged)
            <a href="{{ route('pengaduan.index', ['tab' => 'masuk']) }}" 
               style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; {{ $tab === 'masuk' ? 'background:var(--primary, #0d2c6e); color:#fff;' : 'background:#f1f5f9; color:#475569;' }}">
                📥 Kotak Masuk Tindakan
                @if (count($pengaduanMasuk) > 0)
                    <span style="background:#ef4444; color:#fff; font-size:10px; padding:2px 6px; border-radius:10px;">{{ count($pengaduanMasuk) }}</span>
                @endif
            </a>
            <a href="{{ route('pengaduan.index', ['tab' => 'riwayat']) }}" 
               style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; {{ $tab === 'riwayat' ? 'background:var(--primary, #0d2c6e); color:#fff;' : 'background:#f1f5f9; color:#475569;' }}">
                📋 Riwayat Terproses ({{ count($pengaduanRiwayat) }})
            </a>
        @endif

        {{-- TAB TUGAS SAYA (Untuk semua role / eksekutor yang mendapat tugas) --}}
        <a href="{{ route('pengaduan.index', ['tab' => 'tugas']) }}" 
           style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; {{ $tab === 'tugas' ? 'background:#0284c7; color:#fff;' : 'background:#f1f5f9; color:#475569;' }}">
            🎯 Tugas Saya
            @if (count($tugasSaya) > 0)
                <span style="background:{{ $tab === 'tugas' ? '#fff' : '#0284c7' }}; color:{{ $tab === 'tugas' ? '#0284c7' : '#fff' }}; font-size:10px; padding:2px 6px; border-radius:10px; font-weight:700;">{{ count($tugasSaya) }}</span>
            @endif
        </a>

        <a href="{{ route('pengaduan.index', ['tab' => 'saya']) }}" 
           style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; {{ $tab === 'saya' ? 'background:var(--primary, #0d2c6e); color:#fff;' : 'background:#f1f5f9; color:#475569;' }}">
            👤 Aduan Pribadi Saya ({{ count($pengaduanSaya) }})
        </a>

        <a href="{{ route('pengaduan.index', ['tab' => 'buat']) }}" 
           style="text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; display:inline-flex; align-items:center; gap:6px; margin-left:auto; {{ $tab === 'buat' ? 'background:#16a34a; color:#fff;' : 'background:#dcfce7; color:#15803d;' }}">
            ➕ Buat Pengaduan Baru
        </a>
    </div>

    @if ($tab === 'masuk' && $isPrivileged)
        {{-- TAB KOTAK MASUK TINDAKAN --}}
        <div class="table-card">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="font-size:16px; margin:0;">Pengaduan Menunggu Tindakan Anda</h2>
                    <p style="margin:2px 0 0; font-size:12px; color:var(--text-muted);">
                        @if ($myRole === 'kadiv')
                            Verifikasi pengaduan kategori <strong>{{ $divisiKadiv === 'teknik' ? 'Pelanggaran Teknik' : 'Pelanggaran Administrasi' }}</strong>
                        @elseif ($myRole === 'kspi')
                            Review pengaduan masuk & penunjukan eksekutor investigasi
                        @elseif ($myRole === 'dirut')
                            Approval kelayakan investigasi & persetujuan sanksi
                        @elseif ($myRole === 'tpdpk')
                            Penugasan investigasi lapangan & penyusunan berita acara
                        @endif
                    </p>
                </div>
                <span style="font-size:12px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:12px;">{{ count($pengaduanMasuk) }} Perlu Aksi</span>
            </div>
            @include('pengaduan.partials.table', ['items' => $pengaduanMasuk, 'action' => true])
        </div>

    @elseif ($tab === 'riwayat' && $isPrivileged)
        {{-- TAB RIWAYAT TERPROSES --}}
        <div class="table-card">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border);">
                <h2 style="font-size:16px; margin:0;">Riwayat Pengaduan yang Telah Diproses</h2>
            </div>
            @include('pengaduan.partials.table', ['items' => $pengaduanRiwayat, 'action' => true])
        </div>

    @elseif ($tab === 'tugas')
        {{-- TAB TUGAS SAYA (TASKS EKSEKUTOR) --}}
        <div class="table-card">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2 style="font-size:16px; margin:0; display:flex; align-items:center; gap:8px;">
                        <span>🎯</span> Daftar Penugasan Investigasi & Eksekusi Saya
                    </h2>
                    <p style="margin:2px 0 0; font-size:12.5px; color:var(--text-muted);">
                        Kelola tugas pemeriksaan dan investigasi pengaduan yang ditugaskan kepada Anda oleh KSPI.
                    </p>
                </div>
                <span style="font-size:12px; font-weight:700; background:#f0f9ff; color:#0369a1; padding:4px 10px; border-radius:12px; border:1px solid #bae6fd;">
                    {{ count($tugasSaya) }} Tugas
                </span>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Pengaduan</th>
                        <th>Judul & Instruksi Tugas</th>
                        <th>Penugas</th>
                        <th>Status Tugas</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tugasSaya as $task)
                        @php
                            $taskBadge = match($task->status) {
                                'Diproses' => 'background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;',
                                'Selesai' => 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
                                'Dibatalkan' => 'background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;',
                                default => 'background:#fef3c7; color:#b45309; border:1px solid #fde68a;',
                            };
                        @endphp
                        <tr>
                            <td>
                                <strong style="color:#0d2c6e;">{{ $task->nomor_pengaduan ?? 'PGD' }}</strong><br>
                                <small style="color:var(--text-muted);">{{ date('d M Y H:i', strtotime($task->created_at)) }}</small>
                            </td>
                            <td style="max-width:300px;">
                                <strong style="font-size:13.5px; color:#0f172a;">{{ $task->title }}</strong>
                                <div style="font-size:12px; color:#475569; margin-top:2px;">
                                    {{ $task->description ?? 'Lakukan investigasi lapangan.' }}
                                </div>
                                @if (!empty($task->notes))
                                    <div style="margin-top:6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:4px 8px; font-size:11.5px; color:#334155;">
                                        <strong>Hasil:</strong> {{ $task->notes }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong style="font-size:12.5px;">{{ $task->assigned_by_name ?? 'KSPI' }}</strong><br>
                                <small style="color:var(--text-muted);">Kategori: {{ $task->category ?? 'Investigasi' }}</small>
                            </td>
                            <td>
                                <span style="{{ $taskBadge }} font-size:11.5px; font-weight:700; padding:4px 10px; border-radius:12px; display:inline-block;">
                                    {{ $task->status }}
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:inline-flex; gap:6px;">
                                    @if ($task->status !== 'Selesai')
                                        <button type="button" 
                                                onclick="bukaModalUpdateTask('{{ $task->id }}', '{{ addslashes($task->title) }}', '{{ $task->status }}', '{{ addslashes($task->notes ?? '') }}')"
                                                style="background:#0284c7; color:#fff; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; border:none; cursor:pointer;">
                                            ⚡ Proses Tugas
                                        </button>
                                    @endif
                                    <a href="{{ route('pengaduan.detail', $task->pengaduan_id) }}" 
                                       style="background:#f1f5f9; color:#334155; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; border:1px solid #cbd5e1;">
                                        Lihat Kasus ➔
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">
                                Belum ada tugas investigasi yang ditugaskan kepada Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($tab === 'saya')
        {{-- TAB ADUAN PRIBADI --}}
        <div class="table-card">
            <div style="padding:16px 20px; border-bottom:1px solid var(--border);">
                <h2 style="font-size:16px; margin:0;">Daftar Pengaduan yang Anda Kirimkan</h2>
            </div>
            @include('pengaduan.partials.table', ['items' => $pengaduanSaya, 'action' => false])
        </div>

    @elseif ($tab === 'buat')
        {{-- TAB FORM BUAT PENGADUAN --}}
        <div class="ribbon-card" style="max-width:750px; margin:0 auto;">
            <div class="ribbon-head" style="margin-bottom:16px;">
                <h2 style="font-size:17px;">Formulir Pengaduan Baru</h2>
            </div>
            @include('pengaduan.partials.form')
        </div>
    @endif

<!-- Modal Proses / Update Task Eksekutor -->
<div id="modal-update-task" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:12px; width:100%; max-width:540px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow:hidden;">
        <div style="padding:14px 18px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="font-weight:700; font-size:15px; color:#0f172a;" id="task-modal-title">
                ⚡ Perbarui Status Tugas
            </div>
            <button type="button" onclick="tutupModalUpdateTask()" style="background:none; border:none; font-size:20px; color:#64748b; cursor:pointer;">&times;</button>
        </div>

        <form id="form-update-task" method="POST" action="" enctype="multipart/form-data" style="padding:18px;">
            @csrf
            <div class="field" style="margin-bottom:12px;">
                <label style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Status Baru *</label>
                <select name="status" id="task-modal-status" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:13px;">
                    <option value="Diproses">Mulai Proses (Diproses)</option>
                    <option value="Selesai">Tandai Selesai (Kirim Hasil ke KSPI)</option>
                </select>
            </div>

            <div class="field" style="margin-bottom:12px;">
                <label style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Catatan / Hasil Investigasi *</label>
                <textarea name="notes" id="task-modal-notes" rows="3" required placeholder="Tuliskan uraian hasil temuan pemeriksaan dan rekomendasi..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-size:12.5px;"></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:16px;">
                <div class="field">
                    <label style="font-weight:600; font-size:12px; display:block; margin-bottom:3px;">Foto Bukti (Opsional)</label>
                    <input type="file" name="foto_bukti[]" multiple accept="image/*" style="width:100%; font-size:11.5px; border:1px solid #cbd5e1; border-radius:6px; padding:6px; background:#f8fafc;">
                </div>
                <div class="field">
                    <label style="font-weight:600; font-size:12px; display:block; margin-bottom:3px;">Dokumen BAP (Opsional)</label>
                    <input type="file" name="dokumen[]" multiple accept=".pdf,.doc,.docx" style="width:100%; font-size:11.5px; border:1px solid #cbd5e1; border-radius:6px; padding:6px; background:#f8fafc;">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" onclick="tutupModalUpdateTask()" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">
                    Batal
                </button>
                <button type="submit" style="background:#0284c7; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:13px; font-weight:700; cursor:pointer;">
                    Simpan & Perbarui
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalUpdateTask(taskId, title, status, notes) {
    const m = document.getElementById('modal-update-task');
    const f = document.getElementById('form-update-task');
    if (m && f) {
        f.action = "{{ url('pengaduan/tasks') }}/" + taskId + "/update";
        document.getElementById('task-modal-title').textContent = "⚡ " + title;
        document.getElementById('task-modal-status').value = status === 'Menunggu' ? 'Diproses' : status;
        document.getElementById('task-modal-notes').value = notes || '';
        m.style.display = 'flex';
    }
}

function tutupModalUpdateTask() {
    const m = document.getElementById('modal-update-task');
    if (m) m.style.display = 'none';
}
</script>

@endsection
