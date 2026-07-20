@php
    $g = $gapok ?? [];
    $old = fn ($key, $default = '') => old($key, $g[$key] ?? $default);
@endphp

<div class="form-grid">
    <div class="form-group">
        <label for="golongan">Golongan</label>
        <input type="text" id="golongan" name="golongan" value="{{ $old('golongan') }}" placeholder="Contoh: III/A" required>
        @error('golongan') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="masa_kerja">Masa Kerja</label>
        <input type="text" id="masa_kerja" name="masa_kerja" value="{{ $old('masa_kerja') }}" placeholder="Contoh: 0-5 tahun" required>
        @error('masa_kerja') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group span-2">
        <label for="nominal">Nominal Gaji Pokok (Rp)</label>
        <input type="number" id="nominal" name="nominal" min="0" step="1000" value="{{ $old('nominal') }}" required>
        @error('nominal') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
