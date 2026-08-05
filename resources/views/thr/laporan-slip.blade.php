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

<div class="toolbar">
    <form method="GET" action="{{ route('thr.laporan-slip') }}" style="display:flex; gap:10px;">
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
    @endphp

    @if ($item)
        @php
            $thrNominal = $item['thr_diterima'] ?? 0;
            $tunjTetap = ($item['total_pendapatan'] ?? 0) - ($item['gapok'] ?? 0);
        @endphp

        <!-- Banner THR (Matching Theme SIMPEG: Navy & Sky Blue) -->
        <div style="background: linear-gradient(135deg, var(--navy) 0%, var(--navy-2) 50%, var(--teal-dark) 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: var(--shadow-md); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-size: 14px; font-weight: 500; opacity: 0.85; margin-bottom: 4px;">THR Terakhir Cair &middot; Tahun {{ $tahun }}</div>
                <div style="font-size: 32px; font-weight: 700; font-family: 'Space Grotesk', sans-serif;">
                    Rp {{ number_format($thrNominal, 0, ',', '.') }}
                </div>
            </div>
            <a href="{{ route('thr.show', $item['id']) }}" class="btn" style="background: var(--surface); color: var(--teal-dark); font-weight: 700; padding: 12px 20px; border-radius: 10px; text-decoration: none; box-shadow: var(--shadow-sm);">
                📄 Cek Detail (Slip Resmi THR)
            </a>
        </div>

        <!-- Grid Responsif Desktop -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; margin-bottom: 24px;">
            
            <!-- Card 1: Rincian Perhitungan -->
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    RINCIAN PERHITUNGAN
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Gaji pokok</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($item['gapok'] ?? 0, 0, ',', '.') }}</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #475569;">Tunjangan tetap</span>
                        <span style="font-weight: 600; color: #0f172a;">Rp {{ number_format($tunjTetap, 0, ',', '.') }}</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: var(--teal-soft); border-radius: 10px; margin-top: 8px;">
                        <span style="font-weight: 700; color: var(--teal-dark);">Total THR Diterima</span>
                        <span style="font-weight: 700; color: var(--teal); font-size: 16px;">Rp {{ number_format($thrNominal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Riwayat Per Tahun -->
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    RIWAYAT PER TAHUN
                </div>

                @if (!empty($riwayatThr) && count($riwayatThr) > 0)
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach ($riwayatThr as $rw)
                            <a href="{{ route('thr.show', $rw['id']) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                                <span style="font-weight: 600; color: #334155; font-size: 14px;">THR {{ $rw['tahun'] }}</span>
                                <span style="font-weight: 700; color: #d97706; font-size: 14px;">Rp {{ number_format($rw['thr_diterima'], 0, ',', '.') }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="table-empty">Belum ada riwayat THR terbit sebelumnya.</div>
                @endif
            </div>

        </div>

    @else
        <div class="table-card" style="padding: 40px; text-align: center; margin-top: 16px;">
            <div class="table-empty">Belum ada data THR yang terbit untuk Anda pada tahun {{ $tahun }}. Silakan pilih tahun lain di atas.</div>
        </div>
    @endif

@else
    <!-- Tampilan Admin / Keuangan -->
    <p class="report-note">Menampilkan THR yang sudah terbit. Klik "Lihat Slip" untuk detail lengkap per pegawai.</p>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>THR Diterima</th>
                    <th style="width:1%"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td>Rp {{ number_format($d['thr_diterima'], 0, ',', '.') }}</td>
                        <td><a href="{{ route('thr.show', $d['id']) }}" class="btn btn-outline btn-sm">Lihat Slip</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="table-empty">Belum ada THR yang terbit untuk tahun ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
