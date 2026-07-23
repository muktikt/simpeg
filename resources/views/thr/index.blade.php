@extends('layouts.app')

@section('title', 'Proses THR')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); $bisaKelola = in_array($myRole, ['1', '2']); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan THR / Proses THR</div>
    <h1>Proses THR</h1>
</div>

<div class="toolbar">
    <form method="GET" action="{{ route('thr.index') }}" style="display:flex; gap:10px;">
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>

    @if ($bisaKelola)
        <a href="{{ route('thr.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Proses THR Pegawai
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
                <th>THR Diterima</th>
                <th>Status</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($thr as $t)
                <tr>
                    <td class="cell-nik">{{ $t['nik'] }}</td>
                    <td class="cell-name">{{ $t['nama'] }}</td>
                    <td>{{ \App\Http\Controllers\ThrController::KATEGORI[$t['kategori']] ?? $t['kategori'] }}</td>
                    <td>Rp {{ number_format($t['total_pendapatan'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($t['total_potongan_pendapatan'] + $t['total_potongan_non_pendapatan'], 0, ',', '.') }}</td>
                    <td><strong>Rp {{ number_format($t['thr_diterima'], 0, ',', '.') }}</strong></td>
                    <td>
                        @if ($t['status'] === 'terbit')
                            <span class="badge badge-PT">Terbit</span>
                        @else
                            <span class="badge badge-PH">Draft</span>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('thr.show', $t['id']) }}" class="btn btn-outline btn-sm">Lihat</a>
                            @if ($bisaKelola && $t['status'] !== 'terbit')
                                <form action="{{ route('thr.terbitkan', $t['id']) }}" method="POST" onsubmit="event.preventDefault(); openConfirmModal(this, {title: 'Terbitkan THR', text: 'Terbitkan THR ini? Setelah terbit tidak bisa diubah/dihapus lagi.', btnLabel: 'Ya, Terbitkan', theme: 'info'});" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Terbitkan</button>
                                </form>
                                <form action="{{ route('thr.destroy', $t['id']) }}" method="POST" onsubmit="event.preventDefault(); openConfirmModal(this, {title: 'Hapus Draft THR', text: 'Apakah Anda yakin ingin menghapus draft THR ini?', btnLabel: 'Ya, Hapus', theme: 'danger'});" style="margin:0;">
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
                        <div class="table-empty">Belum ada proses THR untuk tahun {{ $tahun }}.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
