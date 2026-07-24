<?php

namespace App\Http\Controllers\Concerns;

/**
 * Alur approval berjenjang untuk Gaji Proses, THR, dan Gaji 13.
 *
 * Disamakan dengan sistem lama (proses_terbit_gaji_dirum.php dkk) yang
 * mengecek field disetujui_oleh terhadap NIK yang di-hardcode di kode
 * ('1711254'=Kepegawaian, '1711002'=Dirum, '1711001'=Dirut). Di sini NIK
 * approver diambil dari config/simpeg_approval.php supaya bisa diubah
 * tanpa perlu edit kode program.
 *
 * Urutan status: draft -> kepegawaian -> dirum -> terbit (final).
 * Dipakai bareng oleh GajiProsesController, ThrController, GajiTigabelasController.
 */
trait HasApprovalChain
{
    protected function nextStageFor(string $status): ?string
    {
        return match ($status) {
            'draft' => 'kepegawaian',
            'kepegawaian' => 'dirum',
            'dirum' => 'dirut',
            default => null,
        };
    }

    protected function approverNikFor(string $stage): ?string
    {
        return config('simpeg_approval')[$stage] ?? null;
    }

    /**
     * Cek apakah pengguna yang sedang login adalah approver yang berhak
     * untuk tahap berikutnya dari status saat ini.
     */
    protected function canUserApprove(string $status): bool
    {
        $stage = $this->nextStageFor($status);

        if (! $stage) {
            return false;
        }

        return session('simpeg_user.nik') === $this->approverNikFor($stage);
    }

    /**
     * Wrapper public dari canUserApprove() - dipakai ApprovalController
     * buat cek dari controller lain tanpa perlu ubah visibility method aslinya.
     */
    public function canUserApprovePublic(string $status): bool
    {
        return $this->canUserApprove($status);
    }

    /**
     * Terapkan approval ke baris data - pindah ke status berikutnya,
     * catat siapa yang menyetujui dan kapan (kalau sudah final/terbit).
     */
    protected function applyApproval(array $row): array
    {
        $stage = $this->nextStageFor($row['status']);
        $row['status'] = $stage === 'dirut' ? 'terbit' : $stage;
        $row['disetujui_oleh'] = session('simpeg_user.nama_peg', 'Admin');

        if ($row['status'] === 'terbit') {
            $row['tgl_terbit'] = now()->toDateString();
        }

        return $row;
    }

    /**
     * Label status untuk ditampilkan di tabel/halaman detail.
     */
    public static function approvalStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Menunggu Kepegawaian',
            'kepegawaian' => 'Menunggu Dirum',
            'dirum' => 'Menunggu Dirut',
            'terbit' => 'Terbit (Final)',
            default => ucfirst($status),
        };
    }
}
