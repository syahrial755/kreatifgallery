@extends('layouts.dashboard')
@section('title', 'My Works')
@section('heading', 'My Works')
@section('content')
@php
    $tabs = [null => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
@endphp
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            @php $n = $key ? ($counts[$key] ?? 0) : $counts->sum(); @endphp
            <a href="{{ route('kreator.karya.index', $key ? ['status' => $key] : []) }}"
               class="px-4 py-2 rounded-full text-sm font-semibold border transition {{ $status === $key ? 'bg-primary text-white border-primary' : 'bg-white border-gray-200 hover:border-primary' }}">
                {{ $label }} <span class="opacity-70">({{ $n }})</span></a>
        @endforeach
    </div>
    <a href="{{ route('kreator.karya.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm hover:bg-secondary transition">
        <i data-lucide="plus" class="w-4 h-4"></i> Upload Work</a>
</div>

<div class="mt-5 space-y-3">
    @forelse ($karyas as $k)
        @php $m = $k->medias->first(); @endphp
        <article class="bg-white rounded-card border border-gray-100 shadow-sm p-4 flex flex-col md:flex-row md:items-center gap-4">
            <div class="flex items-center gap-4 min-w-0 flex-1">
                <div class="w-20 h-20 rounded-xl bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                    @if ($m && ! $m->isVideo()) <img src="{{ $m->thumb_url ?? $m->url }}" alt="" class="w-full h-full object-cover">
                    @elseif ($m) <i data-lucide="play" class="w-6 h-6 text-muted"></i>
                    @else <i data-lucide="image" class="w-6 h-6 text-muted"></i> @endif
                </div>
                <div class="min-w-0">
                    <a href="{{ route('karya.show', $k) }}" class="font-bold text-lg leading-tight block truncate hover:text-primary">{{ $k->judul }}</a>
                    <p class="text-sm text-muted">{{ $k->kategori->nama_kategori }} · {{ $k->created_at->translatedFormat('d M Y') }}</p>
                    <div class="mt-2 flex items-center gap-3 text-xs text-muted">
                        <x-status-badge :status="$k->status" />
                        <span class="inline-flex items-center gap-1"><i data-lucide="heart" class="w-3.5 h-3.5"></i>{{ $k->likes_count }}</span>
                        <span class="inline-flex items-center gap-1"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i>{{ $k->comments_count }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 md:justify-end">
                <a href="{{ route('karya.show', $k) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 text-sm font-semibold hover:border-primary hover:text-primary"><i data-lucide="eye" class="w-4 h-4"></i> View</a>
                <a href="{{ route('kreator.karya.edit', $k) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 text-sm font-semibold hover:border-primary hover:text-primary"><i data-lucide="pencil" class="w-4 h-4"></i> Edit</a>
                @if ($k->status === 'ditolak')
                    <button type="button" data-note="{{ $k->catatan_moderasi ?: 'Tidak ada catatan.' }}" data-note-title="Catatan moderasi"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-danger/40 text-danger text-sm font-semibold hover:bg-danger/10"><i data-lucide="message-square-warning" class="w-4 h-4"></i> Catatan</button>
                    <a href="{{ route('kreator.karya.edit', $k) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-secondary"><i data-lucide="send" class="w-4 h-4"></i> Submit revision</a>
                @elseif ($k->status === 'disetujui' && $k->admin)
                    <button type="button" data-note="Disetujui oleh {{ $k->admin->name }}." data-note-title="Catatan moderasi"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 text-sm font-semibold hover:border-primary hover:text-primary"><i data-lucide="message-square" class="w-4 h-4"></i> Catatan</button>
                @endif
                <form method="POST" action="{{ route('kreator.karya.destroy', $k) }}"
                      data-confirm="Hapus karya &quot;{{ $k->judul }}&quot;? File media ikut terhapus dan tidak bisa dikembalikan."
                      data-confirm-button="Ya, hapus" data-confirm-color="#E05252">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-danger hover:bg-danger/10"><i data-lucide="trash-2" class="w-4 h-4"></i> Delete</button>
                </form>
            </div>
        </article>
    @empty
        <div class="bg-white rounded-card border border-gray-100 p-12 text-center">
            <i data-lucide="images" class="w-10 h-10 text-muted mx-auto"></i>
            <p class="font-bold mt-3">{{ $status ? 'Tidak ada karya dengan status ini' : 'Belum ada karya' }}</p>
            <a href="{{ route('kreator.karya.create') }}" class="inline-block mt-4 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-secondary">Unggah karya</a>
        </div>
    @endforelse
</div>
{{ $karyas->links() }}
@endsection
