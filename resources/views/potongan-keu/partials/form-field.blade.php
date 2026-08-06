@php
    $item = $item ?? [];
    $old = fn ($key, $default = 0) => old($key, $item[$key] ?? $default);
@endphp

<div class="form-grid">
    <div class="form-group span-2">
        <label for="pegawai_id">Pegawai</label>
        <select id="pegawai_id" name="pegawai_id" required>
            <option value="">-- Pilih Pegawai --</option>
            @foreach ($pegawaiList as $p)
                <option value="{{ $p['id'] }}" @selected((string) old('pegawai_id', $item['pegawai_id'] ?? '') === (string) $p['id'])>
                    {{ $p['nik'] }} - {{ $p['nama'] }}
                </option>
            @endforeach
        </select>
        @error('pegawai_id') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    @foreach ($kolom as $k)
        <div class="form-group">
            <label for="{{ $k }}">{{ $kolomLabels[$k] }} (Rp)</label>
            <input type="number" id="{{ $k }}" name="{{ $k }}" min="0" step="1000" value="{{ $old($k) }}">
            @error($k) <div class="form-error">{{ $message }}</div> @enderror
        </div>
    @endforeach
</div>
