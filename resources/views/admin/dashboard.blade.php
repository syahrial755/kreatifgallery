@extends('layouts.dashboard')
@section('title', 'Overview')
@section('heading', 'Overview')
@section('content')
@php
    $cards = [
        ['Total pengguna', $stats['users'], 'users', 'text-primary bg-primary/10'],
        ['Total kreator', $stats['creators'], 'palette', 'text-primary bg-primary/10'],
        ['Total karya', $stats['karya'], 'images', 'text-primary bg-primary/10'],
        ['Menunggu', $stats['menunggu'], 'clock', 'text-warning bg-warning/10'],
        ['Disetujui', $stats['disetujui'], 'check-circle-2', 'text-success bg-success/10'],
        ['Ditolak', $stats['ditolak'], 'x-circle', 'text-danger bg-danger/10'],
        ['Total laporan', $stats['laporan'], 'flag', 'text-danger bg-danger/10'],
    ];
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-4">
    @foreach ($cards as [$label, $value, $icon, $tone])
        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-5">
            <span class="w-10 h-10 rounded-xl flex items-center justify-center {{ $tone }}"><i data-lucide="{{ $icon }}" class="w-5 h-5"></i></span>
            <p class="text-3xl font-extrabold mt-4">{{ $value }}</p>
            <p class="text-sm text-muted">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid xl:grid-cols-3 gap-6">
    <section class="xl:col-span-2 bg-white rounded-card border border-gray-100 shadow-sm p-6">
        <h2 class="font-extrabold">Aktivitas publikasi · 14 hari</h2>
        <p class="text-sm text-muted">Karya diunggah dibanding karya yang disetujui dan tayang.</p>
        <div class="mt-4 h-72"><canvas id="chart-publikasi" aria-label="Grafik aktivitas publikasi" role="img"></canvas></div>
    </section>

    <section class="bg-white rounded-card border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-extrabold">Ringkasan laporan</h2>
            <a href="{{ route('admin.laporan.index') }}" class="text-sm font-semibold text-primary">Semua</a>
        </div>
        <div class="px-6 py-4 flex gap-6">
            <div><p class="text-3xl font-extrabold text-danger">{{ $stats['laporan_baru'] }}</p><p class="text-xs text-muted">Perlu ditangani</p></div>
            <div><p class="text-3xl font-extrabold text-success">{{ $stats['laporan'] - $stats['laporan_baru'] }}</p><p class="text-xs text-muted">Selesai</p></div>
        </div>
        @forelse ($latestReports as $r)
            <a href="{{ route('admin.laporan.show', $r) }}" class="block px-6 py-3 border-t border-gray-50 hover:bg-gray-50">
                <p class="text-sm font-semibold truncate">{{ $r->karya?->judul ?? ($r->komentar ? 'Komentar: '.\Illuminate\Support\Str::limit($r->komentar->isi_komentar, 40) : 'Komentar') }}</p>
                <p class="text-xs text-muted truncate">{{ $r->pelapor->name }} · {{ \Illuminate\Support\Str::limit($r->alasan, 50) }}</p>
            </a>
        @empty
            <p class="px-6 py-6 text-sm text-muted border-t border-gray-50">Tidak ada laporan baru.</p>
        @endforelse
    </section>
</div>

<div class="mt-6 grid xl:grid-cols-3 gap-6">
    <section class="xl:col-span-2 bg-white rounded-card border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-extrabold">Antrian moderasi</h2>
            <a href="{{ route('admin.moderasi.index') }}" class="text-sm font-semibold text-primary">Buka antrian ({{ $stats['menunggu'] }})</a>
        </div>
        @forelse ($queue as $k)
            @php $m = $k->medias->first(); @endphp
            <a href="{{ route('admin.moderasi.show', $k) }}" class="flex items-center gap-4 px-6 py-3.5 border-b border-gray-50 last:border-0 hover:bg-gray-50">
                <div class="w-14 h-14 rounded-xl bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                    @if ($m && ! $m->isVideo()) <img src="{{ $m->thumb_url ?? $m->url }}" alt="" class="w-full h-full object-cover">
                    @else <i data-lucide="{{ $m ? 'play' : 'image' }}" class="w-5 h-5 text-muted"></i> @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold truncate">{{ $k->judul }}</p>
                    <p class="text-xs text-muted">{{ $k->user->name }} · {{ $k->kategori->nama_kategori }} · {{ $k->created_at->diffForHumans() }}</p>
                </div>
                <span class="text-sm font-semibold text-primary shrink-0">Tinjau</span>
            </a>
        @empty
            <div class="px-6 py-12 text-center">
                <i data-lucide="check-check" class="w-10 h-10 text-success mx-auto"></i>
                <p class="font-bold mt-3">Antrian kosong</p>
                <p class="text-sm text-muted">Semua karya sudah dimoderasi.</p>
            </div>
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
            <p class="px-6 py-8 text-sm text-muted text-center">Belum ada aktivitas.</p>
        @endforelse
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (!window.Chart) return;
        var d = @json($chart);
        new Chart(document.getElementById('chart-publikasi'), {
            type: 'line',
            data: { labels: d.labels, datasets: [
                { label: 'Diunggah', data: d.uploads, borderColor: '#635BDB', backgroundColor: 'rgba(99,91,219,.12)', fill: true, tension: .35, pointRadius: 3 },
                { label: 'Disetujui / tayang', data: d.published, borderColor: '#16A36A', backgroundColor: 'rgba(22,163,106,.10)', fill: true, tension: .35, pointRadius: 3 }
            ] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
        });
    })();
</script>
@endpush
