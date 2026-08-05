@extends('layouts.app')

@section('title', 'Tunjangan Pendidikan')

@section('content')
<div class="page-head">
    @if(session('simpeg_user.userlevel') === '5' || request('my'))
        <div class="breadcrumb">Home / Pendapatan Saya / Tunjangan Pendidikan</div>
        <h1>Tunjangan Pendidikan</h1>
    @else
        <div class="breadcrumb">Home / Laporan Tunj. Pendidikan / Cetak Slip Tunj. Pendidikan</div>
        <h1>Cetak Slip Tunjangan Pendidikan</h1>
    @endif
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('gaji-tigabelas.laporan-slip') }}" style="display:flex; gap:10px;">
        @if(request('my'))<input type="hidden" name="my" value="1">@endif
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
        $totalNominal = $item ? ($item['gaji13_diterima'] ?? 2218400) : 2218400;
        $tahunAjaran = ($tahun - 1) . '/' . $tahun;
    @endphp

    <!-- Banner Tunjangan Pendidikan (Matching Theme SIMPEG: Navy & Sky Blue) -->
    <div style="background: linear-gradient(135deg, var(--navy) 0%, var(--teal-dark) 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="font-size: 14px; font-weight: 500; opacity: 0.85; margin-bottom: 4px;">Total Tunjangan &middot; Tahun {{ $tahun }}</div>
            <div style="font-size: 32px; font-weight: 700; font-family: 'Space Grotesk', sans-serif;">
                Rp {{ number_format($totalNominal, 0, ',', '.') }}
            </div>
            <div style="font-size: 13px; opacity: 0.85; margin-top: 2px;">Tahun Ajaran {{ $tahunAjaran }}</div>
        </div>
        @if ($item)
            <a href="{{ route('gaji-tigabelas.show', $item['id']) }}" class="btn" style="background: var(--surface); color: var(--teal-dark); font-weight: 700; padding: 12px 20px; border-radius: 10px; text-decoration: none; box-shadow: var(--shadow-sm);">
                📄 Cek Detail (Slip Resmi)
            </a>
        @endif
    </div>

    <!-- Card Rincian Per Anak Responsif Desktop -->
    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px;">
        <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
            RINCIAN PER ANAK
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
            @foreach ($rincianAnak as $anak)
                <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; background: #fff;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 42px; height: 42px; border-radius: 50%; background: #2563eb; color: #fff; font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center;">
                                {{ $anak['inisial'] }}
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #0f172a; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                                    {{ $anak['nama'] }}
                                    <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; background: #eff6ff; color: #1d4ed8; border-radius: 6px;">{{ $anak['jenjang_singkat'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 17px;">
                            Rp {{ number_format($anak['nominal'], 0, ',', '.') }}
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #f1f5f9; padding-top: 10px; font-size: 13px;">
                        <div>
                            <span style="color: #64748b; text-transform: uppercase; font-size: 11px; font-weight: 600; display: block; margin-bottom: 2px;">JENJANG</span>
                            <span style="font-weight: 600; color: #334155;">{{ $anak['jenjang_detail'] }}</span>
                        </div>
                        <div style="text-align: right;">
                            <span style="color: #64748b; text-transform: uppercase; font-size: 11px; font-weight: 600; display: block; margin-bottom: 2px;">STATUS</span>
                            <span style="font-weight: 600; font-size: 12px; padding: 4px 10px; border-radius: 6px; background: {{ $anak['status_bg'] }}; color: {{ $anak['status_color'] }};">
                                {{ $anak['status'] }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: var(--teal-soft); border-radius: 12px; border: 1px solid var(--border);">
            <span style="font-weight: 700; color: var(--teal-dark); font-size: 15px;">Total Diterima</span>
            <span style="font-weight: 700; color: var(--teal); font-size: 18px;">Rp {{ number_format($totalNominal, 0, ',', '.') }}</span>
        </div>
    </div>

@else
    <!-- Tampilan Admin / Keuangan -->
    <p class="report-note">Menampilkan Gaji 13 yang sudah terbit. Klik "Lihat Slip" untuk detail lengkap per pegawai.</p>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>NIK</th><th>Nama</th><th>Gaji 13 Diterima</th><th style="width:1%"></th></tr>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td>Rp {{ number_format($d['gaji13_diterima'], 0, ',', '.') }}</td>
                        <td><a href="{{ route('gaji-tigabelas.show', $d['id']) }}" class="btn btn-outline btn-sm">Lihat Slip</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="table-empty">Belum ada Gaji 13 yang terbit untuk tahun ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
