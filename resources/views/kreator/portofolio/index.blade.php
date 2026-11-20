@extends('layouts.dashboard')
@section('title', 'Portfolio')
@section('heading', 'Portfolio')
@section('content')
<div class="flex items-center justify-between gap-4">
    <p class="text-muted text-sm">Kelompokkan karya Anda ke dalam portofolio. Portofolio publik tampil di profil Anda (hanya karya yang sudah disetujui).</p>
    <a href="{{ route('kreator.portofolio.create') }}" class="shrink-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm hover:bg-secondary transition">
        <i data-lucide="plus" class="w-4 h-4"></i> Portofolio baru</a>
</div>

<div class="mt-5 grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
    @forelse ($portofolios as $p)
        <article class="bg-white rounded-card border border-gray-100 shadow-sm p-5 flex flex-col">
            <div class="flex items-start justify-between gap-3">
                <h2 class="font-bold leading-snug">{{ $p->judul }}</h2>
                <span class="shrink-0 text-xs font-bold rounded-full px-2.5 py-1 {{ $p->is_public ? 'bg-success/15 text-success' : 'bg-gray-100 text-muted' }}">{{ $p->is_public ? 'Publik' : 'Privat' }}</span>
            </div>
            <p class="text-sm text-muted mt-2 line-clamp-3 flex-1">{{ $p->deskripsi ?: 'Tanpa deskripsi.' }}</p>
            <p class="text-xs font-semibold text-primary mt-3">{{ $p->karyas_count }} karya</p>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('kreator.portofolio.edit', $p) }}" class="flex-1 text-center px-3 py-2 rounded-xl border border-gray-200 text-sm font-semibold hover:border-primary hover:text-primary">Edit</a>
                <form method="POST" action="{{ route('kreator.portofolio.destroy', $p) }}" data-confirm="Hapus portofolio &quot;{{ $p->judul }}&quot;? Karya di dalamnya tidak ikut terhapus."
                      data-confirm-button="Ya, hapus" data-confirm-color="#E05252">
                    @csrf @method('DELETE')
                    <button class="px-3 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-danger hover:bg-danger/10" aria-label="Hapus portofolio"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </form>
            </div>
        </article>
    @empty
        <div class="sm:col-span-2 xl:col-span-3 bg-white rounded-card border border-gray-100 p-12 text-center">
            <i data-lucide="briefcase" class="w-10 h-10 text-muted mx-auto"></i>
            <p class="font-bold mt-3">Belum ada portofolio</p>
            <a href="{{ route('kreator.portofolio.create') }}" class="inline-block mt-4 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-secondary">Buat portofolio</a>
        </div>
    @endforelse
</div>
@endsection
