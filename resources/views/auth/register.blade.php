@extends('layouts.guest')
@section('title', 'Daftar — KreatifGallery')
@section('content')
    <h1 class="text-3xl font-extrabold">Buat akun</h1>
    <p class="text-muted text-sm mt-1 mb-6">Bergabung sebagai kreator atau pengunjung.</p>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-semibold mb-1">Nama</label>
            <input id="name" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
            @error('name') <p class="text-sm mt-1" style="color:#E05252">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-semibold mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
            @error('email') <p class="text-sm mt-1" style="color:#E05252">{{ $message }}</p> @enderror
        </div>
        <div>
            <span class="block text-sm font-semibold mb-1">Saya ingin mendaftar sebagai</span>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['kreator' => 'Kreator', 'pengunjung' => 'Pengunjung'] as $val => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $val }}" class="peer sr-only"
                               @checked(old('role', 'kreator') === $val)>
                        <span class="block text-center rounded-xl border border-gray-200 py-2.5 text-sm font-semibold peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('role') <p class="text-sm mt-1" style="color:#E05252">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-semibold mb-1">Kata sandi</label>
            <input id="password" name="password" type="password" required minlength="8"
                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
            @error('password') <p class="text-sm mt-1" style="color:#E05252">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-semibold mb-1">Konfirmasi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="w-full rounded-xl border border-gray-200 px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
        </div>
        <button class="w-full rounded-full bg-primary hover:bg-secondary text-white font-semibold py-2.5 transition">Daftar</button>
    </form>

    <p class="text-sm text-muted text-center mt-6">Sudah punya akun?
        <a href="{{ route('login') }}" class="text-primary font-semibold">Masuk</a></p>
@endsection
