<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CekNikController extends Controller
{
    /**
     * Cek NIK Bulan Lalu / Bulan Ini — menampilkan NIK yang ada di potongan keu
     * pada periode tertentu. Disamakan dengan cek_nik_old.php / cek_nik_new.php.
     *
     * Hapus Kesalahan NIK — hapus potongan berdasarkan NIK + bulan + tahun.
     * Disamakan dengan hapus_nik_potongan.php.
     */

    protected function getPotonganGaji(): array
    {
        return session('dummy_potongan_gaji', []);
    }

    public function bulanLalu()
    {
        $target = now()->subMonth();
        $nikList = collect($this->getPotonganGaji())->filter(function ($r) use ($target) {
            $d = \Carbon\Carbon::parse($r['tgl_potongan']);
            return $d->month === $target->month && $d->year === $target->year;
        })->pluck('nik')->unique()->values();

        return view('cek-nik.bulan-lalu', compact('nikList'));
    }

    public function bulanIni()
    {
        $nikList = collect($this->getPotonganGaji())->filter(function ($r) {
            $d = \Carbon\Carbon::parse($r['tgl_potongan']);
            return $d->month === now()->month && $d->year === now()->year;
        })->pluck('nik')->unique()->values();

        return view('cek-nik.bulan-ini', compact('nikList'));
    }

    public function hapusForm()
    {
        return view('cek-nik.hapus');
    }

    public function hapusProses(Request $request)
    {
        $validated = $request->validate([
            'nik'   => 'required|string',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
        ]);

        $nik   = $validated['nik'];
        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];

        $data = collect($this->getPotonganGaji());
        $before = $data->count();
        $data = $data->reject(function ($r) use ($nik, $bulan, $tahun) {
            $d = \Carbon\Carbon::parse($r['tgl_potongan']);
            return $r['nik'] === $nik && $d->month === $bulan && $d->year === $tahun;
        })->values()->all();

        session()->put('dummy_potongan_gaji', $data);

        $deleted = $before - count($data);
        if ($deleted > 0) {
            return back()->with('success', "Berhasil menghapus {$deleted} data potongan NIK {$nik}.");
        }

        return back()->withErrors(['nik' => 'Data potongan dengan NIK dan periode tersebut tidak ditemukan.']);
    }
}
