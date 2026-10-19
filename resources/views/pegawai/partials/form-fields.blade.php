@php
    $p = $pegawai ?? [];
    $old = fn ($key, $default = '') => old($key, $p[$key] ?? $default);
    $statusOptions = [
        'PT' => 'Pegawai Tetap', 'DI' => 'Direksi', 'CP' => 'Calon Pegawai',
        'PH' => 'Honorer', 'TK' => 'Tenaga Kontrak', 'PN' => 'Pensiun',
    ];
@endphp

<div class="form-grid">
    <div class="form-group">
        <label for="nik">NIK</label>
        <input type="text" id="nik" name="nik" value="{{ $old('nik') }}" required>
        @error('nik') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="nama">Nama Lengkap</label>
        <input type="text" id="nama" name="nama" value="{{ $old('nama') }}" required>
        @error('nama') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="jabatan">Jabatan</label>
        <select id="jabatan" name="jabatan" required>
            <option value="">-- Pilih Jabatan --</option>
            @php
                $currentJabatan = $old('jabatan');
                $jabatanOptions = $jabatanList ?? (app(\App\Http\Controllers\PegawaiController::class)->getJabatanList());
                if ($currentJabatan && !in_array($currentJabatan, $jabatanOptions)) {
                    array_unshift($jabatanOptions, $currentJabatan);
                }
            @endphp
            @foreach ($jabatanOptions as $j)
                <option value="{{ $j }}" @selected($currentJabatan === $j)>{{ $j }}</option>
            @endforeach
        </select>
        @error('jabatan') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="unit_kerja">Unit Kerja</label>
        <select id="unit_kerja" name="unit_kerja" required>
            <option value="">-- Pilih Unit Kerja --</option>
            @php
                $currentUnit = $old('unit_kerja');
                $unitOptions = $unitKerjaList ?? (app(\App\Http\Controllers\PegawaiController::class)->getUnitKerjaList());
                if ($currentUnit && !in_array($currentUnit, $unitOptions)) {
                    array_unshift($unitOptions, $currentUnit);
                }
            @endphp
            @foreach ($unitOptions as $u)
                <option value="{{ $u }}" @selected($currentUnit === $u)>{{ $u }}</option>
            @endforeach
        </select>
        @error('unit_kerja') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="status_peg">Status Kepegawaian</label>
        <select id="status_peg" name="status_peg" required>
            <option value="">-- Pilih Status --</option>
            @foreach ($statusOptions as $val => $label)
                <option value="{{ $val }}" @selected($old('status_peg') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status_peg') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="tgl_masuk">Tanggal Masuk</label>
        <input type="date" id="tgl_masuk" name="tgl_masuk" value="{{ $old('tgl_masuk') }}" required>
        @error('tgl_masuk') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="telp">No. Telepon</label>
        <input type="text" id="telp" name="telp" value="{{ $old('telp') }}">
        @error('telp') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group span-2">
        <label for="alamat">Alamat</label>
        <textarea id="alamat" name="alamat" rows="2">{{ $old('alamat') }}</textarea>
        @error('alamat') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
