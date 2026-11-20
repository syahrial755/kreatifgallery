@extends('layouts.dashboard')
@section('title', 'Tinjau karya')
@section('heading', 'Tinjau karya')
@section('content')
<a href="{{ route('admin.moderasi.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-primary mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke antrian</a>

<div class="grid xl:grid-cols-3 gap-6">
    {{-- Pratinjau seluruh media --}}
    <section class="xl:col-span-2 space-y-4">
        @forelse ($karya->medias as $m)
            <figure class="bg-white rounded-card border border-gray-100 shadow-sm overflow-hidden p-2">
                @if ($m->isVideo())
                    <video src="{{ $m->url }}" controls playsinline preload="metadata" class="w-full max-h-[70vh] mx-auto"></video>
                @else
                    <img src="{{ $m->url }}" alt="{{ $karya->judul }} — media {{ $loop->iteration }}" class="w-full max-h-[70vh] object-contain mx-auto">
                @endif
                <figcaption class="text-xs text-muted px-2 py-1.5">Media {{ $loop->iteration }} dari {{ $karya->medias->count() }} · {{ $m->isVideo() ? 'Video' : 'Gambar' }}</figcaption>
            </figure>
        @empty
            <div class="bg-white rounded-card border border-danger/30 p-8 text-center text-danger">Karya ini tidak memiliki media.</div>
        @endforelse
    </section>

    <aside class="space-y-5">
        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-bold text-primary bg-primary/10 rounded-full px-2.5 py-1">{{ $karya->kategori->nama_kategori }}</span>
                <x-status-badge :status="$karya->status" />
            </div>
            <h2 class="text-xl font-extrabold mt-3 leading-tight">{{ $karya->judul }}</h2>
            <p class="text-xs text-muted mt-1">Diunggah {{ $karya->created_at->translatedFormat('d F Y, H:i') }}</p>
            <p class="mt-4 text-sm leading-relaxed whitespace-pre-line break-words">{{ $karya->deskripsi ?: 'Tanpa deskripsi.' }}</p>
        </div>

        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide">Kreator</p>
            <div class="flex items-center gap-3 mt-3">
                <img src="{{ $karya->user->foto_url }}" alt="" class="w-12 h-12 rounded-full object-cover">
                <div class="min-w-0">
                    <a href="{{ route('admin.pengguna.show', $karya->user) }}" class="font-bold hover:text-primary block truncate">{{ $karya->user->name }}</a>
                    <p class="text-xs text-muted truncate">{{ $karya->user->email }}</p>
                    <p class="text-xs text-muted">{{ $creatorWorks }} karya · {{ $karya->user->is_active ? 'akun aktif' : 'akun nonaktif' }}</p>
                </div>
            </div>
        </div>

        @if ($karya->status === 'menunggu')
            <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 space-y-5">
                <form method="POST" action="{{ route('admin.moderasi.approve', $karya) }}"
                      data-confirm="Setujui karya ini dan tayangkan di galeri publik?" data-confirm-button="Ya, setujui" data-confirm-color="#16A36A" data-confirm-icon="question">
                    @csrf
                    <button class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-success text-white font-semibold hover:opacity-90 transition">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i> Setujui karya</button>
                </form>

                <div class="border-t border-gray-100 pt-5">
                    <form method="POST" action="{{ route('admin.moderasi.reject', $karya) }}"
                          data-confirm="Tolak karya ini? Kreator akan menerima catatan Anda." data-confirm-button="Ya, tolak" data-confirm-color="#E05252">
                        @csrf
                        <label for="catatan_moderasi" class="block text-sm font-semibold mb-1">Catatan penolakan <span class="text-danger">*</span></label>
                        <textarea id="catatan_moderasi" name="catatan_moderasi" rows="4" maxlength="1000" required
                                  placeholder="Jelaskan alasan penolakan dan apa yang perlu diperbaiki…"
                                  class="w-full rounded-xl border border-gray-200 px-4 py-2.5 focus:border-danger focus:ring-2 focus:ring-danger/20 outline-none">{{ old('catatan_moderasi') }}</textarea>
                        @error('catatan_moderasi') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
                        <button class="mt-3 w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-danger/40 text-danger font-semibold hover:bg-danger/10 transition">
                            <i data-lucide="x-circle" class="w-5 h-5"></i> Tolak karya</button>
                    </form>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-sm">
                Karya ini sudah dimoderasi{{ $karya->admin ? ' oleh '.$karya->admin->name : '' }}.
                @if ($karya->catatan_moderasi) <p class="mt-2 text-muted whitespace-pre-line">Catatan: {{ $karya->catatan_moderasi }}</p> @endif
                <a href="{{ route('karya.show', $karya) }}" class="inline-block mt-3 font-semibold text-primary">Buka halaman karya</a>
            </div>
        @endif
    </aside>
</div>
@endsection
