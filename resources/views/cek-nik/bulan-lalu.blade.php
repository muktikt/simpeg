@extends('layouts.app')

@section('title', 'Data NIK Bulan Lalu')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Set Potongan / Cek NIK Bulan Lalu</div>
    <h1>DATA NIK BULAN KEMARIN</h1>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:60px;">No</th>
                <th>NIK</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($nikList as $nik)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="cell-nik" style="font-weight:600;">{{ $nik }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">
                        <div class="table-empty">Tidak ada data NIK potongan untuk bulan lalu.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
