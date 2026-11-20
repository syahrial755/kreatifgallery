@extends('layouts.dashboard')
@section('title', 'Artwork Moderation')
@section('heading', 'Artwork Moderation')
@section('content')
<p class="text-sm text-muted">Karya berstatus “menunggu”, diurutkan dari yang paling lama menunggu. Karya ini belum tampil di galeri publik.</p>

<div class="mt-5 grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
    @forelse ($karyas as $k)
        @php $m = $k->medias->first(); @endphp
        <article class="bg-white rounded-card border border-gray-100 shadow-sm overflow-hidden flex flex-col">
            <a href="{{ route('admin.moderasi.show', $k) }}" class="block relative bg-gray-100 aspect-[4/3] overflow-hidden">
                @if ($m && ! $m->isVideo()) <img src="{{ $m->thumb_url ?? $m->url }}" alt="{{ $k->judul }}" class="w-full h-full object-cover">
                @elseif ($m) <video src="{{ $m->url }}#t=0.1" preload="metadata" muted class="w-full h-full object-cover"></video>
                @else <span class="absolute inset-0 flex items-center justify-center text-muted"><i data-lucide="image" class="w-8 h-8"></i></span> @endif
                @if ($k->medias->count() > 1)
                    <span class="absolute top-3 right-3 bg-dark/70 text-white text-xs font-semibold rounded-full px-2 py-1">{{ $k->medias->count() }} file</span>
                @endif
            </a>
            <div class="p-5 flex-1 flex flex-col">
                <a href="{{ route('admin.moderasi.show', $k) }}" class="font-bold leading-snug line-clamp-2 hover:text-primary">{{ $k->judul }}</a>
                <p class="text-sm text-muted mt-1">{{ $k->user->name }} · {{ $k->kategori->nama_kategori }}</p>
                <p class="text-xs text-muted mt-0.5">Diajukan {{ $k->created_at->diffForHumans() }}</p>
                <div class="mt-4 flex gap-2 mt-auto pt-4">
                    <a href="{{ route('admin.moderasi.show', $k) }}" class="flex-1 text-center px-3 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-secondary">Tinjau</a>
                    <form method="POST" action="{{ route('admin.moderasi.approve', $k) }}" data-confirm="Setujui &quot;{{ $k->judul }}&quot; dan tayangkan di galeri?" data-confirm-button="Ya, setujui" data-confirm-color="#16A36A" data-confirm-icon="question">
                        @csrf <button class="px-3 py-2 rounded-xl border border-success/40 text-success text-sm font-semibold hover:bg-success/10" aria-label="Setujui cepat"><i data-lucide="check" class="w-4 h-4"></i></button>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="sm:col-span-2 xl:col-span-3 bg-white rounded-card border border-gray-100 p-14 text-center">
            <i data-lucide="check-check" class="w-12 h-12 text-success mx-auto"></i>
            <p class="font-bold mt-3 text-lg">Antrian kosong</p>
            <p class="text-sm text-muted mt-1">Tidak ada karya yang menunggu moderasi.</p>
        </div>
    @endforelse
</div>
{{ $karyas->links() }}
@endsection
