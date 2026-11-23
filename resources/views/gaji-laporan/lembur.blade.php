@extends('layouts.app')

@section('title', 'Laporan Lembur')

@section('content')
<div class="page-head">
    @if(session('simpeg_user.userlevel') === '5' || request('my'))
        <div class="breadcrumb">Home / Pendapatan Saya / Lembur</div>
        <h1>Rincian Lembur</h1>
    @else
        <div class="breadcrumb">Home / Laporan Penggajian / Lap. Lembur</div>
        <h1>Laporan Lembur</h1>
    @endif
</div>

@include('gaji-laporan.partials.filter-toolbar')

@if (session('simpeg_user.userlevel') === '5' || request('my'))
    @php
        $bulanNama = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? 'Bulan Ini';
    @endphp

    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px;">
        <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
            RIWAYAT LEMBUR PEGAWAI
        </div>

        <div class="table-card" style="box-shadow: none; border: 1px solid #cbd5e1;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th>BULAN / PERIODE</th>
                        <th style="text-align: center;">JAM LEMBUR</th>
                        <th style="text-align: right;">UANG LEMBUR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatLembur as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td style="font-weight: 600; color: #334155;">{{ $item['bulan_nama'] }}</td>
                            <td style="text-align: center; font-weight: 600; color: #2563eb;">{{ $item['jam_lembur'] }} jam</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;">Rp {{ number_format($item['nominal_lembur'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="table-empty">Belum ada data lembur recorded untuk Anda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@else
    <!-- Tampilan Admin / Keuangan -->
    @if (session('simpeg_user.userlevel') === '1')
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
            <p style="font-size:12px; color:var(--text-muted); margin:0;">Data terintegrasi otomatis dengan modul Prestasi, tabel Lembur, dan Payroll.</p>
            <div style="display:flex; gap:8px;">
                <a href="{{ route('gaji-laporan.lembur.template', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn btn-outline" style="border-color:#10B981; color:#059669; font-weight:600;" title="Unduh template Excel dengan daftar pegawai aktif">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Unduh Template Lembur
                </a>
                <button type="button" class="btn" style="background:#059669; color:#fff; font-weight:600;" onclick="openImportModal('modal-import-lembur')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Unggah Excel Lembur
                </button>
            </div>
        </div>
    @else
        <p style="font-size:12px; color:var(--text-muted); margin:-8px 0 16px;">Data diambil dari modul Prestasi (field Jam Lembur).</p>
    @endif

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Tanggal Prestasi</th>
                    <th>Jam Lembur</th>
                    <th>Nominal Lembur</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $d)
                    <tr>
                        <td class="cell-nik">{{ $d['nik'] }}</td>
                        <td class="cell-name">{{ $d['nama'] }}</td>
                        <td>{{ formatTglIndo($d['tanggal'] ?? null) }}</td>
                        <td>{{ $d['jam_lembur'] }} jam</td>
                        <td>Rp {{ number_format($d['nominal_lembur'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="table-empty">Belum ada data lembur untuk periode ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Unggah File Excel Lemburan --}}
    @if (session('simpeg_user.userlevel') === '1')
        <div id="modal-import-lembur" class="pk-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; padding:20px;">
            <div style="background:#ffffff; width:100%; max-width:520px; border-radius:18px; box-shadow:0 20px 40px rgba(0,0,0,0.25); overflow:hidden;">
                <div style="padding:18px 24px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-size:16px; font-weight:700; color:#0F2A3D;">Unggah Rekap Jam Lemburan Pegawai</div>
                    <button type="button" onclick="closeImportModal('modal-import-lembur')" style="background:none; border:none; font-size:20px; color:#64748B; cursor:pointer;">&times;</button>
                </div>
                <form action="{{ route('gaji-laporan.lembur.import') }}" method="POST" enctype="multipart/form-data" style="padding:24px;">
                    @csrf
                    <div style="margin-bottom:18px;">
                        <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Target Periode Lembur:</label>
                        <div style="display:flex; gap:10px;">
                            <select name="bulan" class="form-control" style="flex:1; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                                @foreach (\App\Http\Controllers\AbsensiController::BULAN as $val => $label)
                                    <option value="{{ $val }}" @selected($bulan === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <select name="tahun" class="form-control" style="width:110px; padding:8px 12px; border-radius:8px; border:1px solid #CBD5E1; font-size:13px;">
                                @for ($y = now()->year; $y >= now()->year - 3; $y--)
                                    <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom:18px;">
                        <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Mode Pengunggahan:</label>
                        <div style="display:flex; gap:12px;">
                            <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                                <input type="radio" name="mode" value="update" checked>
                                <span>Perbarui jika sudah ada</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                                <input type="radio" name="mode" value="skip">
                                <span>Lewati jika sudah ada</span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-bottom:20px;">
                        <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Pilih File Excel (.xlsx / .csv):</label>
                        <input type="file" name="file_excel" accept=".xlsx,.csv,.txt" required style="display:block; width:100%; padding:10px; border:2px dashed #CBD5E1; border-radius:10px; background:#F8FAFC; font-size:13px;">
                        <p style="font-size:11.5px; color:#64748B; margin-top:6px;">
                            Rate: <strong>Rp {{ number_format(\App\Http\Controllers\PrestasiController::RATE_LEMBUR_PER_JAM, 0, ',', '.') }}/jam</strong>. Kolom: NIK, Nama, Unit Kerja, Jam Lembur.
                        </p>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:16px; border-top:1px solid #E2E8F0;">
                        <button type="button" class="btn btn-outline" onclick="closeImportModal('modal-import-lembur')">Batal</button>
                        <button type="submit" class="btn" style="background:#059669; color:#fff; font-weight:600;">Impor Lembur Sekarang</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        window.openImportModal = function(id) {
            var modal = document.getElementById(id);
            if (modal) {
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
                modal.style.display = 'flex';
            }
        };
        window.closeImportModal = function(id) {
            var modal = document.getElementById(id);
            if (modal) modal.style.display = 'none';
        };
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('modal-import-lembur');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeImportModal('modal-import-lembur');
                });
            }
        });
        </script>
    @endif
@endif
@endsection
