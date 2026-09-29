@extends('layouts.app')

@section('title', 'Tunjangan Hari Raya (THR)')

@section('content')
<div class="page-head">
    @if(session('simpeg_user.userlevel') === '5' || request('my'))
        <div class="breadcrumb">Home / Pendapatan Saya / THR</div>
        <h1>Tunjangan Hari Raya (THR)</h1>
    @else
        <div class="breadcrumb">Home / Laporan THR / Cetak Slip THR</div>
        <h1>Cetak Slip THR</h1>
    @endif
</div>

<div class="toolbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <form method="GET" action="{{ route('thr.laporan-slip') }}" style="display:flex; gap:10px;">
        @if(request('my'))<input type="hidden" name="my" value="1">@endif
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    <button type="button" class="btn btn-outline" onclick="window.print()" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>

@if (session('simpeg_user.userlevel') === '5' || request('my'))
    @php
        $item = $data->first();
    @endphp

    @if ($item)
        @php
            $thrNominal = $item['thr_diterima'] ?? 0;
            $totalPotongan = ($item['total_potongan_pendapatan'] ?? 0) + ($item['total_potongan_non_pendapatan'] ?? 0);
        @endphp

        @include('partials.official-slip', [
            'judul' => 'DAFTAR TUNJANGAN HARI RAYA TAHUN : ' . $tahun,
            'data' => $item,
            'labelDiterima' => 'JUMLAH PENDAPATAN DITERIMA',
        ])

        @if (!empty($riwayatThr) && count($riwayatThr) > 0)
            <div class="panel" style="max-width:840px; margin:0 auto 32px;">
                <h3 style="margin-bottom:14px; font-size:15px;">Riwayat THR Tahun Lainnya</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:10px;">
                    @foreach ($riwayatThr as $rw)
                        <a href="{{ route('thr.show', $rw['id']) }}" style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; text-decoration:none;" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='#F8FAFC'">
                            <span style="font-weight:600; color:#334155; font-size:13px;">THR {{ $rw['tahun'] }}</span>
                            <span style="font-weight:700; color:#D97706; font-size:13.5px;">Rp {{ number_format($rw['thr_diterima'], 0, ',', '.') }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    @else
        <div class="table-card" style="padding: 40px; text-align: center; margin-top: 16px;">
            <div class="table-empty">Belum ada data THR yang terbit untuk Anda pada tahun {{ $tahun }}. Silakan pilih tahun lain di atas.</div>
        </div>
    @endif

@else
    <!-- Tampilan Admin / Keuangan -->
    <p class="report-note">Menampilkan daftar slip THR pegawai yang sudah terbit. Klik "Cetak Slip Resmi" pada pegawai yang ingin dicetak.</p>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama Pegawai</th>
                    <th style="text-align:right;">Total THR Bersih</th>
                    <th style="width:1%; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td style="text-align:right; font-weight:700; color:#0F172A;">Rp {{ number_format($d['thr_diterima'], 0, ',', '.') }}</td>
                        <td style="white-space:nowrap; text-align:center;">
                            <a href="{{ route('thr.show', $d['id']) }}" class="btn btn-outline btn-sm" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="14" height="14"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
                                Cetak Slip Resmi
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="table-empty">Belum ada THR yang terbit untuk tahun ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
