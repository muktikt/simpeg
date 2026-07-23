@extends('layouts.app')

@section('title', 'Gaji 13 / Tunjangan Pendidikan')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Gaji 13 / Proses Gaji 13</div>
    <h1>Gaji 13 / Tunjangan Pendidikan</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('gaji-tigabelas.index') }}" style="display:flex; gap:10px;">
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    @if ($bisaKelola)
        <a href="{{ route('gaji-tigabelas.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Proses Gaji 13 Pegawai
        </a>
    @endif
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
                <th>Gaji 13 Diterima</th>
                <th>Status</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gaji13 as $g)
                <tr>
                    <td class="cell-nik">{{ $g['nik'] }}</td>
                    <td class="cell-name">{{ $g['nama'] }}</td>
                    <td>{{ \App\Http\Controllers\GajiTigabelasController::KATEGORI[$g['kategori']] ?? $g['kategori'] }}</td>
                    <td>Rp {{ number_format($g['total_pendapatan'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($g['total_potongan_pendapatan'] + $g['total_potongan_non_pendapatan'], 0, ',', '.') }}</td>
                    <td><strong>Rp {{ number_format($g['gaji13_diterima'], 0, ',', '.') }}</strong></td>
                    <td>
                        @if ($g['status'] === 'terbit')
                            <span class="badge badge-PT">Terbit</span>
                        @else
                            <span class="badge badge-PH">Draft</span>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('gaji-tigabelas.show', $g['id']) }}" class="btn btn-outline btn-sm">Lihat</a>
                            @if ($bisaKelola && $g['status'] !== 'terbit')
                                <form action="{{ route('gaji-tigabelas.terbitkan', $g['id']) }}" method="POST" onsubmit="event.preventDefault(); openConfirmModal(this, {title: 'Terbitkan Gaji 13', text: 'Terbitkan Gaji 13 ini? Setelah terbit tidak bisa diubah/dihapus lagi.', btnLabel: 'Ya, Terbitkan', theme: 'info'});" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Terbitkan</button>
                                </form>
                                <form action="{{ route('gaji-tigabelas.destroy', $g['id']) }}" method="POST" onsubmit="event.preventDefault(); openConfirmModal(this, {title: 'Hapus Draft Gaji 13', text: 'Apakah Anda yakin ingin menghapus draft Gaji 13 ini?', btnLabel: 'Ya, Hapus', theme: 'danger'});" style="margin:0;">
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
                        <div class="table-empty">Belum ada proses Gaji 13 untuk tahun {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
