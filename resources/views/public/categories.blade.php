@extends('layouts.app')
@section('title', 'Kategori — KreatifGallery')
@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14">
    <h1 class="text-4xl sm:text-5xl font-extrabold">Kategori</h1>
    <p class="text-muted mt-2 text-lg">Pilih bidang kreatif yang ingin Anda jelajahi.</p>

    @if ($kategoris->isEmpty())
        <p class="mt-8 text-muted">Belum ada kategori.</p>
    @else
        <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($kategoris as $k)
                @php $sm = $k->sampul?->medias->first(fn ($x) => ! $x->isVideo()); @endphp
                <a href="{{ route('explore', ['kategori' => $k->slug]) }}"
                   class="kg-tile group relative block overflow-hidden rounded-3xl bg-gray-200 text-ink {{ $loop->first && $k->slug === 'fotografi' ? 'sm:col-span-2 aspect-[16/9] sm:aspect-[21/9]' : 'aspect-[4/3]' }}">
                    @if ($sm && ! $sm->isVideo())
                        <img src="{{ $sm->thumb_url ?? $sm->url }}" alt="" loading="lazy" class="kg-img absolute inset-0 w-full h-full object-cover">
                    @endif
                                    <div class="absolute left-4 right-4 bottom-4 rounded-2xl bg-white/90 backdrop-blur px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-display font-bold text-2xl inline-flex items-center gap-2">
                                @if ($k->slug === 'fotografi') <i data-lucide="camera" class="w-5 h-5 text-primary"></i> @endif
                                {{ $k->nama_kategori }}</h2>
                            <span class="text-xs font-semibold bg-primary/10 text-primary rounded-full px-3 py-1 shrink-0">{{ $k->publik_count }} karya</span>
                        </div>
                        <p class="text-sm text-muted mt-1 line-clamp-2 max-w-xl">{{ $k->deskripsi ?: 'Jelajahi karya pada kategori ini.' }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
@endsection
