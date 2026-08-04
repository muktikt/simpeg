@extends('layouts.app')

@section('title', 'Pengaduan Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaduan Pegawai</div>
    <h1>Pengaduan Pegawai</h1>
</div>

@if (session('success'))
    <div class="alert alert-success" style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:6px; margin-bottom:20px;">
        {{ session('success') }}
    </div>
@endif

<div style="display:grid; grid-template-columns: 360px 1fr; gap:20px; align-items:start;">
    <!-- Form Pengaduan Baru (Khusus Pegawai) -->
    <div class="ribbon-card">
        <div class="ribbon-head" style="margin-bottom:12px;">
            <h2 style="font-size:16px;">Kirim Pengaduan Baru</h2>
        </div>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">Sampaikan kendala, pertanyaan, atau pengaduan seputar penggajian & kepegawaian Anda.</p>

        <form method="POST" action="{{ route('pengaduan.store') }}">
            @csrf
            <div class="field" style="margin-bottom:12px;">
                <label for="subjek">Subjek / Topik Pengaduan</label>
                <div class="input-wrap">
                    <input type="text" id="subjek" name="subjek" required placeholder="Contoh: Kendala Slip Gaji Bulan Ini">
                </div>
            </div>
            <div class="field" style="margin-bottom:16px;">
                <label for="pesan">Detail Pengaduan</label>
                <div class="input-wrap">
                    <textarea id="pesan" name="pesan" rows="4" required placeholder="Jelaskan detail kendala atau pertanyaan Anda..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:10px; font-family:inherit;"></textarea>
                </div>
            </div>
            <button type="submit" class="btn-submit" style="width:100%;">Kirim Pengaduan</button>
        </form>
    </div>

    <!-- Tabel / Daftar Pengaduan -->
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Subjek</th>
                    <th>Pengirim</th>
                    <th>Pesan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengaduan as $item)
                    <tr>
                        <td style="white-space:nowrap; font-size:13px; color:var(--text-muted);">{{ $item['tanggal'] }}</td>
                        <td style="font-weight:600;">{{ $item['subjek'] }}</td>
                        <td>{{ $item['nama'] }} <br><small style="color:var(--text-muted);">({{ $item['nik'] }})</small></td>
                        <td style="font-size:13px; max-width:280px;">{{ $item['pesan'] }}</td>
                        <td>
                            <span class="badge badge-{{ $item['status'] === 'Selesai' ? 'PT' : 'DI' }}">{{ $item['status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="table-empty">Belum ada riwayat pengaduan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
