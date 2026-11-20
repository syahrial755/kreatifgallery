@php
    $icons = ['submitted' => 'upload', 'revision' => 'refresh-cw', 'approved' => 'check-circle-2', 'rejected' => 'x-circle', 'comment' => 'message-circle'];
    $tones = ['approved' => 'text-success', 'rejected' => 'text-danger', 'revision' => 'text-warning'];
@endphp
<div class="relative" data-dropdown>
    <button type="button" data-dropdown-toggle class="relative p-2.5 rounded-xl hover:bg-gray-50" aria-label="Notifikasi" aria-haspopup="true">
        <i data-lucide="bell" class="w-5 h-5"></i>
        @if (($unreadCount ?? 0) > 0)
            <span class="absolute top-1.5 right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-danger text-white text-[10px] font-bold flex items-center justify-center">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>
    <div data-dropdown-menu class="hidden absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-card border border-gray-100 shadow-lg overflow-hidden z-50">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="font-bold text-sm">Notifikasi</p>
            @if (($unreadCount ?? 0) > 0)
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">@csrf
                    <button class="text-xs font-semibold text-primary">Tandai semua dibaca</button></form>
            @endif
        </div>
        <div class="max-h-80 overflow-y-auto">
            @forelse ($recentNotifications ?? [] as $n)
                <form method="POST" action="{{ route('notifikasi.baca', $n->id) }}">@csrf
                    <button class="w-full text-left flex gap-3 px-4 py-3 hover:bg-gray-50 {{ $n->read_at ? '' : 'bg-primary/5' }}">
                        <i data-lucide="{{ $icons[$n->data['kind'] ?? ''] ?? 'bell' }}" class="w-4 h-4 mt-0.5 shrink-0 {{ $tones[$n->data['kind'] ?? ''] ?? 'text-primary' }}"></i>
                        <span class="min-w-0">
                            <span class="block text-sm leading-snug line-clamp-3">{{ $n->data['message'] ?? '' }}</span>
                            <span class="block text-xs text-muted mt-1">{{ $n->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <p class="px-4 py-8 text-sm text-muted text-center">Belum ada notifikasi.</p>
            @endforelse
        </div>
        <a href="{{ route('notifikasi.index') }}" class="block text-center text-sm font-semibold text-primary py-3 border-t border-gray-100 hover:bg-gray-50">Lihat semua</a>
    </div>
</div>
