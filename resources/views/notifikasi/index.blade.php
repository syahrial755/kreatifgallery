@php
    $dash = auth()->user()->isKreator() || auth()->user()->isAdmin();
    $icons = ['submitted' => 'upload', 'revision' => 'refresh-cw', 'approved' => 'check-circle-2', 'rejected' => 'x-circle', 'comment' => 'message-circle'];
    $tones = ['approved' => 'text-success', 'rejected' => 'text-danger', 'revision' => 'text-warning'];
@endphp
@extends($dash ? 'layouts.dashboard' : 'layouts.app')
@section('title', 'Notifikasi')
@section('heading', 'Notifications')
@section('content')
<div class="{{ $dash ? '' : 'max-w-3xl mx-auto px-4 sm:px-6 pt-10' }}">
    <div class="flex items-center justify-between gap-4 mb-4">
        @unless ($dash) <h1 class="text-3xl font-extrabold">Notifikasi</h1> @endunless
        <form method="POST" action="{{ route('notifikasi.baca-semua') }}" class="ml-auto">@csrf
            <button class="px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold hover:border-primary hover:text-primary">Tandai semua dibaca</button>
        </form>
    </div>

    <div class="bg-white rounded-card border border-gray-100 shadow-sm overflow-hidden">
        @forelse ($notifications as $n)
            <form method="POST" action="{{ route('notifikasi.baca', $n->id) }}">@csrf
                <button class="w-full text-left flex gap-4 px-5 py-4 border-b border-gray-50 last:border-0 hover:bg-gray-50 {{ $n->read_at ? '' : 'bg-primary/5' }}">
                    <i data-lucide="{{ $icons[$n->data['kind'] ?? ''] ?? 'bell' }}" class="w-5 h-5 mt-0.5 shrink-0 {{ $tones[$n->data['kind'] ?? ''] ?? 'text-primary' }}"></i>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm leading-snug">{{ $n->data['message'] ?? '' }}</span>
                        <span class="block text-xs text-muted mt-1">{{ $n->created_at->translatedFormat('d M Y, H:i') }} · {{ $n->created_at->diffForHumans() }}</span>
                    </span>
                    @unless ($n->read_at) <span class="w-2.5 h-2.5 rounded-full bg-primary mt-1.5 shrink-0" title="Belum dibaca"></span> @endunless
                </button>
            </form>
        @empty
            <div class="px-6 py-14 text-center">
                <i data-lucide="bell-off" class="w-10 h-10 text-muted mx-auto"></i>
                <p class="font-bold mt-3">Belum ada notifikasi</p>
            </div>
        @endforelse
    </div>
    {{ $notifications->links() }}
</div>
@endsection
