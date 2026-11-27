@extends('layouts.app')

@section('title', 'Slip Tunjangan Pendidikan - ' . $item['nama'])

@section('content')
@php
    $myRole = (string) session('simpeg_user.userlevel');
    $bisaKelola = in_array($myRole, ['1', '2'], true);
    $backRoute = $myRole === '5'
        ? route('gaji-tigabelas.laporan-slip', ['my' => 1, 'tp_id' => $item['id']])
        : route('tunjangan-pendidikan.index', $backParams ?? []);
@endphp

<div class="page-head no-print">
    <div class="breadcrumb">Home / SDM / Tunjangan Pendidikan / Slip</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0 0 4px;">Slip Tunjangan Pendidikan — {{ $item['nama'] }}</h1>
            <p style="margin:0; font-size:13px; color:var(--text-muted);">
                NIK: <strong>{{ $item['nik'] }}</strong> &middot; Periode: <strong>{{ $item['bulan_label'] }}</strong> &middot; Unit: {{ $item['unit_kerja'] }}
            </p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                Cetak Slip
            </button>
            @if ($bisaKelola)
                <a href="{{ route('tunjangan-pendidikan.create') }}" class="btn btn-outline" style="font-weight:600;">+ Input Baru</a>
            @endif
            <a href="{{ $backRoute }}" class="btn btn-outline">Kembali</a>
        </div>
    </div>
</div>

@include('tunjangan-pendidikan.partials.official-slip', ['item' => $item])
@endsection
