<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KreatifGallery')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'], display: ['Bricolage Grotesque', 'Plus Jakarta Sans', 'system-ui', 'sans-serif'] },
            colors: { primary: '#635BDB', secondary: '#8B83F6', canvas: '#F4F5F8', ink: '#1B1D2A', muted: '#6E7387', gold: '#F0B24B', dark: '#171923' }
        } } }
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="kg-hero font-sans text-ink antialiased min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-md">
        <a href="{{ route('home') }}" class="flex items-center justify-center gap-2.5 mb-7 text-ink">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-sm font-display font-extrabold">KG</span>
            <span class="font-display text-2xl font-extrabold tracking-tight">KreatifGallery</span>
        </a>
        <div class="bg-white rounded-3xl shadow-xl shadow-primary/10 ring-1 ring-black/5 p-7 sm:p-9">
            @yield('content')
        </div>
    </main>
</body>
</html>
