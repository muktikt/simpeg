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
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tgl Entry</th>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    @foreach ($kolom as $k)
                        <th style="text-align:right;">{{ $kolomLabels[$k] }}</th>
                    @endforeach
                    <th style="text-align:right;">Total Potongan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($item['tgl_potongan'])->translatedFormat('d/m/Y') }}</td>
                        <td class="cell-nik">{{ $item['nik'] }}</td>
                        <td class="cell-name">{{ $item['nama'] }}</td>
                        @foreach ($kolom as $k)
                            <td style="text-align:right;">Rp {{ number_format($item[$k] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($kolom) + 5 }}">
                            <div class="table-empty">Data tidak ditemukan untuk periode ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
