@php
    $a = $absensi ?? [];
    $old = fn ($key, $default = '') => old($key, $a[$key] ?? $default);
@endphp

<div class="form-grid">
    <div class="form-group span-2">
        <label for="pegawai_id">Pegawai</label>
        <select id="pegawai_id" name="pegawai_id" required @if(isset($absensi)) disabled @endif>
            <option value="">-- Pilih Pegawai --</option>
            @foreach ($pegawaiList as $p)
                <option value="{{ $p['id'] }}" @selected((string) $old('pegawai_id') === (string) $p['id'])>{{ $p['nik'] }} - {{ $p['nama'] }}</option>
            @endforeach
        </select>
        @if (isset($absensi))
            <input type="hidden" name="pegawai_id" value="{{ $absensi['pegawai_id'] }}">
        @endif
        @error('pegawai_id') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="bulan">Bulan</label>
        <select id="bulan" name="bulan" required>
            @foreach ($bulanList as $val => $label)
                <option value="{{ $val }}" @selected((string) $old('bulan', $bulan ?? '') === (string) $val)>{{ $label }}</option>
            @endforeach
        </select>
        @error('bulan') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="tahun">Tahun</label>
        <input type="number" id="tahun" name="tahun" value="{{ $old('tahun', $tahun ?? now()->year) }}" required>
        @error('tahun') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="hari_kerja">Hari Kerja</label>
        <input type="number" id="hari_kerja" name="hari_kerja" min="0" max="31" value="{{ $old('hari_kerja') }}" required>
        @error('hari_kerja') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="hadir">Hadir</label>
        <input type="number" id="hadir" name="hadir" min="0" max="31" value="{{ $old('hadir') }}" required>
        @error('hadir') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="sakit">Sakit</label>
        <input type="number" id="sakit" name="sakit" min="0" max="31" value="{{ $old('sakit', 0) }}" required>
        @error('sakit') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="izin">Izin</label>
        <input type="number" id="izin" name="izin" min="0" max="31" value="{{ $old('izin', 0) }}" required>
        @error('izin') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="alpha">Alpha</label>
        <input type="number" id="alpha" name="alpha" min="0" max="31" value="{{ $old('alpha', 0) }}" required>
        @error('alpha') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group span-2">
        <label for="keterangan">Keterangan</label>
        <textarea id="keterangan" name="keterangan" rows="2">{{ $old('keterangan') }}</textarea>
        @error('keterangan') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
