@extends('layouts.app')

@section('title', 'Cetak Slip Insentif')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Laporan Insentif / Cetak Slip Insentif</div>
    <h1>Cetak Slip Insentif</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('insentif.laporan-slip') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
        <select name="sumber" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            <option value="gaji13" @selected($sumber === 'gaji13')>Dari Gaji 13</option>
            <option value="gaji_bulanan" @selected($sumber === 'gaji_bulanan')>Dari Gaji Bulanan (Permen)</option>
        </select>
        @if ($sumber === 'gaji_bulanan')
            <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
                @foreach ($bulanList as $val => $label)
                    <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>

<p class="report-note">Insentif tidak punya data input sendiri, murni menampilkan data yang sudah terbit dari {{ $sumber === 'gaji13' ? 'modul Gaji 13' : 'modul Proses Gaji Bulanan' }}.</p>

<div class="table-card">
    <table class="data-table">
        <thead><tr><th>NIK</th><th>Nama</th><th>Nominal</th></tr></thead>
        <tbody>
            @forelse ($data as $d)
                <tr>
                    <td class="cell-nik">{{ $d['nik'] }}</td>
                    <td class="cell-name">{{ $d['nama'] }}</td>
                    <td>Rp {{ number_format($d[$nominalKey], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3"><div class="table-empty">Belum ada data yang terbit untuk periode ini.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
