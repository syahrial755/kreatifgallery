@props(['karya'])
@php
    $cover = $karya->medias->first();
    $isFoto = $karya->kategori?->slug === 'fotografi';
@endphp
<article class="kg-card group break-inside-avoid mb-6">
    <a href="{{ route('karya.show', $karya) }}" class="block relative overflow-hidden rounded-2xl bg-gray-200" tabindex="-1" aria-hidden="true">
        @if ($cover && $cover->isVideo())
            <video src="{{ $cover->url }}#t=0.1" preload="metadata" muted playsinline class="kg-img w-full aspect-[4/3] object-cover"></video>
            <span class="absolute top-3 left-3 bg-white/90 text-ink rounded-full p-1.5"><i data-lucide="play" class="w-3.5 h-3.5"></i></span>
        @elseif ($cover)
            <img src="{{ $cover->thumb_url ?? $cover->url }}" alt="" loading="lazy" class="kg-img w-full min-h-[8rem] object-cover">
            @if ($isFoto)
                <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-xs font-semibold text-ink">
                    <i data-lucide="camera" class="w-3.5 h-3.5"></i> Foto</span>
            @endif
        @else
            <div class="aspect-[4/3] flex items-center justify-center text-muted"><i data-lucide="image" class="w-8 h-8"></i></div>
        @endif
        @if ($karya->medias->count() > 1)
            <span class="absolute top-3 right-3 bg-white/90 backdrop-blur text-ink text-xs font-semibold rounded-full px-2.5 py-1 inline-flex items-center gap-1">
                <i data-lucide="layers" class="w-3 h-3"></i>{{ $karya->medias->count() }}</span>
        @endif
    </a>
    <div class="pt-3 px-0.5">
        <a href="{{ route('karya.show', $karya) }}" class="font-display font-bold text-[17px] leading-snug line-clamp-2 hover:text-primary">{{ $karya->judul }}</a>
        <div class="mt-2 flex items-center justify-between gap-3">
            <a href="{{ route('profil', $karya->user) }}" class="flex items-center gap-2 min-w-0">
                <img src="{{ $karya->user->foto_url }}" alt="" class="w-6 h-6 rounded-full object-cover">
                <span class="text-sm text-muted truncate hover:text-ink">{{ $karya->user->name }}</span>
            </a>
            <span class="flex items-center gap-3 text-xs text-muted shrink-0">
                <span class="inline-flex items-center gap-1" title="Suka"><i data-lucide="heart" class="w-3.5 h-3.5"></i>{{ $karya->likes_count ?? 0 }}</span>
                <span class="inline-flex items-center gap-1" title="Komentar"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i>{{ $karya->comments_count ?? 0 }}</span>
            </span>
        </div>
    </div>
</article>
