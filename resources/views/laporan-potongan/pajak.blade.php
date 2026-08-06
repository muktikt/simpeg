@extends('layouts.app')

@section('title', $judul)

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan / {{ $judul }}</div>
    <h1>{{ strtoupper($judul) }}</h1>
</div>

<div class="filter-card" style="background:#fff; padding:16px; border-radius:8px; border:1px solid var(--border-color); margin-bottom:16px;">
    <form method="GET" style="display:flex; gap:16px; align-items:flex-end;">
        <div class="form-group" style="margin:0;">
            <label for="bulan">Bulan</label>
            <select id="bulan" name="bulan">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($bulan === $m)>
                        {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label for="tahun">Tahun</label>
            <input type="number" id="tahun" name="tahun" value="{{ $tahun }}" min="2000" max="2100" style="width:120px;">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama Pegawai</th>
                <th style="text-align:right;">PPh 21 / Pajak</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="cell-nik">{{ $item['nik'] }}</td>
                    <td class="cell-name">{{ $item['nama'] }}</td>
                    <td style="text-align:right; font-weight:600;">Rp {{ number_format(($item['total'] ?? 0) * 0.05, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <div class="table-empty">Data pajak tidak ditemukan untuk periode ini.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
