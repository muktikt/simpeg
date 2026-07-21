@extends('layouts.app')

@section('title', 'Proses Gaji Bulanan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Proses Gaji / Proses Gaji Bulanan</div>
    <h1>Proses Gaji Bulanan</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('gaji-proses.index') }}" style="display:flex; gap:10px;">
        <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
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

    <a href="{{ route('gaji-proses.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Proses Gaji Pegawai
    </a>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Total Pendapatan</th>
                <th>Total Potongan</th>
                <th>Gaji Bersih</th>
                <th>Status</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gaji as $g)
                <tr>
                    <td class="cell-nik">{{ $g['nik'] }}</td>
                    <td class="cell-name">{{ $g['nama'] }}</td>
                    <td>{{ \App\Http\Controllers\GajiProsesController::KATEGORI[$g['kategori']] ?? $g['kategori'] }}</td>
                    <td>Rp {{ number_format($g['total_pendapatan'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($g['total_potongan'], 0, ',', '.') }}</td>
                    <td><strong>Rp {{ number_format($g['gaji_bersih'], 0, ',', '.') }}</strong></td>
                    <td>
                        @if ($g['status'] === 'terbit')
                            <span class="badge badge-PT">Terbit</span>
                        @else
                            <span class="badge badge-PH">Draft</span>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('gaji-proses.show', $g['id']) }}" class="btn btn-outline btn-sm">Lihat</a>
                            @if ($g['status'] !== 'terbit')
                                <form action="{{ route('gaji-proses.terbitkan', $g['id']) }}" method="POST" onsubmit="return confirm('Terbitkan gaji ini? Setelah terbit tidak bisa diubah/dihapus lagi.');" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Terbitkan</button>
                                </form>
                                <form action="{{ route('gaji-proses.destroy', $g['id']) }}" method="POST" onsubmit="return confirm('Hapus draft ini?');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="table-empty">Belum ada proses gaji untuk {{ $bulanList[$bulan] }} {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
