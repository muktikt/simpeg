@php
    $p = $prestasi ?? [];
    $old = fn ($key, $default = '') => old($key, $p[$key] ?? $default);
@endphp

<div class="form-grid">
    <div class="form-group span-2">
        <label for="pegawai_id">Pegawai</label>
        <select id="pegawai_id" name="pegawai_id" required>
            <option value="">-- Pilih Pegawai --</option>
            @foreach ($pegawaiList as $emp)
                <option value="{{ $emp['id'] }}" @selected((string) $old('pegawai_id') === (string) $emp['id'])>{{ $emp['nik'] }} - {{ $emp['nama'] }}</option>
            @endforeach
        </select>
        @error('pegawai_id') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="tanggal">Tanggal Prestasi</label>
        <input type="date" id="tanggal" name="tanggal" value="{{ $old('tanggal') }}" required>
        @error('tanggal') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="karya">Karya</label>
        <input type="text" id="karya" name="karya" value="{{ $old('karya') }}" required>
        @error('karya') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="absensi">Absensi</label>
        <input type="text" id="absensi" name="absensi" value="{{ $old('absensi') }}" required>
        @error('absensi') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="jam_lembur">Jam Lembur</label>
        <input type="number" id="jam_lembur" name="jam_lembur" min="0" max="300" step="0.5" value="{{ $old('jam_lembur') }}" required>
        @error('jam_lembur') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label for="alpha">Alpha (A)</label>
        <input type="number" id="alpha" name="alpha" min="0" max="31" value="{{ $old('alpha', 0) }}" required>
        @error('alpha') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="izin_ket">Izin Keterangan (I)</label>
        <input type="number" id="izin_ket" name="izin_ket" min="0" max="31" value="{{ $old('izin_ket', 0) }}" required>
        @error('izin_ket') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="izin_non_ket">Izin Tanpa Keterangan (ItK)</label>
        <input type="number" id="izin_non_ket" name="izin_non_ket" min="0" max="31" value="{{ $old('izin_non_ket', 0) }}" required>
        @error('izin_non_ket') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="sakit_ket">Sakit Keterangan (S)</label>
        <input type="number" id="sakit_ket" name="sakit_ket" min="0" max="31" value="{{ $old('sakit_ket', 0) }}" required>
        @error('sakit_ket') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="sakit_non_ket">Sakit Tanpa Keterangan (StD)</label>
        <input type="number" id="sakit_non_ket" name="sakit_non_ket" min="0" max="31" value="{{ $old('sakit_non_ket', 0) }}" required>
        @error('sakit_non_ket') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="dinas_luar">Dinas Luar (DL)</label>
        <input type="number" id="dinas_luar" name="dinas_luar" min="0" max="31" value="{{ $old('dinas_luar', 0) }}" required>
        @error('dinas_luar') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="cuti">Cuti</label>
        <input type="number" id="cuti" name="cuti" min="0" max="31" value="{{ $old('cuti', 0) }}" required>
        @error('cuti') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group span-2">
        <label for="alasan_cuti">Keterangan Cuti</label>
        <textarea id="alasan_cuti" name="alasan_cuti" rows="2">{{ $old('alasan_cuti') }}</textarea>
        @error('alasan_cuti') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
<p style="font-size:12px; color:var(--text-muted); margin-top:12px;">
    Total Uang Lembur dihitung otomatis: Jam Lembur &times; Rp {{ number_format(\App\Http\Controllers\PrestasiController::RATE_LEMBUR_PER_JAM, 0, ',', '.') }} per jam.
</p>
