@extends('layouts.auth')

@section('title', 'Masuk Akun')

@section('content')
<div class="login-shell">
    <div class="login-side">
        <div>
            <div class="brand">
                <div class="brand-mark">SP</div>
                <div>
                    <div class="brand-name">SIMPEG</div>
                    <div class="brand-sub">Sistem Informasi Kepegawaian</div>
                </div>
            </div>
            <div class="side-copy">
                <h1>Kelola data kepegawaian dalam satu tempat.</h1>
                <p>Absensi, penggajian, THR, hingga laporan kepegawaian — terhubung dan tercatat rapi untuk seluruh unit kerja.</p>
            </div>
            <div class="side-stats">
                <div><p>1.240</p><p>Pegawai aktif</p></div>
                <div><p>18</p><p>Unit kerja</p></div>
                <div><p>99.8%</p><p>Uptime sistem</p></div>
            </div>
        </div>
        <div class="foot-note">&copy; {{ date('Y') }} SIMPEG — PDAM Tirta Daya Amerta</div>
    </div>

    <div class="form-side">
        <div class="form-head">
            <h2>Masuk akun</h2>
            <p>Gunakan NIK dan kata sandi kepegawaian kamu.</p>
        </div>

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="field">
                <label for="nik">NIK</label>
                <div class="input-wrap">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <input type="text" id="nik" name="nik" placeholder="Contoh: 1711254" value="{{ old('nik') }}" autofocus>
                </div>
            </div>
            @error('nik')
                <div class="error-text">{{ $message }}</div>
            @enderror

            <div class="field">
                <label for="password">Kata sandi</label>
                <div class="input-wrap" style="position: relative;">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <input type="password" id="password" name="password" placeholder="••••••••" style="padding-right: 42px;">
                    <button type="button" id="toggle-password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; cursor: pointer; color: var(--text-muted); display: flex; align-items: center; z-index: 10;">
                        <!-- Eye icon (open/show) -->
                        <svg id="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width: 18px; height: 18px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <!-- Eye icon (closed/hide) -->
                        <svg id="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width: 18px; height: 18px; display: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <div class="row-between">
                <label><input type="checkbox" style="accent-color: var(--teal);"> Ingatkan saya</label>
                <a href="#">Lupa kata sandi?</a>
            </div>
            <button type="submit" class="btn-submit">Masuk</button>
        </form>
        <p class="divider-note">Kesulitan masuk? Hubungi admin SDM di ext. 214</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('toggle-password');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function () {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'block';
                } else {
                    passwordInput.type = 'password';
                    eyeOpen.style.display = 'block';
                    eyeClosed.style.display = 'none';
                }
            });
        }
    });
</script>
@endsection
