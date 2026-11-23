<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\HasApprovalChain;
use App\Http\Controllers\AuditLogController;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    use HasApprovalChain;

    public function index(Request $request)
    {
        $userNik   = (string) session('simpeg_user.nik', '');
        $userLevel = (int) session('simpeg_user.userlevel', 0);
        $userRole  = (int) session('simpeg_role', 0);

        $sdmNik      = (string) config('simpeg_approval.kepegawaian', '1711254');
        $dirumNik    = (string) config('simpeg_approval.dirum', '1711002');
        $dirutNik    = (string) config('simpeg_approval.dirut', '1711001');
        $keuanganNik = (string) config('simpeg_approval.keuangan', '1711296');

        $isSdm   = ($userNik === $sdmNik);
        $isDirum = ($userNik === $dirumNik);
        $isDirut = ($userNik === $dirutNik);
        $isKeu   = ($userLevel === 2 || $userRole === 2 || $userNik === $keuanganNik);

        $selectedStatus = strtolower(trim((string) $request->query('status', '')));
        if ($selectedStatus === '') {
            $selectedStatus = $isKeu ? 'all' : 'pending';
        }
        $pending = [];

        // 1. Gaji Bulanan
        try {
            $gajiProses = app(GajiProsesController::class)->all();
            foreach ($gajiProses as $item) {
                $itemSt = strtolower($item['status'] ?? '');
                $isFinal = in_array($itemSt, ['terbit', 'y', 'final', 'selesai'], true);
                $nextStage = $this->nextStageFor($item['status'] ?? '');
                $match = false;
                if ($nextStage === 'kepegawaian' && $isSdm) $match = true;
                elseif ($nextStage === 'dirum' && ($isDirum || $userNik === $this->approverNikFor('dirum'))) $match = true;
                elseif ($nextStage === 'dirut' && ($isDirut || $userNik === $this->approverNikFor('dirut'))) $match = true;

                if ($selectedStatus === 'pending' && !$match) continue;
                if ($selectedStatus === 'final' && !$isFinal) continue;
                if (!$match && !$isFinal && $selectedStatus !== 'all') continue;
                if (!$match && !$isFinal) continue;

                $gapok = (float) ($item['gapok'] ?? ($item['gaji_pokok'] ?? 0));
                $totalPendapatan = (float) ($item['total_pendapatan'] ?? 0);
                $totalPotongan = (float) ($item['total_potongan'] ?? 0);
                $gajiBersih = (float) ($item['gaji_bersih'] ?? ($totalPendapatan - $totalPotongan));
                $totalTunjangan = max(0, $totalPendapatan - $gapok);

                $lbl = $isFinal ? 'Terbit Final' : 'Menunggu Persetujuan Anda';

                $item['jenis'] = 'Gaji Bulanan';
                $item['jenis_key'] = 'gaji';
                $item['nominal'] = $gajiBersih;
                $item['breakdown'] = [
                    'Gaji Pokok' => $gapok,
                    'Total Tunjangan' => $totalTunjangan,
                    'Total Pendapatan (Bruto)' => $totalPendapatan,
                    'Total Potongan' => $totalPotongan,
                    'Gaji Bersih' => $gajiBersih,
                ];
                $item['route'] = route('gaji-proses.show', $item['id']);
                $item['status'] = $item['status'] ?? ($isFinal ? 'terbit' : 'draft');
                $item['status_label'] = $lbl;
                $item['can_approve'] = $match && !$isFinal;
                $pending[] = $item;
            }
        } catch (\Throwable $e) {
            Log::warning('Approval Gaji Bulanan query failed: ' . $e->getMessage());
        }

        // 2. THR
        try {
            $thr = app(ThrController::class)->all();
            foreach ($thr as $item) {
                $itemSt = strtolower($item['status'] ?? '');
                $isFinal = in_array($itemSt, ['terbit', 'y', 'final', 'selesai'], true);
                $nextStage = $this->nextStageFor($item['status'] ?? '');
                $match = false;
                if ($nextStage === 'kepegawaian' && $isSdm) $match = true;
                elseif ($nextStage === 'dirum' && ($isDirum || $userNik === $this->approverNikFor('dirum'))) $match = true;
                elseif ($nextStage === 'dirut' && ($isDirut || $userNik === $this->approverNikFor('dirut'))) $match = true;

                if ($selectedStatus === 'pending' && !$match) continue;
                if ($selectedStatus === 'final' && !$isFinal) continue;
                if (!$match && !$isFinal && $selectedStatus !== 'all') continue;
                if (!$match && !$isFinal) continue;

                $gapok = (float) ($item['gapok'] ?? ($item['gaji_pokok'] ?? 0));
                $totalPendapatan = (float) ($item['total_pendapatan'] ?? 0);
                $totalPotongan = (float) (($item['total_potongan_pendapatan'] ?? 0) + ($item['total_potongan_non_pendapatan'] ?? 0));
                $thrDiterima = (float) ($item['thr_diterima'] ?? ($totalPendapatan - $totalPotongan));
                $totalTunjangan = max(0, $totalPendapatan - $gapok);

                $lbl = $isFinal ? 'Terbit Final' : 'Menunggu Persetujuan Anda';

                $item['jenis'] = 'THR';
                $item['jenis_key'] = 'thr';
                $item['nominal'] = $thrDiterima;
                $item['breakdown'] = [
                    'Masa Kerja' => ($item['masa_kerja_tahun'] ?? 0) . ' Tahun',
                    'Gaji Pokok' => $gapok,
                    'Total Tunjangan' => $totalTunjangan,
                    'Total Pendapatan' => $totalPendapatan,
                    'Total Potongan' => $totalPotongan,
                    'THR Diterima' => $thrDiterima,
                ];
                $item['route'] = route('thr.show', $item['id']);
                $item['status'] = $item['status'] ?? ($isFinal ? 'terbit' : 'draft');
                $item['status_label'] = $lbl;
                $item['can_approve'] = $match && !$isFinal;
                $pending[] = $item;
            }
        } catch (\Throwable $e) {
            Log::warning('Approval THR query failed: ' . $e->getMessage());
        }

        // 3. Gaji 13
        try {
            $gaji13 = app(GajiTigabelasController::class)->all();
            foreach ($gaji13 as $item) {
                $itemSt = strtolower($item['status'] ?? '');
                $isFinal = in_array($itemSt, ['terbit', 'y', 'final', 'selesai'], true);
                $nextStage = $this->nextStageFor($item['status'] ?? '');
                $match = false;
                if ($nextStage === 'kepegawaian' && $isSdm) $match = true;
                elseif ($nextStage === 'dirum' && ($isDirum || $userNik === $this->approverNikFor('dirum'))) $match = true;
                elseif ($nextStage === 'dirut' && ($isDirut || $userNik === $this->approverNikFor('dirut'))) $match = true;

                if ($selectedStatus === 'pending' && !$match) continue;
                if ($selectedStatus === 'final' && !$isFinal) continue;
                if (!$match && !$isFinal && $selectedStatus !== 'all') continue;
                if (!$match && !$isFinal) continue;

                $gapok = (float) ($item['gapok'] ?? ($item['gaji_pokok'] ?? 0));
                $totalPendapatan = (float) ($item['total_pendapatan'] ?? 0);
                $totalPotongan = (float) ($item['total_potongan'] ?? 0);
                $g13Diterima = (float) ($item['gaji13_diterima'] ?? ($totalPendapatan - $totalPotongan));
                $totalTunjangan = max(0, $totalPendapatan - $gapok);

                $lbl = $isFinal ? 'Terbit Final' : 'Menunggu Persetujuan Anda';

                $item['jenis'] = 'Gaji 13';
                $item['jenis_key'] = 'gaji13';
                $item['nominal'] = $g13Diterima;
                $item['breakdown'] = [
                    'Gaji Pokok' => $gapok,
                    'Total Tunjangan' => $totalTunjangan,
                    'Total Pendapatan' => $totalPendapatan,
                    'Total Potongan' => $totalPotongan,
                    'Gaji 13 Diterima' => $g13Diterima,
                ];
                $item['route'] = route('gaji-tigabelas.show', $item['id']);
                $item['status'] = $item['status'] ?? ($isFinal ? 'terbit' : 'draft');
                $item['status_label'] = $lbl;
                $item['can_approve'] = $match && !$isFinal;
                $pending[] = $item;
            }
        } catch (\Throwable $e) {
            Log::warning('Approval Gaji 13 query failed: ' . $e->getMessage());
        }

        // 4. Potongan Keuangan (Gaji, THR, Gaji 13)
        // Alur berjenjang: Keuangan Input (draft) -> SDM/Kepegawaian -> Dirum -> Dirut (Terbit 'Y' & Final ke Payroll)
        try {
            $potonganQuery = DB::table('potongan_keu');

            if ($selectedStatus === 'pending') {
                if ($isSdm) {
                    $potonganQuery->where(function ($q) {
                        $q->whereIn('status', ['N', 'draft'])->orWhereNull('status');
                    });
                } elseif ($isDirum) {
                    $potonganQuery->where('status', 'kepegawaian');
                } elseif ($isDirut) {
                    $potonganQuery->where('status', 'dirum');
                } else {
                    $potonganQuery->where(function ($q) {
                        $q->whereIn('status', ['N', 'draft', 'kepegawaian', 'dirum'])->orWhereNull('status');
                    });
                }
            } elseif ($selectedStatus === 'final' || $selectedStatus === 'terbit') {
                $potonganQuery->whereIn('status', ['Y', 'terbit']);
            } elseif ($selectedStatus === 'draft') {
                $potonganQuery->where(function ($q) {
                    $q->whereIn('status', ['N', 'draft'])->orWhereNull('status');
                });
            } elseif ($selectedStatus === 'sdm' || $selectedStatus === 'kepegawaian') {
                $potonganQuery->where('status', 'kepegawaian');
            } elseif ($selectedStatus === 'dirum') {
                $potonganQuery->where('status', 'dirum');
            }
            // Jika $selectedStatus === 'all', tampilkan semua status agar riwayat/histori bulanan tetap ada & tidak hilang

            $potonganItems = $potonganQuery->orderByDesc('id')->get();
            if ($potonganItems->isNotEmpty()) {
                $pegawaiMap = collect(app(PegawaiController::class)->all())->keyBy('nik');
                $tipeLabels = [
                    'gaji'   => 'Potongan Gaji',
                    'thr'    => 'Potongan THR',
                    'gaji13' => 'Potongan Gaji 13',
                ];
                $kolomPot = [
                    'pot_koperasi', 'pot_darmawanita', 'pot_air', 'pot_kas',
                    'pot_bjb', 'pot_bjbs', 'pot_asuransi', 'pot_btn',
                    'pot_zakat_profesi', 'pot_bpjs', 'pot_bpr',
                ];

                foreach ($potonganItems as $p) {
                    $pArr = (array) $p;
                    $total = 0;
                    foreach ($kolomPot as $k) {
                        $total += (float) ($pArr[$k] ?? 0);
                    }
                    $peg = $pegawaiMap->get($pArr['nik'] ?? '');
                    if (! $peg && ! empty($pArr['pegawai_id'])) {
                        $peg = $pegawaiMap->firstWhere('db_id', $pArr['pegawai_id']);
                    }
                    $namaPeg = $peg['nama'] ?? ($pArr['nik'] ?? '-');
                    $tipe = $pArr['tipe'] ?? 'gaji';

                    $breakdown = [];
                    $kolomMap = [
                        'pot_koperasi'      => 'Koperasi',
                        'pot_darmawanita'   => 'D. Wanita',
                        'pot_air'           => 'Ledeng',
                        'pot_kas'           => 'KAS',
                        'pot_bjb'           => 'BJB',
                        'pot_bjbs'          => 'BJB Syariah',
                        'pot_asuransi'      => 'Asuransi',
                        'pot_btn'           => 'BTN',
                        'pot_bpr'           => 'BRI / BPR',
                        'pot_zakat_profesi' => 'Zakat',
                        'pot_bpjs'          => 'BPJS',
                    ];
                    foreach ($kolomMap as $k => $label) {
                        $breakdown[$label] = (float) ($pArr[$k] ?? 0);
                    }

                    $itemStatus = $pArr['status'] ?? 'draft';
                    $lbl = match ($itemStatus) {
                        'kepegawaian' => 'Disetujui SDM (Menunggu Dirum)',
                        'dirum'       => 'Disetujui Dirum (Menunggu Dirut)',
                        'Y', 'terbit' => 'Terbit Final',
                        default       => 'Draft (Menunggu SDM)',
                    };

                    $canApprove = false;
                    if ($isDirut && $itemStatus === 'dirum') {
                        $canApprove = true;
                    } elseif ($isDirum && $itemStatus === 'kepegawaian') {
                        $canApprove = true;
                    } elseif ($isSdm && in_array($itemStatus, ['N', 'draft', null, ''], true)) {
                        $canApprove = true;
                    }

                    $pending[] = [
                        'id' => $pArr['id'],
                        'jenis' => $tipeLabels[$tipe] ?? ('Potongan ' . ucfirst($tipe)),
                        'jenis_key' => 'potongan_' . $tipe,
                        'nik' => $pArr['nik'],
                        'nama' => $namaPeg,
                        'jabatan' => $peg['jabatan'] ?? '-',
                        'unit_kerja' => $peg['unit_kerja'] ?? '-',
                        'bulan' => (int) ($pArr['bulan'] ?? now()->month),
                        'tahun' => (int) ($pArr['tahun'] ?? now()->year),
                        'nominal' => $total,
                        'breakdown' => $breakdown,
                        'petugas_entri' => $pArr['petugas_entri'] ?? '-',
                        'tgl_potongan' => !empty($pArr['tgl_potongan']) ? date('d/m/Y', strtotime($pArr['tgl_potongan'])) : '-',
                        'status' => $itemStatus,
                        'status_label' => $lbl,
                        'can_approve' => $canApprove,
                        'route' => route('approval.index', ['jenis' => 'potongan_' . $tipe]),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Approval potongan_keu query failed: ' . $e->getMessage());
        }

        // 5. Pengajuan Cuti (Khusus SDM)
        if ($isSdm) {
            try {
                $cutiPending = DB::table('pengajuan_cuti')
                    ->leftJoin('pegawai', 'pengajuan_cuti.pegawai_id', '=', 'pegawai.id')
                    ->whereRaw("LOWER(pengajuan_cuti.status) = 'pending'")
                    ->select(
                        'pengajuan_cuti.*',
                        'pegawai.nik as p_nik',
                        'pegawai.name as p_name',
                        'pegawai.jabatan as p_jabatan',
                        'pegawai.unit_kerja as p_unit'
                    )
                    ->orderByDesc('pengajuan_cuti.created_at')
                    ->get();

                foreach ($cutiPending as $c) {
                    $cTime = $c->created_at ? strtotime($c->created_at) : time();
                    $tMulai = $c->tanggal_mulai ? date('d/m/Y', strtotime($c->tanggal_mulai)) : '';
                    $tSelesai = $c->tanggal_selesai ? date('d/m/Y', strtotime($c->tanggal_selesai)) : '';
                    $tglRange = ($tMulai && $tSelesai) ? "$tMulai s/d $tSelesai" : ($c->alasan ?? '-');

                    $pending[] = [
                        'id' => $c->id,
                        'jenis' => 'Pengajuan Cuti',
                        'jenis_key' => 'cuti',
                        'nik' => $c->p_nik ?? '-',
                        'nama' => $c->p_name ?? ($c->nama_pegawai ?? 'Pegawai'),
                        'jabatan' => $c->p_jabatan ?? '-',
                        'unit_kerja' => $c->p_unit ?? '-',
                        'bulan' => (int) date('n', $cTime),
                        'tahun' => (int) date('Y', $cTime),
                        'nominal' => 0,
                        'keterangan' => ($c->jenis ?? 'Cuti') . " ($tglRange)",
                        'breakdown' => [
                            'Jenis Cuti' => $c->jenis ?? 'Cuti Tahunan',
                            'Tanggal Mulai' => $tMulai,
                            'Tanggal Selesai' => $tSelesai,
                            'Alasan' => $c->alasan ?? '-',
                        ],
                        'status' => 'PENDING',
                        'status_label' => 'Menunggu Persetujuan SDM',
                        'route' => route('cuti.index', ['tahun' => (int) date('Y', $cTime)]),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Approval pengajuan_cuti query failed: ' . $e->getMessage());
            }
        }

        // Koleksi Data & Filter
        $allCollection = collect($pending);

        // Rekap Opsi Jenis yang relevan untuk role ini
        $countsByKey = $allCollection->groupBy('jenis_key')->map->count();

        if ($isSdm) {
            $masterJenis = [
                ['key' => 'cuti', 'label' => 'Pengajuan Cuti', 'count' => $countsByKey->get('cuti', 0)],
                ['key' => 'gaji', 'label' => 'Gaji Bulanan', 'count' => $countsByKey->get('gaji', 0)],
                ['key' => 'thr', 'label' => 'THR', 'count' => $countsByKey->get('thr', 0)],
                ['key' => 'gaji13', 'label' => 'Gaji 13', 'count' => $countsByKey->get('gaji13', 0)],
                ['key' => 'potongan_gaji', 'label' => 'Potongan Gaji', 'count' => $countsByKey->get('potongan_gaji', 0)],
                ['key' => 'potongan_thr', 'label' => 'Potongan THR', 'count' => $countsByKey->get('potongan_thr', 0)],
                ['key' => 'potongan_gaji13', 'label' => 'Potongan Gaji 13', 'count' => $countsByKey->get('potongan_gaji13', 0)],
            ];
        } elseif ($isDirum || $isDirut) {
            $masterJenis = [
                ['key' => 'gaji', 'label' => 'Gaji Bulanan', 'count' => $countsByKey->get('gaji', 0)],
                ['key' => 'thr', 'label' => 'THR', 'count' => $countsByKey->get('thr', 0)],
                ['key' => 'gaji13', 'label' => 'Gaji 13', 'count' => $countsByKey->get('gaji13', 0)],
                ['key' => 'potongan_gaji', 'label' => 'Potongan Gaji', 'count' => $countsByKey->get('potongan_gaji', 0)],
                ['key' => 'potongan_thr', 'label' => 'Potongan THR', 'count' => $countsByKey->get('potongan_thr', 0)],
                ['key' => 'potongan_gaji13', 'label' => 'Potongan Gaji 13', 'count' => $countsByKey->get('potongan_gaji13', 0)],
            ];
        } elseif ($isKeu) {
            $masterJenis = [
                ['key' => 'potongan_gaji', 'label' => 'Potongan Gaji', 'count' => $countsByKey->get('potongan_gaji', 0)],
                ['key' => 'potongan_thr', 'label' => 'Potongan THR', 'count' => $countsByKey->get('potongan_thr', 0)],
                ['key' => 'potongan_gaji13', 'label' => 'Potongan Gaji 13', 'count' => $countsByKey->get('potongan_gaji13', 0)],
            ];
        } else {
            $masterJenis = [
                ['key' => 'cuti', 'label' => 'Pengajuan Cuti', 'count' => $countsByKey->get('cuti', 0)],
                ['key' => 'gaji', 'label' => 'Gaji Bulanan', 'count' => $countsByKey->get('gaji', 0)],
                ['key' => 'thr', 'label' => 'THR', 'count' => $countsByKey->get('thr', 0)],
                ['key' => 'gaji13', 'label' => 'Gaji 13', 'count' => $countsByKey->get('gaji13', 0)],
                ['key' => 'potongan_gaji', 'label' => 'Potongan Gaji', 'count' => $countsByKey->get('potongan_gaji', 0)],
                ['key' => 'potongan_thr', 'label' => 'Potongan THR', 'count' => $countsByKey->get('potongan_thr', 0)],
                ['key' => 'potongan_gaji13', 'label' => 'Potongan Gaji 13', 'count' => $countsByKey->get('potongan_gaji13', 0)],
            ];
        }

        $jenisList = collect($masterJenis);

        // Rekap Opsi Tahun unik
        $tahunList = $allCollection->pluck('tahun')->filter()->unique()->sortDesc()->values();
        $currY = (int) now()->year;
        $tahunList = $tahunList->push($currY)->push($currY - 1)->push($currY - 2)->unique()->sortDesc()->values();

        // Terapkan Filter
        $filtered = $allCollection;

        $selectedJenis = $request->query('jenis', $isKeu ? 'potongan_gaji' : 'all');
        if ($isKeu && ($selectedJenis === 'all' || empty($selectedJenis))) {
            $selectedJenis = 'potongan_gaji';
        }
        $selectedBulan = $request->query('bulan', 'all');
        $selectedTahun = $request->query('tahun', 'all');
        $searchQuery   = trim((string) $request->query('q', ''));
        $sort          = strtolower(trim((string) $request->query('sort', 'terbaru')));

        if ($selectedJenis && $selectedJenis !== 'all') {
            $filtered = $filtered->filter(fn ($item) => ($item['jenis_key'] ?? '') === $selectedJenis || ($item['jenis'] ?? '') === $selectedJenis);
        }

        if ($selectedBulan && $selectedBulan !== 'all') {
            $filtered = $filtered->filter(fn ($item) => (int) ($item['bulan'] ?? 0) === (int) $selectedBulan);
        }

        if ($selectedTahun && $selectedTahun !== 'all') {
            $filtered = $filtered->filter(fn ($item) => (int) ($item['tahun'] ?? 0) === (int) $selectedTahun);
        }

        if ($searchQuery !== '') {
            $qLower = strtolower($searchQuery);
            $filtered = $filtered->filter(function ($item) use ($qLower) {
                return str_contains(strtolower($item['nik'] ?? ''), $qLower)
                    || str_contains(strtolower($item['nama'] ?? ''), $qLower)
                    || str_contains(strtolower($item['jenis'] ?? ''), $qLower);
            });
        }

        // Pengurutan (Ascending / Descending)
        if ($sort === 'terlama') {
            $filtered = $filtered->sortBy(function ($item) {
                return ($item['tgl_potongan'] ?? ($item['tahun'] ?? '')) . '_' . str_pad((string)($item['id'] ?? 0), 10, '0', STR_PAD_LEFT);
            })->values();
        } elseif ($sort === 'nik_asc') {
            $filtered = $filtered->sortBy('nik')->values();
        } elseif ($sort === 'nik_desc') {
            $filtered = $filtered->sortByDesc('nik')->values();
        } elseif ($sort === 'nama_asc') {
            $filtered = $filtered->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
        } elseif ($sort === 'nama_desc') {
            $filtered = $filtered->sortByDesc('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
        } else {
            // Default terbaru: Prioritaskan berkas yang butuh persetujuan (can_approve = true) di paling atas,
            // lalu urutkan berdasarkan tanggal & ID terbaru
            $filtered = $filtered->sort(function ($a, $b) {
                $aCanApprove = !empty($a['can_approve']) ? 1 : 0;
                $bCanApprove = !empty($b['can_approve']) ? 1 : 0;
                if ($aCanApprove !== $bCanApprove) {
                    return $bCanApprove <=> $aCanApprove;
                }

                $aTime = !empty($a['tgl_potongan']) ? $a['tgl_potongan'] : (sprintf('%04d-%02d-01', $a['tahun'] ?? now()->year, $a['bulan'] ?? 1));
                $bTime = !empty($b['tgl_potongan']) ? $b['tgl_potongan'] : (sprintf('%04d-%02d-01', $b['tahun'] ?? now()->year, $b['bulan'] ?? 1));

                $aKey = $aTime . '_' . str_pad((string)($a['id'] ?? 0), 10, '0', STR_PAD_LEFT);
                $bKey = $bTime . '_' . str_pad((string)($b['id'] ?? 0), 10, '0', STR_PAD_LEFT);

                return strcmp($bKey, $aKey);
            })->values();
        }

        // Pagination 20 Data per Halaman
        $perPage = 20;
        $currentPage = (int) $request->query('page', 1);
        if ($currentPage < 1) $currentPage = 1;

        $totalItems = $filtered->count();
        $pagedItems = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $pagedItems,
            $totalItems,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $totalPending = $allCollection->where('can_approve', true)->count();
        $totalFinal   = $allCollection->filter(fn($it) => in_array($it['status'] ?? '', ['Y', 'terbit', 'disetujui', 'approved'], true))->count();
        $totalPendingInFilter = $filtered->where('can_approve', true)->count();

        return view('approval.index', [
            'paginator'            => $paginator,
            'items'                => $pagedItems,
            'totalAll'             => $allCollection->count(),
            'totalPending'         => $totalPending,
            'totalFinal'           => $totalFinal,
            'totalPendingInFilter' => $totalPendingInFilter,
            'filteredCount'        => $totalItems,
            'jenisList'            => $jenisList,
            'tahunList'            => $tahunList,
            'selectedJenis'        => $selectedJenis,
            'selectedBulan'        => $selectedBulan,
            'selectedTahun'        => $selectedTahun,
            'selectedStatus'       => $selectedStatus,
            'sort'                 => $sort,
            'searchQuery'          => $searchQuery,
        ]);
    }

    /**
     * Setujui satu item approval langsung dari halaman Approval (AJAX / Form).
     */
    public function approveSingle(Request $request)
    {
        $id = $request->input('id');
        $jenisKey = (string) $request->input('jenis_key');

        $userNik   = (string) session('simpeg_user.nik', '');
        $userLevel = (int) session('simpeg_user.userlevel', 0);
        $userRole  = (int) session('simpeg_role', 0);
        $sdmNik    = (string) config('simpeg_approval.kepegawaian', '1711254');
        $dirumNik  = (string) config('simpeg_approval.dirum', '1711002');
        $dirutNik  = (string) config('simpeg_approval.dirut', '1711001');

        $isSdm   = ($userLevel === 1 || $userRole === 1 || $userNik === $sdmNik);
        $isDirum = ($userNik === $dirumNik);
        $isDirut = ($userNik === $dirutNik);
        $isKeu   = ($userLevel === 2 || $userRole === 2 || $userNik === (string) config('simpeg_approval.keuangan', '1711296'));

        try {
            if (str_starts_with($jenisKey, 'potongan_')) {
                $row = DB::table('potongan_keu')->where('id', $id)->first();
                if (!$row) abort(404, 'Data potongan tidak ditemukan.');

                if ($isSdm && in_array($row->status, ['N', 'draft', null], true)) {
                    // Tahap 1: Kepegawaian / SDM menyetujui
                    DB::table('potongan_keu')->where('id', $id)->update([
                        'status' => 'kepegawaian',
                        'disetujui_kepegawaian_oleh' => session('simpeg_user.nama_peg', 'SDM') . ' (' . $userNik . ')',
                        'tgl_setuju_kepegawaian' => now(),
                        'updated_at' => now(),
                    ]);
                    $msg = 'Potongan pegawai berhasil diverifikasi & disetujui oleh Bagian SDM/Kepegawaian. Lanjut ke Direktur Umum.';
                } elseif ($isDirum && $row->status === 'kepegawaian') {
                    // Tahap 2: Direktur Umum menyetujui
                    DB::table('potongan_keu')->where('id', $id)->update([
                        'status' => 'dirum',
                        'updated_at' => now(),
                    ]);
                    $msg = 'Potongan pegawai berhasil disetujui oleh Direktur Umum. Lanjut ke Direktur Utama (Final).';
                } elseif ($isDirut && $row->status === 'dirum') {
                    // Tahap 3: Direktur Utama mengesahkan Final (Terbit 'Y') & sinkron ke Payroll
                    DB::table('potongan_keu')->where('id', $id)->update([
                        'status' => 'Y',
                        'disetujui_oleh' => session('simpeg_user.nama_peg', 'Direktur Utama') . ' (' . $userNik . ')',
                        'updated_at' => now(),
                    ]);
                    $updatedRow = DB::table('potongan_keu')->where('id', $id)->first();
                    if ($updatedRow) {
                        app(PotonganKeuController::class)->syncToPayroll($updatedRow);
                    }
                    $msg = 'Potongan pegawai berhasil disetujui final oleh Direktur Utama dan resmi mengalir ke laporan payroll!';
                } else {
                    abort(403, 'Anda tidak berhak menyetujui potongan ini pada tahap status saat ini (' . ($row->status ?? 'draft') . ').');
                }
            } elseif ($jenisKey === 'cuti') {
                if (!$isSdm) abort(403, 'Hanya SDM yang berhak menyetujui pengajuan cuti.');
                DB::table('pengajuan_cuti')->where('id', $id)->update([
                    'status' => 'DISETUJUI',
                    'updated_at' => now(),
                ]);
                $msg = 'Pengajuan cuti pegawai berhasil disetujui.';
            } elseif ($jenisKey === 'gaji') {
                app(GajiProsesController::class)->terbitkanDirect((int) $id);
                $msg = 'Gaji bulanan pegawai berhasil disetujui.';
            } elseif ($jenisKey === 'thr') {
                app(ThrController::class)->terbitkanDirect((int) $id);
                $msg = 'THR pegawai berhasil disetujui.';
            } elseif ($jenisKey === 'gaji13') {
                app(GajiTigabelasController::class)->terbitkanDirect((int) $id);
                $msg = 'Gaji 13 pegawai berhasil disetujui.';
            } else {
                return response()->json(['success' => false, 'message' => 'Jenis approval tidak valid.'], 400);
            }

            AuditLogController::record(
                'APPROVE',
                'Approval',
                "Menyetujui {$jenisKey} ID #{$id} - {$msg}",
                $userNik,
                session('simpeg_user.nama_peg'),
                match ($userLevel) {
                    1 => 'SDM',
                    2 => 'Keuangan',
                    7 => 'Direktur (DIRUT)',
                    default => 'User Web',
                }
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('Approval approveSingle error: ' . $e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyetujui: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal menyetujui: ' . $e->getMessage());
        }
    }

    /**
     * Setujui banyak item sekaligus atau SEMUA item yang sedang menunggu approval.
     */
    public function approveBulk(Request $request)
    {
        $items = $request->input('items', []); // [ ['id' => 1, 'jenis_key' => 'potongan_gaji'], ... ]
        $mode = $request->input('mode', 'selected'); // 'selected' or 'all_filtered'
        $filterJenis = $request->input('filter_jenis', 'all');
        $filterBulan = $request->input('filter_bulan', 'all');
        $filterTahun = $request->input('filter_tahun', 'all');

        $userNik   = (string) session('simpeg_user.nik', '');
        $userLevel = (int) session('simpeg_user.userlevel', 0);
        $userRole  = (int) session('simpeg_role', 0);
        $sdmNik    = (string) config('simpeg_approval.kepegawaian', '1711254');
        $dirumNik  = (string) config('simpeg_approval.dirum', '1711002');
        $dirutNik  = (string) config('simpeg_approval.dirut', '1711001');

        $isSdm   = ($userLevel === 1 || $userRole === 1 || $userNik === $sdmNik);
        $isDirum = ($userNik === $dirumNik);
        $isDirut = ($userNik === $dirutNik);
        $isKeu   = ($userLevel === 2 || $userRole === 2 || $userNik === (string) config('simpeg_approval.keuangan', '1711296'));

        try {
            $approvedCount = 0;

            if ($mode === 'all_filtered') {
                // 1. Potongan Keuangan
                if (in_array($filterJenis, ['all', 'potongan_gaji', 'potongan_thr', 'potongan_gaji13'], true)) {
                    $potQuery = DB::table('potongan_keu');
                    if ($filterJenis !== 'all') {
                        $potQuery->where('tipe', str_replace('potongan_', '', $filterJenis));
                    }
                    if ($filterBulan !== 'all') {
                        $potQuery->where('bulan', (int) $filterBulan);
                    }
                    if ($filterTahun !== 'all') {
                        $potQuery->where('tahun', (int) $filterTahun);
                    }

                    if ($isSdm) {
                        // Tahap 1: SDM menyetujui semua draft potongan
                        $potQuery->where(function ($q) {
                            $q->whereIn('status', ['N', 'draft'])->orWhereNull('status');
                        });
                        $affected = $potQuery->update([
                            'status' => 'kepegawaian',
                            'disetujui_kepegawaian_oleh' => session('simpeg_user.nama_peg', 'SDM') . ' (' . $userNik . ')',
                            'tgl_setuju_kepegawaian' => now(),
                            'updated_at' => now(),
                        ]);
                        $approvedCount += $affected;
                    } elseif ($isDirum) {
                        // Tahap 2: Dirum menyetujui semua potongan yang sudah lolos SDM
                        $potQuery->where('status', 'kepegawaian');
                        $affected = $potQuery->update([
                            'status' => 'dirum',
                            'updated_at' => now(),
                        ]);
                        $approvedCount += $affected;
                    } elseif ($isDirut) {
                        // Tahap 3: Dirut menyetujui final & sinkronisasi ke payroll
                        $potQuery->where('status', 'dirum');
                        $rowsToSync = $potQuery->get();
                        $affected = $potQuery->update([
                            'status' => 'Y',
                            'disetujui_oleh' => session('simpeg_user.nama_peg', 'Direktur Utama') . ' (' . $userNik . ')',
                            'updated_at' => now(),
                        ]);
                        foreach ($rowsToSync as $r) {
                            app(PotonganKeuController::class)->syncToPayroll($r);
                        }
                        $approvedCount += $affected;
                    }
                }

                // 2. Pengajuan Cuti (SDM)
                if ($isSdm && in_array($filterJenis, ['all', 'cuti'], true)) {
                    $cutiQuery = DB::table('pengajuan_cuti')->whereRaw("LOWER(status) = 'pending'");
                    if ($filterTahun !== 'all') {
                        $cutiQuery->whereYear('created_at', (int) $filterTahun);
                    }
                    $affected = $cutiQuery->update([
                        'status' => 'DISETUJUI',
                        'updated_at' => now(),
                    ]);
                    $approvedCount += $affected;
                }

                // 3. Gaji, THR, Gaji 13
                if (in_array($filterJenis, ['all', 'gaji'], true)) {
                    $gajiProses = app(GajiProsesController::class)->all();
                    foreach ($gajiProses as $g) {
                        if ($this->nextStageFor($g['status'] ?? '') === 'kepegawaian' && $isSdm) {
                            app(GajiProsesController::class)->terbitkanDirect($g['id']);
                            $approvedCount++;
                        }
                    }
                }
                if (in_array($filterJenis, ['all', 'thr'], true)) {
                    $thrItems = app(ThrController::class)->all();
                    foreach ($thrItems as $t) {
                        if ($this->nextStageFor($t['status'] ?? '') === 'kepegawaian' && $isSdm) {
                            app(ThrController::class)->terbitkanDirect($t['id']);
                            $approvedCount++;
                        }
                    }
                }
                if (in_array($filterJenis, ['all', 'gaji13'], true)) {
                    $g13Items = app(GajiTigabelasController::class)->all();
                    foreach ($g13Items as $g) {
                        if ($this->nextStageFor($g['status'] ?? '') === 'kepegawaian' && $isSdm) {
                            app(GajiTigabelasController::class)->terbitkanDirect($g['id']);
                            $approvedCount++;
                        }
                    }
                }
            } else {
                // Setujui berdasarkan daftar items yang dicentang
                foreach ($items as $item) {
                    $itemId = $item['id'] ?? null;
                    $itemJenis = $item['jenis_key'] ?? '';
                    if (! $itemId) continue;

                    if (str_starts_with($itemJenis, 'potongan_')) {
                        $pRow = DB::table('potongan_keu')->where('id', $itemId)->first();
                        if ($pRow) {
                            if ($isSdm && in_array($pRow->status, ['N', 'draft', null], true)) {
                                DB::table('potongan_keu')->where('id', $itemId)->update([
                                    'status' => 'kepegawaian',
                                    'disetujui_kepegawaian_oleh' => session('simpeg_user.nama_peg', 'SDM') . ' (' . $userNik . ')',
                                    'tgl_setuju_kepegawaian' => now(),
                                    'updated_at' => now(),
                                ]);
                                $approvedCount++;
                            } elseif ($isDirum && $pRow->status === 'kepegawaian') {
                                DB::table('potongan_keu')->where('id', $itemId)->update([
                                    'status' => 'dirum',
                                    'updated_at' => now(),
                                ]);
                                $approvedCount++;
                            } elseif ($isDirut && $pRow->status === 'dirum') {
                                DB::table('potongan_keu')->where('id', $itemId)->update([
                                    'status' => 'Y',
                                    'disetujui_oleh' => session('simpeg_user.nama_peg', 'Direktur Utama') . ' (' . $userNik . ')',
                                    'updated_at' => now(),
                                ]);
                                $updatedRow = DB::table('potongan_keu')->where('id', $itemId)->first();
                                if ($updatedRow) {
                                    app(PotonganKeuController::class)->syncToPayroll($updatedRow);
                                }
                                $approvedCount++;
                            }
                        }
                    } elseif ($itemJenis === 'cuti' && $isSdm) {
                        DB::table('pengajuan_cuti')->where('id', $itemId)->update([
                            'status' => 'DISETUJUI',
                            'updated_at' => now(),
                        ]);
                        $approvedCount++;
                    } elseif ($itemJenis === 'gaji') {
                        app(GajiProsesController::class)->terbitkanDirect((int) $itemId);
                        $approvedCount++;
                    } elseif ($itemJenis === 'thr') {
                        app(ThrController::class)->terbitkanDirect((int) $itemId);
                        $approvedCount++;
                    } elseif ($itemJenis === 'gaji13') {
                        app(GajiTigabelasController::class)->terbitkanDirect((int) $itemId);
                        $approvedCount++;
                    }
                }
            }

            $msg = "Sebanyak {$approvedCount} item approval berhasil disetujui.";

            AuditLogController::record(
                'APPROVE',
                'Approval',
                "Menyetujui massal {$approvedCount} item ({$filterJenis})",
                $userNik,
                session('simpeg_user.nama_peg'),
                match ($userLevel) {
                    1 => 'SDM',
                    2 => 'Keuangan',
                    7 => 'Direktur (DIRUT)',
                    default => 'User Web',
                }
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg, 'approved_count' => $approvedCount]);
            }
            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('Approval approveBulk error: ' . $e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyetujui massal: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Gagal menyetujui massal: ' . $e->getMessage());
        }
    }
}

