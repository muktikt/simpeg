@extends('layouts.app')

@section('title', 'Pengaturan Akun Pengguna')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Pengaturan Umum / Pengaturan Akun Pengguna</div>
    <h1>Pengaturan Akun Pengguna</h1>
</div>

<div class="toolbar">
    <div></div>
    <a href="{{ route('user-akses.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Tambah Akun
    </a>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nama Lengkap</th>
                <th>Username (NIK)</th>
                <th>Level</th>
                <th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $u)
                <tr>
                    <td class="cell-name">{{ $u['nama'] }}</td>
                    <td class="cell-nik">{{ $u['username'] }}</td>
                    <td>{{ $roleList[$u['userlevel']]['label'] ?? $u['userlevel'] }}</td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('user-akses.edit', $u['id']) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form action="{{ route('user-akses.destroy', $u['id']) }}" method="POST" onsubmit="return confirm('Hapus akun ini? Pengguna tidak akan bisa login lagi.');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <div class="table-empty">Belum ada akun pengguna.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
