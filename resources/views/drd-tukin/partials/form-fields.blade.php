@php
    $d = $drd ?? [];
    $old = fn ($key, $default = '') => old($key, $d[$key] ?? $default);
@endphp

<div class="form-grid">
    <div class="form-group span-2">
        <label for="tanggal">Tanggal</label>
        <input type="date" id="tanggal" name="tanggal" value="{{ $old('tanggal') }}" required>
        @error('tanggal') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="nominal_drd">Nominal DRD (Rp)</label>
        <input type="number" id="nominal_drd" name="nominal_drd" min="1" step="1000" value="{{ $old('nominal_drd') }}" required>
        @error('nominal_drd') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="nominal_penerimaan">Nominal Penerimaan (Rp)</label>
        <input type="number" id="nominal_penerimaan" name="nominal_penerimaan" min="0" step="1000" value="{{ $old('nominal_penerimaan') }}" required>
        @error('nominal_penerimaan') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
<p style="font-size:12px; color:var(--text-muted); margin-top:12px;">
    Efisiensi (%) dihitung otomatis dari Nominal Penerimaan &divide; Nominal DRD &times; 100, tidak perlu diisi manual.
</p>
