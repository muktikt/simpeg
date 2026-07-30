@extends('layouts.app')

@section('title', 'Profile Saya')

@section('content')
@php
    $namaPeg = $pegawai['nama'] ?? $userLogin['nama_peg'];
    $inisial = collect(explode(' ', $namaPeg))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $badgeLabel = [
        'PT' => 'Pegawai Tetap', 'DI' => 'Direksi', 'CP' => 'Calon Pegawai',
        'PH' => 'Honorer', 'TK' => 'Tenaga Kontrak', 'PN' => 'Pensiun',
    ];
    $tabLabels = [
        'keluarga' => 'Keluarga',
        'golongan' => 'Golongan',
        'jabatan_riwayat' => 'Jabatan',
        'pendidikan' => 'Pendidikan',
        'prestasi' => 'Prestasi',
        'pengaturan' => 'Pengaturan Akun',
    ];
    $defaultTab = $errors->any() ? 'pengaturan' : 'keluarga';
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Profile Saya</div>
    <h1>Profile Saya</h1>
</div>

<div class="profile-header">
    <div class="avatar-lg">{{ strtoupper($inisial) }}</div>
    <div class="info">
        <h2>{{ $namaPeg }}</h2>
        <p>{{ $pegawai['jabatan'] ?? $userLogin['jabatan'] }} &middot; {{ $pegawai['unit_kerja'] ?? 'Unit Kerja' }}</p>
    </div>
    <div class="meta">
        <div><p>{{ $pegawai['nik'] ?? $userLogin['nik'] }}</p><p>NIK</p></div>
        <div>
            <p><span class="badge badge-{{ $pegawai['status_peg'] ?? 'PT' }}">{{ $badgeLabel[$pegawai['status_peg'] ?? 'PT'] ?? ($pegawai['status_peg'] ?? 'Pegawai Tetap') }}</span></p>
            <p>Status</p>
        </div>
        <div>
            <p>{{ !empty($pegawai['tgl_masuk']) ? \Illuminate\Support\Carbon::parse($pegawai['tgl_masuk'])->translatedFormat('d M Y') : '-' }}</p>
            <p>Tgl Masuk</p>
        </div>
    </div>
</div>

<div class="tabs" id="profile-tabs">
    @foreach ($tabLabels as $type => $label)
        @php
            $count = ($type === 'pengaturan') ? null : count($pegawai[$type] ?? []);
        @endphp
        <button type="button" class="tab-btn {{ $type === $defaultTab ? 'active' : '' }}" data-tab="{{ $type }}" onclick="switchTab('{{ $type }}')">
            {{ $label }} {{ $count !== null ? "($count)" : '' }}
        </button>
    @endforeach
</div>

@foreach ($tabLabels as $type => $label)
    @if ($type === 'pengaturan')
        <div class="tab-panel {{ $type === $defaultTab ? 'active' : '' }}" data-panel="pengaturan">
            <div class="ribbon-card" style="max-width:580px; margin-top:8px;">
                <div class="ribbon-head" style="margin-bottom:12px;">
                    <h2 style="font-size:17px;">Ubah Password Akun</h2>
                </div>
                <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">Perbarui password akun Anda secara berkala untuk menjaga keamanan data.</p>
                <form method="POST" action="{{ route('profile.update-password') }}">
                    @csrf
                    @method('PUT')
                    <div class="field">
                        <label for="current_password">Password Saat Ini</label>
                        <div class="input-wrap">
                            <input type="password" id="current_password" name="current_password" required placeholder="Masukkan password saat ini">
                        </div>
                        @error('current_password') <div class="error-text" style="margin-top:6px;">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="new_password">Password Baru</label>
                        <div class="input-wrap">
                            <input type="password" id="new_password" name="new_password" required placeholder="Masukkan password baru">
                        </div>
                        @error('new_password') <div class="error-text" style="margin-top:6px;">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="new_password_confirmation">Konfirmasi Password Baru</label>
                        <div class="input-wrap">
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation" required placeholder="Ulangi password baru">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" style="margin-top:8px;">Simpan Password Baru</button>
                </form>
            </div>
        </div>
    @else
        <div class="tab-panel {{ $type === $defaultTab ? 'active' : '' }}" data-panel="{{ $type }}">
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach ($detailTypes[$type]['fields'] as $field)
                                <th>{{ $field['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pegawai[$type] ?? [] as $item)
                            <tr>
                                @foreach ($detailTypes[$type]['fields'] as $field)
                                    <td>{{ $item[$field['key']] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($detailTypes[$type]['fields']) }}">
                                    <div class="table-empty">Belum ada data {{ strtolower($label) }}.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endforeach

<script>
function switchTab(type) {
    document.querySelectorAll('#profile-tabs .tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === type));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === type));
}
</script>
@endsection
