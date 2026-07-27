@extends('layouts.app')

@section('title', 'Profile Saya')

@section('content')
@php
    $inisial = collect(explode(' ', $userLogin['nama_peg']))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $roleList = config('simpeg_roles');
@endphp

<div class="page-head">
    <div class="breadcrumb">Home / Profile Saya</div>
    <h1>Profile Saya</h1>
</div>

<div class="profile-header">
    <div class="avatar-lg">{{ strtoupper($inisial) }}</div>
    <div class="info">
        <h2>{{ $userLogin['nama_peg'] }}</h2>
        <p>{{ $userLogin['jabatan'] }}</p>
    </div>
    <div class="meta">
        <div><p>{{ $userLogin['nik'] }}</p><p>NIK</p></div>
        <div><p>{{ $roleList[$userLogin['userlevel']]['label'] ?? $userLogin['userlevel'] }}</p><p>Level Akses</p></div>
    </div>
</div>

<div class="split">
    <div class="panel">
        <h3>Data Kepegawaian</h3>
        @if ($pegawai)
            <div class="activity-row"><div class="activity-text">Unit Kerja</div><div class="activity-time">{{ $pegawai['unit_kerja'] }}</div></div>
            <div class="activity-row"><div class="activity-text">Status Kepegawaian</div><div class="activity-time">{{ $pegawai['status_peg'] }}</div></div>
            <div class="activity-row"><div class="activity-text">Tanggal Masuk</div><div class="activity-time">{{ \Illuminate\Support\Carbon::parse($pegawai['tgl_masuk'])->translatedFormat('d M Y') }}</div></div>
            <div class="activity-row"><div class="activity-text">No. Telepon</div><div class="activity-time">{{ $pegawai['telp'] ?: '-' }}</div></div>
            <div style="margin-top:14px;">
                <a href="{{ route('pegawai.show', $pegawai['id']) }}" class="btn btn-outline btn-sm">Lihat Detail Lengkap</a>
            </div>
        @else
            <p style="font-size:12.5px; color:var(--text-muted);">Data kepegawaian untuk NIK ini belum terdaftar di modul Data Pegawai.</p>
        @endif
    </div>

    <div class="panel">
        <h3>Ubah Password</h3>
        <form method="POST" action="{{ route('profile.update-password') }}">
            @csrf
            @method('PUT')
            <div class="form-group" style="margin-bottom:14px;">
                <label for="current_password">Password Saat Ini</label>
                <input type="password" id="current_password" name="current_password" required>
                @error('current_password') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label for="new_password">Password Baru</label>
                <input type="password" id="new_password" name="new_password" required>
                @error('new_password') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label for="new_password_confirmation">Konfirmasi Password Baru</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
        </form>
    </div>
</div>
@endsection
