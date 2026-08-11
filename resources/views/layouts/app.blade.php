<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIMPEG | @yield('title', 'Beranda')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
/* Custom Logout Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 42, 61, 0.4);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
}
.modal-overlay.active {
    opacity: 1;
    pointer-events: auto;
}
.modal-card {
    background: #ffffff;
    padding: 32px;
    border-radius: 20px;
    width: 90%;
    max-width: 380px;
    text-align: center;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border: 1px solid #E1E7E9;
    transform: scale(0.95);
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-overlay.active .modal-card {
    transform: scale(1);
}
.modal-icon {
    width: 56px;
    height: 56px;
    background: #FEE2E2;
    color: #DC2626;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
}
.modal-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: #0F2A3D;
    margin-bottom: 8px;
}
.modal-text {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: #64748B;
    line-height: 1.5;
    margin-bottom: 24px;
}
.modal-actions {
    display: flex;
    gap: 12px;
}
.modal-actions button {
    flex: 1;
    padding: 12px 16px;
    border-radius: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}
.btn-cancel {
    background: #F2F5F5;
    color: #1E2A32;
}
.btn-cancel:hover {
    background: #E1E7E9;
}
.btn-confirm {
    background: #D85A30;
    color: #ffffff;
}
.btn-confirm:hover {
    background: #C24D28;
    box-shadow: 0 4px 12px rgba(216, 90, 48, 0.2);
}
</style>
</head>
<body class="app-page">
<div class="layout">

    @include('partials.sidebar')

    <div class="main">
        <header class="topbar">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="text" placeholder="Cari NIK atau nama pegawai...">
            </div>
            <div class="topbar-right">
                <div class="icon-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                </div>
                <div class="profile">
                    @if (session('simpeg_user.userlevel') === '5')
                        <a href="{{ route('profile.show') }}" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit;">
                            <div class="avatar">
                                @php
                                    $namaUser = session('simpeg_user.nama_peg', 'Pengguna');
                                    $inisial = collect(explode(' ', $namaUser))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                                @endphp
                                {{ strtoupper($inisial) }}
                            </div>
                            <div>
                                <div class="profile-name">{{ $namaUser }}</div>
                                <div class="profile-role">{{ session('simpeg_user.jabatan', '-') }}</div>
                            </div>
                        </a>
                    @else
                        <div style="display:flex; align-items:center; gap:10px; color:inherit;">
                            <div class="avatar">
                                @php
                                    $namaUser = session('simpeg_user.nama_peg', 'Pengguna');
                                    $inisial = collect(explode(' ', $namaUser))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                                @endphp
                                {{ strtoupper($inisial) }}
                            </div>
                            <div>
                                <div class="profile-name">{{ $namaUser }}</div>
                                <div class="profile-role">{{ session('simpeg_user.jabatan', '-') }}</div>
                            </div>
                        </div>
                    @endif
                    <form action="{{ route('logout') }}" method="POST" class="logout-form" id="logout-form">
                        @csrf
                        <button type="button" class="logout-btn" title="Keluar" onclick="openLogoutModal()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <div class="content">
            @if (session('success'))
                <div class="flash-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 6L9 17l-5-5"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>

<!-- Global Custom Modal -->
<div id="global-custom-modal" class="modal-overlay">
    <div class="modal-card">
        <div id="global-modal-icon" class="modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>
        <h3 id="global-modal-title" class="modal-title">Konfirmasi</h3>
        <p id="global-modal-text" class="modal-text">Apakah Anda yakin?</p>
        <div id="global-modal-actions" class="modal-actions">
            <button type="button" id="global-modal-cancel" class="btn-cancel" onclick="closeCustomModal()">Batal</button>
            <button type="button" id="global-modal-confirm" class="btn-confirm">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script>
let customModalConfirmCallback = null;

function showCustomModal({ title, text, type = 'warning', confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', showCancel = true, onConfirm = null }) {
    const modal = document.getElementById('global-custom-modal');
    const iconContainer = document.getElementById('global-modal-icon');
    const titleEl = document.getElementById('global-modal-title');
    const textEl = document.getElementById('global-modal-text');
    const cancelBtn = document.getElementById('global-modal-cancel');
    const confirmBtn = document.getElementById('global-modal-confirm');

    titleEl.textContent = title || 'Informasi';
    textEl.textContent = text || '';

    // Style icon container
    iconContainer.className = 'modal-icon';
    let iconSvg = '';

    if (type === 'download' || type === 'info') {
        iconContainer.style.background = '#E0F2FE';
        iconContainer.style.color = '#0284C7';
        iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>`;
    } else if (type === 'danger' || type === 'delete') {
        iconContainer.style.background = '#FEE2E2';
        iconContainer.style.color = '#DC2626';
        iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            <line x1="10" y1="11" x2="10" y2="17"></line>
            <line x1="14" y1="11" x2="14" y2="17"></line>
        </svg>`;
    } else if (type === 'logout') {
        iconContainer.style.background = '#FEE2E2';
        iconContainer.style.color = '#DC2626';
        iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>`;
    } else if (type === 'success') {
        iconContainer.style.background = '#DCFCE7';
        iconContainer.style.color = '#16A34A';
        iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>`;
    } else { // warning / default
        iconContainer.style.background = '#FEF3C7';
        iconContainer.style.color = '#D97706';
        iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>`;
    }

    iconContainer.innerHTML = iconSvg;

    if (showCancel) {
        cancelBtn.style.display = 'block';
        cancelBtn.textContent = cancelText;
    } else {
        cancelBtn.style.display = 'none';
    }

    confirmBtn.textContent = confirmText;
    if (type === 'danger' || type === 'delete' || type === 'logout') {
        confirmBtn.style.background = '#D85A30';
    } else if (type === 'download' || type === 'info') {
        confirmBtn.style.background = '#0284C7';
    } else {
        confirmBtn.style.background = '#D85A30';
    }

    customModalConfirmCallback = onConfirm;
    modal.classList.add('active');
}

function closeCustomModal() {
    const modal = document.getElementById('global-custom-modal');
    if (modal) modal.classList.remove('active');
    customModalConfirmCallback = null;
}

function openLogoutModal() {
    showCustomModal({
        title: 'Keluar Aplikasi',
        text: 'Apakah Anda yakin ingin mengakhiri sesi ini dan keluar dari sistem SIMPEG?',
        type: 'logout',
        confirmText: 'Ya, Keluar',
        cancelText: 'Batal',
        showCancel: true,
        onConfirm: () => {
            document.getElementById('logout-form').submit();
        }
    });
}

function confirmSubmit(event, message, title = 'Konfirmasi', type = 'warning', confirmText = 'Ya, Lanjutkan') {
    event.preventDefault();
    const form = event.target.closest('form') || event.target;
    showCustomModal({
        title: title,
        text: message,
        type: type,
        confirmText: confirmText,
        cancelText: 'Batal',
        showCancel: true,
        onConfirm: () => {
            form.submit();
        }
    });
    return false;
}

function showCustomAlert(message, title = 'Informasi', type = 'info') {
    showCustomModal({
        title: title,
        text: message,
        type: type,
        confirmText: 'OK',
        showCancel: false
    });
}

document.getElementById('global-modal-confirm').addEventListener('click', function() {
    const callback = customModalConfirmCallback;
    closeCustomModal();
    if (typeof callback === 'function') {
        callback();
    }
});

document.getElementById('global-custom-modal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeCustomModal();
    }
});
</script>
</body>
</html>
