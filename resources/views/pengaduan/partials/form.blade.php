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

    <!-- Pemilihan Pihak Terlapor dari Database Pegawai -->
    <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:12px 14px; margin-bottom:14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <label style="font-weight:700; font-size:13px; color:#0369a1; margin:0; display:flex; align-items:center; gap:6px;">
                <span>👤</span> Pelaku / Pihak yang Diadukan *
            </label>
            <button type="button" onclick="bukaModalPilihPegawai()" style="background:#0284c7; color:#fff; border:none; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                🔍 Pilih dari Data Pegawai
            </button>
        </div>

        <div id="badge-terpilih" style="display:none; background:#e0f2fe; border:1px solid #7dd3fc; border-radius:6px; padding:8px 12px; margin-bottom:10px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-size:11px; font-weight:700; color:#0369a1; text-transform:uppercase;">Pegawai Terpilih</span>
                    <div id="badge-terpilih-nama" style="font-size:13.5px; font-weight:700; color:#0f172a;"></div>
                    <div id="badge-terpilih-detail" style="font-size:12px; color:#475569;"></div>
                </div>
                <button type="button" onclick="resetPilihPegawai()" style="background:none; border:none; color:#ef4444; font-size:12px; font-weight:700; cursor:pointer;">
                    ✕ Ganti
                </button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:8px;">
            <div class="field">
                <label for="pihak_terlapor" style="font-weight:600; font-size:12px; display:block; margin-bottom:3px; color:#334155;">Nama Terlapor *</label>
                <div class="input-wrap">
                    <input type="text" id="pihak_terlapor" name="pihak_terlapor" required placeholder="Nama pihak yang diadukan" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-family:inherit; font-size:13px;">
                </div>
            </div>
            <div class="field">
                <label for="nik_pelaku" style="font-weight:600; font-size:12px; display:block; margin-bottom:3px; color:#334155;">NIK Pelaku (Opsional)</label>
                <div class="input-wrap">
                    <input type="text" id="nik_pelaku" name="nik_pelaku" placeholder="Nomor Induk Karyawan" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-family:inherit; font-size:13px;">
                </div>
            </div>
        </div>

        <div class="field">
            <label for="jabatan_pelaku" style="font-weight:600; font-size:12px; display:block; margin-bottom:3px; color:#334155;">Jabatan / Unit Kerja Terlapor (Opsional)</label>
            <div class="input-wrap">
                <input type="text" id="jabatan_pelaku" name="jabatan_pelaku" placeholder="Contoh: Staf Lapangan / Cabang Jatibarang" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:8px 10px; font-family:inherit; font-size:13px;">
            </div>
        </div>
    </div>

    <div class="field" style="margin-bottom:12px;">
        <label for="deskripsi" style="font-weight:600; font-size:13px; display:block; margin-bottom:4px;">Detail Kronologi Pengaduan *</label>
        <div class="input-wrap">
            <textarea id="deskripsi" name="deskripsi" rows="4" required placeholder="Jelaskan secara rinci kronologi kejadian, waktu, tempat, dan fakta-fakta terkait..." style="width:100%; border:1px solid var(--border); border-radius:6px; padding:10px 12px; font-family:inherit;"></textarea>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:14px; align-items:start;">
        <div class="field" style="display:flex; flex-direction:column;">
            <label for="foto_bukti" style="font-weight:600; font-size:12.5px; display:flex; flex-direction:column; justify-content:flex-end; min-height:36px; margin-bottom:6px; line-height:1.25;">
                <span>Foto Bukti</span>
                <span style="font-size:11px; font-weight:400; color:var(--text-muted);">(Maks 5MB)</span>
            </label>
            <div class="input-wrap" style="width:100%;">
                <input type="file" id="foto_bukti" name="foto_bukti[]" multiple accept="image/*" style="width:100%; font-size:11.5px; padding:6px 8px; border:1px solid var(--border); border-radius:6px; background:#f8fafc; box-sizing:border-box; height:38px; cursor:pointer;">
            </div>
        </div>
        <div class="field" style="display:flex; flex-direction:column;">
            <label for="dokumen_pendukung" style="font-weight:600; font-size:12.5px; display:flex; flex-direction:column; justify-content:flex-end; min-height:36px; margin-bottom:6px; line-height:1.25;">
                <span>Dokumen PDF/Word</span>
                <span style="font-size:11px; font-weight:400; color:var(--text-muted);">(Maks 10MB)</span>
            </label>
            <div class="input-wrap" style="width:100%;">
                <input type="file" id="dokumen_pendukung" name="dokumen_pendukung[]" multiple accept=".pdf,.doc,.docx" style="width:100%; font-size:11.5px; padding:6px 8px; border:1px solid var(--border); border-radius:6px; background:#f8fafc; box-sizing:border-box; height:38px; cursor:pointer;">
            </div>
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

<!-- Modal Pilih Pegawai Terlapor -->
<div id="modal-pilih-pegawai" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:12px; width:100%; max-width:540px; max-height:85vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden;">
        <div style="padding:14px 18px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="font-weight:700; font-size:15px; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <span>👤</span> Pilih Pegawai yang Diadukan
            </div>
            <button type="button" onclick="tutupModalPilihPegawai()" style="background:none; border:none; font-size:20px; color:#64748b; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div style="padding:12px 18px; border-bottom:1px solid #e2e8f0;">
            <input type="text" id="cari-pegawai-input" oninput="filterDaftarPegawai(this.value)" placeholder="Cari nama, NIK, jabatan, atau unit kerja..." style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13px; box-sizing:border-box;">
        </div>

        <div id="daftar-pegawai-container" style="overflow-y:auto; flex:1; padding:10px 18px; display:flex; flex-direction:column; gap:6px;">
            @if (!empty($daftarPegawai))
                @foreach ($daftarPegawai as $peg)
                    <div class="pegawai-item" 
                         data-nama="{{ strtolower($peg->name ?? '') }}" 
                         data-nik="{{ strtolower($peg->nik ?? '') }}" 
                         data-jabatan="{{ strtolower($peg->jabatan ?? '') }}" 
                         data-unit="{{ strtolower($peg->unit_kerja ?? '') }}"
                         onclick="pilihPegawai('{{ addslashes($peg->name ?? '') }}', '{{ $peg->nik ?? '' }}', '{{ addslashes($peg->jabatan ?? '') }}', '{{ addslashes($peg->unit_kerja ?? '') }}')"
                         style="padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer; transition:all 0.15s; background:#fff;"
                         onmouseover="this.style.background='#f0f9ff'; this.style.borderColor='#7dd3fc';"
                         onmouseout="this.style.background='#fff'; this.style.borderColor='#e2e8f0';">
                        <div style="font-weight:700; font-size:13px; color:#0f172a;">{{ $peg->name }}</div>
                        <div style="font-size:11.5px; color:#64748b;">
                            {{ $peg->jabatan ?? '-' }} · NIK: <strong>{{ $peg->nik }}</strong> {{ !empty($peg->unit_kerja) ? '· ' . $peg->unit_kerja : '' }}
                        </div>
                    </div>
                @endforeach
            @else
                <div style="text-align:center; padding:20px; color:#94a3b8; font-size:13px;">Data pegawai tidak tersedia.</div>
            @endif
        </div>

        <div style="padding:10px 18px; border-top:1px solid #e2e8f0; background:#f8fafc; text-align:right;">
            <button type="button" onclick="tutupModalPilihPegawai()" style="background:#e2e8f0; color:#334155; border:none; padding:7px 14px; border-radius:6px; font-size:12.5px; font-weight:600; cursor:pointer;">
                Batal
            </button>
        </div>
    </div>
</div>

<script>
function bukaModalPilihPegawai() {
    const m = document.getElementById('modal-pilih-pegawai');
    if (m) {
        m.style.display = 'flex';
        const input = document.getElementById('cari-pegawai-input');
        if (input) {
            input.value = '';
            input.focus();
            filterDaftarPegawai('');
        }
    }
}

function tutupModalPilihPegawai() {
    const m = document.getElementById('modal-pilih-pegawai');
    if (m) m.style.display = 'none';
}

function filterDaftarPegawai(query) {
    const q = (query || '').toLowerCase().trim();
    const items = document.querySelectorAll('#daftar-pegawai-container .pegawai-item');
    items.forEach(el => {
        const nama = el.getAttribute('data-nama') || '';
        const nik = el.getAttribute('data-nik') || '';
        const jabatan = el.getAttribute('data-jabatan') || '';
        const unit = el.getAttribute('data-unit') || '';
        if (!q || nama.includes(q) || nik.includes(q) || jabatan.includes(q) || unit.includes(q)) {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    });
}

function pilihPegawai(nama, nik, jabatan, unit) {
    document.getElementById('pihak_terlapor').value = nama;
    document.getElementById('nik_pelaku').value = nik;
    const jabStr = unit ? (jabatan + ' (' + unit + ')') : jabatan;
    document.getElementById('jabatan_pelaku').value = jabStr;

    const badge = document.getElementById('badge-terpilih');
    const badgeNama = document.getElementById('badge-terpilih-nama');
    const badgeDetail = document.getElementById('badge-terpilih-detail');

    if (badge && badgeNama && badgeDetail) {
        badgeNama.textContent = nama;
        badgeDetail.textContent = jabStr + ' · NIK: ' + nik;
        badge.style.display = 'block';
    }

    tutupModalPilihPegawai();
}

function resetPilihPegawai() {
    document.getElementById('pihak_terlapor').value = '';
    document.getElementById('nik_pelaku').value = '';
    document.getElementById('jabatan_pelaku').value = '';
    const badge = document.getElementById('badge-terpilih');
    if (badge) badge.style.display = 'none';
}
</script>
