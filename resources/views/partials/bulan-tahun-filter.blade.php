@php
    $bulanOptions = $bulanList ?? \App\Http\Controllers\AbsensiController::BULAN;
    $tahunOpts = $tahunOptions ?? range((int) now()->year + 1, min(2024, (int) now()->year - 3));
    $hasCustomFilter = (!empty($search) || (string) $bulan !== (string) $defaultBulan || (string) $tahun !== (string) $defaultTahun);
@endphp

<div class="toolbar">
    <form method="GET" action="{{ $actionUrl }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; background:#fff;">
            <option value="all" @selected((string) $bulan === 'all')>Semua Bulan</option>
            @foreach ($bulanOptions as $val => $label)
                <option value="{{ $val }}" @selected((string) $bulan === (string) $val)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px; background:#fff;">
            <option value="all" @selected((string) $tahun === 'all')>Semua Tahun</option>
            @foreach ($tahunOpts as $y)
                <option value="{{ $y }}" @selected((string) $tahun === (string) $y)>{{ $y }}</option>
            @endforeach
        </select>

        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="text" name="q" value="{{ $search }}" placeholder="Cari NIK atau Nama..." style="border:none; outline:none; font-size:13px; width:200px;">
        </div>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <a id="{{ $resetBtnId }}" href="{{ $actionUrl }}" class="btn btn-outline btn-sm" style="display:{{ $hasCustomFilter ? 'inline-flex' : 'none' }}; align-items:center; color:#DC2626; border-color:#FECACA;">Reset</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btnReset = document.getElementById(@json($resetBtnId));
    var form = btnReset ? btnReset.closest('form') : null;
    if (form && btnReset) {
        var defBulan = @json((string) $defaultBulan);
        var defTahun = @json((string) $defaultTahun);

        function checkFilter() {
            var b = form.querySelector('[name="bulan"]')?.value || defBulan;
            var t = form.querySelector('[name="tahun"]')?.value || defTahun;
            var q = form.querySelector('[name="q"]')?.value?.trim() || '';
            btnReset.style.display = (b !== defBulan || t !== defTahun || q !== '') ? 'inline-flex' : 'none';
        }

        form.querySelectorAll('select, input').forEach(function(el) {
            el.addEventListener('change', checkFilter);
            el.addEventListener('input', checkFilter);
        });
    }
});
</script>
