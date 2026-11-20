@extends('layouts.dashboard')
@php $edit = $portofolio->exists; $cls = 'w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none'; @endphp
@section('title', $edit ? 'Edit portofolio' : 'Portofolio baru')
@section('heading', $edit ? 'Edit portofolio' : 'Portofolio baru')
@section('content')
<form method="POST" action="{{ $edit ? route('kreator.portofolio.update', $portofolio) : route('kreator.portofolio.store') }}" class="max-w-3xl space-y-5">
    @csrf @if ($edit) @method('PUT') @endif
    <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 space-y-5">
        <div>
            <label for="judul" class="block text-sm font-semibold mb-1">Judul <span class="text-danger">*</span></label>
            <input id="judul" name="judul" maxlength="150" required value="{{ old('judul', $portofolio->judul) }}" class="{{ $cls }}">
            @error('judul') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="deskripsi" class="block text-sm font-semibold mb-1">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000" class="{{ $cls }}">{{ old('deskripsi', $portofolio->deskripsi) }}</textarea>
            @error('deskripsi') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-3 text-sm font-semibold">
            <input type="checkbox" name="is_public" value="1" class="rounded border-gray-300 w-4 h-4" @checked(old('is_public', $portofolio->is_public))>
            Tampilkan di profil publik
        </label>
    </div>

    <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6">
        <h2 class="font-bold">Pilih karya</h2>
        @error('karya_ids.*') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
        @if ($karyas->isEmpty())
            <p class="text-sm text-muted mt-2">Anda belum punya karya. <a href="{{ route('kreator.karya.create') }}" class="text-primary font-semibold">Unggah karya</a> terlebih dahulu.</p>
        @else
            @php $sel = collect(old('karya_ids', $dipilih))->map(fn ($v) => (int) $v)->all(); @endphp
            <div class="mt-3 grid sm:grid-cols-2 gap-2 max-h-80 overflow-y-auto">
                @foreach ($karyas as $k)
                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 px-3 py-2.5 cursor-pointer hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                        <input type="checkbox" name="karya_ids[]" value="{{ $k->id }}" class="rounded border-gray-300 w-4 h-4" @checked(in_array($k->id, $sel, true))>
                        <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $k->judul }}</span>
                        <x-status-badge :status="$k->status" />
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-muted mt-3">Karya yang belum disetujui tidak tampil di profil publik.</p>
        @endif
    </div>

    <div class="flex items-center gap-3">
        <button class="px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-secondary transition">{{ $edit ? 'Simpan perubahan' : 'Buat portofolio' }}</button>
        <a href="{{ route('kreator.portofolio.index') }}" class="px-5 py-3 rounded-xl font-semibold text-muted hover:bg-white">Batal</a>
    </div>
</form>
@endsection
