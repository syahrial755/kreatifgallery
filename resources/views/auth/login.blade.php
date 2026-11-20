@extends('layouts.guest')
@section('title', 'Masuk — KreatifGallery')
@section('content')
    <h1 class="text-3xl font-extrabold">Masuk</h1>
    <p class="text-muted text-sm mt-1 mb-6">Lanjutkan ke galeri dan portofolio Anda.</p>

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-semibold mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
            @error('email') <p class="text-sm mt-1" style="color:#E05252">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-semibold mb-1">Kata sandi</label>
            <input id="password" name="password" type="password" required
                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
            @error('password') <p class="text-sm mt-1" style="color:#E05252">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" name="remember" class="rounded border-gray-300"> Ingat saya
        </label>
        <button class="w-full rounded-full bg-primary hover:bg-secondary text-white font-semibold py-2.5 transition">Masuk</button>
    </form>

    <p class="text-sm text-muted text-center mt-6">Belum punya akun?
        <a href="{{ route('register') }}" class="text-primary font-semibold">Daftar</a></p>
@endsection
