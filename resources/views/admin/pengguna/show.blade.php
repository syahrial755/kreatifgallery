@extends('layouts.dashboard')
@section('title', $user->name)
@section('heading', 'Detail pengguna')
@section('content')
<a href="{{ route('admin.pengguna.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-primary mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke daftar</a>

<div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 flex flex-col sm:flex-row gap-5 sm:items-center">
    <img src="{{ $user->foto_url }}" alt="" class="w-20 h-20 rounded-full object-cover">
    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-2xl font-extrabold">{{ $user->name }}</h2>
            <span class="text-xs font-bold rounded-full px-2.5 py-1 bg-primary/10 text-primary capitalize">{{ $user->role }}</span>
            <span class="text-xs font-bold rounded-full px-2.5 py-1 {{ $user->is_active ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        </div>
        <p class="text-sm text-muted mt-1">{{ $user->email }} · bergabung {{ $user->created_at->translatedFormat('d F Y') }}</p>
        @if ($user->bio) <p class="text-sm mt-2 whitespace-pre-line">{{ $user->bio }}</p> @endif
    </div>
    @if ($user->id !== auth()->id())
        <form method="POST" action="{{ route('admin.pengguna.status', $user) }}"
              data-confirm="{{ $user->is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan kembali akun ini?' }}"
              data-confirm-button="{{ $user->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan' }}" data-confirm-color="{{ $user->is_active ? '#E05252' : '#16A36A' }}">
            @csrf @method('PATCH')
            <button class="px-5 py-2.5 rounded-xl font-semibold text-white {{ $user->is_active ? 'bg-danger' : 'bg-success' }} hover:opacity-90">{{ $user->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
        </form>
    @endif
</div>

<div class="mt-5 grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-4">
    @foreach ([['Karya', $stats['karya']], ['Disetujui', $stats['disetujui']], ['Menunggu', $stats['menunggu']], ['Ditolak', $stats['ditolak']], ['Suka diterima', $stats['suka_diterima']], ['Komentar ditulis', $stats['komentar_ditulis']], ['Laporan dikirim', $stats['laporan_dikirim']]] as [$l, $v])
        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-4"><p class="text-2xl font-extrabold">{{ $v }}</p><p class="text-xs text-muted">{{ $l }}</p></div>
    @endforeach
</div>

<section class="mt-6 bg-white rounded-card border border-gray-100 shadow-sm">
    <h2 class="font-extrabold px-6 py-4 border-b border-gray-100">Karya terbaru</h2>
    @forelse ($karyas as $k)
        <div class="flex items-center justify-between gap-4 px-6 py-3 border-b border-gray-50 last:border-0">
            <div class="min-w-0"><p class="font-bold truncate">{{ $k->judul }}</p><p class="text-xs text-muted">{{ $k->kategori->nama_kategori }} · {{ $k->created_at->translatedFormat('d M Y') }}</p></div>
            <div class="flex items-center gap-3 shrink-0">
                <x-status-badge :status="$k->status" />
                <a href="{{ $k->status === 'menunggu' ? route('admin.moderasi.show', $k) : route('karya.show', $k) }}" class="text-sm font-semibold text-primary">{{ $k->status === 'menunggu' ? 'Tinjau' : 'Lihat' }}</a>
            </div>
        </div>
    @empty
        <p class="px-6 py-10 text-sm text-muted text-center">Pengguna ini belum memiliki karya.</p>
    @endforelse
</section>
@endsection
