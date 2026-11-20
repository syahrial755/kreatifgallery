@extends('layouts.dashboard')
@section('title', 'Reports')
@section('heading', 'Reports')
@section('content')
@php $tabs = [null => 'Semua', 'baru' => 'Baru', 'selesai' => 'Selesai']; @endphp
<div class="flex flex-wrap gap-2">
    @foreach ($tabs as $key => $label)
        @php $n = $key ? ($counts[$key] ?? 0) : $counts->sum(); @endphp
        <a href="{{ route('admin.laporan.index', $key ? ['status' => $key] : []) }}"
           class="px-4 py-2 rounded-full text-sm font-semibold border transition {{ $status === $key ? 'bg-primary text-white border-primary' : 'bg-white border-gray-200 hover:border-primary' }}">{{ $label }} <span class="opacity-70">({{ $n }})</span></a>
    @endforeach
</div>

<div class="mt-5 bg-white rounded-card border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm min-w-[820px]">
        <thead class="text-left text-xs uppercase tracking-wide text-muted border-b border-gray-100">
            <tr><th class="px-5 py-3">Dilaporkan</th><th class="px-3 py-3">Alasan</th><th class="px-3 py-3">Pelapor</th><th class="px-3 py-3">Waktu</th><th class="px-3 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr>
        </thead>
        <tbody>
        @forelse ($reports as $r)
            <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60">
                <td class="px-5 py-3 max-w-[260px]">
                    @if ($r->karya)
                        <span class="text-xs font-bold text-primary">Karya</span>
                        <p class="font-bold truncate">{{ $r->karya->judul }}</p>
                    @else
                        <span class="text-xs font-bold text-warning">Komentar</span>
                        <p class="truncate">{{ $r->komentar?->isi_komentar ?? ($r->snapshot ?: '(komentar dihapus)') }}</p>
                    @endif
                </td>
                <td class="px-3 py-3 max-w-[240px]"><p class="line-clamp-2">{{ $r->alasan }}</p></td>
                <td class="px-3 py-3">{{ $r->pelapor->name }}</td>
                <td class="px-3 py-3 text-muted whitespace-nowrap">{{ $r->created_at->diffForHumans() }}</td>
                <td class="px-3 py-3"><x-status-badge :status="$r->status" /></td>
                <td class="px-5 py-3 text-right"><a href="{{ route('admin.laporan.show', $r) }}" class="px-3 py-1.5 rounded-lg {{ $r->status === 'baru' ? 'bg-primary text-white hover:bg-secondary' : 'border border-gray-200 hover:border-primary hover:text-primary' }} text-xs font-semibold">{{ $r->status === 'baru' ? 'Tangani' : 'Lihat' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-14 text-center text-muted">Tidak ada laporan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $reports->links() }}
@endsection
