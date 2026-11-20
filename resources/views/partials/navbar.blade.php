@php
    $links = [
        ['home', 'Home', 'home'],
        ['explore', 'Explore', 'explore'],
        ['categories', 'Categories', 'categories'],
        ['about', 'About', 'about'],
    ];
    $user = auth()->user();
@endphp
<header class="sticky top-0 z-40 bg-white/85 backdrop-blur-md border-b border-black/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-extrabold text-lg shrink-0">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary to-secondary text-white flex items-center justify-center text-sm font-display">KG</span>
            <span class="font-display text-xl tracking-tight">KreatifGallery</span>
        </a>

        <nav class="hidden md:flex items-center gap-1" aria-label="Navigasi utama">
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}"
                   class="px-4 py-2 rounded-full text-sm font-semibold transition {{ request()->routeIs($route) ? 'text-primary bg-primary/10' : 'text-muted hover:text-ink hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="hidden md:flex items-center gap-2">
            @guest
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-full text-sm font-semibold hover:bg-gray-100">Login</a>
                <a href="{{ route('register') }}" class="px-5 py-2 rounded-full text-sm font-semibold bg-ink text-white hover:bg-primary transition">Register</a>
            @else
                @if ($user->isKreator())
                    <a href="{{ route('kreator.karya.create') }}" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full text-sm font-semibold bg-ink text-white hover:bg-primary transition">
                        <i data-lucide="upload" class="w-4 h-4"></i> Upload Work</a>
                @endif
                @include('partials.notif-dropdown')
                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-toggle class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full hover:bg-gray-50" aria-haspopup="true">
                        <img src="{{ $user->foto_url }}" alt="" class="w-8 h-8 rounded-full object-cover">
                        <span class="text-sm font-semibold max-w-[120px] truncate">{{ $user->name }}</span>
                    </button>
                    <div data-dropdown-menu class="hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl ring-1 ring-black/5 shadow-xl p-1.5 text-sm">
                        @if ($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gray-50"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard Admin</a>
                        @elseif ($user->isKreator())
                            <a href="{{ route('kreator.dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gray-50"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard Kreator</a>
                            <a href="{{ route('profil', $user) }}" class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gray-50"><i data-lucide="user" class="w-4 h-4"></i> Profil publik</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="w-full flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gray-50 text-danger"><i data-lucide="log-out" class="w-4 h-4"></i> Logout</button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>

        <button type="button" id="menu-toggle" class="md:hidden p-2.5 rounded-xl hover:bg-gray-50" aria-label="Buka menu" aria-expanded="false">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>

    {{-- Menu mobile: target sentuh >= 44px --}}
    <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-white px-4 pb-4 pt-2">
        @foreach ($links as [$route, $label])
            <a href="{{ route($route) }}" class="block py-3 px-3 rounded-xl font-semibold {{ request()->routeIs($route) ? 'text-primary bg-primary/10' : '' }}">{{ $label }}</a>
        @endforeach
        <div class="mt-2 pt-2 border-t border-gray-100 space-y-1">
            @guest
                <a href="{{ route('login') }}" class="block py-3 px-3 rounded-xl font-semibold">Login</a>
                <a href="{{ route('register') }}" class="block py-3 px-3 rounded-xl font-semibold bg-primary text-white text-center">Register</a>
            @else
                @if ($user->isKreator())
                    <a href="{{ route('kreator.karya.create') }}" class="block py-3 px-3 rounded-xl font-semibold bg-primary text-white text-center">Upload Work</a>
                @elseif ($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block py-3 px-3 rounded-xl font-semibold">Dashboard Admin</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="w-full text-left py-3 px-3 rounded-xl font-semibold text-danger">Logout ({{ $user->name }})</button>
                </form>
            @endguest
        </div>
    </div>
</header>
