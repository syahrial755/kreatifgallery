<?php

namespace App\Http\Controllers\Kreator;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Karya;
use App\Models\Like;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $byStatus = $user->karyas()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $stats = [
            'total' => (int) $byStatus->sum(),
            'disetujui' => (int) ($byStatus[Karya::DISETUJUI] ?? 0),
            'menunggu' => (int) ($byStatus[Karya::MENUNGGU] ?? 0),
            'ditolak' => (int) ($byStatus[Karya::DITOLAK] ?? 0),
            'suka' => Like::whereHas('karya', fn ($q) => $q->where('user_id', $user->id))->count(),
            'komentar' => Comment::whereHas('karya', fn ($q) => $q->where('user_id', $user->id))->count(),
        ];

        $recent = $user->karyas()->with(['medias', 'kategori'])->withCount(['likes', 'comments'])->latest()->take(5)->get();

        // Aktivitas terbaru: suka & komentar pada karya milik kreator.
        $likes = Like::with(['user', 'karya'])->whereHas('karya', fn ($q) => $q->where('user_id', $user->id))
            ->latest('created_at')->take(6)->get()
            ->map(fn ($l) => ['icon' => 'heart', 'text' => "{$l->user->name} menyukai \"{$l->karya->judul}\"", 'at' => $l->created_at, 'url' => route('karya.show', $l->karya)]);
        $comments = Comment::with(['user', 'karya'])->whereHas('karya', fn ($q) => $q->where('user_id', $user->id))
            ->latest()->take(6)->get()
            ->map(fn ($c) => ['icon' => 'message-circle', 'text' => "{$c->user->name} mengomentari \"{$c->karya->judul}\"", 'at' => $c->created_at, 'url' => route('karya.show', $c->karya).'#komentar-'.$c->id]);
        $activity = $likes->concat($comments)->filter(fn ($a) => $a['at'])->sortByDesc('at')->take(8)->values();

        $notifications = $user->notifications()->latest()->take(5)->get();

        return view('kreator.dashboard', compact('stats', 'recent', 'activity', 'notifications'));
    }
}
