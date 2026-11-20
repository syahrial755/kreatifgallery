<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Karya;
use App\Models\Kategori;
use App\Models\Like;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $totalKarya = Karya::count();
        $totalSuka = Like::count();
        $totalKomentar = Comment::count();
        $publik = Karya::where('status', Karya::DISETUJUI)->count();

        $summary = [
            'suka' => $totalSuka,
            'komentar' => $totalKomentar,
            'rata_suka' => $publik ? round($totalSuka / $publik, 1) : 0,
            'approval_rate' => ($d = Karya::whereIn('status', [Karya::DISETUJUI, Karya::DITOLAK])->count()) ? round($publik / $d * 100) : null,
        ];

        // Karya per kategori (hanya yang tayang)
        $kategori = Kategori::withCount(['karyas as publik_count' => fn ($q) => $q->where('status', Karya::DISETUJUI)])
            ->orderByDesc('publik_count')->orderBy('nama_kategori')->get();

        // Distribusi status
        $byStatus = Karya::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        // Pengguna baru per hari (30 hari)
        $days = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $perDay = User::where('created_at', '>=', $days->first())->pluck('created_at')
            ->groupBy(fn ($d) => $d->format('Y-m-d'))->map->count();

        // Kreator teratas berdasarkan suka pada karya tayang
        $likeTotals = DB::table('likes')->join('karyas', 'karyas.id', '=', 'likes.karya_id')
            ->where('karyas.status', Karya::DISETUJUI)
            ->select('karyas.user_id', DB::raw('count(*) as total'))->groupBy('karyas.user_id')
            ->orderByDesc('total')->limit(5)->pluck('total', 'user_id');
        $topCreators = User::whereIn('id', $likeTotals->keys())->get()
            ->map(fn ($u) => (object) ['user' => $u, 'suka' => $likeTotals[$u->id]])->sortByDesc('suka')->values();

        $topWorks = Karya::publik()->with(['user', 'medias'])->withCount(['likes', 'comments'])
            ->orderByDesc('likes_count')->latest('id')->take(5)->get();

        $charts = [
            'kategori' => ['labels' => $kategori->pluck('nama_kategori'), 'data' => $kategori->pluck('publik_count')],
            'status' => [
                'labels' => ['Disetujui', 'Menunggu', 'Ditolak'],
                'data' => [(int) ($byStatus[Karya::DISETUJUI] ?? 0), (int) ($byStatus[Karya::MENUNGGU] ?? 0), (int) ($byStatus[Karya::DITOLAK] ?? 0)],
            ],
            'pengguna' => [
                'labels' => $days->map(fn ($d) => $d->translatedFormat('d M'))->all(),
                'data' => $days->map(fn ($d) => $perDay[$d->format('Y-m-d')] ?? 0)->all(),
            ],
        ];

        return view('admin.analitik', compact('summary', 'charts', 'topCreators', 'topWorks', 'totalKarya'));
    }
}
