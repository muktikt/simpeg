<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIMPEG | @yield('title', 'Beranda')</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
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

<!-- Custom Logout Modal -->
<div id="logout-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>
        <h3 class="modal-title">Keluar Aplikasi</h3>
        <p class="modal-text">Apakah Anda yakin ingin mengakhiri sesi ini dan keluar dari sistem SIMPEG?</p>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeLogoutModal()">Batal</button>
            <button type="button" class="btn-confirm" onclick="submitLogout()">Ya, Keluar</button>
        </div>
    </div>
</div>

<!-- Reusable Confirm Modal -->
<div id="confirm-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon" id="confirm-modal-icon">
            <svg id="confirm-icon-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <svg id="confirm-icon-danger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24" style="display:none;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <svg id="confirm-icon-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24" style="display:none;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
        <h3 class="modal-title" id="confirm-modal-title">Konfirmasi</h3>
        <p class="modal-text" id="confirm-modal-text">Apakah Anda yakin?</p>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeConfirmModal()">Batal</button>
            <button type="button" class="btn-confirm" id="confirm-modal-btn" onclick="executeConfirm()">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script>
function openLogoutModal() {
    const modal = document.getElementById('logout-modal');
    modal.classList.add('active');
}

function closeLogoutModal() {
    const modal = document.getElementById('logout-modal');
    modal.classList.remove('active');
}

function submitLogout() {
    document.getElementById('logout-form').submit();
}

// Close modal when clicking outside the card
document.getElementById('logout-modal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeLogoutModal();
    }
});

// ──────────────────────────────────────────────
// Reusable Confirm Modal
// ──────────────────────────────────────────────
let _confirmTargetForm = null;

const CONFIRM_THEMES = {
    danger:  { bg: '#FEE2E2', color: '#DC2626', btnBg: '#DC2626', btnHover: '#B91C1C' },
    warning: { bg: '#FEF3C7', color: '#D97706', btnBg: '#D97706', btnHover: '#B45309' },
    info:    { bg: '#CCFBF1', color: '#0D9488', btnBg: '#0D9488', btnHover: '#0F766E' },
};

/**
 * Open the reusable confirm modal.
 *
 * @param {HTMLFormElement} form   - The form to submit if confirmed.
 * @param {Object} opts
 * @param {string} opts.title     - Modal title.
 * @param {string} opts.text      - Modal body text.
 * @param {string} opts.btnLabel  - Confirm button label.
 * @param {string} opts.theme     - 'danger' | 'warning' | 'info' (default: 'warning').
 */
function openConfirmModal(form, opts = {}) {
    _confirmTargetForm = form;

    const theme = CONFIRM_THEMES[opts.theme || 'warning'] || CONFIRM_THEMES.warning;

    // Set title & text
    document.getElementById('confirm-modal-title').textContent = opts.title || 'Konfirmasi';
    document.getElementById('confirm-modal-text').textContent  = opts.text  || 'Apakah Anda yakin?';

    // Set button
    const btn = document.getElementById('confirm-modal-btn');
    btn.textContent       = opts.btnLabel || 'Ya, Lanjutkan';
    btn.style.background  = theme.btnBg;
    btn.onmouseenter      = () => { btn.style.background = theme.btnHover; };
    btn.onmouseleave      = () => { btn.style.background = theme.btnBg; };

    // Set icon color
    const iconEl = document.getElementById('confirm-modal-icon');
    iconEl.style.background = theme.bg;
    iconEl.style.color      = theme.color;

    // Show the right icon
    document.getElementById('confirm-icon-warning').style.display = (opts.theme || 'warning') === 'warning' ? 'block' : 'none';
    document.getElementById('confirm-icon-danger').style.display  = opts.theme === 'danger'  ? 'block' : 'none';
    document.getElementById('confirm-icon-info').style.display    = opts.theme === 'info'    ? 'block' : 'none';

    // Open
    document.getElementById('confirm-modal').classList.add('active');
}

function closeConfirmModal() {
    document.getElementById('confirm-modal').classList.remove('active');
    _confirmTargetForm = null;
}

function executeConfirm() {
    if (_confirmTargetForm) {
        _confirmTargetForm.submit();
    }
    closeConfirmModal();
}

// Close when clicking outside
document.getElementById('confirm-modal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeConfirmModal();
    }
});
</script>
</body>
</html>

