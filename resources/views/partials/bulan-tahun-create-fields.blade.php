@php
    $bulanOptions = $bulanList ?? \App\Http\Controllers\AbsensiController::BULAN;
    $tahunOpts = $tahunOptions ?? range((int) now()->year + 1, min(2024, (int) now()->year - 3));
    $selectedBulan = old('bulan', $defaultBulan ?? now()->month);
    $selectedTahun = old('tahun', $defaultTahun ?? now()->year);
@endphp

<div class="form-group">
    <label for="bulan" style="font-weight:600;">Bulan <span style="color:#DC2626;">*</span></label>
    <select id="bulan" name="bulan" class="form-control" required>
        @foreach ($bulanOptions as $val => $label)
            <option value="{{ $val }}" @selected((int) $selectedBulan === (int) $val)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="tahun" style="font-weight:600;">Tahun <span style="color:#DC2626;">*</span></label>
    <select id="tahun" name="tahun" class="form-control" required>
        @foreach ($tahunOpts as $y)
            <option value="{{ $y }}" @selected((int) $selectedTahun === (int) $y)>{{ $y }}</option>
        @endforeach
    </select>
</div>
