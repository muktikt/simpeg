@extends('layouts.app')

@section('title', $pageTitle ?? 'Proses Gaji Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Proses Gaji Bulanan / {{ $pageTitle ?? 'Proses Gaji Pegawai' }}</div>
    <h1>{{ $pageTitle ?? 'Proses Gaji Pegawai' }}</h1>
</div>

<form method="POST" action="{{ route('gaji-proses.store') }}" id="gaji-form">
    @csrf

    <div class="form-card" style="max-width:100%;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:14.5px; margin:0 0 14px;">Data Pegawai & Periode</h3>
        <div class="form-grid">
            <div class="form-group span-2">
                <label for="pegawai_id">Pegawai</label>
                <select id="pegawai_id" name="pegawai_id" required onchange="onPegawaiChange()">
                    @if (count($pegawaiList) > 1)
                        <option value="">-- Pilih Pegawai --</option>
                    @endif
                    @foreach ($pegawaiList as $p)
                        <option value="{{ $p['id'] }}" @selected((string) old('pegawai_id', $selectedPegawaiId ?? '') === (string) $p['id'])>{{ $p['nik'] }} - {{ $p['nama'] }} ({{ $p['jabatan'] }})</option>
                    @endforeach
                </select>
                @error('pegawai_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="kategori">Kategori Pegawai</label>
                <select id="kategori" name="kategori" required onchange="onPegawaiChange()">
                    @foreach ($kategoriList as $val => $label)
                        <option value="{{ $val }}" @selected(old('kategori', $selectedKategori ?? 'satuan') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('kategori') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label>Info Keluarga (otomatis)</label>
                <input type="text" id="info-keluarga" value="Pilih pegawai dulu" disabled>
            </div>
            <div class="form-group">
                <label for="bulan">Bulan</label>
                <select id="bulan" name="bulan" required onchange="onPegawaiChange()">
                    @foreach ($bulanList ?? \App\Http\Controllers\AbsensiController::BULAN as $val => $label)
                        <option value="{{ $val }}" @selected((int) old('bulan', now()->month) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="tahun">Tahun</label>
                <input type="number" id="tahun" name="tahun" value="{{ old('tahun', now()->year) }}" required onchange="onPegawaiChange()">
            </div>
        </div>
        <div id="penalti-info-container" style="display:none; margin-top:14px; padding:12px 16px; border-radius:8px; border:1px solid #cbd5e1; background:#f8fafc; font-size:12.5px;"></div>
    </div>

    <div class="form-card" style="max-width:100%; margin-top:16px;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:14.5px; margin:0 0 14px;">Komponen Pendapatan</h3>
        <div class="form-grid">
            @foreach ($komponenPendapatan as $key => $label)
                <div class="form-group">
                    <label for="{{ $key }}">
                        {{ $label }}
                        @if($key === 'lembur')
                            <span style="font-size:11px; font-weight:normal; color:var(--teal, #0f766e);">(Otomatis dari Prestasi SDM)</span>
                        @elseif($key === 'tunjangan_pajak')
                            <span id="info-pajak-pendapatan" style="font-size:11px; font-weight:normal; color:var(--teal, #0f766e);">(Gross-Up TER PP 58)</span>
                        @elseif($key === 'tunjangan_bpjstk')
                            <span style="font-size:11px; font-weight:normal; color:var(--teal, #0f766e);">(6.89% Perusahaan)</span>
                        @elseif($key === 'tunjangan_bpjskes')
                            <span style="font-size:11px; font-weight:normal; color:var(--teal, #0f766e);">(4% Perusahaan)</span>
                        @endif
                    </label>
                    <input type="number" id="{{ $key }}" name="{{ $key }}" min="0" step="1" value="{{ old($key, 0) }}" class="komponen-pendapatan" oninput="onKomponenChange('{{ $key }}')" @if($key === 'lembur') readonly style="background-color: var(--card-bg, #f1f5f9); cursor: not-allowed;" title="Diambil otomatis dari Modul Set Prestasi Pegawai (Rate: Rp 9.375/jam)" @endif>
                </div>
            @endforeach
        </div>
        <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border); display:flex; justify-content:flex-end;">
            <div style="font-size:13.5px;">Total Pendapatan: <strong id="total-pendapatan-display">Rp 0</strong></div>
        </div>
    </div>

    <div class="form-card" style="max-width:100%; margin-top:16px;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:14.5px; margin:0 0 14px;">Komponen Potongan</h3>
        <div class="form-grid">
            @foreach ($komponenPotongan as $key => $label)
                <div class="form-group">
                    <label for="{{ $key }}">
                        {{ $label }}
                        @if($key === 'potongan_pajak')
                            <span id="info-pajak-potongan" style="font-size:11px; font-weight:normal; color:#dc2626;">(Otomatis PPh 21)</span>
                        @elseif($key === 'potongan_bpjstk')
                            <span style="font-size:11px; font-weight:normal; color:#dc2626;">(9.89%: 6.89% + 3%)</span>
                        @elseif($key === 'potongan_bpjskes')
                            <span style="font-size:11px; font-weight:normal; color:#dc2626;">(5%: 4% + 1%)</span>
                        @endif
                    </label>
                    <input type="number" id="{{ $key }}" name="{{ $key }}" min="0" step="1" value="{{ old($key, 0) }}" class="komponen-potongan" oninput="hitungTotal()">
                </div>
            @endforeach
        </div>
        <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border); display:flex; justify-content:flex-end;">
            <div style="font-size:13.5px;">Total Potongan: <strong id="total-potongan-display">Rp 0</strong></div>
        </div>
    </div>

    <div class="form-card" id="gaji-bersih-card" style="max-width:100%; margin-top:16px; background:var(--teal-soft); transition: background-color 0.2s ease;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:600;">Gaji Bersih (Take Home Pay)</div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:22px; font-weight:600; color:var(--teal-dark);" id="gaji-bersih-display">Rp 0</div>
        </div>
        <div id="gaji-minus-alert" style="display:none; margin-top:12px; padding:10px 14px; background:#fef2f2; border:1px solid #f87171; border-radius:6px; color:#991b1b; font-size:13px; font-weight:600;">
            Peringatan: Gaji Bersih Minus! Total potongan lebih besar dari total pendapatan. Silakan verifikasi kembali komponen potongan pegawai!
        </div>
    </div>

    <div class="form-actions" style="max-width:100%;">
        <button type="submit" class="btn btn-primary">Simpan sebagai Draft</button>
        <a href="{{ route('gaji-proses.index') }}" class="btn btn-outline">Batal</a>
    </div>
</form>

<script>
const pegawaiKeluargaUrl = @json(route('gaji-proses.hitung-keluarga', ['pegawaiId' => '__ID__'], false));
let currentKeluargaData = null;

function updateTunjanganKeluarga() {
    if (!currentKeluargaData) return;
    const gapokField = document.getElementById('gapok');
    const gapok = parseFloat(gapokField ? gapokField.value : 0) || 0;

    const tunjIstriField = document.getElementById('tunjangan_istri');
    const tunjAnakField = document.getElementById('tunjangan_anak');
    const jmlAnak = currentKeluargaData.jml_anak || 0;
    const kategori = document.getElementById('kategori')?.value || 'satuan';

    if (currentKeluargaData.is_direksi) {
        // Formula Direksi: Istri 10%, Anak 5% per anak (maks 2), Transport 25%, BPJS TK & Kes
        if (tunjIstriField) {
            tunjIstriField.value = currentKeluargaData.kawin ? Math.floor(gapok * 0.1) : 0;
        }
        if (tunjAnakField) {
            tunjAnakField.value = jmlAnak > 0 ? Math.floor(gapok * 0.05 * jmlAnak) : 0;
        }
        const tunjTrans = document.getElementById('tunjangan_transport');
        if (tunjTrans) tunjTrans.value = Math.floor(gapok * 0.25);

        const tunjBpjstk = document.getElementById('tunjangan_bpjstk');
        if (tunjBpjstk) tunjBpjstk.value = Math.round(gapok * 0.0689);

        const laporBpjs = Math.min(gapok + parseFloat(tunjIstriField?.value || 0) + parseFloat(tunjAnakField?.value || 0), 12000000);
        const tunjBpjskes = document.getElementById('tunjangan_bpjskes');
        if (tunjBpjskes) tunjBpjskes.value = Math.round(laporBpjs * 0.04);

        const potBpjstk = document.getElementById('potongan_bpjstk');
        if (potBpjstk) potBpjstk.value = Math.round(gapok * 0.0689 + gapok * 0.03);

        const potBpjskes = document.getElementById('potongan_bpjskes');
        if (potBpjskes) potBpjskes.value = Math.round(laporBpjs * 0.05);
    } else if (kategori === 'capeg') {
        // Capeg: Istri 10%, Anak 5% per anak, Pangan 80% (Rp 96.000/jiwa), Perumahan 0
        if (tunjIstriField) {
            tunjIstriField.value = currentKeluargaData.kawin ? Math.floor(gapok * 0.1) : 0;
        }
        if (tunjAnakField) {
            tunjAnakField.value = jmlAnak > 0 ? Math.floor(gapok * 0.05 * jmlAnak) : 0;
        }
        const tunjPangan = document.getElementById('tunjangan_pangan');
        if (tunjPangan) tunjPangan.value = Math.floor(120000 * (jmlAnak + (currentKeluargaData.kawin ? 1 : 0) + 1) * 0.8);
        const tunjPerum = document.getElementById('tunjangan_perumahan');
        if (tunjPerum) tunjPerum.value = 0;
    } else if (['honor', 'kontrak', 'tt'].includes(kategori)) {
        // Honorer, Kontrak, TT: Tidak ada tunjangan keluarga, perumahan, pangan, prestasi
        if (tunjIstriField) tunjIstriField.value = 0;
        if (tunjAnakField) tunjAnakField.value = 0;
        const tunjPangan = document.getElementById('tunjangan_pangan');
        if (tunjPangan) tunjPangan.value = 0;
        const tunjPerum = document.getElementById('tunjangan_perumahan');
        if (tunjPerum) tunjPerum.value = 0;
    } else {
        // Pegawai Tetap (Satuan): Istri 10%, Anak 2% per anak, Pangan Rp 120.000/jiwa
        if (tunjIstriField) {
            tunjIstriField.value = currentKeluargaData.kawin ? Math.round(gapok * 0.1) : 0;
        }
        if (tunjAnakField) {
            tunjAnakField.value = jmlAnak > 0 ? Math.round(gapok * 0.02 * jmlAnak) : 0;
        }
        const tunjPangan = document.getElementById('tunjangan_pangan');
        if (tunjPangan) tunjPangan.value = 120000 * (jmlAnak + (currentKeluargaData.kawin ? 1 : 0) + 1);
    }
}

async function onPegawaiChange() {
    const pegawaiId = document.getElementById('pegawai_id').value;
    const info = document.getElementById('info-keluarga');

    if (!pegawaiId) {
        info.value = 'Pilih pegawai dulu';
        currentKeluargaData = null;
        return;
    }

    info.value = 'Menghitung...';

    const bulan = document.getElementById('bulan')?.value || '';
    const tahun = document.getElementById('tahun')?.value || '';
    const kategori = document.getElementById('kategori')?.value || '';
    const url = pegawaiKeluargaUrl.replace('__ID__', pegawaiId) + '?bulan=' + encodeURIComponent(bulan) + '&tahun=' + encodeURIComponent(tahun) + '&kategori=' + encodeURIComponent(kategori);

    try {
        const res = await fetch(url);
        if (!res.ok) {
            throw new Error('HTTP ' + res.status);
        }
        const data = await res.json();
        currentKeluargaData = data;

        let infoAnak = 'Anak: ' + (data.total_anak !== undefined ? data.total_anak : (data.jml_anak || 0));
        if (data.total_anak !== undefined && data.jml_anak !== undefined && data.total_anak > data.jml_anak) {
            infoAnak = 'Total Anak: ' + data.total_anak + ' (' + data.jml_anak + ' dapat tunjangan)';
        }
        let direksiLabel = data.is_direksi ? ' [DIREKSI: ' + (data.direksi_role || 'Direksi').toUpperCase() + '] ' : '';
        info.value = direksiLabel + (data.kawin ? 'Kawin' : 'Belum Kawin') + ', ' + infoAnak + ', PTKP: ' + (data.kode_ptkp || '-');

        // Auto-isi komponen pendapatan dan potongan sesuai formula kategori
        if (data.auto_komponen) {
            for (const [key, val] of Object.entries(data.auto_komponen)) {
                const el = document.getElementById(key);
                if (el && val !== undefined && val !== null) {
                    el.value = val;
                }
            }
        } else if (data.is_direksi) {
            if (data.gapok_direksi !== undefined && data.gapok_direksi !== null) {
                const el = document.getElementById('gapok');
                if (el) el.value = data.gapok_direksi;
            }
            if (data.tunj_transport_direksi !== undefined && data.tunj_transport_direksi !== null) {
                const el = document.getElementById('tunjangan_transport');
                if (el) el.value = data.tunj_transport_direksi;
            }
            if (data.tunj_istri_direksi !== undefined && data.tunj_istri_direksi !== null) {
                const el = document.getElementById('tunjangan_istri');
                if (el) el.value = data.tunj_istri_direksi;
            }
            if (data.tunj_anak_direksi !== undefined && data.tunj_anak_direksi !== null) {
                const el = document.getElementById('tunjangan_anak');
                if (el) el.value = data.tunj_anak_direksi;
            }
            if (data.tunj_bpjstk_direksi !== undefined && data.tunj_bpjstk_direksi !== null) {
                const el = document.getElementById('tunjangan_bpjstk');
                if (el) el.value = data.tunj_bpjstk_direksi;
            }
            if (data.tunj_bpjskes_direksi !== undefined && data.tunj_bpjskes_direksi !== null) {
                const el = document.getElementById('tunjangan_bpjskes');
                if (el) el.value = data.tunj_bpjskes_direksi;
            }
            if (data.pot_bpjstk_direksi !== undefined && data.pot_bpjstk_direksi !== null) {
                const el = document.getElementById('potongan_bpjstk');
                if (el) el.value = data.pot_bpjstk_direksi;
            }
            if (data.pot_bpjskes_direksi !== undefined && data.pot_bpjskes_direksi !== null) {
                const el = document.getElementById('potongan_bpjskes');
                if (el) el.value = data.pot_bpjskes_direksi;
            }
        } else {
            updateTunjanganKeluarga();
        }

        // Auto-isi Lembur dari modul Prestasi SDM
        const lemburField = document.getElementById('lembur');
        if (lemburField && data.lembur !== undefined) {
            lemburField.value = data.lembur;
        }

        // Auto-isi Potongan dari Keuangan (Koperasi, Darmawanita, Ledeng, Kas, Bank, Zakat, dll)
        if (data.potongan_keu) {
            const pk = data.potongan_keu;
            const mapFields = {
                'potongan_koperasi': pk.pot_koperasi,
                'potongan_darmawanita': pk.pot_darmawanita,
                'potongan_ledeng': pk.pot_air,
                'potongan_kas': pk.pot_kas,
                'potongan_bjb': pk.pot_bjb,
                'potongan_bjbs': pk.pot_bjbs,
                'potongan_asuransi': pk.pot_asuransi,
                'potongan_btn': pk.pot_btn,
                'potongan_bpr': pk.pot_bpr,
                'potongan_zakat': pk.pot_zakat_profesi,
            };
            for (const [fieldId, val] of Object.entries(mapFields)) {
                const el = document.getElementById(fieldId);
                if (el && val !== undefined && val !== null) {
                    el.value = val;
                }
            }
        }

        // Tampilkan info penalti absensi, sanksi disiplin, dan kepesertaan Dapenma
        const penaltiBox = document.getElementById('penalti-info-container');
        if (penaltiBox) {
            const hasPenalti = (data.info_penalti && data.info_penalti.has_penalti);
            const hasSanksi = (data.info_sanksi && data.info_sanksi.has_sanksi);
            const isDapenma = (data.info_dapenma && data.info_dapenma.is_peserta);

            if (hasPenalti || hasSanksi || isDapenma) {
                let html = '';

                if (hasSanksi) {
                    const is = data.info_sanksi;
                    html += '<div style="font-weight:600; color:#b91c1c; margin-bottom:6px; display:flex; align-items:center; gap:6px;">';
                    html += '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
                    html += 'Peringatan Sanksi Disiplin Pegawai (Modul Sanksi):</div>';
                    html += '<ul style="margin:0 0 10px 0; padding-left:18px; color:#475569; display:flex; flex-direction:column; gap:4px;">';
                    html += '<li>Jenis Sanksi: <strong style="color:#b91c1c;">' + (is.detail ? is.detail.jenis_sanksi : 'Sanksi Disiplin') + '</strong>' + (is.detail && is.detail.tingkat ? ' (Tingkat: ' + is.detail.tingkat + ')' : '') + (is.detail && is.detail.nomor_sk && is.detail.nomor_sk !== '-' ? ' &bull; SK: ' + is.detail.nomor_sk : '') + '</li>';
                    if (is.persen > 0) {
                        html += '<li>Potongan Persentase: <strong>' + is.persen + '%</strong> dari Penghasilan Pokok</li>';
                    }
                    html += '<li>Nominal Potongan Sanksi: <strong style="color:#b91c1c; font-size:14px;">Rp ' + Math.round(is.potongan).toLocaleString('id-ID') + '</strong> (Otomatis masuk ke Potongan Sanksi)</li>';
                    html += '</ul>';
                }

                if (hasPenalti) {
                    const ip = data.info_penalti;
                    html += '<div style="font-weight:600; color:#b45309; margin-bottom:6px;">Penalti Kehadiran & Prestasi Terdeteksi (Modul SDM):</div>';
                    html += '<ul style="margin:0' + (isDapenma ? ' 0 10px 0' : '') + '; padding-left:18px; color:#475569; display:flex; flex-direction:column; gap:4px;">';
                    if (ip.pot_transport > 0 || ip.persen_kehadiran > 0) {
                        html += '<li>Potongan T. Transportasi: <strong style="color:#b91c1c;">Rp ' + Math.round(ip.pot_transport).toLocaleString('id-ID') + '</strong> (Penalti: ' + ip.persen_kehadiran + '% &bull; Alpha: ' + ip.alpha + ' hr, Izin: ' + (ip.izin_ket + ip.izin_non_ket) + ' hr, Sakit: ' + (ip.sakit_ket + ip.sakit_non_ket) + ' hr) &rarr; Sisa Transport: <strong>Rp ' + Math.round(ip.calc_transport).toLocaleString('id-ID') + '</strong></li>';
                    }
                    if (ip.pot_prestasi > 0 || ip.persen_telat > 0 || ip.prestasi_hangus) {
                        html += '<li>Tunjangan Prestasi: Skor Akhir <strong>' + ip.skor_prestasi + '%</strong> (Keterlambatan: ' + ip.telat + 'x [-' + ip.persen_telat + '%]) &rarr; Sisa Prestasi: <strong>Rp ' + Math.round(ip.calc_prestasi).toLocaleString('id-ID') + '</strong></li>';
                    }
                    if (ip.prestasi_hangus) {
                        html += '<li style="color:#b91c1c; font-weight:600; margin-top:2px;">Perhatian: Skor prestasi akhir &le; 30%, maka tunjangan prestasi hangus (Rp 0) sesuai aturan SK Direksi.</li>';
                    }
                    html += '</ul>';
                }

                if (isDapenma) {
                    const idp = data.info_dapenma;
                    html += '<div style="font-weight:600; color:#0369a1; margin-bottom:6px; display:flex; align-items:center; gap:6px;">';
                    html += '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
                    html += 'Kepesertaan DAPENMA Terdaftar (Master Asuransi PHDP):</div>';
                    html += '<ul style="margin:0; padding-left:18px; color:#475569; display:flex; flex-direction:column; gap:4px;">';
                    html += '<li>No. Peserta: <strong>' + (idp.nomor_peserta || '-') + '</strong> &bull; Nominal PHDP: <strong>Rp ' + Math.round(idp.nominal_phdp).toLocaleString('id-ID') + '</strong></li>';
                    html += '<li>Potongan Beban Pegawai (5%): <strong style="color:#0284c7; font-size:14px;">Rp ' + Math.round(idp.nominal_beban).toLocaleString('id-ID') + '</strong> (Otomatis masuk ke Potongan Dapenma)</li>';
                    html += '</ul>';
                }

                penaltiBox.innerHTML = html;
                penaltiBox.style.display = 'block';
                if (hasSanksi || (hasPenalti && data.info_penalti.prestasi_hangus)) {
                    penaltiBox.style.borderColor = '#f87171';
                    penaltiBox.style.background = '#fff1f2';
                } else if (hasPenalti) {
                    penaltiBox.style.borderColor = '#f59e0b';
                    penaltiBox.style.background = '#fffbeb';
                } else {
                    penaltiBox.style.borderColor = '#38bdf8';
                    penaltiBox.style.background = '#f0f9ff';
                }
            } else {
                penaltiBox.style.display = 'none';
                penaltiBox.innerHTML = '';
            }
        }

        // Tampilkan info pajak TER Gross-Up jika ada
        if (data.info_pajak) {
            const lblPend = document.getElementById('info-pajak-pendapatan');
            const lblPot = document.getElementById('info-pajak-potongan');
            const txt = '(' + data.info_pajak.kategori_ter + ': ' + data.info_pajak.persen_label + ' Gross-Up)';
            if (lblPend) lblPend.textContent = txt;
            if (lblPot) lblPot.textContent = txt;
        }

        hitungTotal();
    } catch (e) {
        console.error('Error hitung data keluarga:', e);
        info.value = 'Gagal menghitung data keluarga';
    }
}

const TER_A_JS = [
    [5400000, 0.0], [5650000, 0.0025], [5950000, 0.005], [6300000, 0.0075], [6750000, 0.01],
    [7500000, 0.0125], [8550000, 0.015], [9650000, 0.0175], [10050000, 0.02], [10350000, 0.0225],
    [10700000, 0.025], [11050000, 0.03], [11600000, 0.035], [12500000, 0.04], [13750000, 0.05],
    [15100000, 0.06], [16950000, 0.07], [19750000, 0.08], [24150000, 0.09], [26450000, 0.10],
    [28000000, 0.11], [30050000, 0.12], [32400000, 0.13], [35400000, 0.14], [39100000, 0.15],
    [43850000, 0.16], [47800000, 0.17], [51400000, 0.18], [56300000, 0.19], [62200000, 0.20],
    [68600000, 0.21], [77500000, 0.22], [89000000, 0.23], [103000000, 0.24], [125000000, 0.25],
    [157000000, 0.26], [206000000, 0.27], [337000000, 0.28], [454000000, 0.29], [550000000, 0.30],
    [695000000, 0.31], [910000000, 0.32], [1400000000, 0.33], [Infinity, 0.34]
];

const TER_B_JS = [
    [6200000, 0.0], [6500000, 0.0025], [6850000, 0.005], [7300000, 0.0075], [9200000, 0.01],
    [10750000, 0.015], [11250000, 0.02], [11600000, 0.025], [12600000, 0.03], [13600000, 0.04],
    [14950000, 0.05], [16400000, 0.06], [18450000, 0.07], [21850000, 0.08], [26000000, 0.09],
    [27700000, 0.10], [29350000, 0.11], [31450000, 0.12], [33950000, 0.13], [37100000, 0.14],
    [41100000, 0.15], [45800000, 0.16], [49500000, 0.17], [53800000, 0.18], [58500000, 0.19],
    [64000000, 0.20], [71000000, 0.21], [80000000, 0.22], [93000000, 0.23], [109000000, 0.24],
    [129000000, 0.25], [163000000, 0.26], [211000000, 0.27], [374000000, 0.28], [459000000, 0.29],
    [555000000, 0.30], [704000000, 0.31], [957000000, 0.32], [1405000000, 0.33], [Infinity, 0.34]
];

const TER_C_JS = [
    [6600000, 0.0], [6950000, 0.0025], [7350000, 0.005], [7800000, 0.0075], [8850000, 0.01],
    [10900000, 0.0125], [11200000, 0.015], [12050000, 0.02], [12950000, 0.03], [14150000, 0.04],
    [15550000, 0.05], [17050000, 0.06], [19500000, 0.07], [22700000, 0.08], [26600000, 0.09],
    [28100000, 0.10], [30100000, 0.11], [32600000, 0.12], [35400000, 0.13], [38900000, 0.14],
    [43000000, 0.15], [47400000, 0.16], [51200000, 0.17], [55800000, 0.18], [60400000, 0.19],
    [66700000, 0.20], [74500000, 0.21], [83200000, 0.22], [95600000, 0.23], [110000000, 0.24],
    [134000000, 0.25], [169500000, 0.26], [221000000, 0.27], [390000000, 0.28], [463000000, 0.29],
    [561000000, 0.30], [709000000, 0.31], [965000000, 0.32], [1419000000, 0.33], [Infinity, 0.34]
];

function getTerRateJs(kat, inc) {
    const tbl = (kat === 'TER_B') ? TER_B_JS : ((kat === 'TER_C') ? TER_C_JS : TER_A_JS);
    for (const [limit, rate] of tbl) {
        if (inc <= limit) return rate;
    }
    return 0.34;
}

function getKatTerJs(ptkp) {
    const c = (ptkp || '').toUpperCase().replace(/[\/\-\s]/g, '');
    if (['TK', 'TK0', 'TK1', 'K0', 'K'].includes(c)) return 'TER_A';
    if (['TK2', 'TK3', 'K1', 'K2'].includes(c)) return 'TER_B';
    if (c === 'K3') return 'TER_C';
    return 'TER_A';
}

function recalcPph21GrossUp() {
    if (!currentKeluargaData) return;
    const ptkp = currentKeluargaData.kode_ptkp || 'TK';
    const katTer = getKatTerJs(ptkp);

    let brutoSebelumPajak = 0;
    document.querySelectorAll('.komponen-pendapatan').forEach(el => {
        if (el.id !== 'tunjangan_pajak') {
            brutoSebelumPajak += parseFloat(el.value) || 0;
        }
    });

    const rate1 = getTerRateJs(katTer, brutoSebelumPajak);
    const brutoEstimasi = brutoSebelumPajak + (brutoSebelumPajak * rate1);
    const rateFinal = getTerRateJs(katTer, brutoEstimasi);

    let pph = 0;
    if (rateFinal > 0 && rateFinal < 1) {
        pph = Math.round((brutoSebelumPajak * rateFinal) / (1 - rateFinal));
    }

    const tPajakEl = document.getElementById('tunjangan_pajak');
    const pPajakEl = document.getElementById('potongan_pajak');
    if (tPajakEl) tPajakEl.value = pph;
    if (pPajakEl) pPajakEl.value = pph;

    const lblPend = document.getElementById('info-pajak-pendapatan');
    const lblPot = document.getElementById('info-pajak-potongan');
    const txt = '(' + katTer + ': ' + (rateFinal * 100).toFixed(1) + '% Gross-Up)';
    if (lblPend) lblPend.textContent = txt;
    if (lblPot) lblPot.textContent = txt;
}

function updateBpjsJs() {
    const gapokVal = parseFloat(document.getElementById('gapok')?.value) || 0;
    const kategori = document.getElementById('kategori')?.value || 'satuan';
    if (['dirut', 'dirum', 'dirtek'].includes(kategori)) return;

    const umk = 2794237;
    const gapokTk = Math.max(gapokVal, umk);
    const tBpjstk = Math.round(gapokTk * 0.0689);
    const pBpjstk = Math.round(gapokTk * (0.0689 + 0.03));

    const tIstri = parseFloat(document.getElementById('tunjangan_istri')?.value) || 0;
    const tAnak = parseFloat(document.getElementById('tunjangan_anak')?.value) || 0;
    const tPangan = parseFloat(document.getElementById('tunjangan_pangan')?.value) || 0;
    const tJab = parseFloat(document.getElementById('tunjangan_jabatan')?.value) || 0;
    const tPerusahaan = 300000;

    let upahLaporKes = gapokVal + tIstri + tAnak + tPangan;
    if (kategori === 'satuan') upahLaporKes += (tJab + tPerusahaan);
    const laporKes = Math.min(upahLaporKes, 12000000);

    const faktor = (kategori === 'capeg') ? 0.8 : 1.0;
    const tBpjskes = Math.round((laporKes * 0.04) * faktor);
    const pBpjskes = Math.round((laporKes * 0.05) * faktor);

    const elTBpjstk = document.getElementById('tunjangan_bpjstk');
    const elPBpjstk = document.getElementById('potongan_bpjstk');
    const elTBpjskes = document.getElementById('tunjangan_bpjskes');
    const elPBpjskes = document.getElementById('potongan_bpjskes');

    if (elTBpjstk) elTBpjstk.value = tBpjstk;
    if (elPBpjstk) elPBpjstk.value = pBpjstk;
    if (elTBpjskes) elTBpjskes.value = tBpjskes;
    if (elPBpjskes) elPBpjskes.value = pBpjskes;
}

function onKomponenChange(key) {
    if (key === 'gapok') {
        updateTunjanganKeluarga();
        updateBpjsJs();
    }
    if (['gapok', 'tunjangan_istri', 'tunjangan_anak', 'tunjangan_pangan', 'tunjangan_jabatan'].includes(key)) {
        updateBpjsJs();
    }
    if (key !== 'tunjangan_pajak') {
        recalcPph21GrossUp();
    }
    hitungTotal();
}

function formatRupiah(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function hitungTotal() {
    let totalPendapatan = 0;
    document.querySelectorAll('.komponen-pendapatan').forEach(el => {
        totalPendapatan += parseFloat(el.value) || 0;
    });

    let totalPotongan = 0;
    document.querySelectorAll('.komponen-potongan').forEach(el => {
        totalPotongan += parseFloat(el.value) || 0;
    });

    const gajiBersih = totalPendapatan - totalPotongan;
    document.getElementById('total-pendapatan-display').textContent = formatRupiah(totalPendapatan);
    document.getElementById('total-potongan-display').textContent = formatRupiah(totalPotongan);

    const displayEl = document.getElementById('gaji-bersih-display');
    displayEl.textContent = formatRupiah(gajiBersih);

    const minusAlert = document.getElementById('gaji-minus-alert');
    const cardEl = document.getElementById('gaji-bersih-card');

    if (gajiBersih < 0) {
        displayEl.style.color = '#dc2626';
        if (cardEl) cardEl.style.background = '#fef2f2';
        if (minusAlert) {
            minusAlert.style.display = 'block';
            minusAlert.textContent = 'Peringatan: Gaji Bersih Minus (' + formatRupiah(gajiBersih) + ')! Total potongan (' + formatRupiah(totalPotongan) + ') melebihi total pendapatan (' + formatRupiah(totalPendapatan) + '). Silakan verifikasi kembali rincian potongan pegawai!';
        }
    } else {
        displayEl.style.color = 'var(--teal-dark)';
        if (cardEl) cardEl.style.background = 'var(--teal-soft)';
        if (minusAlert) {
            minusAlert.style.display = 'none';
        }
    }
}

document.getElementById('gapok')?.addEventListener('input', function() {
    onKomponenChange('gapok');
});

// Auto-trigger jika pegawai sudah terisi pada saat form dimuat
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('pegawai_id')?.value) {
        onPegawaiChange();
    } else {
        hitungTotal();
    }
});
</script>
@endsection
