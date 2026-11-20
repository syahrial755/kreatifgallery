@extends('layouts.dashboard')
@section('title', 'Profil')
@section('heading', $user->isAdmin() ? 'Profile' : 'My Profile')
@section('content')
@php $cls = 'w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none'; @endphp
<form method="POST" action="{{ route('akun.profil.update') }}" enctype="multipart/form-data" class="max-w-2xl bg-white rounded-card border border-gray-100 shadow-sm p-6 space-y-5">
    @csrf @method('PUT')
    <div class="flex items-center gap-5">
        <img id="foto-preview" src="{{ $user->foto_url }}" alt="Foto profil" class="w-24 h-24 rounded-full object-cover">
        <div>
            <label class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold cursor-pointer hover:border-primary hover:text-primary">
                <i data-lucide="camera" class="w-4 h-4"></i> Ganti foto
                <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="sr-only">
            </label>
            @if ($user->foto_profil)
                <label class="mt-2 flex items-center gap-2 text-sm text-muted"><input type="checkbox" name="hapus_foto" value="1" class="rounded border-gray-300"> Hapus foto</label>
            @endif
            <p class="text-xs text-muted mt-1.5">JPG, PNG, atau WebP · maks. 2 MB</p>
            @error('foto') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label for="name" class="block text-sm font-semibold mb-1">Nama <span class="text-danger">*</span></label>
        <input id="name" name="name" required maxlength="100" value="{{ old('name', $user->name) }}" class="{{ $cls }}">
        @error('name') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">Email</label>
        <input value="{{ $user->email }}" disabled class="{{ $cls }} bg-gray-50 text-muted">
    </div>
    <div>
        <label for="bio" class="block text-sm font-semibold mb-1">Biografi</label>
        <textarea id="bio" name="bio" rows="4" maxlength="500" class="{{ $cls }}">{{ old('bio', $user->bio) }}</textarea>
        @error('bio') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
    </div>
    <div class="flex items-center gap-3">
        <button class="px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-secondary transition">Simpan profil</button>
        @if ($user->isKreator())
            <a href="{{ route('profil', $user) }}" class="px-5 py-3 rounded-xl font-semibold text-muted hover:bg-gray-50">Lihat profil publik</a>
        @endif
    </div>
</form>
<script>
    document.getElementById('foto').addEventListener('change', function (e) {
        var f = e.target.files[0]; if (f) document.getElementById('foto-preview').src = URL.createObjectURL(f);
    });
</script>
@endsection
