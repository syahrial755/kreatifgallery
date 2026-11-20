@extends('layouts.dashboard')
@section('title', 'Edit kategori')
@section('heading', 'Edit kategori')
@section('content')
@php $inp = 'w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none'; @endphp
<form method="POST" action="{{ route('admin.kategori.update', $kategori->id) }}" class="max-w-xl bg-white rounded-card border border-gray-100 shadow-sm p-6 space-y-4">
    @csrf @method('PUT')
    <div>
        <label for="nama_kategori" class="block text-sm font-semibold mb-1">Nama <span class="text-danger">*</span></label>
        <input id="nama_kategori" name="nama_kategori" required maxlength="80" value="{{ old('nama_kategori', $kategori->nama_kategori) }}" class="{{ $inp }}">
        @error('nama_kategori') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="deskripsi" class="block text-sm font-semibold mb-1">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="500" class="{{ $inp }}">{{ old('deskripsi', $kategori->deskripsi) }}</textarea>
        @error('deskripsi') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
    </div>
    <p class="text-xs text-muted">Tautan kategori (<code>{{ $kategori->slug }}</code>) tidak berubah saat nama diubah.</p>
    <div class="flex items-center gap-3">
        <button class="px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-secondary transition">Simpan</button>
        <a href="{{ route('admin.kategori.index') }}" class="px-5 py-3 rounded-xl font-semibold text-muted hover:bg-gray-50">Batal</a>
    </div>
</form>
@endsection
