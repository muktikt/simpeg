@extends('layouts.app')

@section('title', $label)

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / {{ $label }}</div>
    <h1>{{ $label }}</h1>
</div>

<div class="placeholder-box">
    <h2>Modul ini belum dipindahkan</h2>
    <p>Fitur "{{ $label }}" masih ada di sistem SIMPEG lama dan belum dimigrasikan ke Laravel.<br>Menu tetap ditampilkan supaya tidak ada fitur yang hilang selama proses migrasi bertahap.</p>
</div>
@endsection
