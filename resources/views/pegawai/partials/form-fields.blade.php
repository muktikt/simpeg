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
        <input type="text" id="jabatan" name="jabatan" value="{{ $old('jabatan') }}" required>
        @error('jabatan') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="unit_kerja">Unit Kerja</label>
        <input type="text" id="unit_kerja" name="unit_kerja" value="{{ $old('unit_kerja') }}" required>
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
