<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GajiLaporanController extends Controller
{
    /**
     * MODUL INI READ-ONLY - TIDAK PUNYA DATA SENDIRI.
     *
     * Dicek ke sistem lama: semua file di grup menu "Laporan Penggajian"
     * (kecuali Absensi, Prestasi, Gapok/Golongan yang sudah dibuat
     * terpisah) ternyata cuma filter/format berbeda dari data yang sama:
     *   - Lap. Lembur          -> tbl_prestasi (field jam_lembur/nominal_lembur)
     *   - Lap. Slip Gaji        -> tbl_gaji_detail (data Gaji Proses)
     *   - Lap. Buku Besar Gaji  -> tbl_gaji_detail, semua baris jadi 1 daftar
     *   - Lap. Buku Besar Per Sub -> sama, dikelompokkan per unit kerja
     *   - Lap. Payroll          -> tbl_gaji_detail + tbl_rek_bjbs (rekening)
     *   - Lap. Pajak            -> tbl_gaji_detail (field potongan_pajak)
     *   - Lap. BPJSTK           -> tbl_gaji_detail (field tunjangan/potongan_bpjstk)
     *   - Lap. Tunj. Perumahan  -> tbl_gaji_detail (field tunjangan_perumahan)
     *
     * Jadi semua method di bawah ini murni MENYARING & MERINGKAS data dari
     * GajiProsesController (dummy_gaji_proses) dan PrestasiController
     * (dummy_prestasi_gaji) yang sudah ada - tidak ada CRUD baru.
     * Hanya data yang sudah TERBIT yang ditampilkan (draft tidak dihitung),
     * mengikuti pola query asli yang selalu mengacu ke data final.
     */
    protected function gajiTerbit(int $bulan, int $tahun)
    {
        return collect(session('dummy_gaji_proses', []))
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->filter(fn ($row) => $row['status'] === 'terbit');
    }

    protected function pegawaiById(int $id): ?array
    {
        return collect(session('dummy_pegawai', []))->firstWhere('id', $id);
    }

    protected function periodeInput(Request $request): array
    {
        return [
            'bulan' => (int) $request->get('bulan', now()->month),
            'tahun' => (int) $request->get('tahun', now()->year),
        ];
    }

    public function lembur(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = collect(session('dummy_prestasi_gaji', []))
            ->filter(fn ($row) => \Illuminate\Support\Carbon::parse($row['tanggal'])->month === $bulan
                && \Illuminate\Support\Carbon::parse($row['tanggal'])->year === $tahun
                && $row['jam_lembur'] > 0)
            ->map(function ($row) {
                $p = $this->pegawaiById($row['pegawai_id']);
                $row['nik'] = $p['nik'] ?? '-';
                $row['nama'] = $p['nama'] ?? '-';
                $row['nominal_lembur'] = $row['jam_lembur'] * PrestasiController::RATE_LEMBUR_PER_JAM;

                return $row;
            })
            ->sortBy('nama')
            ->values();

        return view('gaji-laporan.lembur', compact('data', 'bulan', 'tahun'));
    }

    public function slipGaji(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();

        return view('gaji-laporan.slip-gaji', compact('data', 'bulan', 'tahun'));
    }

    public function bukuBesar(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalGaji = $data->sum('gaji_bersih');

        return view('gaji-laporan.buku-besar', compact('data', 'bulan', 'tahun', 'totalGaji'));
    }

    public function bukuBesarPerSub(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)
            ->map(function ($row) {
                $p = $this->pegawaiById($row['pegawai_id']);
                $row['unit_kerja'] = $p['unit_kerja'] ?? '-';

                return $row;
            })
            ->groupBy('unit_kerja')
            ->map(fn ($group) => [
                'rows' => $group->sortBy('nama')->values(),
                'total' => $group->sum('gaji_bersih'),
            ]);

        return view('gaji-laporan.buku-besar-per-sub', compact('data', 'bulan', 'tahun'));
    }

    public function payroll(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalPayroll = $data->sum('gaji_bersih');

        return view('gaji-laporan.payroll', compact('data', 'bulan', 'tahun', 'totalPayroll'));
    }

    public function pajak(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalPajak = $data->sum('potongan_pajak');

        return view('gaji-laporan.pajak', compact('data', 'bulan', 'tahun', 'totalPajak'));
    }

    public function bpjstk(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalBpjstk = $data->sum('tunjangan_bpjstk') + $data->sum('potongan_bpjstk');

        return view('gaji-laporan.bpjstk', compact('data', 'bulan', 'tahun', 'totalBpjstk'));
    }

    public function tunjPerumahan(Request $request)
    {
        [$bulan, $tahun] = array_values($this->periodeInput($request));

        $data = $this->gajiTerbit($bulan, $tahun)->sortBy('nama')->values();
        $totalPerumahan = $data->sum('tunjangan_perumahan');

        return view('gaji-laporan.tunj-perumahan', compact('data', 'bulan', 'tahun', 'totalPerumahan'));
    }
}
