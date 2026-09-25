<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
 
class ProfileController extends Controller
{
    /**
     * Jenis data riwayat yang dipakai khusus di dalam CV (Diklat & Sertifikasi).
     * Polanya sama seperti PegawaiDetailController::TYPES, tapi di-scope
     * sendiri di sini karena diinput mandiri oleh pegawai lewat halaman Profil,
     * bukan oleh Admin SDM lewat halaman Data Pegawai.
     */
    public const CV_DETAIL_TYPES = ['diklat', 'sertifikasi'];
 
    public static function cvDetailFieldConfig(string $type): array
    {
        return match ($type) {
            'diklat' => [
                'title' => 'Diklat / Pelatihan',
                'fields' => [
                    ['key' => 'nama', 'label' => 'Nama Diklat/Pelatihan', 'type' => 'text'],
                    ['key' => 'penyelenggara', 'label' => 'Penyelenggara', 'type' => 'text'],
                    ['key' => 'tahun', 'label' => 'Tahun', 'type' => 'text'],
                ],
            ],
            'sertifikasi' => [
                'title' => 'Sertifikasi',
                'fields' => [
                    ['key' => 'nama', 'label' => 'Nama Sertifikat', 'type' => 'text'],
                    ['key' => 'penyelenggara', 'label' => 'Penyelenggara', 'type' => 'text'],
                    ['key' => 'tahun', 'label' => 'Tahun', 'type' => 'text'],
                ],
            ],
            default => abort(404),
        };
    }
 
    /**
     * CATATAN: profile.php di sistem lama ternyata masih template demo
     * AdminLTE yang belum pernah diisi data asli (isinya "Nina Mcintire,
     * Software Engineer", followers palsu, teks Lorem ipsum) - tidak
     * pernah dihubungkan ke data pegawai yang login sama sekali.
     *
     * Jadi di sini dibuat ulang jadi halaman yang beneran fungsional:
     * menampilkan data diri pegawai yang sedang login (dari session) +
     * form ganti password, mengikuti pola perubahan_kata_sandi.php yang
     * asli (Current Password wajib cocok dulu sebelum bisa diganti).
     */
    public function show()
    {
        $userLogin = session('simpeg_user');
        $allPegawai = app(PegawaiController::class)->all();
        $pegawai = collect($allPegawai)->firstWhere('nik', $userLogin['nik']);

        if (! $pegawai) {
            $pegawai = [
                'id' => 999,
                'nik' => $userLogin['nik'],
                'nama' => $userLogin['nama_peg'],
                'jabatan' => $userLogin['jabatan'],
                'unit_kerja' => 'Unit Kerja',
                'status_peg' => 'PT',
                'tgl_masuk' => date('Y-m-d'),
                'telp' => '-',
                'alamat' => '-',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ];
        } else {
            // Muat relasi lengkap dari database (keluarga, golongan, jabatan, dll)
            $detailData = app(PegawaiController::class)->find($pegawai['id']);
            if ($detailData) {
                $pegawai = array_merge($pegawai, $detailData);
            }
        }

        // Pastikan keluarga selalu terisi langsung dari database DB
        if (!empty($pegawai['db_id'])) {
            try {
                $pegawai['keluarga'] = \Illuminate\Support\Facades\DB::table('keluarga')
                    ->where('pegawai_id', $pegawai['db_id'])
                    ->get()
                    ->map(function ($r) {
                        $arr = (array) $r;
                        $arr['tgl_lahir'] = $arr['tgl_lahir'] ?? $arr['tanggal_lahir'] ?? '-';
                        $arr['tanggal_lahir'] = $arr['tanggal_lahir'] ?? $arr['tgl_lahir'] ?? '-';
                        $arr['keterangan'] = $arr['keterangan'] ?? $arr['pekerjaan'] ?? '-';
                        $arr['pekerjaan'] = $arr['pekerjaan'] ?? $arr['keterangan'] ?? '-';
                        return $arr;
                    })
                    ->toArray();
            } catch (\Throwable $e) {}

            try {
                $pegawai['prestasi'] = \Illuminate\Support\Facades\DB::table('prestasi')
                    ->where('pegawai_id', $pegawai['db_id'])
                    ->orderByDesc('id')
                    ->get()
                    ->map(function ($r) {
                        $arr = (array) $r;
                        if (!empty($arr['keterangan']) && str_starts_with(trim($arr['keterangan']), '{')) {
                            $dec = json_decode($arr['keterangan'], true);
                            if (is_array($dec) && !empty($dec['desc'])) {
                                $arr['keterangan'] = $dec['desc'];
                            }
                        }
                        return $arr;
                    })
                    ->toArray();
            } catch (\Throwable $e) {}
        }

        $detailTypes = [];
        foreach (PegawaiDetailController::TYPES as $type) {
            $detailTypes[$type] = PegawaiDetailController::fieldConfig($type);
        }
 
        // Ambil data dokumen resmi (Surat Kerja & Surat Diklat) yang beneran
        // diunggah Admin SDM lewat menu Dokumen Surat (tabel dokumen_pegawai).
        $pegawai = $this->attachDokumenResmi($pegawai);
 
        $pegawai['surat_kerja'] = $pegawai['surat_kerja'] ?? null;
        $pegawai['surat_diklat'] = $pegawai['surat_diklat'] ?? null;
 
        $cv = $this->getPegawaiCv($pegawai['db_id'] ?? $pegawai['nik']);
        $pegawai['biodata'] = $cv['biodata'] ?? [];
        $pegawai['diklat'] = $cv['diklat'] ?? [];
        $pegawai['sertifikasi'] = $cv['sertifikasi'] ?? [];
        $pegawai['kompetensi'] = $cv['kompetensi'] ?? [];
 
        $cvDetailTypes = [];
        foreach (self::CV_DETAIL_TYPES as $type) {
            $cvDetailTypes[$type] = self::cvDetailFieldConfig($type);
        }
 
        return view('profile.show', compact('userLogin', 'pegawai', 'detailTypes', 'cvDetailTypes'));
    }
 
    protected function currentPegawai(): array
    {
        $userLogin = session('simpeg_user');
        $allPegawai = app(PegawaiController::class)->all();
        $pegawai = collect($allPegawai)->firstWhere('nik', $userLogin['nik'] ?? '');
 
        if (! $pegawai) {
            abort(404, 'Data pegawai tidak ditemukan.');
        }
 
        return $pegawai;
    }

    protected function cvStoragePath(mixed $key): string
    {
        return storage_path("app/cv_pegawai_{$key}.json");
    }

    protected function getPegawaiCv(mixed $key): array
    {
        $path = $this->cvStoragePath($key);
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) return $data;
        }
        return ['biodata' => [], 'kompetensi' => [], 'diklat' => [], 'sertifikasi' => []];
    }

    protected function savePegawaiCv(mixed $key, array $cv): void
    {
        $path = $this->cvStoragePath($key);
        @file_put_contents($path, json_encode($cv, JSON_PRETTY_PRINT));
    }
 
    /**
     * Ambil data dokumen resmi (Surat Kerja & Surat Diklat) milik pegawai
     * dari tabel dokumen_pegawai (yang diisi Admin SDM lewat menu
     * Dokumen Surat), lalu tempelkan ke array $pegawai supaya bisa dipakai
     * di halaman Profile Saya (view file & download beneran berfungsi).
     */
    protected function normalizeFileUrl(?string $url): string
    {
        if (empty($url) || $url === '#') {
            return '#';
        }
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        if (! empty($path) && str_starts_with($path, '/uploads/')) {
            return asset(ltrim($path, '/'));
        }
        return $url;
    }

    /**
     * Ambil data dokumen resmi (Surat Kerja & Surat Diklat) milik pegawai
     * dari tabel dokumen_pegawai (yang diisi Admin SDM lewat menu
     * Dokumen Surat), lalu tempelkan ke array $pegawai supaya bisa dipakai
     * di halaman Profile Saya (view file & download beneran berfungsi).
     */
    protected function attachDokumenResmi(array $pegawai): array
    {
        $dbId = $pegawai['db_id'] ?? null;
        if (! $dbId && ! empty($pegawai['nik'])) {
            $dbId = DB::table('pegawai')->where('nik', (string) $pegawai['nik'])->value('id');
        }
        $validUuid = ($dbId && \Illuminate\Support\Str::isUuid((string) $dbId)) ? (string) $dbId : null;

        try {
            $docs = DB::table('dokumen_pegawai')
                ->where(function ($q) use ($validUuid) {
                    if ($validUuid) {
                        $q->where('pegawai_id', $validUuid);
                    } else {
                        $q->whereRaw('1=0');
                    }
                })
                ->get();
        } catch (\Throwable $e) {
            return $pegawai;
        }

        $skDoc = $docs->first(fn ($d) => in_array(strtolower($d->kategori ?? ''), ['sk', 'surat_kerja'], true));
        $diklatDoc = $docs->first(fn ($d) => in_array(strtolower($d->kategori ?? ''), ['diklat', 'surat_diklat'], true));

        if ($skDoc) {
            $pegawai['surat_kerja'] = [
                'id' => $skDoc->id,
                'nomor' => $skDoc->nomor,
                'judul' => $skDoc->judul,
                'tgl_terbit' => ! empty($skDoc->created_at) ? substr((string) $skDoc->created_at, 0, 10) : null,
                'file_name' => $skDoc->file_nama,
                'file_url' => $this->normalizeFileUrl($skDoc->file_url),
            ];
        }

        if ($diklatDoc) {
            $pegawai['surat_diklat'] = [
                'id' => $diklatDoc->id,
                'nomor' => $diklatDoc->nomor,
                'judul' => $diklatDoc->judul,
                'tgl_terbit' => ! empty($diklatDoc->created_at) ? substr((string) $diklatDoc->created_at, 0, 10) : null,
                'file_name' => $diklatDoc->file_nama,
                'file_url' => $this->normalizeFileUrl($diklatDoc->file_url),
            ];
        }

        return $pegawai;
    }

    /**
     * Unduh berkas fisik Surat Kerja (SK) / Surat Diklat milik pegawai yang
     * sedang login. Hanya bisa mengunduh dokumen milik dirinya sendiri
     * (dicari berdasarkan pegawai_id dari session login, bukan dari input
     * ID sembarangan), dan hanya berjalan kalau Admin SDM sudah benar-benar
     * mengunggah file fisiknya (bukan cuma isi nomor/judul saja).
     */
    public function downloadDokumen(string $jenis)
    {
        abort_unless(in_array($jenis, ['sk', 'diklat'], true), 404);

        $pegawai = $this->currentPegawai();
        $dbId = $pegawai['db_id'] ?? null;
        if (! $dbId && ! empty($pegawai['nik'])) {
            $dbId = DB::table('pegawai')->where('nik', (string) $pegawai['nik'])->value('id');
        }
        $validUuid = ($dbId && \Illuminate\Support\Str::isUuid((string) $dbId)) ? (string) $dbId : null;
        $nik = (string) ($pegawai['nik'] ?? '');
        $intId = (string) ($pegawai['id'] ?? '');
        $isDiklat = in_array($jenis, ['diklat', 'surat_diklat'], true);
        $categories = $isDiklat ? ['diklat', 'surat_diklat'] : ['sk', 'surat_kerja'];

        $doc = null;
        try {
            $doc = DB::table('dokumen_pegawai')
                ->where(function ($q) use ($validUuid, $nik, $intId) {
                    if ($validUuid) {
                        $q->where('pegawai_id', $validUuid);
                    }
                    if (! empty($nik)) {
                        $q->orWhereRaw('pegawai_id::text = ?', [$nik]);
                    }
                    if (! empty($intId)) {
                        $q->orWhereRaw('pegawai_id::text = ?', [$intId]);
                    }
                })
                ->whereIn(DB::raw('LOWER(kategori)'), $categories)
                ->first();
        } catch (\Throwable $e) {
            $doc = null;
        }

        if ($doc && ! empty($doc->file_url) && $doc->file_url !== '#') {
            $parsedPath = parse_url($doc->file_url, PHP_URL_PATH);
            $relativePath = ltrim($parsedPath ?? '', '/');
            $fullPath = public_path($relativePath);

            if (! File::exists($fullPath)) {
                $baseName = basename($parsedPath);
                $fullPath = public_path('uploads/dokumen/' . $baseName);
            }

            if (! File::exists($fullPath)) {
                $cleanName = preg_replace('/^\d+_/', '', basename($parsedPath));
                $matches = glob(public_path('uploads/dokumen/*') . $cleanName);
                if (! empty($matches) && File::exists($matches[0])) {
                    $fullPath = $matches[0];
                }
            }

            if (File::exists($fullPath)) {
                return response()->download($fullPath, $doc->file_nama ?? 'Dokumen.pdf');
            }
        }

        request()->merge(['jenis' => $jenis, 'pegawai_id' => $pegawai['id'] ?? null]);
        return app(DokumenSuratController::class)->cetak(request(), $doc->id ?? null);
    }
 
    public function uploadDokumen(Request $request)
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'jenis_dokumen' => 'required|in:surat_kerja,surat_diklat',
            'nomor' => 'required|string|max:100',
            'judul' => 'required|string|max:200',
            'tgl_terbit' => 'required|date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5000',
        ]);
 
        $allPegawai = app(PegawaiController::class)->all();
        $fileName = 'Dokumen_' . time() . '.pdf';
        
        if ($request->hasFile('file')) {
            $fileName = $request->file('file')->getClientOriginalName();
        }
 
        $targetPegawai = collect($allPegawai)->firstWhere('id', $validated['pegawai_id']);
        if ($targetPegawai && ! empty($targetPegawai['db_id'])) {
            $fileUrl = '#';
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $destination = public_path('uploads/dokumen');
                if (! File::isDirectory($destination)) {
                    File::makeDirectory($destination, 0755, true, true);
                }
                $storedName = time() . '_' . $file->getClientOriginalName();
                $file->move($destination, $storedName);
                $fileUrl = url('uploads/dokumen/' . $storedName);
            }

            $kategoriDb = $validated['jenis_dokumen'] === 'surat_kerja' ? 'SK' : 'Diklat';
            try {
                DB::table('dokumen_pegawai')->updateOrInsert(
                    ['pegawai_id' => $targetPegawai['db_id'], 'kategori' => $kategoriDb],
                    [
                        'nomor' => $validated['nomor'],
                        'judul' => $validated['judul'],
                        'file_url' => $fileUrl,
                        'file_nama' => $fileName,
                        'diunggah_oleh' => session('simpeg_user.nama_peg') ?? 'Admin SDM',
                        'created_at' => now(),
                    ]
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB uploadDokumen failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Dokumen berhasil diunggah/diperbarui oleh Admin SDM.');
    }
 
    /**
     * Simpan/perbarui Data Pribadi (Biodata Diri) untuk Curriculum Vitae.
     */
    public function updateBiodata(Request $request)
    {
        $validated = $request->validate([
            'tempat_lahir' => 'nullable|string|max:100',
            'tgl_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'status_kawin' => 'nullable|string|max:50',
            'alamat' => 'nullable|string|max:255',
            'telp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
        ]);
 
        $pegawai = $this->currentPegawai();
        $cvKey = $pegawai['db_id'] ?? $pegawai['nik'];
        $cv = $this->getPegawaiCv($cvKey);
        $cv['biodata'] = $validated;
        $this->savePegawaiCv($cvKey, $cv);

        if (! empty($pegawai['db_id'])) {
            try {
                $dbUpdates = [];
                if (! empty($validated['alamat'])) $dbUpdates['alamat'] = $validated['alamat'];
                if (! empty($validated['telp'])) $dbUpdates['no_telp'] = $validated['telp'];
                if (! empty($validated['email'])) $dbUpdates['email'] = $validated['email'];
                if (! empty($validated['status_kawin'])) $dbUpdates['status_pernikahan'] = $validated['status_kawin'];
                if (! empty($validated['tempat_lahir']) || ! empty($validated['tgl_lahir'])) {
                    $dbUpdates['tempat_tanggal_lahir'] = trim(($validated['tempat_lahir'] ?? '') . ', ' . ($validated['tgl_lahir'] ?? ''), ', ');
                }
                if (! empty($dbUpdates)) {
                    \Illuminate\Support\Facades\DB::table('pegawai')->where('id', $pegawai['db_id'])->update($dbUpdates);
                    \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
                    \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB update biodata failed: ' . $e->getMessage());
            }
        }
 
        return redirect()->route('profile.show')->with('success', 'Data Pribadi berhasil disimpan.');
    }
 
    /**
     * Simpan/perbarui daftar Kompetensi/Keahlian (1 baris = 1 item).
     */
    public function updateKompetensi(Request $request)
    {
        $validated = $request->validate([
            'kompetensi_text' => 'nullable|string',
        ]);
 
        $items = collect(explode("\n", $validated['kompetensi_text'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
 
        $pegawai = $this->currentPegawai();
        $cvKey = $pegawai['db_id'] ?? $pegawai['nik'];
        $cv = $this->getPegawaiCv($cvKey);
        $cv['kompetensi'] = $items;
        $this->savePegawaiCv($cvKey, $cv);
 
        return redirect()->route('profile.show')->with('success', 'Kompetensi/Keahlian berhasil disimpan.');
    }
 
    protected function validateCvDetailType(string $type): void
    {
        abort_unless(in_array($type, self::CV_DETAIL_TYPES, true), 404);
    }
 
    protected function cvDetailRules(string $type): array
    {
        $rules = [];
 
        foreach (self::cvDetailFieldConfig($type)['fields'] as $field) {
            $rules[$field['key']] = 'required|string|max:150';
        }
 
        return $rules;
    }
 
    /**
     * Tambah item Diklat/Sertifikasi milik pegawai yang login.
     */
    public function storeCvDetail(Request $request, string $type)
    {
        $this->validateCvDetailType($type);
        $validated = $request->validate($this->cvDetailRules($type));
 
        $pegawai = $this->currentPegawai();
        $cvKey = $pegawai['db_id'] ?? $pegawai['nik'];
        $cv = $this->getPegawaiCv($cvKey);
        $items = $cv[$type] ?? [];
        $newId = $items ? max(array_column($items, 'id')) + 1 : 1;
        $validated['id'] = $newId;
        $items[] = $validated;
        $cv[$type] = $items;
        $this->savePegawaiCv($cvKey, $cv);
 
        return redirect()->route('profile.show')->with('success', self::cvDetailFieldConfig($type)['title'].' berhasil ditambahkan.');
    }
 
    public function updateCvDetail(Request $request, string $type, int $itemId)
    {
        $this->validateCvDetailType($type);
        $validated = $request->validate($this->cvDetailRules($type));
 
        $pegawai = $this->currentPegawai();
        $cvKey = $pegawai['db_id'] ?? $pegawai['nik'];
        $cv = $this->getPegawaiCv($cvKey);
        $cv[$type] = collect($cv[$type] ?? [])->map(function ($item) use ($itemId, $validated) {
            if ($item['id'] === $itemId) {
                return array_merge($item, $validated);
            }
            return $item;
        })->all();
        $this->savePegawaiCv($cvKey, $cv);
 
        return redirect()->route('profile.show')->with('success', self::cvDetailFieldConfig($type)['title'].' berhasil diperbarui.');
    }
 
    public function destroyCvDetail(string $type, int $itemId)
    {
        $this->validateCvDetailType($type);
 
        $pegawai = $this->currentPegawai();
        $cvKey = $pegawai['db_id'] ?? $pegawai['nik'];
        $cv = $this->getPegawaiCv($cvKey);
        $cv[$type] = collect($cv[$type] ?? [])->reject(fn ($item) => $item['id'] === $itemId)->values()->all();
        $this->savePegawaiCv($cvKey, $cv);
 
        return redirect()->route('profile.show')->with('success', 'Data berhasil dihapus.');
    }
 
    /**
     * Halaman CV siap cetak (A4) - dipakai untuk tombol "Lihat CV" & "Download CV"
     * (download = print-to-PDF lewat browser, mengikuti pola dokumen-surat/cetak).
     */
    public function cvCetak()
    {
        $pegawai = $this->currentPegawai();
        $cvKey = $pegawai['db_id'] ?? $pegawai['nik'];
        $cv = $this->getPegawaiCv($cvKey);
 
        $pegawai['biodata'] = $cv['biodata'] ?? [];
        $pegawai['pendidikan'] = $pegawai['pendidikan'] ?? [];
        $pegawai['jabatan_riwayat'] = $pegawai['jabatan_riwayat'] ?? [];
        $pegawai['diklat'] = $cv['diklat'] ?? [];
        $pegawai['sertifikasi'] = $cv['sertifikasi'] ?? [];
        $pegawai['kompetensi'] = $cv['kompetensi'] ?? [];
        $pegawai['prestasi'] = $pegawai['prestasi'] ?? [];
 
        return view('profile.cv-cetak', compact('pegawai'));
    }
 
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:4|confirmed',
        ]);
 
        $userLogin = session('simpeg_user');
 
        if ($validated['current_password'] !== $userLogin['password']) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }
 
        // Update password di session login yang sedang aktif.
        $userLogin['password'] = $validated['new_password'];
        session()->put('simpeg_user', $userLogin);
 
        return redirect()->route('profile.show')->with('success', 'Password berhasil diubah.');
    }

    public function storeKeluarga(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'hubungan' => 'required|string|max:50',
            'tgl_lahir' => 'nullable|date',
            'keterangan' => 'nullable|string|max:100',
        ]);

        $userLogin = session('simpeg_user');
        $dbPeg = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $userLogin['nik'])->first();

        if ($dbPeg) {
            \Illuminate\Support\Facades\DB::table('keluarga')->insert([
                'pegawai_id' => $dbPeg->id,
                'nama' => $validated['nama'],
                'hubungan' => $validated['hubungan'],
                'tanggal_lahir' => $validated['tgl_lahir'] ?? null,
                'pekerjaan' => $validated['keterangan'] ?? '-',
                'created_at' => now(),
            ]);

            \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
            \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        }

        return redirect()->route('profile.show')->with('success', 'Data anggota keluarga berhasil ditambahkan.');
    }

    public function destroyKeluarga(int $id)
    {
        $userLogin = session('simpeg_user');
        $dbPeg = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $userLogin['nik'])->first();

        if ($dbPeg) {
            \Illuminate\Support\Facades\DB::table('keluarga')
                ->where('id', $id)
                ->where('pegawai_id', $dbPeg->id)
                ->delete();

            \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
            \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        }

        return redirect()->route('profile.show')->with('success', 'Data anggota keluarga berhasil dihapus.');
    }
}
 