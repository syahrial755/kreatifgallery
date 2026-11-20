@extends('layouts.dashboard')
@section('title', 'Analytics')
@section('heading', 'Analytics')
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ([['Total suka', $summary['suka'], 'heart'], ['Total komentar', $summary['komentar'], 'message-circle'], ['Rata-rata suka / karya tayang', $summary['rata_suka'], 'trending-up'], ['Tingkat persetujuan', $summary['approval_rate'] === null ? '—' : $summary['approval_rate'].'%', 'badge-check']] as [$l, $v, $ic])
        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-5">
            <span class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center"><i data-lucide="{{ $ic }}" class="w-5 h-5"></i></span>
            <p class="text-3xl font-extrabold mt-4">{{ $v }}</p><p class="text-sm text-muted">{{ $l }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid xl:grid-cols-3 gap-6">
    <section class="xl:col-span-2 bg-white rounded-card border border-gray-100 shadow-sm p-6">
        <h2 class="font-extrabold">Pengguna baru · 30 hari</h2>
        <div class="mt-4 h-64"><canvas id="c-pengguna" role="img" aria-label="Grafik pengguna baru per hari"></canvas></div>
    </section>
    <section class="bg-white rounded-card border border-gray-100 shadow-sm p-6">
        <h2 class="font-extrabold">Status karya</h2>
        @if ($totalKarya === 0) <p class="mt-6 text-sm text-muted text-center py-10">Belum ada karya.</p>
        @else <div class="mt-4 h-64"><canvas id="c-status" role="img" aria-label="Distribusi status karya"></canvas></div> @endif
    </section>
</div>

<div class="mt-6 bg-white rounded-card border border-gray-100 shadow-sm p-6">
    <h2 class="font-extrabold">Karya tayang per kategori</h2>
    <div class="mt-4 h-72"><canvas id="c-kategori" role="img" aria-label="Karya tayang per kategori"></canvas></div>
</div>

<div class="mt-6 grid xl:grid-cols-2 gap-6">
    <section class="bg-white rounded-card border border-gray-100 shadow-sm">
        <h2 class="font-extrabold px-6 py-4 border-b border-gray-100">Kreator teratas (berdasarkan suka)</h2>
        @forelse ($topCreators as $i => $c)
            <a href="{{ route('admin.pengguna.show', $c->user) }}" class="flex items-center gap-4 px-6 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50">
                <span class="w-6 text-sm font-extrabold text-muted">{{ $i + 1 }}</span>
                <img src="{{ $c->user->foto_url }}" alt="" class="w-9 h-9 rounded-full object-cover">
                <span class="flex-1 font-semibold truncate">{{ $c->user->name }}</span>
                <span class="text-sm text-muted inline-flex items-center gap-1"><i data-lucide="heart" class="w-4 h-4"></i>{{ $c->suka }}</span>
            </a>
        @empty <p class="px-6 py-10 text-sm text-muted text-center">Belum ada suka.</p> @endforelse
    </section>
    <section class="bg-white rounded-card border border-gray-100 shadow-sm">
        <h2 class="font-extrabold px-6 py-4 border-b border-gray-100">Karya terpopuler</h2>
        @forelse ($topWorks as $i => $k)
            <a href="{{ route('karya.show', $k) }}" class="flex items-center gap-4 px-6 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50">
                <span class="w-6 text-sm font-extrabold text-muted">{{ $i + 1 }}</span>
                <span class="flex-1 min-w-0"><span class="block font-semibold truncate">{{ $k->judul }}</span><span class="block text-xs text-muted">{{ $k->user->name }}</span></span>
                <span class="text-sm text-muted inline-flex items-center gap-3"><span class="inline-flex items-center gap-1"><i data-lucide="heart" class="w-4 h-4"></i>{{ $k->likes_count }}</span><span class="inline-flex items-center gap-1"><i data-lucide="message-circle" class="w-4 h-4"></i>{{ $k->comments_count }}</span></span>
            </a>
        @empty <p class="px-6 py-10 text-sm text-muted text-center">Belum ada karya tayang.</p> @endforelse
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (!window.Chart) return;
        var d = @json($charts);
        var opts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } };

        new Chart(document.getElementById('c-pengguna'), { type: 'bar',
            data: { labels: d.pengguna.labels, datasets: [{ data: d.pengguna.data, backgroundColor: '#635BDB', borderRadius: 6 }] }, options: opts });
        new Chart(document.getElementById('c-kategori'), { type: 'bar',
            data: { labels: d.kategori.labels, datasets: [{ data: d.kategori.data, backgroundColor: '#8B83F6', borderRadius: 6 }] }, options: opts });
        var st = document.getElementById('c-status');
        if (st) new Chart(st, { type: 'doughnut',
            data: { labels: d.status.labels, datasets: [{ data: d.status.data, backgroundColor: ['#16A36A', '#E9A23B', '#E05252'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } } });
    })();
</script>
@endpush
