@extends('layouts.dashboard')
@section('title', 'Detail laporan')
@section('heading', 'Detail laporan')
@section('content')
@php
    $isKomentar = ! $report->karya_id;
    $target = $isKomentar ? $report->komentar : $report->karya;
    $labels = ['abaikan' => 'Diabaikan', 'hapus_komentar' => 'Komentar dihapus', 'turunkan_karya' => 'Karya diturunkan'];
@endphp
<a href="{{ route('admin.laporan.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-primary mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke daftar laporan</a>

<div class="grid xl:grid-cols-3 gap-6">
    <section class="xl:col-span-2 space-y-5">
        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold text-muted uppercase tracking-wide">Laporan {{ $isKomentar ? 'komentar' : 'karya' }}</p>
                <x-status-badge :status="$report->status" />
            </div>
            <p class="mt-3 text-sm"><strong>{{ $report->pelapor->name }}</strong> melaporkan pada {{ $report->created_at->translatedFormat('d F Y, H:i') }}:</p>
            <blockquote class="mt-2 border-l-4 border-warning bg-warning/10 rounded-r-xl px-4 py-3 text-sm whitespace-pre-line break-words">{{ $report->alasan }}</blockquote>
        </div>

        @if ($isKomentar)
            <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6">
                <p class="text-xs font-semibold text-muted uppercase tracking-wide">Komentar yang dilaporkan</p>
                @if ($target)
                    <div class="flex gap-3 mt-3">
                        <img src="{{ $target->user->foto_url }}" alt="" class="w-10 h-10 rounded-full object-cover">
                        <div class="min-w-0">
                            <p class="text-sm"><strong>{{ $target->user->name }}</strong> <span class="text-muted">· {{ $target->created_at->diffForHumans() }}</span></p>
                            <p class="mt-1 text-sm whitespace-pre-line break-words">{{ $target->isi_komentar }}</p>
                            <a href="{{ route('karya.show', $target->karya) }}#komentar-{{ $target->id }}" class="inline-block mt-2 text-sm font-semibold text-primary">Lihat pada karya “{{ $target->karya->judul }}”</a>
                        </div>
                    </div>
                @else
                    <p class="mt-3 text-sm text-muted">Komentar ini sudah dihapus.</p>
                    @if ($report->snapshot) <blockquote class="mt-2 border-l-4 border-gray-200 pl-3 text-sm text-muted whitespace-pre-line">{{ $report->snapshot }}</blockquote> @endif
                @endif
            </div>
        @else
            <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6">
                <p class="text-xs font-semibold text-muted uppercase tracking-wide">Karya yang dilaporkan</p>
                <div class="flex items-center justify-between gap-3 mt-2">
                    <h2 class="text-lg font-extrabold">{{ $target->judul }}</h2><x-status-badge :status="$target->status" />
                </div>
                <p class="text-sm text-muted">oleh <a class="text-primary font-semibold" href="{{ route('admin.pengguna.show', $target->user) }}">{{ $target->user->name }}</a></p>
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ($target->medias as $m)
                        <div class="rounded-xl overflow-hidden bg-gray-100 aspect-square">
                            @if ($m->isVideo()) <video src="{{ $m->url }}#t=0.1" controls preload="metadata" class="w-full h-full object-cover"></video>
                            @else <img src="{{ $m->thumb_url ?? $m->url }}" alt="" class="w-full h-full object-cover"> @endif
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('karya.show', $target) }}" class="inline-block mt-4 text-sm font-semibold text-primary">Buka halaman karya</a>
            </div>
        @endif
    </section>

    <aside>
        @if ($report->status === 'baru')
            <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 space-y-5">
                <h2 class="font-extrabold">Tindak lanjut</h2>

                <form method="POST" action="{{ route('admin.laporan.tangani', $report) }}" data-confirm="Abaikan laporan ini? Tidak ada perubahan pada konten." data-confirm-button="Ya, abaikan" data-confirm-icon="question">
                    @csrf <input type="hidden" name="tindakan" value="abaikan">
                    <button class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-gray-200 font-semibold hover:border-primary hover:text-primary"><i data-lucide="eye-off" class="w-4 h-4"></i> Abaikan laporan</button>
                </form>

                @if ($isKomentar && $target)
                    <form method="POST" action="{{ route('admin.laporan.tangani', $report) }}" data-confirm="Hapus komentar ini secara permanen?" data-confirm-button="Ya, hapus komentar" data-confirm-color="#E05252">
                        @csrf <input type="hidden" name="tindakan" value="hapus_komentar">
                        <button class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-danger text-white font-semibold hover:opacity-90"><i data-lucide="trash-2" class="w-4 h-4"></i> Hapus komentar</button>
                    </form>
                @endif

                @if (! $isKomentar && $target->status === 'disetujui')
                    <form method="POST" action="{{ route('admin.laporan.tangani', $report) }}" data-confirm="Turunkan karya dari galeri? Kreator menerima catatan Anda." data-confirm-button="Ya, turunkan" data-confirm-color="#E05252">
                        @csrf <input type="hidden" name="tindakan" value="turunkan_karya">
                        <label for="catatan_moderasi" class="block text-sm font-semibold mb-1">Alasan penurunan <span class="text-danger">*</span></label>
                        <textarea id="catatan_moderasi" name="catatan_moderasi" rows="3" maxlength="1000" required class="w-full rounded-xl border border-gray-200 px-4 py-2.5 focus:border-danger focus:ring-2 focus:ring-danger/20 outline-none">{{ old('catatan_moderasi') }}</textarea>
                        @error('catatan_moderasi') <p class="text-sm mt-1 text-danger">{{ $message }}</p> @enderror
                        <button class="mt-3 w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-danger text-white font-semibold hover:opacity-90"><i data-lucide="arrow-down-circle" class="w-4 h-4"></i> Turunkan karya</button>
                    </form>
                @elseif (! $isKomentar)
                    <p class="text-xs text-muted">Karya ini tidak sedang tayang (status: {{ $target->status }}), sehingga tidak dapat diturunkan.</p>
                @endif
            </div>
        @else
            <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 text-sm">
                <h2 class="font-extrabold">Sudah ditangani</h2>
                <p class="mt-2">Tindakan: <strong>{{ $labels[$report->tindakan] ?? $report->tindakan }}</strong></p>
                <p class="text-muted mt-1">Oleh {{ $report->penangan?->name ?? '—' }} · {{ $report->handled_at?->translatedFormat('d M Y, H:i') }}</p>
            </div>
        @endif
    </aside>
</div>
@endsection
