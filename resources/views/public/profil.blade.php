@extends('layouts.app')
@section('title', $user->name.' — KreatifGallery')
@section('content')
<section class="kg-hero">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16 flex flex-col sm:flex-row gap-7 items-center sm:items-end">
        <img src="{{ $user->foto_url }}" alt="Foto profil {{ $user->name }}" class="w-32 h-32 rounded-full object-cover ring-4 ring-white shadow-sm">
        <div class="text-center sm:text-left flex-1">
            <h1 class="text-4xl sm:text-5xl font-extrabold">{{ $user->name }}</h1>
            <p class="mt-3 text-muted max-w-2xl whitespace-pre-line">{{ $user->bio ?: 'Kreator ini belum menulis biografi.' }}</p>
        </div>
        <div class="flex gap-8 sm:gap-10 shrink-0">
            <div class="text-center sm:text-left"><p class="font-display text-3xl font-extrabold">{{ $totalKarya }}</p><p class="text-xs text-muted">Total karya</p></div>
            <div class="text-center sm:text-left"><p class="font-display text-3xl font-extrabold">{{ $totalSuka }}</p><p class="text-xs text-muted">Total suka</p></div>
        </div>
    </div>
</section>

@if ($portofolios->isNotEmpty())
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-12">
    <h2 class="text-3xl font-extrabold mb-5">Portofolio</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($portofolios as $p)
            <div class="bg-white rounded-2xl ring-1 ring-black/5 p-6">
                <p class="font-display font-bold text-lg">{{ $p->judul }}</p>
                @if ($p->deskripsi) <p class="text-sm text-muted mt-1.5 line-clamp-2">{{ $p->deskripsi }}</p> @endif
                <p class="text-xs text-primary font-semibold mt-4">{{ $p->publik_count }} karya</p>
            </div>
        @endforeach
    </div>
</section>
@endif

<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-12">
    <h2 class="text-3xl font-extrabold mb-6">Karya</h2>
    @if ($karyas->isEmpty())
        <div class="bg-white rounded-3xl ring-1 ring-black/5 p-10 text-center text-muted">Belum ada karya yang tayang.</div>
    @else
        <div class="masonry">@foreach ($karyas as $karya) <x-karya-card :karya="$karya" /> @endforeach</div>
        {{ $karyas->links() }}
    @endif
</section>
@endsection
