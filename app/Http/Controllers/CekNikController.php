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
        try {
            return \Illuminate\Support\Facades\DB::table('potongan_keu')
                ->where('tipe', 'gaji')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->toArray();
        } catch (\Throwable $e) {}

        return [];
    }

    public function bulanLalu()
    {
        $target = now()->subMonth();
        $nikList = collect($this->getPotonganGaji())->filter(function ($r) use ($target) {
            $d = \Carbon\Carbon::parse($r['tgl_potongan'] ?? $r['created_at'] ?? now());
            return $d->month === $target->month && $d->year === $target->year;
        })->pluck('nik')->filter()->unique()->values();

        return view('cek-nik.bulan-lalu', compact('nikList'));
    }

    public function bulanIni()
    {
        $nikList = collect($this->getPotonganGaji())->filter(function ($r) {
            $d = \Carbon\Carbon::parse($r['tgl_potongan'] ?? $r['created_at'] ?? now());
            return $d->month === now()->month && $d->year === now()->year;
        })->pluck('nik')->filter()->unique()->values();

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

        $deleted = 0;
        try {
            // Hapus dari database potongan_keu
            $rows = \Illuminate\Support\Facades\DB::table('potongan_keu')
                ->where('tipe', 'gaji')
                ->where('nik', $nik)
                ->get();

            $idsToDelete = [];
            foreach ($rows as $row) {
                $d = \Carbon\Carbon::parse($row->tgl_potongan ?? $row->created_at ?? now());
                if ($d->month === $bulan && $d->year === $tahun) {
                    $idsToDelete[] = $row->id;
                }
            }

            if (!empty($idsToDelete)) {
                $deleted = \Illuminate\Support\Facades\DB::table('potongan_keu')
                    ->whereIn('id', $idsToDelete)
                    ->delete();
            }
        } catch (\Throwable $e) {}

        if ($deleted > 0) {
            return back()->with('success', "Berhasil menghapus {$deleted} data potongan NIK {$nik}.");
        }

        return back()->withErrors(['nik' => 'Data potongan dengan NIK dan periode tersebut tidak ditemukan.']);
    }
}
