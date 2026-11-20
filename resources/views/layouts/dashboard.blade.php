@php $authUser = auth()->user(); $isAdmin = $authUser->isAdmin(); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — KreatifGallery</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'], display: ['Bricolage Grotesque', 'Plus Jakarta Sans', 'system-ui', 'sans-serif'] },
            colors: { primary: '#635BDB', secondary: '#8B83F6', canvas: '#F4F5F8', ink: '#1B1D2A', muted: '#6E7387', gold: '#F0B24B',
                      dark: '#171923', success: '#16A36A', warning: '#E9A23B', danger: '#E05252' },
            borderRadius: { card: '16px' }
        } } }
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script>try { if (localStorage.getItem('kg-sidebar') === 'collapsed') document.documentElement.classList.add('sidebar-collapsed'); } catch (e) {}</script>
    @stack('head')
</head>
<body class="bg-canvas font-sans text-ink antialiased">
    <div id="sidebar-overlay" class="hidden fixed inset-0 bg-dark/50 z-40 lg:hidden"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-dark text-gray-300 flex flex-col -translate-x-full lg:translate-x-0 transition-transform lg:transition-[width]">
        <div class="h-16 px-5 flex items-center gap-2.5 border-b border-white/10 shrink-0">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary to-secondary text-white flex items-center justify-center text-sm font-display font-extrabold shrink-0">KG</span>
            <span class="sb-label font-display text-lg font-extrabold tracking-tight text-white">KreatifGallery</span>
        </div>
        <nav class="flex-1 overflow-y-auto p-3 space-y-1" aria-label="Navigasi dashboard">
            @include($isAdmin ? 'partials.sidebar-admin' : 'partials.sidebar-kreator')
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="p-3 border-t border-white/10 shrink-0">@csrf
            <button class="sb-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-300 hover:bg-white/10 hover:text-white" title="Logout">
                <i data-lucide="log-out" class="w-5 h-5 shrink-0"></i><span class="sb-label">Logout</span>
            </button>
        </form>
    </aside>

    <div id="dash-main" class="lg:pl-64 min-h-screen flex flex-col transition-[padding]">
        <header class="sticky top-0 z-30 h-16 bg-white/85 backdrop-blur-md border-b border-black/5 px-4 sm:px-6 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="sidebar-toggle" class="p-2.5 rounded-xl hover:bg-gray-50" aria-label="Ciutkan atau buka sidebar">
                    <i data-lucide="panel-left" class="w-5 h-5"></i></button>
                <h1 class="font-extrabold text-lg truncate">@yield('heading', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('home') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-semibold text-muted hover:bg-gray-50">
                    <i data-lucide="external-link" class="w-4 h-4"></i> Lihat situs</a>
                @include('partials.notif-dropdown')
                <div class="flex items-center gap-2 pl-1">
                    <img src="{{ $authUser->foto_url }}" alt="" class="w-9 h-9 rounded-full object-cover">
                    <span class="hidden md:block text-sm font-semibold max-w-[140px] truncate">{{ $authUser->name }}</span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 max-w-[1400px] w-full mx-auto">
            @if ($errors->any() && ! $errors->has('media'))
                <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger" role="alert">
                    <p class="font-semibold">Periksa kembali isian Anda.</p>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <script>
        window.__flash = @json(['success' => session('success'), 'error' => session('error'), 'info' => session('info')]);
    </script>
    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
