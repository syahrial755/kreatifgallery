@php
    $items = [
        ['admin.dashboard', 'Overview', 'layout-dashboard', 'admin.dashboard', null],
        ['admin.moderasi.index', 'Artwork Moderation', 'shield-check', 'admin.moderasi.*', $pendingCount ?? 0],
        ['admin.karya.index', 'All Works', 'images', 'admin.karya.*', null],
        ['admin.kategori.index', 'Categories', 'tags', 'admin.kategori.*', null],
        ['admin.pengguna.index', 'Users', 'users', 'admin.pengguna.*', null],
        ['admin.laporan.index', 'Reports', 'flag', 'admin.laporan.*', $newReportCount ?? 0],
        ['admin.analitik', 'Analytics', 'bar-chart-3', 'admin.analitik', null],
        ['notifikasi.index', 'Notifications', 'bell', 'notifikasi.*', $unreadCount ?? 0],
        ['akun.profil', 'Profile', 'user', 'akun.profil*', null],
        ['akun.pengaturan', 'Settings', 'settings', 'akun.pengaturan', null],
    ];
@endphp
@foreach ($items as [$route, $label, $icon, $pattern, $badge])
    @php $on = request()->routeIs($pattern); @endphp
    <a href="{{ route($route) }}" title="{{ $label }}"
       class="sb-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ $on ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}"
       @if ($on) aria-current="page" @endif>
        <i data-lucide="{{ $icon }}" class="w-5 h-5 shrink-0"></i>
        <span class="sb-label flex-1">{{ $label }}</span>
        @if ($badge)
            <span class="sb-label text-xs font-bold {{ $route === 'admin.moderasi.index' ? 'bg-warning text-dark' : 'bg-danger text-white' }} rounded-full px-2 py-0.5">{{ $badge }}</span>
        @endif
    </a>
@endforeach
