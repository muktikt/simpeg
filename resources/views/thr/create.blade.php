@extends('layouts.app')

@section('title', 'Proses THR Pegawai')

@section('content')
<div class="page-head">
    <div class="breadcrumb">Home / Proses THR / Proses Baru</div>
    <h1>Proses THR Pegawai</h1>
</div>

<form method="POST" action="{{ route('thr.store') }}" id="thr-form">
    @csrf

    <div class="form-card" style="max-width:100%;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:14.5px; margin:0 0 14px;">Data Pegawai & Periode</h3>
        <div class="form-grid">
            <div class="form-group span-2">
                <label for="pegawai_id">Pegawai</label>
                <select id="pegawai_id" name="pegawai_id" required onchange="onPegawaiChange()">
                    <option value="">-- Pilih Pegawai --</option>
                    @foreach ($pegawaiList as $p)
                        <option value="{{ $p['id'] }}" @selected((string) old('pegawai_id') === (string) $p['id'])>{{ $p['nik'] }} - {{ $p['nama'] }}</option>
                    @endforeach
                </select>
                @error('pegawai_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="kategori">Kategori Pegawai</label>
                <select id="kategori" name="kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($kategoriList as $val => $label)
                        <option value="{{ $val }}" @selected(old('kategori') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('kategori') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label>Info Keluarga (otomatis)</label>
                <input type="text" id="info-keluarga" value="Pilih pegawai dulu" disabled>
            </div>
            <div class="form-group">
                <label for="tahun">Tahun</label>
                <input type="number" id="tahun" name="tahun" value="{{ old('tahun', now()->year) }}" required>
            </div>
        </div>
    </div>

    <div class="form-card" style="max-width:100%; margin-top:16px;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:14.5px; margin:0 0 14px;">Komponen Pendapatan</h3>
        <div class="form-grid">
            @foreach ($komponenPendapatan as $key => $label)
                <div class="form-group">
                    <label for="{{ $key }}">{{ $label }}</label>
                    <input type="number" id="{{ $key }}" name="{{ $key }}" min="0" step="1" value="{{ old($key, 0) }}" class="komponen-pendapatan" oninput="hitungTotal()">
                </div>
            @endforeach
        </div>
        <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border); display:flex; justify-content:flex-end;">
            <div style="font-size:13.5px;">Total Pendapatan: <strong id="total-pendapatan-display">Rp 0</strong></div>
        </div>
    </div>

    <div class="form-card" style="max-width:100%; margin-top:16px;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:14.5px; margin:0 0 14px;">Potongan dari Pendapatan</h3>
        <div class="form-grid">
            @foreach ($potonganPendapatan as $key => $label)
                <div class="form-group">
                    <label for="{{ $key }}">{{ $label }}</label>
                    <input type="number" id="{{ $key }}" name="{{ $key }}" min="0" step="1" value="{{ old($key, 0) }}" class="komponen-potongan" oninput="hitungTotal()">
                </div>
            @endforeach
        </div>
    </div>

    <div class="form-card" style="max-width:100%; margin-top:16px;">
        <h3 style="font-family:'Space Grotesk',sans-serif; font-size:14.5px; margin:0 0 14px;">Potongan Non-Pendapatan</h3>
        <div class="form-grid">
            @foreach ($potonganNonPendapatan as $key => $label)
                <div class="form-group">
                    <label for="{{ $key }}">{{ $label }}</label>
                    <input type="number" id="{{ $key }}" name="{{ $key }}" min="0" step="1" value="{{ old($key, 0) }}" class="komponen-potongan" oninput="hitungTotal()">
                </div>
            @endforeach
        </div>
        <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--border); display:flex; justify-content:flex-end;">
            <div style="font-size:13.5px;">Total Potongan: <strong id="total-potongan-display">Rp 0</strong></div>
        </div>
    </div>

    <div class="form-card" style="max-width:100%; margin-top:16px; background:var(--teal-soft);">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-family:'Space Grotesk',sans-serif; font-size:15px; font-weight:600;">THR Diterima</div>
            <div style="font-family:'IBM Plex Mono',monospace; font-size:22px; font-weight:600; color:var(--teal-dark);" id="thr-diterima-display">Rp 0</div>
        </div>
    </div>

    <div class="form-actions" style="max-width:100%;">
        <button type="submit" class="btn btn-primary">Simpan sebagai Draft</button>
        <a href="{{ route('thr.index') }}" class="btn btn-outline">Batal</a>
    </div>
</form>

<script>
const pegawaiKeluargaUrl = @json(route('thr.hitung-keluarga', ['pegawaiId' => '__ID__']));

async function onPegawaiChange() {
    const pegawaiId = document.getElementById('pegawai_id').value;
    const info = document.getElementById('info-keluarga');

    if (!pegawaiId) {
        info.value = 'Pilih pegawai dulu';
        return;
    }

    info.value = 'Menghitung...';

    try {
        const res = await fetch(pegawaiKeluargaUrl.replace('__ID__', pegawaiId));
        const data = await res.json();

        info.value = (data.kawin ? 'Kawin' : 'Belum Kawin') + ', Anak: ' + data.jml_anak + ', PTKP: ' + data.kode_ptkp;

        const gapokField = document.getElementById('gapok');
        const tunjIstriField = document.getElementById('tunjangan_istri');
        if (gapokField && tunjIstriField && data.kawin) {
            const gapok = parseFloat(gapokField.value) || 0;
            tunjIstriField.value = Math.round(gapok * 0.1);
            hitungTotal();
        }
    } catch (e) {
        info.value = 'Gagal menghitung data keluarga';
    }
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

    document.getElementById('total-pendapatan-display').textContent = formatRupiah(totalPendapatan);
    document.getElementById('total-potongan-display').textContent = formatRupiah(totalPotongan);
    document.getElementById('thr-diterima-display').textContent = formatRupiah(totalPendapatan - totalPotongan);
}

hitungTotal();
</script>
@endsection
