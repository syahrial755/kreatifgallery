@props(['karya'])
@php $cover = $karya->medias->first(fn ($x) => ! $x->isVideo()); @endphp
<article {{ $attributes->merge(['class' => 'kg-tile group min-w-0']) }}>
    <a href="{{ route('karya.show', $karya) }}" class="block relative overflow-hidden rounded-2xl bg-gray-200 aspect-square" tabindex="-1" aria-hidden="true">
        @if ($cover)
            <img src="{{ $cover->thumb_url ?? $cover->url }}" alt="" loading="lazy" class="kg-img absolute inset-0 w-full h-full object-cover">
        @else
            <div class="absolute inset-0 flex items-center justify-center text-muted"><i data-lucide="image" class="w-8 h-8"></i></div>
        @endif
    </a>
    <div class="pt-2.5 px-0.5 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('karya.show', $karya) }}" class="block font-display font-bold leading-snug truncate hover:text-primary">{{ $karya->judul }}</a>
            <p class="text-xs text-muted truncate">{{ $karya->user->name }}</p>
        </div>
        <span class="inline-flex items-center gap-1 text-xs text-muted shrink-0 pt-0.5" title="Suka"><i data-lucide="heart" class="w-3.5 h-3.5"></i>{{ $karya->likes_count ?? 0 }}</span>
    </div>
</article>
