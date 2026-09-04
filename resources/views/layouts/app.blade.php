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

/* ===================================================
   SEARCHABLE SELECT / PEGAWAI PICKER COMPONENT
=================================================== */
.form-group .css-search-container,
.css-search-container {
    position: relative;
    width: 100%;
    font-family: 'Inter', sans-serif;
}
.form-group .css-input-wrap,
.css-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    background: #ffffff !important;
    border: 1px solid #CBD5E1 !important;
    border-radius: 9px !important;
    transition: all 0.2s ease;
    cursor: pointer;
    box-sizing: border-box;
    overflow: hidden;
}
.form-group .css-input-wrap:focus-within, 
.form-group .css-input-wrap.active,
.css-input-wrap:focus-within, 
.css-input-wrap.active {
    border-color: #D85A30 !important;
    box-shadow: 0 0 0 3px rgba(216, 90, 48, 0.15) !important;
    background: #ffffff !important;
}
.css-search-icon {
    position: absolute;
    left: 12px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 16px !important;
    height: 16px !important;
    color: #94A3B8 !important;
    pointer-events: none;
    z-index: 2;
}
.form-group input.css-input,
.css-search-container input.css-input,
input.css-input {
    width: 100% !important;
    padding: 10px 68px 10px 40px !important;
    padding-left: 40px !important;
    border: none !important;
    outline: none !important;
    font-size: 13.5px !important;
    color: #1E293B !important;
    background: transparent !important;
    font-family: inherit !important;
    border-radius: 9px !important;
    height: 40px !important;
    min-height: 40px !important;
    box-shadow: none !important;
    line-height: normal !important;
    box-sizing: border-box !important;
}
.form-group input.css-input:focus,
.css-search-container input.css-input:focus,
input.css-input:focus {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
}
.css-input::placeholder {
    color: #94A3B8 !important;
}
.css-actions {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    gap: 4px;
    z-index: 2;
}
.css-clear-btn {
    display: none;
    background: #F1F5F9;
    border: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    color: #64748B;
    font-size: 14px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    padding: 0;
    line-height: 1;
}
.css-clear-btn:hover {
    background: #E2E8F0;
    color: #0F172A;
}
.css-arrow {
    width: 16px;
    height: 16px;
    color: #64748B;
    transition: transform 0.2s ease;
    margin-right: 4px;
    pointer-events: none;
}
.css-input-wrap.active .css-arrow {
    transform: rotate(180deg);
}
.css-dropdown-menu {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #CBD5E1;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    max-height: 280px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
    padding: 6px;
}
.css-dropdown-menu.show {
    display: block;
}
.css-option-item {
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 2px;
    transition: background 0.15s ease;
}
.css-option-item:hover, .css-option-item.highlighted {
    background: #F8FAFC;
}
.css-option-item.selected {
    background: #FFF7ED;
    border-left: 3px solid #D85A30;
}
.css-opt-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.css-opt-name {
    font-weight: 600;
    font-size: 13.5px;
    color: #0F2A3D;
}
.css-opt-nik {
    font-family: monospace;
    font-size: 11.5px;
    background: #F1F5F9;
    color: #475569;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
}
.css-opt-sub {
    font-size: 12px;
    color: #64748B;
}
.css-no-results {
    padding: 16px 12px;
    text-align: center;
    color: #94A3B8;
    font-size: 13px;
}
</style>
</head>
<body class="app-page">
<div class="layout">

    @include('partials.sidebar')

    <div class="main">
        <header class="topbar">
            <div class="topbar-right" style="margin-left: auto;">
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
            @if (session('error'))
                <div class="flash-error" style="background:#FEE2E2; color:#DC2626; border:1px solid #FECACA; padding:12px 16px; border-radius:10px; margin-bottom:16px; display:flex; align-items:center; gap:8px; font-weight:500; font-size:14px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="flash-error" style="background:#FEE2E2; color:#DC2626; border:1px solid #FECACA; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-weight:500; font-size:14px;">
                    <div style="font-weight:700; margin-bottom:4px; display:flex; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Terdapat kesalahan pada formulir:
                    </div>
                    <ul style="margin:4px 0 0 24px; padding:0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
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

@yield('modals')

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

// ==========================================
// AUTO-SEARCH DEBOUNCE DENGAN CURSOR PRESERVATION
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('input[name="q"], input[name="search"], input[name="keyword"], .search-box input, .ds-search-input, form.search-bar input');

    // Restore focus and cursor position after auto reload
    const activeSearchName = sessionStorage.getItem('simpeg_active_search_name');
    const savedCursorPos = sessionStorage.getItem('simpeg_search_cursor');
    if (activeSearchName) {
        sessionStorage.removeItem('simpeg_active_search_name');
        sessionStorage.removeItem('simpeg_search_cursor');
        
        searchInputs.forEach(input => {
            if ((input.name && input.name === activeSearchName) || (input.id && input.id === activeSearchName) || (input.classList.contains('ds-search-input') && activeSearchName === 'ds-search-input')) {
                input.focus();
                const pos = savedCursorPos !== null ? parseInt(savedCursorPos, 10) : input.value.length;
                try {
                    input.setSelectionRange(pos, pos);
                } catch (e) {}
            }
        });
    }

    searchInputs.forEach(input => {
        let debounceTimer = null;
        const form = input.closest('form');
        if (!form) return;

        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            // Jeda 450ms setelah berhenti mengetik
            debounceTimer = setTimeout(() => {
                sessionStorage.setItem('simpeg_active_search_name', input.name || input.id || 'q');
                sessionStorage.setItem('simpeg_search_cursor', input.selectionStart || input.value.length);
                form.submit();
            }, 450);
        });

        // Jika user tekan Enter, langsung submit seketika
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(debounceTimer);
                sessionStorage.setItem('simpeg_active_search_name', input.name || input.id || 'q');
                sessionStorage.setItem('simpeg_search_cursor', input.selectionStart || input.value.length);
                form.submit();
            }
        });
    });

    // ==========================================
    // INITIALIZE SEARCHABLE PEGAWAI SELECT PICKER
    // ==========================================
    initSearchablePegawaiSelects();
});

function initSearchablePegawaiSelects() {
    const selectElements = document.querySelectorAll('select#pegawai_id, select[name="pegawai_id"], select#modal-pegawai-id, select.searchable-pegawai, select[data-searchable="pegawai"]');

    selectElements.forEach(select => {
        if (select.dataset.searchableInitialized === 'true') return;
        select.dataset.searchableInitialized = 'true';

        const parent = select.parentElement;
        const options = Array.from(select.options);

        // Parsing options data
        const parsedOptions = options.map((opt, idx) => {
            const rawText = opt.text.trim();
            const val = opt.value;
            if (!val || val === '') {
                return { isPlaceholder: true, value: '', text: rawText, rawText: rawText };
            }

            // Parse patterns:
            // Pattern 1: "1711001 - MUKTI ALI (Direktur Utama)"
            // Pattern 2: "MUKTI ALI (NIK: 1711001) - Direktur Utama"
            let nik = '';
            let nama = rawText;
            let sub = '';

            const match1 = rawText.match(/^(\d+)\s*-\s*([^(]+)(?:\((.*)\))?$/);
            const match2 = rawText.match(/^([^(]+)\s*\(NIK:\s*(\d+)\)\s*(?:-\s*(.*))?$/i);

            if (match1) {
                nik = match1[1].trim();
                nama = match1[2].trim();
                sub = match1[3] ? match1[3].trim() : '';
            } else if (match2) {
                nama = match2[1].trim();
                nik = match2[2].trim();
                sub = match2[3] ? match2[3].trim() : '';
            } else {
                const dashSplit = rawText.split('-');
                if (dashSplit.length > 1) {
                    nik = dashSplit[0].trim();
                    nama = dashSplit.slice(1).join('-').trim();
                }
            }

            return {
                isPlaceholder: false,
                value: val,
                text: nama,
                nik: nik,
                sub: sub,
                rawText: rawText,
                selected: opt.selected
            };
        });

        // If native select was required, remove native required so hidden element doesn't block form submit
        const isRequired = select.hasAttribute('required') || select.required;
        if (isRequired) {
            select.removeAttribute('required');
            select.dataset.wasRequired = 'true';
        }

        // Hide the original native select
        select.style.cssText = 'position:absolute !important; opacity:0 !important; pointer-events:none !important; width:1px !important; height:1px !important; margin:-1px !important;';

        // Create Custom Container
        const container = document.createElement('div');
        container.className = 'css-search-container';

        const inputWrap = document.createElement('div');
        inputWrap.className = 'css-input-wrap';

        const searchIcon = document.createElement('div');
        searchIcon.innerHTML = `<svg class="css-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>`;
        inputWrap.appendChild(searchIcon.firstElementChild);

        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'css-input';
        searchInput.placeholder = 'Ketik NIK, nama, atau jabatan pegawai...';
        searchInput.autocomplete = 'off';
        searchInput.spellcheck = false;

        const actions = document.createElement('div');
        actions.className = 'css-actions';

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'css-clear-btn';
        clearBtn.innerHTML = '&times;';
        clearBtn.title = 'Hapus Pilihan';

        const arrowIcon = document.createElement('div');
        arrowIcon.innerHTML = `<svg class="css-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>`;

        actions.appendChild(clearBtn);
        actions.appendChild(arrowIcon.firstElementChild);

        inputWrap.appendChild(searchInput);
        inputWrap.appendChild(actions);
        container.appendChild(inputWrap);

        // Dropdown Menu
        const dropdown = document.createElement('div');
        dropdown.className = 'css-dropdown-menu';

        const listContainer = document.createElement('div');
        listContainer.className = 'css-list-container';

        const noResult = document.createElement('div');
        noResult.className = 'css-no-results';
        noResult.textContent = 'Tidak ada pegawai yang cocok.';
        noResult.style.display = 'none';

        dropdown.appendChild(listContainer);
        dropdown.appendChild(noResult);
        container.appendChild(dropdown);

        // Insert container right after native select
        select.parentNode.insertBefore(container, select.nextSibling);

        // Render Options
        let highlightedIndex = -1;
        let visibleItems = [];

        function renderOptions(query = '') {
            listContainer.innerHTML = '';
            visibleItems = [];
            const q = query.toLowerCase().trim();

            const filtered = parsedOptions.filter(opt => {
                if (opt.isPlaceholder) return false;
                if (!q) return true;
                return opt.rawText.toLowerCase().includes(q) ||
                       (opt.nik && opt.nik.toLowerCase().includes(q)) ||
                       (opt.text && opt.text.toLowerCase().includes(q)) ||
                       (opt.sub && opt.sub.toLowerCase().includes(q));
            });

            if (filtered.length === 0) {
                noResult.style.display = 'block';
            } else {
                noResult.style.display = 'none';
                filtered.forEach(opt => {
                    const item = document.createElement('div');
                    item.className = 'css-option-item' + (select.value === opt.value ? ' selected' : '');
                    item.dataset.value = opt.value;
                    item.dataset.raw = opt.rawText;

                    item.innerHTML = `
                        <div class="css-opt-header">
                            <span class="css-opt-name">${opt.text}</span>
                            ${opt.nik ? `<span class="css-opt-nik">${opt.nik}</span>` : ''}
                        </div>
                        ${opt.sub ? `<div class="css-opt-sub">${opt.sub}</div>` : ''}
                    `;

                    item.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectOption(opt);
                    });

                    listContainer.appendChild(item);
                    visibleItems.push({ element: item, data: opt });
                });
            }
            highlightedIndex = -1;
        }

        function selectOption(opt) {
            if (!opt || opt.isPlaceholder) {
                select.value = '';
                searchInput.value = '';
                clearBtn.style.display = 'none';
            } else {
                select.value = opt.value;
                searchInput.value = opt.rawText;
                clearBtn.style.display = 'flex';
            }
            closeDropdown();
            // Dispatch native change event
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function openDropdown() {
            inputWrap.classList.add('active');
            dropdown.classList.add('show');
            renderOptions(searchInput.value === getSelectedRawText() ? '' : searchInput.value);
        }

        function closeDropdown() {
            inputWrap.classList.remove('active');
            dropdown.classList.remove('show');
            const currentRaw = getSelectedRawText();
            if (select.value && currentRaw) {
                searchInput.value = currentRaw;
                clearBtn.style.display = 'flex';
            } else {
                searchInput.value = '';
                clearBtn.style.display = 'none';
            }
        }

        function getSelectedRawText() {
            const selectedOpt = parsedOptions.find(o => String(o.value) === String(select.value));
            return (selectedOpt && !selectedOpt.isPlaceholder) ? selectedOpt.rawText : '';
        }

        // Set initial selected value
        const initialSelected = parsedOptions.find(o => o.selected && !o.isPlaceholder) ||
                                parsedOptions.find(o => String(o.value) === String(select.value) && !o.isPlaceholder);
        if (initialSelected) {
            searchInput.value = initialSelected.rawText;
            clearBtn.style.display = 'flex';
        }

        // Event listeners
        searchInput.addEventListener('focus', openDropdown);
        searchInput.addEventListener('click', openDropdown);

        searchInput.addEventListener('input', function() {
            openDropdown();
            renderOptions(this.value);
            if (!this.value) {
                clearBtn.style.display = 'none';
            }
        });

        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            selectOption(null);
            searchInput.focus();
            openDropdown();
        });

        searchInput.addEventListener('keydown', function(e) {
            if (!dropdown.classList.contains('show')) {
                if (e.key === 'ArrowDown' || e.key === 'Enter') {
                    openDropdown();
                    e.preventDefault();
                    return;
                }
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (visibleItems.length === 0) return;
                highlightedIndex = (highlightedIndex + 1) % visibleItems.length;
                updateHighlight();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (visibleItems.length === 0) return;
                highlightedIndex = (highlightedIndex - 1 + visibleItems.length) % visibleItems.length;
                updateHighlight();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlightedIndex >= 0 && visibleItems[highlightedIndex]) {
                    selectOption(visibleItems[highlightedIndex].data);
                } else if (visibleItems.length === 1) {
                    selectOption(visibleItems[0].data);
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        function updateHighlight() {
            visibleItems.forEach((item, idx) => {
                if (idx === highlightedIndex) {
                    item.element.classList.add('highlighted');
                    item.element.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                } else {
                    item.element.classList.remove('highlighted');
                }
            });
        }

        // Global outside click listener
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                closeDropdown();
            }
        });

        // Listen for programmatic value change on native select
        select.addEventListener('change', function() {
            const raw = getSelectedRawText();
            if (raw) {
                searchInput.value = raw;
                clearBtn.style.display = 'flex';
            } else {
                searchInput.value = '';
                clearBtn.style.display = 'none';
            }
        });
    });
}
</script>
</body>
</html>
