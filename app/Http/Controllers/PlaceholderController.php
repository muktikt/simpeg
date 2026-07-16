<?php

namespace App\Http\Controllers;

class PlaceholderController extends Controller
{
    /**
     * Halaman sementara untuk semua menu lama yang belum dipindah ke Laravel.
     * Tujuannya supaya semua link di sidebar tetap ada / tidak 404,
     * sambil modul-modulnya dimigrasikan satu per satu.
     */
    public function show(string $slug)
    {
        $label = ucwords(str_replace('-', ' ', $slug));

        return view('pages.placeholder', compact('label'));
    }
}
