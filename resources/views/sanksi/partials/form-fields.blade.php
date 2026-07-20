@php
    $s = $sanksi ?? [];
    $old = fn ($key, $default = '') => old($key, $s[$key] ?? $default);
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
        <label for="tanggal">Tanggal Sanksi</label>
        <input type="date" id="tanggal" name="tanggal" value="{{ $old('tanggal') }}" required>
        @error('tanggal') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="jenis_sanksi">Jenis Sanksi</label>
        <select id="jenis_sanksi" name="jenis_sanksi" required>
            <option value="">-- Pilih Jenis --</option>
            @foreach ($jenisSanksiList as $jenis)
                <option value="{{ $jenis }}" @selected($old('jenis_sanksi') === $jenis)>{{ $jenis }}</option>
            @endforeach
        </select>
        @error('jenis_sanksi') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group span-2">
        <label for="keterangan">Keterangan Sanksi</label>
        <textarea id="keterangan" name="keterangan" rows="3">{{ $old('keterangan') }}</textarea>
        @error('keterangan') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group span-2">
        <label for="potongan_persen">Potongan (%)</label>
        <input type="number" id="potongan_persen" name="potongan_persen" min="0" max="100" step="0.5" value="{{ $old('potongan_persen') }}" required>
        @error('potongan_persen') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
