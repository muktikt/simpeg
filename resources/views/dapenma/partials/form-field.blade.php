@php
    $d = $dapenma ?? [];
    $old = fn ($key, $default = '') => old($key, $d[$key] ?? $default);
@endphp

<div class="form-grid">
    <div class="form-group span-2">
        <label for="pegawai_id">Pegawai</label>
        <select id="pegawai_id" name="pegawai_id" required>
            <option value="">-- Pilih Pegawai --</option>
            @foreach ($pegawaiList as $p)
                <option value="{{ $p['id'] }}" @selected((string) $old('pegawai_id') === (string) $p['id'])>{{ $p['nik'] }} - {{ $p['nama'] }}</option>
            @endforeach
        </select>
        @error('pegawai_id') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="nomor_peserta">Nomor Peserta</label>
        <input type="text" id="nomor_peserta" name="nomor_peserta" value="{{ $old('nomor_peserta') }}" required>
        @error('nomor_peserta') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="nominal_phdp">Nominal PHDP (Rp)</label>
        <input type="number" id="nominal_phdp" name="nominal_phdp" min="0" step="1000" value="{{ $old('nominal_phdp') }}" required>
        @error('nominal_phdp') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
<p style="font-size:12px; color:var(--text-muted); margin-top:12px;">
    Jumlah Nominal Beban dihitung otomatis: 5% dari Nominal PHDP.
</p>
