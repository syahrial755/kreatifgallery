@extends('layouts.app')
@section('title', 'Explore — KreatifGallery')
@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14">
    <h1 class="text-4xl sm:text-5xl font-extrabold">Explore</h1>
    <p class="text-muted mt-2 text-lg">Jelajahi karya yang sudah dikurasi admin.</p>

    <form method="GET" action="{{ route('explore') }}" class="mt-8 flex flex-col md:flex-row gap-3">
        @if ($aktif) <input type="hidden" name="kategori" value="{{ $aktif->slug }}"> @endif
        <div class="relative flex-1">
            <i data-lucide="search" class="w-4 h-4 text-muted absolute left-5 top-1/2 -translate-y-1/2"></i>
            <input type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="Cari judul atau deskripsi karya…"
                   class="w-full rounded-full border border-gray-200 bg-white pl-12 pr-5 py-3.5 focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none">
        </div>
        <select name="urut" onchange="this.form.submit()" aria-label="Urutkan"
                class="rounded-full border border-gray-200 bg-white px-5 py-3.5 font-semibold">
            <option value="terbaru" @selected($urut === 'terbaru')>Terbaru</option>
            <option value="populer" @selected($urut === 'populer')>Terpopuler</option>
        </select>
        <button class="px-7 py-3.5 rounded-full bg-ink text-white font-semibold hover:bg-primary transition">Cari</button>
    </form>

    <div class="mt-5 flex flex-wrap gap-2">
        @php $base = array_filter(['q' => $q, 'urut' => $urut !== 'terbaru' ? $urut : null]); @endphp
        <a href="{{ route('explore', $base) }}"
           class="px-4 py-2 rounded-full text-sm font-semibold border transition {{ ! $aktif ? 'bg-ink text-white border-ink' : 'bg-white border-gray-200 hover:border-ink' }}">Semua</a>
        @foreach ($kategoris as $k)
            <a href="{{ route('explore', $base + ['kategori' => $k->slug]) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold border transition {{ $aktif?->id === $k->id ? 'bg-ink text-white border-ink' : 'bg-white border-gray-200 hover:border-ink' }}">
                @if ($k->slug === 'fotografi') <i data-lucide="camera" class="w-3.5 h-3.5 {{ $aktif?->id === $k->id ? 'text-gold' : 'text-primary' }}"></i> @endif
                {{ $k->nama_kategori }}</a>
        @endforeach
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-10">
    @if ($karyas->isEmpty())
        <div class="bg-white rounded-3xl ring-1 ring-black/5 p-12 text-center">
            <i data-lucide="search-x" class="w-10 h-10 text-muted mx-auto"></i>
            <p class="font-display font-bold text-xl mt-3">Tidak ada karya yang cocok</p>
            <p class="text-sm text-muted mt-1">Coba kata kunci lain atau hapus filter kategori.</p>
            <a href="{{ route('explore') }}" class="inline-block mt-5 px-5 py-2.5 rounded-full bg-ink text-white font-semibold text-sm hover:bg-primary transition">Reset pencarian</a>
        </div>
    @else
        <p class="text-sm text-muted mb-5">{{ $karyas->total() }} karya ditemukan</p>
        @if ($aktif?->slug === 'fotografi')
            <div class="masonry-photo">@foreach ($karyas as $karya) <x-photo-tile :karya="$karya" /> @endforeach</div>
        @else
            <div class="masonry">@foreach ($karyas as $karya) <x-karya-card :karya="$karya" /> @endforeach</div>
        @endif
        {{ $karyas->links() }}
    @endif
</section>
@endsection
