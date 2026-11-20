<footer class="kg-dark mt-24 bg-dark text-gray-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-14 pb-10 grid gap-10 md:grid-cols-12">
        <div class="md:col-span-6">
            <div class="flex items-center gap-2.5 text-white">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-sm font-display font-extrabold">KG</span>
                <span class="font-display font-extrabold text-xl tracking-tight">KreatifGallery</span>
            </div>
            <p class="font-display text-2xl sm:text-3xl text-white font-bold leading-tight mt-6 max-w-md">Setiap karya punya cerita.</p>
            <p class="mt-3 text-sm text-gray-400 max-w-sm">Galeri foto, ilustrasi, dan karya digital. Setiap karya ditinjau admin sebelum tayang.</p>
        </div>
        <div class="md:col-span-3">
            <h4 class="text-white font-semibold text-sm mb-4">Jelajahi</h4>
            <ul class="space-y-2.5 text-sm">
                <li><a class="hover:text-white transition" href="{{ route('explore', ['kategori' => 'fotografi']) }}">Fotografi</a></li>
                <li><a class="hover:text-white transition" href="{{ route('explore') }}">Explore</a></li>
                <li><a class="hover:text-white transition" href="{{ route('categories') }}">Kategori</a></li>
                <li><a class="hover:text-white transition" href="{{ route('about') }}">Tentang</a></li>
            </ul>
        </div>
        <div class="md:col-span-3">
            <h4 class="text-white font-semibold text-sm mb-4">Akun</h4>
            <ul class="space-y-2.5 text-sm">
                @guest
                    <li><a class="hover:text-white transition" href="{{ route('login') }}">Login</a></li>
                    <li><a class="hover:text-white transition" href="{{ route('register') }}">Register</a></li>
                @else
                    <li><a class="hover:text-white transition" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isKreator() ? route('kreator.dashboard') : route('home')) }}">Dashboard</a></li>
                @endguest
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <p class="max-w-7xl mx-auto px-4 sm:px-6 py-5 text-xs text-gray-500">&copy; {{ date('Y') }} KreatifGallery</p>
    </div>
</footer>
