@extends('layouts.app')

@section('title', 'Input Tunjangan Pendidikan')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / SDM / Tunjangan Pendidikan / Input Baru</div>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0;">INPUT TUNJANGAN PENDIDIKAN (2026)</h1>
            <p style="margin:4px 0 0 0; color:#64748B; font-size:13px;">Format resmi porsi Gaji Pokok dan Tunjangan Keluarga untuk Tunjangan Pendidikan Pegawai.</p>
        </div>
        <a href="{{ route('tunjangan-pendidikan.index') }}" class="btn btn-outline" style="font-size:13px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali ke Daftar
        </a>
    </div>
</div>

<form method="POST" action="{{ route('tunjangan-pendidikan.store') }}" id="tunjangan-pendidikan-form">
    @csrf

    <!-- Card 1: Data Pegawai & Periode -->
    <div class="form-card" style="max-width:100%; margin-bottom:16px;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:15px; margin:0 0 16px; color:#0F2A3D; display:flex; align-items:center; gap:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="color:#0284C7;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Data Pegawai & Periode
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
                <input type="text" name="bulan_label" id="bulan_label" class="form-control" value="{{ old('bulan_label', $defaultBulanLabel) }}" placeholder="Contoh: JULI 2026" required>
            </div>

            <div class="form-group">
                <label style="font-weight:600;">Status Keluarga & Hak Tunjangan</label>
                <input type="text" id="info_keluarga" class="form-control" value="Pilih pegawai terlebih dahulu" disabled style="background:#F8FAFC; color:#475569;">
            </div>
        </div>
    </div>

    <!-- Card 2: Format Tunjangan Pendidikan (Persis Format Gambar 1) -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:16px; margin-bottom:16px;">
        <!-- Kolom Kiri: Pendapatan -->
        <div class="form-card" style="margin:0;">
            <h3 style="font-family:'Space Grotesk',sans-serif; font-size:15px; margin:0 0 16px; color:#15803D; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Komponen Pendapatan (Pokok & Keluarga)
            </h3>
            
            <div class="form-group" style="margin-bottom:14px;">
                <label for="gapok" style="font-weight:600;">GAPOK (Gaji Pokok) <span style="color:#DC2626;">*</span></label>
                <input type="number" id="gapok" name="gapok" min="0" step="1" value="{{ old('gapok', 0) }}" class="form-control calc-pendapatan" required oninput="hitungTotal()">
            </div>

            <div class="form-group" style="margin-bottom:14px;">
                <label for="tunjangan_istri" style="font-weight:600;">TUNJANGAN ISTRI / SUAMI (10%)</label>
                <input type="number" id="tunjangan_istri" name="tunjangan_istri" min="0" step="1" value="{{ old('tunjangan_istri', 0) }}" class="form-control calc-pendapatan" oninput="hitungTotal()">
            </div>

            <div class="form-group" style="margin-bottom:14px;">
                <label for="tunjangan_anak" style="font-weight:600;">TUNJANGAN ANAK (5% / anak, maks 2)</label>
                <input type="number" id="tunjangan_anak" name="tunjangan_anak" min="0" step="1" value="{{ old('tunjangan_anak', 0) }}" class="form-control calc-pendapatan" oninput="hitungTotal()">
            </div>

            <div style="margin-top:18px; padding:14px; background:#F0FDF4; border-radius:10px; border:1px solid #BBF7D0; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:700; color:#166534; font-size:13.5px;">JUMLAH PENDAPATAN:</span>
                <span style="font-family:'IBM Plex Mono',monospace; font-size:17px; font-weight:700; color:#15803D;" id="display-jumlah-pendapatan">Rp 0</span>
            </div>
        </div>

        <!-- Kolom Kanan: Potongan Non-Insentif -->
        <div class="form-card" style="margin:0;">
            <h3 style="font-family:'Space Grotesk',sans-serif; font-size:15px; margin:0 0 16px; color:#B45309; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Potongan Non-Insentif
            </h3>

            <div class="form-group" style="margin-bottom:14px;">
                <label for="potongan_koperasi" style="font-weight:600;">POTONGAN KOPERASI</label>
                <input type="number" id="potongan_koperasi" name="potongan_koperasi" min="0" step="1" value="{{ old('potongan_koperasi', 0) }}" class="form-control calc-potongan" oninput="hitungTotal()">
            </div>

            <div class="form-group" style="margin-bottom:14px;">
                <label for="potongan_kas" style="font-weight:600;">POTONGAN KAS</label>
                <input type="number" id="potongan_kas" name="potongan_kas" min="0" step="1" value="{{ old('potongan_kas', 0) }}" class="form-control calc-potongan" oninput="hitungTotal()">
            </div>

            <div style="margin-top:18px; padding:14px; background:#FFFBEB; border-radius:10px; border:1px solid #FDE68A; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:700; color:#92400E; font-size:13.5px;">JUMLAH POTONGAN:</span>
                <span style="font-family:'IBM Plex Mono',monospace; font-size:17px; font-weight:700; color:#B45309;" id="display-jumlah-potongan">Rp 0</span>
            </div>
        </div>
    </div>

    <!-- Ringkasan Bersih Diterima -->
    <div class="form-card" style="max-width:100%; background:linear-gradient(135deg, #0F2A3D 0%, #16384C 100%); color:#ffffff; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <div style="font-size:13px; text-transform:uppercase; letter-spacing:0.5px; opacity:0.8;">JUMLAH INSENTIF DITERIMA (BERSIH)</div>
                <div style="font-size:12px; opacity:0.7; margin-top:2px;">(Jumlah Pendapatan dikurangi Jumlah Potongan Non-Insentif)</div>
            </div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:26px; font-weight:700; color:#34D399;" id="display-jumlah-diterima">
                Rp 0
            </div>
        </div>
    </div>

    <!-- Tombol Simpan -->
    <div style="display:flex; gap:12px; justify-content:flex-end;">
        <a href="{{ route('tunjangan-pendidikan.index') }}" class="btn btn-outline" style="padding:10px 20px;">Batal</a>
        <button type="submit" class="btn btn-success" style="padding:10px 24px; font-weight:700; font-size:14px; box-shadow:0 4px 12px rgba(22, 163, 74, 0.25);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Simpan & Terbitkan Slip
        </button>
    </div>
</form>

<script>
const hitungKeluargaBaseUrl = "{{ url('tunjangan-pendidikan/ajax/hitung-keluarga') }}";

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function hitungTotal() {
    const gapok = parseFloat(document.getElementById('gapok').value) || 0;
    const tIstri = parseFloat(document.getElementById('tunjangan_istri').value) || 0;
    const tAnak = parseFloat(document.getElementById('tunjangan_anak').value) || 0;
    const jmlPendapatan = gapok + tIstri + tAnak;

    const potKoperasi = parseFloat(document.getElementById('potongan_koperasi').value) || 0;
    const potKas = parseFloat(document.getElementById('potongan_kas').value) || 0;
    const jmlPotongan = potKoperasi + potKas;

    const diterima = Math.max(0, jmlPendapatan - jmlPotongan);

    document.getElementById('display-jumlah-pendapatan').textContent = formatRupiah(jmlPendapatan);
    document.getElementById('display-jumlah-potongan').textContent = formatRupiah(jmlPotongan);
    document.getElementById('display-jumlah-diterima').textContent = formatRupiah(diterima);
}

function onPegawaiSelected(nik) {
    if (!nik) {
        document.getElementById('info_keluarga').value = 'Pilih pegawai terlebih dahulu';
        return;
    }

    const select = document.getElementById('pegawai_select');
    const selectedOption = select.options[select.selectedIndex];
    document.getElementById('pegawai_id').value = selectedOption.getAttribute('data-id') || '';

    document.getElementById('info_keluarga').value = 'Mengambil data keluarga...';

    fetch(hitungKeluargaBaseUrl + '/' + encodeURIComponent(nik), {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;

        const info = (data.kawin ? 'Kawin (1 Istri)' : 'Belum Kawin') + ', Anak Tunjangan: ' + data.jml_anak;
        document.getElementById('info_keluarga').value = info;

        if (data.gapok > 0) {
            document.getElementById('gapok').value = data.gapok;
            document.getElementById('tunjangan_istri').value = data.tunjangan_istri;
            document.getElementById('tunjangan_anak').value = data.tunjangan_anak;
        }

        hitungTotal();
    })
    .catch(err => {
        console.warn(err);
        document.getElementById('info_keluarga').value = 'Gagal memuat data keluarga otomatis';
    });
}

document.getElementById('gapok').addEventListener('input', function() {
    const gapok = parseFloat(this.value) || 0;
    // Auto-update proporsi tunjangan istri & anak jika ada
    const infoStr = document.getElementById('info_keluarga').value || '';
    if (infoStr.includes('Kawin')) {
        document.getElementById('tunjangan_istri').value = Math.round(gapok * 0.1);
    }
    hitungTotal();
});

hitungTotal();
</script>
@endsection
