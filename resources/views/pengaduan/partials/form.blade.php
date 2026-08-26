<form method="POST" action="{{ route('pengaduan.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="field" style="margin-bottom:12px;">
        <label for="kategori" style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Kategori Pelanggaran *</label>
        <div class="input-wrap">
            <select id="kategori" name="kategori" required style="width:100%; border:1px solid var(--border); border-radius:6px; padding:9px 12px; font-family:inherit;">
                <option value="Pelanggaran Administrasi">Pelanggaran Administrasi (Divisi Administrasi)</option>
                <option value="Pelanggaran Teknik">Pelanggaran Teknik (Divisi Teknik)</option>
                <option value="Umum">Umum</option>
            </select>
        </div>
    </div>

    <div class="field" style="margin-bottom:12px;">
        <label for="judul" style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Judul / Pokok Pengaduan *</label>
        <div class="input-wrap">
            <input type="text" id="judul" name="judul" required placeholder="Contoh: Dugaan Penyalahgunaan Wewenang / Absensi Fiktif" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:9px 12px; font-family:inherit;">
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
        <div class="field">
            <label for="pihak_terlapor" style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Nama Pihak Terlapor *</label>
            <div class="input-wrap">
                <input type="text" id="pihak_terlapor" name="pihak_terlapor" required placeholder="Nama pihak yang diadukan" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:9px 12px; font-family:inherit;">
            </div>
        </div>
        <div class="field">
            <label for="nik_pelaku" style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">NIK Pelaku (Opsional)</label>
            <div class="input-wrap">
                <input type="text" id="nik_pelaku" name="nik_pelaku" placeholder="Nomor Induk Karyawan" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:9px 12px; font-family:inherit;">
            </div>
        </div>
    </div>

    <div class="field" style="margin-bottom:12px;">
        <label for="jabatan_pelaku" style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Jabatan / Unit Kerja Terlapor (Opsional)</label>
        <div class="input-wrap">
            <input type="text" id="jabatan_pelaku" name="jabatan_pelaku" placeholder="Contoh: Staf Lapangan / Cabang Jatibarang" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:9px 12px; font-family:inherit;">
        </div>
    </div>

    <div class="field" style="margin-bottom:12px;">
        <label for="deskripsi" style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Detail Kronologi Pengaduan *</label>
        <div class="input-wrap">
            <textarea id="deskripsi" name="deskripsi" rows="4" required placeholder="Jelaskan secara rinci kronologi kejadian, waktu, tempat, dan fakta-fakta terkait..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:10px 12px; font-family:inherit;"></textarea>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:14px;">
        <div class="field">
            <label for="foto_bukti" style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Foto Bukti (Maks 5MB)</label>
            <input type="file" id="foto_bukti" name="foto_bukti[]" multiple accept="image/*" style="width:100%; font-size:12px;">
        </div>
        <div class="field">
            <label for="dokumen_pendukung" style="font-weight:600; font-size:12.5px; display:block; margin-bottom:4px;">Dokumen PDF/Word (Maks 10MB)</label>
            <input type="file" id="dokumen_pendukung" name="dokumen_pendukung[]" multiple accept=".pdf,.doc,.docx" style="width:100%; font-size:12px;">
        </div>
    </div>

    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 12px; border-radius:6px; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
        <input type="checkbox" id="anonim" name="anonim" value="1" style="width:16px; height:16px; cursor:pointer;">
        <label for="anonim" style="font-size:13px; font-weight:600; color:#334155; cursor:pointer;">
            Kirim sebagai Pelapor Anonim (Rahasiakan Identitas Saya)
        </label>
    </div>

    <button type="submit" class="btn-submit" style="width:100%; padding:11px; background:#0d2c6e; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer;">
        Kirim Laporan Pengaduan
    </button>
</form>
