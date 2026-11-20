@extends('layouts.app')
@section('content')
@php
    $nHero = $heroFoto->count();
    $kreatorLogin = auth()->check() && auth()->user()->isKreator();
@endphp

{{-- Hero: foto adalah hal pertama yang dilihat --}}
<section class="kg-hero">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 sm:py-14 lg:py-16 grid lg:grid-cols-12 gap-10 lg:gap-12 items-center">
        <div class="lg:col-span-5">
            <h1 class="text-[2.6rem] sm:text-6xl lg:text-[4.25rem] font-extrabold leading-[1.02]">Setiap Karya Punya Cerita.</h1>
            <p class="mt-5 text-lg text-muted max-w-md">Foto, ilustrasi, dan karya digital dari para kreator. Jelajahi, beri suka, lalu pamerkan karyamu sendiri.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('explore') }}" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-full bg-ink text-white font-semibold hover:bg-primary transition">
                    Explore Works <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
                @if ($kreatorLogin)
                    <a href="{{ route('kreator.karya.create') }}" class="px-6 py-3.5 rounded-full border border-ink/20 font-semibold hover:bg-white transition">Start Creating</a>
                @elseif (! auth()->check())
                    <a href="{{ route('register') }}" class="px-6 py-3.5 rounded-full border border-ink/20 font-semibold hover:bg-white transition">Start Creating</a>
                @endif
            </div>
            @if ($totalFoto > 0)
                <p class="mt-8 text-sm text-muted flex items-center gap-2">
                    <i data-lucide="camera" class="w-4 h-4 text-primary shrink-0"></i>
                    {{ $totalFoto }} karya fotografi dari {{ $totalFotografer }} fotografer sudah tayang.
                </p>
            @endif
        </div>

        <div class="lg:col-span-7">
            @if ($nHero > 0)
                <div class="grid grid-cols-5 grid-rows-2 gap-3 h-[26rem] sm:h-[32rem] lg:h-[35rem]">
                    @foreach ($heroFoto as $i => $k)
                        @php
                            $m = $k->medias->first(fn ($x) => ! $x->isVideo()) ?? $k->medias->first();
                            $span = $i === 0
                                ? ($nHero === 1 ? 'col-span-5 row-span-2' : 'col-span-3 row-span-2')
                                : ($nHero === 2 ? 'col-span-2 row-span-2' : 'col-span-2');
                        @endphp
                        <a href="{{ route('karya.show', $k) }}" class="kg-hero-photo group relative block overflow-hidden rounded-2xl bg-gray-200 {{ $span }}">
                            <img src="{{ $m->url }}" alt="{{ $k->judul }}" @if ($i > 0) loading="lazy" @endif
                                 class="absolute inset-0 w-full h-full object-cover transition duration-700 group-hover:scale-[1.04]">
                            <div class="absolute left-3 bottom-3 right-3 sm:left-4 sm:bottom-4 sm:right-auto sm:max-w-[85%] rounded-xl bg-white/90 backdrop-blur px-3.5 py-2.5 text-ink shadow-sm">
                                @if ($i === 0)
                                    <p class="text-xs font-semibold text-primary inline-flex items-center gap-1.5"><i data-lucide="award" class="w-3.5 h-3.5"></i> Foto paling disukai</p>
                                @endif
                                <p class="font-display font-bold {{ $i === 0 ? 'text-base sm:text-lg' : 'text-sm' }} leading-tight line-clamp-1">{{ $k->judul }}</p>
                                <p class="text-xs text-muted truncate">{{ $k->user->name }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border-2 border-dashed border-ink/15 h-[22rem] flex flex-col items-center justify-center text-center p-8">
                    <i data-lucide="image-plus" class="w-10 h-10 text-muted"></i>
                    <p class="font-display font-bold mt-3 text-lg">Belum ada karya yang tayang</p>
                    <p class="text-sm text-muted mt-1">Karya pertama akan muncul di sini setelah disetujui admin.</p>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- Fotografi --}}
@if ($galeriFoto->isNotEmpty())
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <div>
            <h2 class="text-3xl sm:text-4xl font-extrabold">Fotografi</h2>
            <p class="text-muted mt-1.5">Lanskap, jalanan, potret, dan kuliner lewat lensa para kreator.</p>
        </div>
        <a href="{{ route('explore', ['kategori' => 'fotografi']) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold rounded-full border border-gray-300 px-4 py-2 hover:border-primary hover:text-primary transition">
            Lihat semua foto <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
    </div>
    <div class="masonry-photo">
        @foreach ($galeriFoto->take(12) as $k) <x-photo-tile :karya="$k" /> @endforeach
    </div>
</section>
@endif

{{-- Kategori --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
    <div class="flex items-end justify-between gap-3 mb-6">
        <h2 class="text-3xl font-extrabold">Kategori</h2>
        <a href="{{ route('categories') }}" class="text-sm font-semibold text-primary hover:underline">Lihat semua</a>
    </div>
    @if ($kategoris->isEmpty())
        <p class="text-sm text-muted">Belum ada kategori.</p>
    @else
        <div class="kg-cats">
            @foreach ($kategoris as $k)
                @php $sm = $k->sampul?->medias->first(fn ($x) => ! $x->isVideo()); @endphp
                <a href="{{ route('explore', ['kategori' => $k->slug]) }}" class="kg-tile group relative block aspect-[4/5] overflow-hidden rounded-2xl bg-gray-200 text-ink">
                    @if ($sm && ! $sm->isVideo())
                        <img src="{{ $sm->thumb_url ?? $sm->url }}" alt="" loading="lazy" class="kg-img absolute inset-0 w-full h-full object-cover">
                    @endif
                    <div class="absolute left-3 right-3 bottom-3 rounded-xl bg-white/90 backdrop-blur px-3.5 py-2.5">
                        <p class="font-display font-bold leading-tight inline-flex items-center gap-1.5">
                            @if ($k->slug === 'fotografi') <i data-lucide="camera" class="w-4 h-4 text-primary"></i> @endif
                            {{ $k->nama_kategori }}</p>
                        <p class="text-xs text-muted">{{ $k->publik_count }} karya</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>

{{-- Populer --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
    <div class="flex items-end justify-between gap-3 mb-6">
        <h2 class="text-3xl font-extrabold">Karya populer</h2>
        <a href="{{ route('explore', ['urut' => 'populer']) }}" class="text-sm font-semibold text-primary hover:underline">Lihat semua</a>
    </div>
    @if ($popular->isEmpty())
        <p class="text-muted text-sm">Belum ada karya populer.</p>
    @else
        <div class="masonry">@foreach ($popular as $karya) <x-karya-card :karya="$karya" /> @endforeach</div>
    @endif
</section>

{{-- Terbaru --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-12">
    <div class="flex items-end justify-between gap-3 mb-6">
        <h2 class="text-3xl font-extrabold">Karya terbaru</h2>
        <a href="{{ route('explore') }}" class="text-sm font-semibold text-primary hover:underline">Lihat semua</a>
    </div>
    @if ($latest->isEmpty())
        <p class="text-muted text-sm">Belum ada karya terbaru.</p>
    @else
        <div class="masonry">@foreach ($latest as $karya) <x-karya-card :karya="$karya" /> @endforeach</div>
    @endif
</section>

{{-- Kreator unggulan --}}
@if ($creators->isNotEmpty())
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-14">
    <h2 class="text-3xl font-extrabold mb-6">Kreator unggulan</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($creators as $c)
            <a href="{{ route('profil', $c) }}" class="group bg-white rounded-2xl ring-1 ring-black/5 hover:ring-primary/40 hover:shadow-lg hover:shadow-primary/5 transition p-6 text-center">
                <img src="{{ $c->foto_url }}" alt="" class="w-20 h-20 rounded-full object-cover mx-auto ring-4 ring-canvas">
                <p class="font-display font-bold text-lg mt-4 group-hover:text-primary">{{ $c->name }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $c->publik_count }} karya</p>
                @if ($c->bio) <p class="text-sm text-muted mt-3 line-clamp-2">{{ $c->bio }}</p> @endif
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- Ajakan untuk kreator --}}
@if (! auth()->check() || $kreatorLogin)
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-16">
    <div class="kg-hero rounded-3xl ring-1 ring-black/5 px-6 sm:px-12 py-10 sm:py-14 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="max-w-xl">
            <h2 class="text-3xl sm:text-4xl font-extrabold">Punya foto atau karya yang ingin dipamerkan?</h2>
            <p class="mt-3 text-muted">Unggah karyamu, admin meninjau, lalu tayang di galeri untuk dilihat semua orang.</p>
        </div>
        @if ($kreatorLogin)
            <a href="{{ route('kreator.karya.create') }}" class="shrink-0 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-ink text-white font-semibold hover:bg-primary transition">
                <i data-lucide="upload" class="w-4 h-4"></i> Unggah karya baru</a>
        @else
            <a href="{{ route('register') }}" class="shrink-0 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-ink text-white font-semibold hover:bg-primary transition">
                Daftar sebagai kreator</a>
        @endif
    </div>
</section>
@endif
@endsection
