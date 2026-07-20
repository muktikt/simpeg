@extends('layouts.app')

@section('title', $pegawai['nama'])

@section('content')
@php
    $myRole = session('simpeg_user.userlevel');
    $inisial = collect(explode(' ', $pegawai['nama']))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $badgeLabel = [
        'PT' => 'Pegawai Tetap', 'DI' => 'Direksi', 'CP' => 'Calon Pegawai',
        'PH' => 'Honorer', 'TK' => 'Tenaga Kontrak', 'PN' => 'Pensiun',
    ];
    $tabLabels = [
        'keluarga' => 'Keluarga', 'golongan' => 'Golongan', 'jabatan_riwayat' => 'Jabatan',
        'pendidikan' => 'Pendidikan', 'prestasi' => 'Prestasi',
    ];
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Data Pegawai / {{ $pegawai['nama'] }}</div>
    <h1>Detail Pegawai</h1>
</div>

<div class="profile-header">
    <div class="avatar-lg">{{ strtoupper($inisial) }}</div>
    <div class="info">
        <h2>{{ $pegawai['nama'] }}</h2>
        <p>{{ $pegawai['jabatan'] }} &middot; {{ $pegawai['unit_kerja'] }}</p>
    </div>
    <div class="meta">
        <div><p>{{ $pegawai['nik'] }}</p><p>NIK</p></div>
        <div><p><span class="badge badge-{{ $pegawai['status_peg'] }}">{{ $badgeLabel[$pegawai['status_peg']] ?? $pegawai['status_peg'] }}</span></p><p>Status</p></div>
        <div><p>{{ \Illuminate\Support\Carbon::parse($pegawai['tgl_masuk'])->translatedFormat('d M Y') }}</p><p>Tgl Masuk</p></div>
    </div>
    @if ($myRole === '1')
        @if ($pegawai['status_peg'] === 'CP')
            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('promote-modal-overlay').classList.add('active')">
                Angkat Jadi Pegawai Tetap
            </button>
        @endif
        <a href="{{ route('pegawai.edit', $pegawai['id']) }}" class="btn btn-outline btn-sm">Edit Data</a>
        <form action="{{ route('pegawai.destroy', $pegawai['id']) }}" method="POST" onsubmit="return confirm('Yakin mau hapus data pegawai ini? Semua riwayat (keluarga, golongan, dll) ikut terhapus.');" style="margin:0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
        </form>
    @endif
</div>

@if ($myRole === '1' && $pegawai['status_peg'] === 'CP')
    <div class="modal-overlay" id="promote-modal-overlay">
        <div class="modal-card form-modal">
            <div class="modal-title">Angkat Jadi Pegawai Tetap</div>
            <p style="font-size:12.5px; color:var(--text-muted); margin:-8px 0 16px;">
                Status <strong>{{ $pegawai['nama'] }}</strong> akan berubah dari Calon Pegawai menjadi Pegawai Tetap. Masukkan NIK baru untuk pegawai ini.
            </p>
            <form method="POST" action="{{ route('pegawai.promote-to-tetap', $pegawai['id']) }}">
                @csrf
                <div class="form-group">
                    <label for="nik_baru">NIK Baru</label>
                    <input type="text" id="nik_baru" name="nik_baru" required value="{{ old('nik_baru') }}">
                    @error('nik_baru') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-actions modal-actions">
                    <button type="submit" class="btn btn-primary">Konfirmasi</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('promote-modal-overlay').classList.remove('active')">Batal</button>
                </div>
            </form>
        </div>
    </div>
@endif

<div class="tabs" id="pegawai-tabs">
    @foreach ($tabLabels as $type => $label)
        <button type="button" class="tab-btn {{ $loop->first ? 'active' : '' }}" data-tab="{{ $type }}" onclick="switchTab('{{ $type }}')">
            {{ $label }} ({{ count($pegawai[$type] ?? []) }})
        </button>
    @endforeach
</div>

@foreach ($tabLabels as $type => $label)
    <div class="tab-panel {{ $loop->first ? 'active' : '' }}" data-panel="{{ $type }}">
        <div class="toolbar">
            <div></div>
            @if ($myRole === '1')
                <button type="button" class="btn btn-primary btn-sm" onclick="openDetailModal('{{ $type }}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah {{ $label }}
                </button>
            @endif
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach ($detailTypes[$type]['fields'] as $field)
                            <th>{{ $field['label'] }}</th>
                        @endforeach
                        @if ($myRole === '1')
                            <th style="width:1%"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pegawai[$type] ?? [] as $item)
                        <tr>
                            @foreach ($detailTypes[$type]['fields'] as $field)
                                <td>{{ $item[$field['key']] }}</td>
                            @endforeach
                            @if ($myRole === '1')
                                <td>
                                    <div class="row-actions">
                                        <button type="button" class="btn btn-outline btn-sm" onclick='openDetailModal("{{ $type }}", {{ json_encode($item) }})'>Edit</button>
                                        <form action="{{ route('pegawai.detail.destroy', [$pegawai['id'], $type, $item['id']]) }}" method="POST" onsubmit="return confirm('Hapus data ini?');" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($detailTypes[$type]['fields']) + 1 }}">
                                <div class="table-empty">Belum ada data {{ strtolower($label) }}.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach

@if ($myRole === '1')
    @include('pegawai.partials.detail-modal')
@endif

<script>
function switchTab(type) {
    document.querySelectorAll('#pegawai-tabs .tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === type));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === type));
}
</script>
@endsection
