@php
    $item = $item ?? [];
    $old = fn ($key, $default = '') => old($key, $item[$key] ?? $default);
@endphp

<div class="form-grid">
    <div class="form-group span-2">
        <label for="pegawai_id">Pegawai</label>
        <select id="pegawai_id" name="pegawai_id" required>
            <option value="">-- Pilih Pegawai --</option>
            @foreach ($pegawaiList as $p)
                <option value="{{ $p['id'] }}" @selected((string) old('pegawai_id', $item['pegawai_id'] ?? '') === (string) $p['id'])>
                    {{ $p['nik'] }} - {{ $p['nama'] }} ({{ $p['jabatan'] }})
                </option>
            @endforeach
        </select>
        @error('pegawai_id') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group span-2">
        <label for="no_rek">Nomor Rekening BJB</label>
        <input type="text" id="no_rek" name="no_rek" value="{{ $old('no_rek') }}" placeholder="Contoh: 0012345678" required>
        @error('no_rek') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
