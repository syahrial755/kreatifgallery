@extends('layouts.dashboard')
@section('title', 'Settings')
@section('heading', 'Settings')
@section('content')
@php $cls = 'w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none'; @endphp
<form method="POST" action="{{ route('akun.password.update') }}" class="max-w-xl bg-white rounded-card border border-gray-100 shadow-sm p-6 space-y-5">
    @csrf @method('PUT')
    <div>
        <h2 class="font-extrabold">Ubah kata sandi</h2>
        <p class="text-sm text-muted mt-1">Gunakan minimal 8 karakter.</p>
    </div>
    <div>
        <label for="current_password" class="block text-sm font-semibold mb-1">Kata sandi saat ini</label>
        <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="{{ $cls }}">
        @error('current_password') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="password" class="block text-sm font-semibold mb-1">Kata sandi baru</label>
        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="{{ $cls }}">
        @error('password') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="password_confirmation" class="block text-sm font-semibold mb-1">Konfirmasi kata sandi baru</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $cls }}">
    </div>
    <button class="px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-secondary transition">Perbarui kata sandi</button>
</form>
@endsection
