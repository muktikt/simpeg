@php
    $menu = config('simpeg_menu');
    $myRole = session('simpeg_user.userlevel');

    $icons = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path d="M8 2v4M16 2v4"/>',
        'shield' => '<path d="M12 2l8 4v6c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-4z"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'report' => '<path d="M6 9V3h12v6"/><path d="M6 18h12v4H6z"/><rect x="4" y="9" width="16" height="9" rx="1"/>',
    ];

    $slugify = fn ($label) => \Illuminate\Support\Str::slug($label);

    $canSee = fn ($group) => empty($group['roles']) || in_array($myRole, $group['roles'], true);
@endphp

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-mark">SP</div>
        <div>
            <div class="brand-text-name">SIMPEG</div>
            <div class="brand-text-sub">v2.0</div>
        </div>
    </div>
    <nav class="nav-scroll">

        @foreach ($menu['single'] as $item)
            @continue(! $canSee($item))
            <a href="{{ $item['route_name'] ? route($item['route_name']) : route('placeholder', $slugify($item['label'])) }}"
               class="nav-link {{ request()->routeIs($item['route_name']) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $icons[$item['icon']] !!}</svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        @foreach ($menu['groups'] as $group)
            @continue(! $canSee($group))
            <div class="nav-group">
                <button class="nav-group-btn" onclick="toggleGroup(this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16">{!! $icons[$group['icon']] !!}</svg>
                    {{ $group['label'] }}
                    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="nav-group-items">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['route_name'] ? route($item['route_name']) : route('placeholder', $slugify($item['label'])) }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach

    </nav>
</aside>
