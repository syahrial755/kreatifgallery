@extends('layouts.dashboard')
@section('title', 'All Works')
@section('heading', 'All Works')
@section('content')
@php $inp = 'rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none'; @endphp
<form method="GET" action="{{ route('admin.karya.index') }}" class="flex flex-col lg:flex-row gap-3">
    <div class="relative flex-1">
        <i data-lucide="search" class="w-4 h-4 text-muted absolute left-4 top-1/2 -translate-y-1/2"></i>
        <input type="search" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Cari judul atau nama kreator…" class="{{ $inp }} w-full pl-11">
    </div>
    <select name="status" aria-label="Status" class="{{ $inp }}">
        <option value="">Semua status</option>
        @foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $v => $l)
            <option value="{{ $v }}" @selected($filters['status'] === $v)>{{ $l }}</option>
        @endforeach
    </select>
    <select name="kategori" aria-label="Kategori" class="{{ $inp }}">
        <option value="">Semua kategori</option>
        @foreach ($kategoris as $k) <option value="{{ $k->id }}" @selected((int) $filters['kategori'] === $k->id)>{{ $k->nama_kategori }}</option> @endforeach
    </select>
    <button class="px-6 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-secondary transition">Filter</button>
    @if ($filters['q'] || $filters['status'] || $filters['kategori'])
        <a href="{{ route('admin.karya.index') }}" class="px-4 py-2.5 rounded-xl font-semibold text-muted hover:bg-white text-center">Reset</a>
    @endif
</form>

<div class="mt-5 bg-white rounded-card border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm min-w-[820px]">
        <thead class="text-left text-xs uppercase tracking-wide text-muted border-b border-gray-100">
            <tr><th class="px-5 py-3">Karya</th><th class="px-3 py-3">Kreator</th><th class="px-3 py-3">Status</th><th class="px-3 py-3 text-center">Suka</th><th class="px-3 py-3 text-center">Komentar</th><th class="px-5 py-3 text-right">Aksi</th></tr>
        </thead>
        <tbody>
        @forelse ($karyas as $k)
            @php $m = $k->medias->first(); @endphp
            <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                            @if ($m && ! $m->isVideo()) <img src="{{ $m->thumb_url ?? $m->url }}" alt="" class="w-full h-full object-cover">
                            @else <i data-lucide="{{ $m ? 'play' : 'image' }}" class="w-4 h-4 text-muted"></i> @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold truncate max-w-[260px]">{{ $k->judul }}</p>
                            <p class="text-xs text-muted">{{ $k->kategori->nama_kategori }} · {{ $k->created_at->translatedFormat('d M Y') }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-3 py-3"><a href="{{ route('admin.pengguna.show', $k->user) }}" class="hover:text-primary">{{ $k->user->name }}</a></td>
                <td class="px-3 py-3"><x-status-badge :status="$k->status" /></td>
                <td class="px-3 py-3 text-center">{{ $k->likes_count }}</td>
                <td class="px-3 py-3 text-center">{{ $k->comments_count }}</td>
                <td class="px-5 py-3">
                    <div class="flex justify-end gap-2">
                        @if ($k->status === 'menunggu')
                            <a href="{{ route('admin.moderasi.show', $k) }}" class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-secondary">Tinjau</a>
                        @else
                            <a href="{{ route('karya.show', $k) }}" class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold hover:border-primary hover:text-primary">Lihat</a>
                        @endif
                        @if ($k->status === 'disetujui')
                            <form method="POST" action="{{ route('admin.karya.turunkan', $k) }}" data-reason="Alasan menurunkan &quot;{{ $k->judul }}&quot;" data-reason-button="Turunkan karya">
                                @csrf <input type="hidden" name="catatan_moderasi">
                                <button class="px-3 py-1.5 rounded-lg border border-danger/40 text-danger text-xs font-semibold hover:bg-danger/10">Turunkan</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-14 text-center text-muted">Tidak ada karya yang cocok dengan filter.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $karyas->links() }}
@endsection
