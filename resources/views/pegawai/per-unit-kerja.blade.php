@extends('layouts.app')

@section('title', 'Daftar Pegawai Per Unit Kerja')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Data Pegawai / Data Per Unit Kerja</div>
    <h1>DAFTAR PEGAWAI AKTIF PER UNIT KERJA</h1>
</div>

<div class="filter-card" style="background:#fff; padding:20px; border-radius:8px; border:1px solid var(--border-color); margin-bottom:20px;">
    <form method="GET" action="{{ route('pegawai.per-unit-kerja') }}" style="display:flex; gap:16px; align-items:flex-end; max-width:600px;">
        <div class="form-group" style="margin:0; flex:1;">
            <label for="unit_kerja" style="font-weight:600; margin-bottom:8px; display:block;">UNIT KERJA</label>
            <select id="unit_kerja" name="unit_kerja" required style="width:100%;">
                <option value="">-- Pilih Unit Kerja --</option>
                @foreach ($unitKerjaList as $unit)
                    <option value="{{ $unit }}" @selected($selected === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Tampilkan
        </button>
    </form>
</div>

@if ($selected)
    <div class="table-card">
        <div class="table-card-header" style="padding:16px; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; font-size:16px; color:var(--text-color);">
                Unit Kerja: <strong>{{ $selected }}</strong> ({{ $filtered->count() }} Pegawai)
            </h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:50px;">No</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Masa Kerja</th>
                    <th>Status Pegawai</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filtered as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="cell-nik">{{ $p['nik'] }}</td>
                        <td class="cell-name">{{ $p['nama'] }}</td>
                        <td>{{ $p['jabatan'] }}</td>
                        <td>{{ $p['masa_kerja'] }}</td>
                        <td>
                            <span class="badge badge-info">{{ $p['status_label'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="table-empty">Tidak ada pegawai aktif di unit kerja ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
