@extends('layouts.app')

@section('title', 'Input Insentif Pendidikan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SDM / Insentif Pendidikan / Input Baru</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0;">INPUT INSENTIF PENDIDIKAN (2026)</h1>
            <p style="margin:4px 0 0 0; color:#64748B; font-size:13px;">Format resmi insentif dan seluruh potongan sesuai regulasi Perumdam Tirta Darma Ayu.</p>
        </div>
        <a href="{{ route('insentif-pendidikan.index') }}" class="btn btn-outline" style="font-size:13px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali ke Daftar
        </a>
    </div>
</div>

<form method="POST" action="{{ route('insentif-pendidikan.store') }}" id="insentif-pendidikan-form">
    @csrf

    <!-- Card 1: Data Pegawai & Periode -->
    <div class="form-card" style="max-width:100%; margin-bottom:16px;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:15px; margin:0 0 16px; color:#0F2A3D; display:flex; align-items:center; gap:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="color:#0284C7;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Data Pegawai &amp; Periode
        </h3>
        <div class="form-grid">
            <div class="form-group span-2">
                <label for="pegawai_select" style="font-weight:600;">Pilih Pegawai <span style="color:#DC2626;">*</span></label>
                <select id="pegawai_select" name="pegawai_nik" class="form-control" required onchange="onPegawaiSelected(this.value)">
                    <option value="">-- Pilih Pegawai (Ketik NIK / Nama) --</option>
                    @foreach ($pegawaiList as $p)
                        <option value="{{ $p['nik'] }}" data-id="{{ $p['id'] ?? '' }}" @selected(old('pegawai_nik') === $p['nik'])>
                            {{ $p['nik'] }} - {{ $p['nama'] }} ({{ $p['jabatan'] ?? '-' }})
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="pegawai_id" id="pegawai_id">
            </div>

            <div class="form-group">
                <label style="font-weight:600;">Bulan / Periode <span style="color:#DC2626;">*</span></label>
                <input type="text" name="periode" id="periode" class="form-control" value="{{ old('periode', $defaultPeriode) }}" placeholder="Contoh: JULI 2026" required>
            </div>

            <div class="form-group">
                <label style="font-weight:600;">Status Data Pegawai</label>
                <input type="text" id="info_status" class="form-control" value="Pilih pegawai untuk auto-fill nominal" disabled style="background:#F8FAFC; color:#475569;">
            </div>
        </div>
    </div>

    <!-- 2 Kolom Formulir: Kiri (12 Insentif), Kanan (9 Potongan Insentif + 10 Potongan Non-Insentif) -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:16px; margin-bottom:16px;">
        
        <!-- KOLOM KIRI: 12 KOMPONEN INSENTIF (PERSIS GAMBAR 2) -->
        <div class="form-card" style="margin:0;">
            <h3 style="font-family:'Space Grotesk',sans-serif; font-size:15px; margin:0 0 16px; color:#15803D; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Komponen Penerimaan Insentif (12 Item)
            </h3>

            <div style="display:grid; grid-template-columns:1fr; gap:12px;">
                <div class="form-group">
                    <label for="insentif_jabatan" style="font-size:12px; font-weight:600;">INSENTIF JABATAN</label>
                    <input type="number" id="insentif_jabatan" name="insentif_jabatan" min="0" step="1" value="{{ old('insentif_jabatan', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_prestasi" style="font-size:12px; font-weight:600;">INSENTIF PRESTASI</label>
                    <input type="number" id="insentif_prestasi" name="insentif_prestasi" min="0" step="1" value="{{ old('insentif_prestasi', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_transportasi" style="font-size:12px; font-weight:600;">INSENTIF TRANSPORTASI</label>
                    <input type="number" id="insentif_transportasi" name="insentif_transportasi" min="0" step="1" value="{{ old('insentif_transportasi', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_pangan" style="font-size:12px; font-weight:600;">INSENTIF PANGAN</label>
                    <input type="number" id="insentif_pangan" name="insentif_pangan" min="0" step="1" value="{{ old('insentif_pangan', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_bpjs_kesehatan" style="font-size:12px; font-weight:600;">INSENTIF BPJS KESEHATAN</label>
                    <input type="number" id="insentif_bpjs_kesehatan" name="insentif_bpjs_kesehatan" min="0" step="1" value="{{ old('insentif_bpjs_kesehatan', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_perumahan" style="font-size:12px; font-weight:600;">INSENTIF PERUMAHAN</label>
                    <input type="number" id="insentif_perumahan" name="insentif_perumahan" min="0" step="1" value="{{ old('insentif_perumahan', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_bpjs_tenaga_kerja" style="font-size:12px; font-weight:600;">INSENTIF BPJS TENAGA KERJA</label>
                    <input type="number" id="insentif_bpjs_tenaga_kerja" name="insentif_bpjs_tenaga_kerja" min="0" step="1" value="{{ old('insentif_bpjs_tenaga_kerja', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_perusahaan" style="font-size:12px; font-weight:600;">INSENTIF PERUSAHAAN</label>
                    <input type="number" id="insentif_perusahaan" name="insentif_perusahaan" min="0" step="1" value="{{ old('insentif_perusahaan', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="lembur" style="font-size:12px; font-weight:600;">LEMBUR</label>
                    <input type="number" id="lembur" name="lembur" min="0" step="1" value="{{ old('lembur', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_pajak" style="font-size:12px; font-weight:600;">INSENTIF PAJAK</label>
                    <input type="number" id="insentif_pajak" name="insentif_pajak" min="0" step="1" value="{{ old('insentif_pajak', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_air_minum" style="font-size:12px; font-weight:600;">INSENTIF AIR MINUM</label>
                    <input type="number" id="insentif_air_minum" name="insentif_air_minum" min="0" step="1" value="{{ old('insentif_air_minum', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
                <div class="form-group">
                    <label for="insentif_komunikasi" style="font-size:12px; font-weight:600;">INSENTIF KOMUNIKASI</label>
                    <input type="number" id="insentif_komunikasi" name="insentif_komunikasi" min="0" step="1" value="{{ old('insentif_komunikasi', 0) }}" class="form-control calc-insentif" oninput="hitungTotal()">
                </div>
            </div>

            <div style="margin-top:18px; padding:14px; background:#F0FDF4; border-radius:10px; border:1px solid #BBF7D0; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:700; color:#166534; font-size:13.5px;">JUMLAH INSENTIF:</span>
                <span style="font-family:'IBM Plex Mono',monospace; font-size:17px; font-weight:700; color:#15803D;" id="display-jumlah-insentif">Rp 0</span>
            </div>
        </div>

        <!-- KOLOM KANAN: POTONGAN INSENTIF (9 ITEM) & POTONGAN NON-INSENTIF (10 ITEM) -->
        <div style="display:flex; flex-direction:column; gap:16px;">
            <!-- Potongan Insentif -->
            <div class="form-card" style="margin:0;">
                <h3 style="font-family:'Space Grotesk',sans-serif; font-size:15px; margin:0 0 16px; color:#DC2626; display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    Potongan Insentif (9 Item)
                </h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px;">
                    <div class="form-group">
                        <label for="potongan_sanksi_perusahaan" style="font-size:11.5px; font-weight:600;">POT. SANKSI PERUSAHAAN</label>
                        <input type="number" id="potongan_sanksi_perusahaan" name="potongan_sanksi_perusahaan" min="0" step="1" value="{{ old('potongan_sanksi_perusahaan', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_pmi_lain" style="font-size:11.5px; font-weight:600;">POT. PMI / LAIN-LAIN</label>
                        <input type="number" id="potongan_pmi_lain" name="potongan_pmi_lain" min="0" step="1" value="{{ old('potongan_pmi_lain', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_dapenma" style="font-size:11.5px; font-weight:600;">POT. DAPENMA</label>
                        <input type="number" id="potongan_dapenma" name="potongan_dapenma" min="0" step="1" value="{{ old('potongan_dapenma', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_bpjs_tenaga_kerja" style="font-size:11.5px; font-weight:600;">POT. BPJS TENAGA KERJA</label>
                        <input type="number" id="potongan_bpjs_tenaga_kerja" name="potongan_bpjs_tenaga_kerja" min="0" step="1" value="{{ old('potongan_bpjs_tenaga_kerja', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_perumahan" style="font-size:11.5px; font-weight:600;">POT. PERUMAHAN</label>
                        <input type="number" id="potongan_perumahan" name="potongan_perumahan" min="0" step="1" value="{{ old('potongan_perumahan', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_insentif_perusahaan" style="font-size:11.5px; font-weight:600;">POT. INSENTIF PERUSAHAAN</label>
                        <input type="number" id="potongan_insentif_perusahaan" name="potongan_insentif_perusahaan" min="0" step="1" value="{{ old('potongan_insentif_perusahaan', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_korpri" style="font-size:11.5px; font-weight:600;">POT. KORPRI</label>
                        <input type="number" id="potongan_korpri" name="potongan_korpri" min="0" step="1" value="{{ old('potongan_korpri', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_pajak" style="font-size:11.5px; font-weight:600;">POT. PAJAK</label>
                        <input type="number" id="potongan_pajak" name="potongan_pajak" min="0" step="1" value="{{ old('potongan_pajak', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_bpjs_kesehatan" style="font-size:11.5px; font-weight:600;">POT. BPJS KESEHATAN</label>
                        <input type="number" id="potongan_bpjs_kesehatan" name="potongan_bpjs_kesehatan" min="0" step="1" value="{{ old('potongan_bpjs_kesehatan', 0) }}" class="form-control calc-pot-insentif" oninput="hitungTotal()">
                    </div>
                </div>
                <div style="margin-top:14px; padding:10px 14px; background:#FEF2F2; border-radius:8px; border:1px solid #FECACA; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-weight:700; color:#991B1B; font-size:12.5px;">JUMLAH POTONGAN INSENTIF:</span>
                    <span style="font-family:'IBM Plex Mono',monospace; font-size:15px; font-weight:700; color:#DC2626;" id="display-jumlah-pot-insentif">Rp 0</span>
                </div>
            </div>

            <!-- Potongan Non-Insentif -->
            <div class="form-card" style="margin:0;">
                <h3 style="font-family:'Space Grotesk',sans-serif; font-size:15px; margin:0 0 16px; color:#B45309; display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Potongan Non-Insentif (10 Item)
                </h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px;">
                    <div class="form-group">
                        <label for="potongan_koperasi" style="font-size:11.5px; font-weight:600;">POT. KOPERASI</label>
                        <input type="number" id="potongan_koperasi" name="potongan_koperasi" min="0" step="1" value="{{ old('potongan_koperasi', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_darma_wanita" style="font-size:11.5px; font-weight:600;">POT. DARMA WANITA</label>
                        <input type="number" id="potongan_darma_wanita" name="potongan_darma_wanita" min="0" step="1" value="{{ old('potongan_darma_wanita', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_rekening_air_minum" style="font-size:11.5px; font-weight:600;">POT. REKENING AIR MINUM</label>
                        <input type="number" id="potongan_rekening_air_minum" name="potongan_rekening_air_minum" min="0" step="1" value="{{ old('potongan_rekening_air_minum', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_kas" style="font-size:11.5px; font-weight:600;">POT. KAS</label>
                        <input type="number" id="potongan_kas" name="potongan_kas" min="0" step="1" value="{{ old('potongan_kas', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_bank_bjb" style="font-size:11.5px; font-weight:600;">POT. BANK BJB</label>
                        <input type="number" id="potongan_bank_bjb" name="potongan_bank_bjb" min="0" step="1" value="{{ old('potongan_bank_bjb', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_bank_bjbs" style="font-size:11.5px; font-weight:600;">POT. BANK BJBS</label>
                        <input type="number" id="potongan_bank_bjbs" name="potongan_bank_bjbs" min="0" step="1" value="{{ old('potongan_bank_bjbs', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_bank_btn" style="font-size:11.5px; font-weight:600;">POT. BANK BTN</label>
                        <input type="number" id="potongan_bank_btn" name="potongan_bank_btn" min="0" step="1" value="{{ old('potongan_bank_btn', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_bank_bpr" style="font-size:11.5px; font-weight:600;">POT. BANK BPR</label>
                        <input type="number" id="potongan_bank_bpr" name="potongan_bank_bpr" min="0" step="1" value="{{ old('potongan_bank_bpr', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_asuransi" style="font-size:11.5px; font-weight:600;">POT. ASURANSI</label>
                        <input type="number" id="potongan_asuransi" name="potongan_asuransi" min="0" step="1" value="{{ old('potongan_asuransi', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                    <div class="form-group">
                        <label for="potongan_zakat_profesi" style="font-size:11.5px; font-weight:600;">POT. ZAKAT PROFESI</label>
                        <input type="number" id="potongan_zakat_profesi" name="potongan_zakat_profesi" min="0" step="1" value="{{ old('potongan_zakat_profesi', 0) }}" class="form-control calc-pot-non-insentif" oninput="hitungTotal()">
                    </div>
                </div>
                <div style="margin-top:14px; padding:10px 14px; background:#FFFBEB; border-radius:8px; border:1px solid #FDE68A; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-weight:700; color:#92400E; font-size:12.5px;">JUMLAH POTONGAN NON-INSENTIF:</span>
                    <span style="font-family:'IBM Plex Mono',monospace; font-size:15px; font-weight:700; color:#B45309;" id="display-jumlah-pot-non-insentif">Rp 0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Bersih Diterima -->
    <div class="form-card" style="max-width:100%; background:linear-gradient(135deg, #0F2A3D 0%, #16384C 100%); color:#ffffff; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <div style="font-size:13px; text-transform:uppercase; letter-spacing:0.5px; opacity:0.8;">JUMLAH INSENTIF DITERIMA</div>
                <div style="font-size:12px; opacity:0.7; margin-top:2px;">(Jumlah Insentif &minus; Jumlah Potongan Insentif &minus; Jumlah Potongan Non-Insentif)</div>
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:26px; font-weight:700; color:#34D399;" id="display-jumlah-insentif-diterima">
                Rp 0
            </div>
        </div>
    </div>

    <!-- Tombol Simpan -->
    <div style="display:flex; gap:12px; justify-content:flex-end;">
        <a href="{{ route('insentif-pendidikan.index') }}" class="btn btn-outline" style="padding:10px 20px;">Batal</a>
        <button type="submit" class="btn btn-success" style="padding:10px 24px; font-weight:700; font-size:14px; box-shadow:0 4px 12px rgba(22, 163, 74, 0.25);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Simpan &amp; Terbitkan Slip
        </button>
    </div>
</form>

<script>
const hitungInsentifBaseUrl = "{{ url('insentif-pendidikan/ajax/hitung-insentif') }}";

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function hitungTotal() {
    let totalInsentif = 0;
    document.querySelectorAll('.calc-insentif').forEach(inp => {
        totalInsentif += parseFloat(inp.value) || 0;
    });

    let totalPotInsentif = 0;
    document.querySelectorAll('.calc-pot-insentif').forEach(inp => {
        totalPotInsentif += parseFloat(inp.value) || 0;
    });

    let totalPotNonInsentif = 0;
    document.querySelectorAll('.calc-pot-non-insentif').forEach(inp => {
        totalPotNonInsentif += parseFloat(inp.value) || 0;
    });

    const diterima = Math.max(0, totalInsentif - (totalPotInsentif + totalPotNonInsentif));

    document.getElementById('display-jumlah-insentif').textContent = formatRupiah(totalInsentif);
    document.getElementById('display-jumlah-pot-insentif').textContent = formatRupiah(totalPotInsentif);
    document.getElementById('display-jumlah-pot-non-insentif').textContent = formatRupiah(totalPotNonInsentif);
    document.getElementById('display-jumlah-insentif-diterima').textContent = formatRupiah(diterima);
}

function onPegawaiSelected(nik) {
    if (!nik) {
        document.getElementById('info_status').value = 'Pilih pegawai untuk auto-fill nominal';
        return;
    }

    const select = document.getElementById('pegawai_select');
    const selectedOption = select.options[select.selectedIndex];
    document.getElementById('pegawai_id').value = selectedOption.getAttribute('data-id') || '';

    document.getElementById('info_status').value = 'Mengambil data payroll pegawai...';

    fetch(hitungInsentifBaseUrl + '/' + encodeURIComponent(nik), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;

        document.getElementById('info_status').value = 'Data payroll berhasil dimuat';
        const vals = res.values || {};

        for (const [key, val] of Object.entries(vals)) {
            const input = document.getElementById(key);
            if (input) {
                input.value = val;
            }
        }

        hitungTotal();
    })
    .catch(err => {
        console.warn(err);
        document.getElementById('info_status').value = 'Gagal memuat auto-fill nominal';
    });
}

hitungTotal();
</script>
@endsection
