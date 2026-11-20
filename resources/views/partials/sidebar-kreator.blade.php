@php
    $items = [
        ['kreator.dashboard', 'Dashboard', 'layout-dashboard', 'kreator.dashboard'],
        ['kreator.karya.index', 'My Works', 'images', 'kreator.karya.index'],
        ['kreator.karya.create', 'Upload Work', 'upload', 'kreator.karya.create'],
        ['kreator.portofolio.index', 'Portfolio', 'briefcase', 'kreator.portofolio.*'],
        ['notifikasi.index', 'Notifications', 'bell', 'notifikasi.*'],
        ['akun.profil', 'My Profile', 'user', 'akun.profil*'],
        ['akun.pengaturan', 'Settings', 'settings', 'akun.pengaturan'],
    ];
@endphp
@foreach ($items as [$route, $label, $icon, $pattern])
    @php $on = request()->routeIs($pattern); @endphp
    <a href="{{ route($route) }}" title="{{ $label }}"
       class="sb-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ $on ? 'bg-primary text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}"
       @if ($on) aria-current="page" @endif>
        <i data-lucide="{{ $icon }}" class="w-5 h-5 shrink-0"></i>
        <span class="sb-label flex-1">{{ $label }}</span>
        @if ($route === 'notifikasi.index' && ($unreadCount ?? 0) > 0)
            <span class="sb-label text-xs font-bold bg-danger text-white rounded-full px-2 py-0.5">{{ $unreadCount }}</span>
        @endif
    </a>
@endforeach
