@extends('layouts.dashboard')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('content')
@php
    $cards = [
        ['Total karya', $stats['total'], 'images', 'text-primary bg-primary/10'],
        ['Disetujui', $stats['disetujui'], 'check-circle-2', 'text-success bg-success/10'],
        ['Menunggu', $stats['menunggu'], 'clock', 'text-warning bg-warning/10'],
        ['Ditolak', $stats['ditolak'], 'x-circle', 'text-danger bg-danger/10'],
        ['Total suka', $stats['suka'], 'heart', 'text-primary bg-primary/10'],
        ['Total komentar', $stats['komentar'], 'message-circle', 'text-primary bg-primary/10'],
    ];
@endphp
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
    @foreach ($cards as [$label, $value, $icon, $tone])
        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-5">
            <span class="w-10 h-10 rounded-xl flex items-center justify-center {{ $tone }}"><i data-lucide="{{ $icon }}" class="w-5 h-5"></i></span>
            <p class="text-3xl font-extrabold mt-4">{{ $value }}</p>
            <p class="text-sm text-muted">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid xl:grid-cols-3 gap-6">
    <section class="xl:col-span-2 bg-white rounded-card border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-extrabold">Karya terbaru &amp; status moderasi</h2>
            <a href="{{ route('kreator.karya.create') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary"><i data-lucide="plus" class="w-4 h-4"></i> Unggah karya</a>
        </div>
        @forelse ($recent as $k)
            @php $m = $k->medias->first(); @endphp
            <div class="flex items-center gap-4 px-6 py-3.5 border-b border-gray-50 last:border-0">
                <div class="w-14 h-14 rounded-xl bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                    @if ($m && ! $m->isVideo()) <img src="{{ $m->thumb_url ?? $m->url }}" alt="" class="w-full h-full object-cover">
                    @elseif ($m) <i data-lucide="play" class="w-5 h-5 text-muted"></i>
                    @else <i data-lucide="image" class="w-5 h-5 text-muted"></i> @endif
                </div>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('karya.show', $k) }}" class="font-bold truncate block hover:text-primary">{{ $k->judul }}</a>
                    <p class="text-xs text-muted">{{ $k->kategori->nama_kategori }} · {{ $k->created_at->translatedFormat('d M Y') }}</p>
                </div>
                <x-status-badge :status="$k->status" />
            </div>
        @empty
            <div class="px-6 py-12 text-center">
                <i data-lucide="image-plus" class="w-10 h-10 text-muted mx-auto"></i>
                <p class="font-bold mt-3">Belum ada karya</p>
                <p class="text-sm text-muted mt-1">Unggah karya pertama Anda untuk memulai portofolio.</p>
                <a href="{{ route('kreator.karya.create') }}" class="inline-block mt-4 px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-secondary">Unggah karya</a>
            </div>
        @endforelse
    </section>

    <div class="space-y-6">
        <section class="bg-white rounded-card border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="font-extrabold">Notifikasi</h2>
                <a href="{{ route('notifikasi.index') }}" class="text-sm font-semibold text-primary">Semua</a>
            </div>
            @forelse ($notifications as $n)
                <div class="px-6 py-3 border-b border-gray-50 last:border-0 {{ $n->read_at ? '' : 'bg-primary/5' }}">
                    <p class="text-sm leading-snug">{{ $n->data['message'] ?? '' }}</p>
                    <p class="text-xs text-muted mt-1">{{ $n->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="px-6 py-8 text-sm text-muted text-center">Belum ada notifikasi.</p>
            @endforelse
        </section>

        <section class="bg-white rounded-card border border-gray-100 shadow-sm">
            <h2 class="font-extrabold px-6 py-4 border-b border-gray-100">Aktivitas terbaru</h2>
            @forelse ($activity as $a)
                <a href="{{ $a['url'] }}" class="flex gap-3 px-6 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50">
                    <i data-lucide="{{ $a['icon'] }}" class="w-4 h-4 mt-0.5 text-primary shrink-0"></i>
                    <span class="min-w-0"><span class="block text-sm leading-snug">{{ $a['text'] }}</span>
                    <span class="block text-xs text-muted mt-0.5">{{ $a['at']->diffForHumans() }}</span></span>
                </a>
            @empty
                <p class="px-6 py-8 text-sm text-muted text-center">Belum ada suka atau komentar.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
