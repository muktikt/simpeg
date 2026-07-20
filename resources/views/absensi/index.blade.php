@extends('layouts.app')

@section('title', 'SET Absensi')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Proses Gaji / SET Absensi</div>
    <h1>SET Absensi</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('absensi.index') }}" style="display:flex; gap:10px;">
        <select name="bulan" class="form-group" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @foreach ($bulanList as $val => $label)
                <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    @if ($myRole === '1')
        <a href="{{ route('absensi.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Input Absensi
        </a>
    @endif
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Unit Kerja</th>
                <th>Hari Kerja</th>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpha</th>
                @if ($myRole === '1')
                    <th style="width:1%"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($absensi as $a)
                <tr>
                    <td class="cell-nik">{{ $a['nik'] }}</td>
                    <td class="cell-name">{{ $a['nama'] }}</td>
                    <td>{{ $a['unit_kerja'] }}</td>
                    <td>{{ $a['hari_kerja'] }}</td>
                    <td>{{ $a['hadir'] }}</td>
                    <td>{{ $a['sakit'] }}</td>
                    <td>{{ $a['izin'] }}</td>
                    <td>{{ $a['alpha'] }}</td>
                    @if ($myRole === '1')
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('absensi.edit', $a['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                                <form action="{{ route('absensi.destroy', $a['id']) }}" method="POST" onsubmit="return confirm('Hapus data absensi ini?');" style="margin:0;">
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
                    <td colspan="9">
                        <div class="table-empty">Belum ada data absensi untuk {{ $bulanList[$bulan] }} {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
