<div class="toolbar">
    <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:10px;">
        @if(request('my'))<input type="hidden" name="my" value="1">@endif
        <select name="bulan" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @foreach (\App\Http\Controllers\AbsensiController::BULAN as $val => $label)
                <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="tahun" onchange="this.form.submit()" style="padding:9px 12px; border-radius:9px; border:1px solid var(--border); font-size:13px;">
            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
            @endfor
        </select>
        @php
            $hasCustomLaporan = ($bulan !== (int) now()->month || $tahun !== (int) now()->year);
            $resetUrlLaporan = url()->current() . (request('my') ? '?my=1' : '');
        @endphp
        @if ($hasCustomLaporan)
            <a href="{{ $resetUrlLaporan }}" class="btn btn-outline btn-sm" style="height:36px; display:inline-flex; align-items:center; color:#DC2626; border-color:#FECACA; padding:0 10px;" title="Kembali ke Periode Saat Ini">Reset</a>
        @endif
    </form>

    <button type="button" class="btn btn-outline" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/></svg>
        Cetak
    </button>
</div>
