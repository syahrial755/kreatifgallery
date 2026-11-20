@extends('layouts.dashboard')
@section('title', 'Categories')
@section('heading', 'Categories')
@section('content')
@php $inp = 'w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none'; @endphp
<div class="grid xl:grid-cols-3 gap-6">
    <form method="POST" action="{{ route('admin.kategori.store') }}" class="bg-white rounded-card border border-gray-100 shadow-sm p-6 space-y-4 h-fit">
        @csrf
        <h2 class="font-extrabold">Kategori baru</h2>
        <div>
            <label for="nama_kategori" class="block text-sm font-semibold mb-1">Nama <span class="text-danger">*</span></label>
            <input id="nama_kategori" name="nama_kategori" required maxlength="80" value="{{ old('nama_kategori') }}" class="{{ $inp }}">
            @error('nama_kategori') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="deskripsi" class="block text-sm font-semibold mb-1">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="500" class="{{ $inp }}">{{ old('deskripsi') }}</textarea>
            @error('deskripsi') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
        </div>
        <button class="w-full px-5 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-secondary transition">Tambah kategori</button>
    </form>

    <div class="xl:col-span-2 bg-white rounded-card border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            <thead class="text-left text-xs uppercase tracking-wide text-muted border-b border-gray-100">
                <tr><th class="px-5 py-3">Kategori</th><th class="px-3 py-3 text-center">Karya</th><th class="px-3 py-3">Dikelola</th><th class="px-5 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody>
            @forelse ($kategoris as $k)
                <tr class="border-b border-gray-50 last:border-0">
                    <td class="px-5 py-3"><p class="font-bold">{{ $k->nama_kategori }}</p><p class="text-xs text-muted line-clamp-1">{{ $k->deskripsi ?: '—' }}</p></td>
                    <td class="px-3 py-3 text-center">{{ $k->karyas_count }}</td>
                    <td class="px-3 py-3 text-muted">{{ $k->admin?->name ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.kategori.edit', $k->id) }}" class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold hover:border-primary hover:text-primary">Edit</a>
                            @if ($k->karyas_count > 0)
                                <button type="button" disabled title="Masih dipakai {{ $k->karyas_count }} karya"
                                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-300 cursor-not-allowed">Hapus</button>
                            @else
                                <form method="POST" action="{{ route('admin.kategori.destroy', $k->id) }}" data-confirm="Hapus kategori &quot;{{ $k->nama_kategori }}&quot;?" data-confirm-button="Ya, hapus" data-confirm-color="#E05252">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1.5 rounded-lg border border-danger/40 text-danger text-xs font-semibold hover:bg-danger/10">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-12 text-center text-muted">Belum ada kategori.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
