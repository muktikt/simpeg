<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotifikasiController extends Controller
{
    /**
     * Helper untuk mendapatkan UUID pegawai dari session pengguna aktif.
     */
    protected function currentPegawaiId(): ?string
    {
        $id = session('simpeg_user.id');
        if (!empty($id)) {
            return (string) $id;
        }

        $nik = session('simpeg_user.nik');
        if (!empty($nik)) {
            $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
            if ($pegawai) {
                return (string) $pegawai->id;
            }
        }

        return null;
    }

    /**
     * Dapatkan target link dan metadata icon berdasarkan konten notifikasi.
     */
    public static function resolveMeta(object|array $item): array
    {
        $judul = is_array($item) ? ($item['judul'] ?? '') : ($item->judul ?? '');
        $pesan = is_array($item) ? ($item['pesan'] ?? '') : ($item->pesan ?? '');
        $pengaduanId = is_array($item) ? ($item['pengaduan_id'] ?? null) : ($item->pengaduan_id ?? null);
        $pengumumanId = is_array($item) ? ($item['pengumuman_id'] ?? null) : ($item->pengumuman_id ?? null);

        $text = mb_strtolower($judul . ' ' . $pesan);

        $url = route('dashboard');
        $type = 'general';
        $badgeBg = '#F1F5F9';
        $badgeColor = '#475569';
        $icon = 'bell';

        if (str_contains($text, 'cuti')) {
            $url = route('cuti.index');
            $type = 'cuti';
            if (str_contains($text, 'setuju') || str_contains($text, 'disetujui')) {
                $badgeBg = '#DCFCE7';
                $badgeColor = '#15803D';
                $icon = 'check-circle';
            } elseif (str_contains($text, 'tolak') || str_contains($text, 'ditolak')) {
                $badgeBg = '#FEE2E2';
                $badgeColor = '#B91C1C';
                $icon = 'x-circle';
            } else {
                $badgeBg = '#FEF3C7';
                $badgeColor = '#B45309';
                $icon = 'clock';
            }
        } elseif (str_contains($text, 'gaji') || str_contains($text, 'thr') || str_contains($text, 'insentif') || str_contains($text, 'tukin') || str_contains($text, 'lembur')) {
            $url = route('profile.show');
            $type = 'payroll';
            $badgeBg = '#E0F2FE';
            $badgeColor = '#0284C7';
            $icon = 'credit-card';
        } elseif (!empty($pengaduanId) || str_contains($text, 'pengaduan') || str_contains($text, 'investigasi')) {
            $url = route('pengaduan.index');
            $type = 'pengaduan';
            $badgeBg = '#FFEDD5';
            $badgeColor = '#C2410C';
            $icon = 'alert-triangle';
        } elseif (!empty($pengumumanId) || str_contains($text, 'pengumuman')) {
            $url = route('pengumuman.index');
            $type = 'pengumuman';
            $badgeBg = '#EDE9FE';
            $badgeColor = '#6D28D9';
            $icon = 'volume-2';
        } elseif (str_contains($text, 'sanksi') || str_contains($text, 'disiplin')) {
            $url = route('sanksi.laporan');
            $type = 'sanksi';
            $badgeBg = '#FFE4E6';
            $badgeColor = '#BE123C';
            $icon = 'shield-alert';
        }

        return [
            'url'         => $url,
            'type'        => $type,
            'badge_bg'    => $badgeBg,
            'badge_color' => $badgeColor,
            'icon'        => $icon,
        ];
    }

    /**
     * API / AJAX: Menghitung total belum dibaca dan mengembalikan daftar notifikasi terbaru.
     */
    public function unreadCount(Request $request)
    {
        $pegawaiId = $this->currentPegawaiId();
        if (!$pegawaiId) {
            return response()->json([
                'success' => false,
                'count'   => 0,
                'items'   => [],
            ]);
        }

        try {
            $unreadCount = DB::table('notifikasi')
                ->where('untuk_pegawai_id', $pegawaiId)
                ->where('dibaca', false)
                ->count();

            $items = DB::table('notifikasi')
                ->where('untuk_pegawai_id', $pegawaiId)
                ->orderByDesc('waktu')
                ->orderByDesc('id')
                ->limit(10)
                ->get()
                ->map(function ($n) {
                    $waktuCarbon = $n->waktu ? Carbon::parse($n->waktu) : Carbon::parse($n->created_at);
                    $meta = self::resolveMeta($n);

                    return [
                        'id'            => $n->id,
                        'judul'         => $n->judul,
                        'pesan'         => $n->pesan,
                        'dibaca'        => (bool) $n->dibaca,
                        'waktu_raw'     => $waktuCarbon->toISOString(),
                        'waktu_tgl'     => $waktuCarbon->translatedFormat('d M Y, H:i'),
                        'waktu_relatif' => $waktuCarbon->diffForHumans(),
                        'url'           => $meta['url'],
                        'type'          => $meta['type'],
                        'badge_bg'      => $meta['badge_bg'],
                        'badge_color'   => $meta['badge_color'],
                        'icon'          => $meta['icon'],
                    ];
                });

            return response()->json([
                'success' => true,
                'count'   => $unreadCount,
                'items'   => $items,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'count'   => 0,
                'items'   => [],
            ], 500);
        }
    }

    /**
     * Halaman Web: Pusat Notifikasi Pegawai & Pejabat (/notifikasi).
     */
    public function index(Request $request)
    {
        $pegawaiId = $this->currentPegawaiId();
        $filterStatus = $request->get('status', 'all'); // 'all', 'unread', 'read'
        $search = trim((string) $request->get('search', ''));

        if (!$pegawaiId) {
            return redirect()->route('dashboard')->with('error', 'Data pengguna tidak valid.');
        }

        $query = DB::table('notifikasi')
            ->where('untuk_pegawai_id', $pegawaiId);

        if ($filterStatus === 'unread') {
            $query->where('dibaca', false);
        } elseif ($filterStatus === 'read') {
            $query->where('dibaca', true);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'ilike', "%{$search}%")
                  ->orWhere('pesan', 'ilike', "%{$search}%");
            });
        }

        $notifikasi = $query->orderByDesc('waktu')->orderByDesc('id')->paginate(15)->withQueryString();

        $unreadTotal = DB::table('notifikasi')
            ->where('untuk_pegawai_id', $pegawaiId)
            ->where('dibaca', false)
            ->count();

        $totalCount = DB::table('notifikasi')
            ->where('untuk_pegawai_id', $pegawaiId)
            ->count();

        return view('notifikasi.index', compact(
            'notifikasi',
            'filterStatus',
            'search',
            'unreadTotal',
            'totalCount'
        ));
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, $id)
    {
        $pegawaiId = $this->currentPegawaiId();
        if (!$pegawaiId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            DB::table('notifikasi')
                ->where('id', $id)
                ->where('untuk_pegawai_id', $pegawaiId)
                ->update(['dibaca' => true]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true]);
            }

            return back()->with('success', 'Notifikasi telah ditandai sebagai dibaca.');
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Gagal memperbarui status notifikasi.']);
        }
    }

    /**
     * Tandai SEMUA notifikasi pengguna sebagai sudah dibaca.
     */
    public function markAllAsRead(Request $request)
    {
        $pegawaiId = $this->currentPegawaiId();
        if (!$pegawaiId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            DB::table('notifikasi')
                ->where('untuk_pegawai_id', $pegawaiId)
                ->where('dibaca', false)
                ->update(['dibaca' => true]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true]);
            }

            return back()->with('success', 'Semua notifikasi telah berhasil ditandai sebagai dibaca.');
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Gagal menandai semua notifikasi: ' . $e->getMessage()]);
        }
    }
}

