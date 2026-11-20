@extends('layouts.app')
@section('title', $karya->judul.' — KreatifGallery')
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $karya->deskripsi), 150))
@section('content')
@php $first = $karya->medias->first(); @endphp
<section class="max-w-7xl mx-auto px-4 sm:px-6 pt-8">
    <nav class="text-sm text-muted mb-4 flex items-center gap-1.5">
        <a href="{{ route('explore') }}" class="hover:text-primary">Explore</a><span>/</span>
        <a href="{{ route('explore', ['kategori' => $karya->kategori->slug]) }}" class="hover:text-primary">{{ $karya->kategori->nama_kategori }}</a>
    </nav>

    @unless ($karya->isPublik())
        <div class="mb-5 rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm">
            <strong>Pratinjau pribadi.</strong> Karya ini berstatus <strong>{{ $karya->status }}</strong> dan belum tampil di galeri publik.
            @if ($karya->status === 'ditolak' && $karya->catatan_moderasi) <br>Catatan moderasi: {{ $karya->catatan_moderasi }} @endif
        </div>
    @endunless

    <div class="grid lg:grid-cols-3 gap-8">
        {{-- Media --}}
        <div class="lg:col-span-2 lg:row-start-1">
            <div data-gallery>
                <div data-gallery-stage class="bg-white ring-1 ring-black/5 rounded-3xl overflow-hidden p-2 sm:p-4 flex items-center justify-center min-h-[18rem]">
                    @if ($first?->isVideo())
                        <video src="{{ $first->url }}" controls playsinline class="w-full max-h-[78vh] object-contain mx-auto rounded-xl"></video>
                    @elseif ($first)
                        <img src="{{ $first->url }}" alt="{{ $karya->judul }}" class="w-full max-h-[78vh] object-contain mx-auto rounded-xl">
                    @else
                        <div class="aspect-[4/3] flex items-center justify-center text-muted"><i data-lucide="image" class="w-10 h-10"></i></div>
                    @endif
                </div>
                @if ($karya->medias->count() > 1)
                    <div class="mt-3 flex gap-2.5 overflow-x-auto pb-1">
                        @foreach ($karya->medias as $i => $m)
                            <button type="button" data-gallery-thumb data-type="{{ $m->tipe_media }}" data-src="{{ $m->url }}" data-alt="{{ $karya->judul }}"
                                    @if ($i === 0) aria-current="true" @endif
                                    class="relative shrink-0 w-20 h-20 rounded-xl overflow-hidden bg-gray-200" aria-label="Media {{ $i + 1 }}">
                                @if ($m->isVideo())
                                    <span class="absolute inset-0 flex items-center justify-center bg-dark/60 text-white"><i data-lucide="play" class="w-5 h-5"></i></span>
                                @else
                                    <img src="{{ $m->thumb_url ?? $m->url }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        {{-- Info --}}
        <aside class="space-y-5 lg:col-start-3 lg:row-start-1 lg:row-span-2">
            <div class="bg-white rounded-2xl ring-1 ring-black/5 p-6">
                <span class="text-xs font-bold text-primary bg-primary/10 rounded-full px-2.5 py-1">{{ $karya->kategori->nama_kategori }}</span>
                <h1 class="text-3xl font-extrabold mt-3 leading-tight">{{ $karya->judul }}</h1>
                <p class="text-xs text-muted mt-1">Diunggah {{ $karya->created_at->translatedFormat('d F Y') }}</p>
                @if ($karya->deskripsi)
                    <p class="mt-4 text-sm leading-relaxed whitespace-pre-line break-words">{{ $karya->deskripsi }}</p>
                @endif

                <div class="mt-5 flex items-center gap-5 text-sm text-muted">
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="heart" class="w-4 h-4"></i><strong class="text-ink">{{ $karya->likes_count }}</strong> suka</span>
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="message-circle" class="w-4 h-4"></i><strong class="text-ink">{{ $karya->comments_count }}</strong> komentar</span>
                </div>

                <div id="aksi" class="mt-5 flex gap-2">
                    @if ($canInteract)
                        @auth
                            <form method="POST" action="{{ route('karya.suka', $karya) }}" class="flex-1">
                                @csrf
                                <button type="submit" aria-pressed="{{ $liked ? 'true' : 'false' }}"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-full py-2.5 text-sm font-semibold transition {{ $liked ? 'bg-primary text-white hover:bg-secondary' : 'border border-gray-300 hover:border-primary hover:text-primary' }}">
                                    <i data-lucide="heart" class="w-4 h-4 {{ $liked ? 'fill-current' : '' }}"></i> {{ $liked ? 'Disukai' : 'Suka' }}</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="flex-1 inline-flex items-center justify-center gap-2 rounded-full border border-gray-300 py-2.5 text-sm font-semibold hover:border-primary hover:text-primary transition"><i data-lucide="heart" class="w-4 h-4"></i> Masuk untuk suka</a>
                        @endauth
                    @endif
                    <button type="button" data-copy="{{ route('karya.show', $karya) }}"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-full border border-gray-300 py-2.5 text-sm font-semibold hover:border-primary hover:text-primary transition">
                        <i data-lucide="link" class="w-4 h-4"></i> Bagikan tautan</button>
                </div>

                @if ($canInteract && auth()->check() && $karya->user_id !== auth()->id())
                    <details class="mt-4 text-sm" @if ($errors->has('alasan')) open @endif>
                        <summary class="cursor-pointer inline-flex items-center gap-1.5 font-semibold text-muted hover:text-danger"><i data-lucide="flag" class="w-4 h-4"></i> Laporkan karya</summary>
                        <form method="POST" action="{{ route('karya.laporkan', $karya) }}" class="mt-3">
                            @csrf
                            <label for="alasan-karya" class="sr-only">Alasan laporan</label>
                            <textarea id="alasan-karya" name="alasan" rows="3" minlength="10" maxlength="1000" required placeholder="Jelaskan pelanggaran (min. 10 karakter)"
                                      class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-danger focus:ring-2 focus:ring-danger/20 outline-none">{{ old('alasan') }}</textarea>
                            @error('alasan') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
                            <button class="mt-2 px-4 py-2 rounded-xl bg-danger text-white font-semibold hover:opacity-90">Kirim laporan</button>
                        </form>
                    </details>
                @endif
            </div>

            <a href="{{ route('profil', $karya->user) }}" class="flex items-center gap-4 bg-white rounded-2xl ring-1 ring-black/5 hover:ring-primary/40 transition p-5">
                <img src="{{ $karya->user->foto_url }}" alt="" class="w-14 h-14 rounded-full object-cover">
                <div class="min-w-0">
                    <p class="text-xs text-muted">Kreator</p>
                    <p class="font-bold truncate">{{ $karya->user->name }}</p>
                    @if ($karya->user->bio) <p class="text-sm text-muted line-clamp-2">{{ $karya->user->bio }}</p> @endif
                </div>
            </a>
        </aside>

        <div class="lg:col-span-2 lg:row-start-2">
            <div>
                <h2 class="text-2xl font-extrabold">Komentar ({{ $karya->comments_count }})</h2>
                @if ($canInteract)
                    @auth
                        <form method="POST" action="{{ route('karya.komentar.store', $karya) }}" class="mt-4 bg-white rounded-2xl ring-1 ring-black/5 p-4">
                            @csrf
                            <label for="isi_komentar" class="sr-only">Komentar</label>
                            <textarea id="isi_komentar" name="isi_komentar" rows="3" maxlength="1000" required placeholder="Tulis komentar…"
                                      class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">{{ old('isi_komentar') }}</textarea>
                            @error('isi_komentar') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
                            <div class="mt-3 flex justify-end">
                                <button class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-primary text-white text-sm font-semibold hover:bg-secondary"><i data-lucide="send" class="w-4 h-4"></i> Kirim komentar</button>
                            </div>
                        </form>
                    @else
                        <p class="mt-4 rounded-xl bg-white border border-gray-100 px-4 py-3 text-sm"><a href="{{ route('login') }}" class="font-semibold text-primary">Masuk</a> atau <a href="{{ route('register') }}" class="font-semibold text-primary">daftar</a> untuk ikut berkomentar.</p>
                    @endauth
                @endif
                <div id="komentar" class="mt-4 space-y-4">
                    @forelse ($comments as $c)
                        <div class="flex gap-3 bg-white rounded-2xl ring-1 ring-black/5 p-4" id="komentar-{{ $c->id }}">
                            <img src="{{ $c->user->foto_url }}" alt="" class="w-9 h-9 rounded-full object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm"><strong>{{ $c->user->name }}</strong>
                                    <span class="text-muted">· {{ $c->created_at->diffForHumans() }}</span></p>
                                <p class="mt-1 text-sm whitespace-pre-line break-words">{{ $c->isi_komentar }}</p>
                                @auth
                                    <div class="mt-2 flex flex-wrap items-start gap-x-4 gap-y-2 text-xs">
                                        @can('delete', $c)
                                            <form method="POST" action="{{ route('komentar.destroy', $c) }}" data-confirm="Hapus komentar ini?" data-confirm-button="Ya, hapus" data-confirm-color="#E05252">
                                                @csrf @method('DELETE')
                                                <button class="font-semibold text-danger hover:underline">Hapus</button>
                                            </form>
                                        @endcan
                                        @if ($canInteract && $c->user_id !== auth()->id())
                                            <details class="w-full sm:w-auto">
                                                <summary class="cursor-pointer font-semibold text-muted hover:text-primary">Laporkan</summary>
                                                <form method="POST" action="{{ route('komentar.laporkan', $c) }}" class="mt-2 sm:w-72">
                                                    @csrf
                                                    <textarea name="alasan" rows="2" minlength="10" maxlength="1000" required placeholder="Alasan laporan (min. 10 karakter)" aria-label="Alasan laporan komentar"
                                                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs focus:border-primary outline-none"></textarea>
                                                    <button class="mt-1.5 px-3 py-1.5 rounded-lg bg-warning/90 text-dark font-semibold">Kirim laporan</button>
                                                </form>
                                            </details>
                                        @endif
                                    </div>
                                @endauth
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">Belum ada komentar.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <div class="mt-14">
            <h2 class="text-2xl font-extrabold mb-6">Karya serupa</h2>
            <div class="masonry">@foreach ($related as $r) <x-karya-card :karya="$r" /> @endforeach</div>
        </div>
    @endif
</section>
@endsection
