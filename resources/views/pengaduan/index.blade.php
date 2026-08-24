@extends('layouts.app')

@section('title', 'Pengaduan Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaduan</div>
    <h1>Pengaduan Pegawai</h1>
</div>

@if (session('success'))
    <div class="alert alert-success" style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:6px; margin-bottom:20px;">
        ✓ {{ session('success') }}
    </div>
@endif

<div style="display:grid; grid-template-columns: {{ $myRole === '5' ? '360px 1fr' : '1fr' }}; gap:20px; align-items:start;">
    @if ($myRole === '5')
    <!-- Form Pengaduan Baru (Khusus Pegawai) -->
    <div class="ribbon-card">
        <div class="ribbon-head" style="margin-bottom:12px;">
            <h2 style="font-size:16px;">Kirim Pengaduan Baru</h2>
        </div>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">Sampaikan kendala, pertanyaan, atau saran seputar penggajian & kepegawaian Anda langsung ke bagian SDM.</p>

        <form method="POST" action="{{ route('pengaduan.store') }}">
            @csrf
            <div class="field" style="margin-bottom:12px;">
                <label for="subjek">Subjek / Topik</label>
                <div class="input-wrap">
                    <input type="text" id="subjek" name="subjek" required placeholder="Contoh: Kendala Slip Gaji / Presensi">
                </div>
            </div>
            <div class="field" style="margin-bottom:16px;">
                <label for="pesan">Detail Pengaduan</label>
                <div class="input-wrap">
                    <textarea id="pesan" name="pesan" rows="4" required placeholder="Jelaskan detail kendala atau pengaduan Anda..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:10px; font-family:inherit;"></textarea>
                </div>
            </div>
            <button type="submit" class="btn-submit" style="width:100%;">Kirim Pengaduan</button>
        </form>
    </div>
    @endif

    <!-- Tabel / Daftar Pengaduan -->
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h2 style="font-size:16px; margin:0;">Daftar Pengaduan {{ $myRole === '5' ? 'Saya' : 'Seluruh Pegawai' }}</h2>
            <span style="font-size:12px; color:var(--text-muted);">Total: {{ count($pengaduan) }} Pengaduan</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. Aduan / Tanggal</th>
                    <th>Subjek</th>
                    @if ($myRole !== '5')
                        <th>Pengirim</th>
                    @endif
                    <th>Detail Pesan</th>
                    <th>Status</th>
                    @if ($myRole === '1')
                        <th>Aksi SDM</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($pengaduan as $item)
                    <tr>
                        <td>
                            <strong style="color:#1e3a8a;">{{ $item['nomor_pengaduan'] }}</strong><br>
                            <small style="color:var(--text-muted);">{{ $item['tanggal'] }}</small>
                        </td>
                        <td style="font-weight:600;">{{ $item['subjek'] }}</td>
                        @if ($myRole !== '5')
                            <td>
                                <strong>{{ $item['nama'] }}</strong><br>
                                <small style="color:var(--text-muted);">NIK: {{ $item['nik'] }}</small>
                            </td>
                        @endif
                        <td style="font-size:13px; max-width:280px; line-height:1.5;">{{ $item['pesan'] }}</td>
                        <td>
                            @php
                                $badgeClass = match($item['status']) {
                                    'SELESAI', 'Selesai' => 'badge-PT',
                                    'DIPROSES', 'Diproses' => 'badge-DI',
                                    'DITOLAK', 'Ditolak' => 'badge-PN',
                                    default => 'badge-CP',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $item['status'] }}</span>
                        </td>
                        @if ($myRole === '1')
                            <td>
                                <form method="POST" action="{{ route('pengaduan.update-status', $item['id']) }}" style="display:flex; gap:6px; align-items:center;">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" style="padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px;">
                                        <option value="DIAJUKAN" {{ $item['status'] === 'DIAJUKAN' ? 'selected' : '' }}>Diajukan</option>
                                        <option value="DIPROSES" {{ $item['status'] === 'DIPROSES' ? 'selected' : '' }}>Diproses</option>
                                        <option value="SELESAI" {{ $item['status'] === 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                                        <option value="DITOLAK" {{ $item['status'] === 'DITOLAK' ? 'selected' : '' }}>Tolak</option>
                                    </select>
                                    <button type="submit" style="background:#0284c7; color:#fff; border:none; border-radius:4px; padding:4px 8px; font-size:12px; cursor:pointer;">Update</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $myRole === '1' ? '6' : ($myRole !== '5' ? '5' : '4') }}">
                            <div class="table-empty">Belum ada riwayat pengaduan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
