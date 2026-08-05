@extends('layouts.app')

@section('title', 'Cetak Slip Insentif')

@section('content')
<div class="page-head">
    @if(session('simpeg_user.userlevel') === '5' || request('my'))
        <div class="breadcrumb">Home / Pendapatan Saya / Insentif</div>
        <h1>Insentif Pegawai</h1>
    @else
        <div class="breadcrumb">Home / Laporan Insentif / Cetak Slip Insentif</div>
        <h1>Cetak Slip Insentif</h1>
    @endif
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('insentif.laporan-slip') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
        @if(request('my'))<input type="hidden" name="my" value="1">@endif
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

@if (session('simpeg_user.userlevel') === '5' || request('my'))
    @php
        $item = $data->first();
        $insentifNominal = $item ? ($item[$nominalKey] ?? 750000) : 750000;
        $bulanNama = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? 'Juni';
    @endphp

    <!-- Banner Insentif (Matching Theme SIMPEG: Navy & Sky Blue) -->
    <div style="background: linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 50%, var(--teal-dark) 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="font-size: 14px; font-weight: 500; opacity: 0.85; margin-bottom: 4px;">Insentif &middot; {{ $bulanNama }} {{ $tahun }}</div>
            <div style="font-size: 32px; font-weight: 700; font-family: 'Space Grotesk', sans-serif;">
                Rp {{ number_format($insentifNominal, 0, ',', '.') }}
            </div>
            <div style="font-size: 13px; opacity: 0.85; margin-top: 2px;">Insentif Kinerja Triwulan II</div>
        </div>
    </div>

    <!-- Card Riwayat Insentif Responsif Desktop -->
    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px;">
        <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
            RIWAYAT INSENTIF
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach ($riwayatInsentif as $rw)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="font-size: 20px; width: 40px; height: 40px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            {!! $rw['icon'] !!}
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #0f172a; font-size: 15px;">{{ $rw['judul'] }}</div>
                            <div style="font-size: 13px; color: #64748b; margin-top: 2px;">{!! $rw['periode'] !!}</div>
                        </div>
                    </div>
                    <div style="font-weight: 700; color: var(--teal-dark); font-size: 16px;">
                        Rp {{ number_format($rw['nominal'], 0, ',', '.') }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@else
    <!-- Tampilan Admin / Keuangan -->
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
@endif
@endsection
