@php
    $ttd = $penandatangan ?? \App\Http\Controllers\PejabatTtdController::getTtd($kodeTtd ?? 'keuangan');
@endphp

@if (!empty($ttd))
<div class="signature-section" style="margin-top:40px; page-break-inside:avoid; width:100%;">
    <div style="text-align:right; font-size:12px; color:#334155; margin-bottom:14px; font-weight:500;">
        {{ $ttd['kota'] ?? 'Indramayu' }}, {{ date('d F Y') }}
    </div>

    <table style="width:100%; border-collapse:collapse; border:none; text-align:center;">
        <tr>
            <td style="width:33.3%; vertical-align:top; border:none; padding:0 8px;">
                <div style="font-size:11px; font-weight:700; color:#64748B; letter-spacing:0.5px;">{{ $ttd['mengetahui']['label'] ?? 'MENGETAHUI' }}</div>
                <div style="font-size:12px; font-weight:700; color:#0F2A3D; margin-top:3px;">{{ $ttd['mengetahui']['jabatan'] }}</div>
                <div style="height:60px;"></div>
                <div style="font-size:12.5px; font-weight:700; text-decoration:underline; color:#0F2A3D;">{{ $ttd['mengetahui']['nama'] }}</div>
                <div style="font-size:11px; color:#64748B; margin-top:2px;">NIK. {{ $ttd['mengetahui']['nik'] }}</div>
            </td>
            <td style="width:33.3%; vertical-align:top; border:none; padding:0 8px;">
                <div style="font-size:11px; font-weight:700; color:#64748B; letter-spacing:0.5px;">{{ $ttd['menyetujui']['label'] ?? 'DISETUJUI' }}</div>
                <div style="font-size:12px; font-weight:700; color:#0F2A3D; margin-top:3px;">{{ $ttd['menyetujui']['jabatan'] }}</div>
                <div style="height:60px;"></div>
                <div style="font-size:12.5px; font-weight:700; text-decoration:underline; color:#0F2A3D;">{{ $ttd['menyetujui']['nama'] }}</div>
                <div style="font-size:11px; color:#64748B; margin-top:2px;">NIK. {{ $ttd['menyetujui']['nik'] }}</div>
            </td>
            <td style="width:33.3%; vertical-align:top; border:none; padding:0 8px;">
                <div style="font-size:11px; font-weight:700; color:#64748B; letter-spacing:0.5px;">{{ $ttd['pembuat']['label'] ?? 'DIBUAT OLEH' }}</div>
                <div style="font-size:12px; font-weight:700; color:#0F2A3D; margin-top:3px;">{{ $ttd['pembuat']['jabatan'] }}</div>
                <div style="height:60px;"></div>
                <div style="font-size:12.5px; font-weight:700; text-decoration:underline; color:#0F2A3D;">{{ $ttd['pembuat']['nama'] }}</div>
                <div style="font-size:11px; color:#64748B; margin-top:2px;">NIK. {{ $ttd['pembuat']['nik'] }}</div>
            </td>
        </tr>
    </table>
</div>
@endif

