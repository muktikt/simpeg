<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogController extends Controller
{
    /**
     * Helper statis untuk mencatat aktivitas pengguna ke tabel activity_logs.
     */
    public static function record(string $action, string $module, string $description, ?string $nik = null, ?string $name = null, ?string $role = null, ?string $device = null): void
    {
        try {
            $user = session('simpeg_user') ?? session('user');
            $userNik = $nik ?? ($user['nik'] ?? 'SYSTEM');
            $userName = $name ?? ($user['nama'] ?? ($user['name'] ?? 'Sistem SIMPEG'));

            $userRole = $role ?? match ((string)($user['userlevel'] ?? ($user['role'] ?? ''))) {
                '1' => 'SDM',
                '2' => 'Keuangan',
                '5' => 'Pegawai',
                '7' => 'Direktur (DIRUT)',
                default => 'User Web',
            };

            $rawDevice = $device ?? (request()->header('X-Device-Info') ?? request()->header('User-Agent') ?? 'Web Browser');
            $deviceInfo = static::cleanDeviceInfo($rawDevice);

            DB::table('activity_logs')->insert([
                'id'          => (string) Str::uuid(),
                'user_nik'    => $userNik,
                'user_name'   => $userName,
                'role'        => $userRole,
                'action'      => strtoupper($action),
                'module'      => $module,
                'description' => $description,
                'device_info' => $deviceInfo,
                'created_at'  => now('Asia/Jakarta'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed recording activity log: ' . $e->getMessage());
        }
    }

    /**
     * Memformat informasi user-agent mentah menjadi nama perangkat yang mudah dibaca.
     */
    public static function cleanDeviceInfo(?string $raw): string
    {
        if (empty($raw)) {
            return 'Perangkat Tidak Terdeteksi';
        }

        // Jika sudah berbentuk nama rapi (misal dari Flutter Mobile: "POCO ...", "Samsung ...")
        if (!str_contains($raw, 'Mozilla/') && !str_contains($raw, 'AppleWebKit/')) {
            return substr($raw, 0, 100);
        }

        $os = 'Lainnya';
        if (str_contains($raw, 'Windows NT 10.0')) $os = 'Windows 10/11';
        elseif (str_contains($raw, 'Windows NT 6.')) $os = 'Windows';
        elseif (str_contains($raw, 'Android')) $os = 'Android';
        elseif (str_contains($raw, 'iPhone')) $os = 'iPhone (iOS)';
        elseif (str_contains($raw, 'iPad')) $os = 'iPad (iPadOS)';
        elseif (str_contains($raw, 'Macintosh')) $os = 'macOS';
        elseif (str_contains($raw, 'Linux')) $os = 'Linux';

        $browser = 'Browser';
        if (str_contains($raw, 'Edg/')) $browser = 'Edge';
        elseif (str_contains($raw, 'Chrome/')) $browser = 'Chrome';
        elseif (str_contains($raw, 'Firefox/')) $browser = 'Firefox';
        elseif (str_contains($raw, 'Safari/')) $browser = 'Safari';

        return "{$browser} ({$os})";
    }

    /**
     * Menampilkan daftar Audit Trail / Log Aktivitas Pengguna dengan filter & pagination.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $action = $request->get('action', 'all');
        $module = $request->get('module', 'all');
        $role = $request->get('role', 'all');
        $dateStart = $request->get('date_start');
        $dateEnd = $request->get('date_end');

        $query = DB::table('activity_logs');

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('user_name', 'ilike', "%{$q}%")
                    ->orWhere('user_nik', 'ilike', "%{$q}%")
                    ->orWhere('description', 'ilike', "%{$q}%")
                    ->orWhere('device_info', 'ilike', "%{$q}%");
            });
        }

        if ($action !== 'all' && !empty($action)) {
            $query->where('action', strtoupper($action));
        }

        if ($module !== 'all' && !empty($module)) {
            $query->where('module', $module);
        }

        if ($role !== 'all' && !empty($role)) {
            $query->where('role', $role);
        }

        if (!empty($dateStart)) {
            $query->whereDate('created_at', '>=', $dateStart);
        }

        if (!empty($dateEnd)) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        // Summary KPI Counts (sebelum pagination)
        $totalLogs = (clone $query)->count();
        $totalLogin = (clone $query)->where('action', 'LOGIN')->count();
        $totalModif = (clone $query)->whereIn('action', ['CREATE', 'UPDATE'])->count();
        $totalDelete = (clone $query)->where('action', 'DELETE')->count();

        // Top 3 Active Users
        $topUsers = (clone $query)
            ->whereNotNull('user_name')
            ->select('user_name', 'role', DB::raw('count(*) as total'))
            ->groupBy('user_name', 'role')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // Ambil data dengan pagination
        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        // Opsi Dropdown Filter
        $actionsList = DB::table('activity_logs')->whereNotNull('action')->distinct()->orderBy('action')->pluck('action');
        $modulesList = DB::table('activity_logs')->whereNotNull('module')->distinct()->orderBy('module')->pluck('module');
        $rolesList   = DB::table('activity_logs')->whereNotNull('role')->distinct()->orderBy('role')->pluck('role');

        return view('audit-log.index', compact(
            'logs',
            'q',
            'action',
            'module',
            'role',
            'dateStart',
            'dateEnd',
            'totalLogs',
            'totalLogin',
            'totalModif',
            'totalDelete',
            'topUsers',
            'actionsList',
            'modulesList',
            'rolesList'
        ));
    }

    /**
     * Cetak Fisik Kedinasan Laporan Audit Trail (Format Landscape A4).
     */
    public function cetak(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $action = $request->get('action', 'all');
        $module = $request->get('module', 'all');
        $role = $request->get('role', 'all');
        $dateStart = $request->get('date_start');
        $dateEnd = $request->get('date_end');

        $query = DB::table('activity_logs');

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('user_name', 'ilike', "%{$q}%")
                    ->orWhere('user_nik', 'ilike', "%{$q}%")
                    ->orWhere('description', 'ilike', "%{$q}%");
            });
        }
        if ($action !== 'all' && !empty($action)) {
            $query->where('action', strtoupper($action));
        }
        if ($module !== 'all' && !empty($module)) {
            $query->where('module', $module);
        }
        if ($role !== 'all' && !empty($role)) {
            $query->where('role', $role);
        }
        if (!empty($dateStart)) {
            $query->whereDate('created_at', '>=', $dateStart);
        }
        if (!empty($dateEnd)) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        $logs = $query->orderByDesc('created_at')->limit(500)->get();
        $pejabatTtd = PejabatTtdController::getTtd('gaji');

        return view('audit-log.cetak', compact(
            'logs',
            'q',
            'action',
            'module',
            'role',
            'dateStart',
            'dateEnd',
            'pejabatTtd'
        ));
    }
}

