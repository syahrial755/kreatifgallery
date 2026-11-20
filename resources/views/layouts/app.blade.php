<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KreatifGallery — Galeri Karya & Portofolio Digital')</title>
    <meta name="description" content="@yield('description', 'Temukan inspirasi, bagikan kreativitas, dan tunjukkan karya terbaikmu kepada dunia.')">
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
    @stack('head')
</head>
<body class="bg-canvas font-sans text-ink antialiased">
    @include('partials.navbar')

    <main>@yield('content')</main>

    @include('partials.footer')

    <script>
        window.__flash = @json(['success' => session('success'), 'error' => session('error'), 'info' => session('info')]);
    </script>
    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
