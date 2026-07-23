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
        <input type="password" id="password" name="password" placeholder="{{ $isEdit ? 'Isi hanya jika ingin mengganti password' : '' }}" {{ $isEdit ? '' : 'required' }}>
        @error('password') <div class="form-error">{{ $message }}</div> @enderror
    </div>
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
