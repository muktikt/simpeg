@extends('layouts.app')

@section('title', 'SET Gaji Pokok')

@section('content')
@php $myRole = session('simpeg_user.userlevel'); @endphp

<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Proses Gaji / SET Gaji Pokok</div>
    <h1>SET Gaji Pokok</h1>
</div>

<div class="toolbar">
    <div></div>
    @if ($myRole === '1')
        <a href="{{ route('gaji-pokok.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Gaji Pokok
        </a>
    @endif
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Golongan</th>
                <th>Masa Kerja</th>
                <th>Nominal Gaji Pokok</th>
                @if ($myRole === '1')
                    <th style="width:1%"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($gapok as $g)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="cell-name">{{ $g['golongan'] }}</td>
                    <td>{{ $g['masa_kerja'] }}</td>
                    <td>Rp {{ number_format($g['nominal'], 0, ',', '.') }}</td>
                    @if ($myRole === '1')
                        <td>
                            <a href="{{ route('gaji-pokok.edit', $g['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="table-empty">Belum ada data gaji pokok.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
