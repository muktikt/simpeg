@php
    $u = $user ?? [];
    $old = fn ($key, $default = '') => old($key, $u[$key] ?? $default);
    $isEdit = isset($user);
@endphp

<div class="form-grid">
    <div class="form-group">
        <label for="nama">Nama Lengkap</label>
        <input type="text" id="nama" name="nama" value="{{ $old('nama') }}" required>
        @error('nama') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="username">Username (NIK)</label>
        <input type="text" id="username" name="username" value="{{ $old('username') }}" required>
        @error('username') <div class="form-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
        <label for="password">Password {{ $isEdit ? '(kosongkan jika tidak ingin mengganti)' : '' }}</label>
        <div style="position: relative;">
            <input type="password" id="password" name="password" placeholder="{{ $isEdit ? 'Isi hanya jika ingin mengganti password' : '' }}" {{ $isEdit ? '' : 'required' }} style="padding-right: 42px; width: 100%;">
            <button type="button" id="toggle-password" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; cursor: pointer; color: var(--text-muted); display: flex; align-items: center; z-index: 10;">
                <!-- Eye icon (open/show) -->
                <svg id="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width: 18px; height: 18px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <!-- Eye icon (closed/hide) -->
                <svg id="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width: 18px; height: 18px; display: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
        @error('password') <div class="form-error">{{ $message }}</div> @enderror
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
    <div class="form-group">
        <label for="userlevel">Level Akses</label>
        <select id="userlevel" name="userlevel" required>
            <option value="">-- Pilih Level --</option>
            @foreach ($roleList as $val => $role)
                <option value="{{ $val }}" @selected($old('userlevel') === $val)>{{ $role['label'] }}</option>
            @endforeach
        </select>
        @error('userlevel') <div class="form-error">{{ $message }}</div> @enderror
    </div>
</div>
