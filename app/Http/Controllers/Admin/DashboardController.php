<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karya;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $byStatus = Karya::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $stats = [
            'users' => User::count(),
            'creators' => User::where('role', User::ROLE_KREATOR)->count(),
            'karya' => (int) $byStatus->sum(),
            'menunggu' => (int) ($byStatus[Karya::MENUNGGU] ?? 0),
            'disetujui' => (int) ($byStatus[Karya::DISETUJUI] ?? 0),
            'ditolak' => (int) ($byStatus[Karya::DITOLAK] ?? 0),
            'laporan' => Report::count(),
            'laporan_baru' => Report::where('status', Report::BARU)->count(),
        ];

        // Grafik aktivitas publikasi 14 hari: karya diunggah vs karya disetujui/tayang.
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $uploads = $this->perDay(Karya::where('created_at', '>=', $days->first())->pluck('created_at'));
        $published = $this->perDay(Karya::whereNotNull('published_at')->where('published_at', '>=', $days->first())->pluck('published_at'));
        $chart = [
            'labels' => $days->map(fn ($d) => $d->translatedFormat('d M'))->all(),
            'uploads' => $days->map(fn ($d) => $uploads[$d->format('Y-m-d')] ?? 0)->all(),
            'published' => $days->map(fn ($d) => $published[$d->format('Y-m-d')] ?? 0)->all(),
        ];

        $queue = Karya::where('status', Karya::MENUNGGU)->with(['medias', 'user', 'kategori'])->oldest()->take(5)->get();
        $latestReports = Report::with(['pelapor', 'karya', 'komentar'])->where('status', Report::BARU)->latest()->take(4)->get();

        return view('admin.dashboard', [
            'stats' => $stats, 'chart' => $chart, 'queue' => $queue,
            'latestReports' => $latestReports, 'activity' => $this->activity(),
        ]);
    }

    /** Aktivitas terbaru lintas entitas: unggahan, keputusan moderasi, laporan, pengguna baru. */
    private function activity(): Collection
    {
        $uploads = Karya::with('user')->latest()->take(6)->get()->map(fn ($k) => [
            'icon' => 'upload', 'at' => $k->created_at,
            'text' => "{$k->user->name} mengunggah \"{$k->judul}\"",
            'url' => $k->status === Karya::MENUNGGU ? route('admin.moderasi.show', $k) : route('karya.show', $k),
        ]);

        $decisions = Karya::with('admin')->whereNotNull('admin_id')->whereIn('status', [Karya::DISETUJUI, Karya::DITOLAK])
            ->latest('updated_at')->take(5)->get()->map(fn ($k) => [
                'icon' => $k->status === Karya::DISETUJUI ? 'check-circle-2' : 'x-circle', 'at' => $k->updated_at,
                'text' => "{$k->admin->name} ".($k->status === Karya::DISETUJUI ? 'menyetujui' : 'menolak')." \"{$k->judul}\"",
                'url' => route('karya.show', $k),
            ]);

        $reports = Report::with('pelapor')->latest()->take(4)->get()->map(fn ($r) => [
            'icon' => 'flag', 'at' => $r->created_at,
            'text' => "{$r->pelapor->name} mengirim laporan (".($r->komentar_id || ! $r->karya_id ? 'komentar' : 'karya').')',
            'url' => route('admin.laporan.show', $r),
        ]);

        $users = User::latest()->take(4)->get()->map(fn ($u) => [
            'icon' => 'user-plus', 'at' => $u->created_at,
            'text' => "Pengguna baru: {$u->name} ({$u->role})", 'url' => route('admin.pengguna.show', $u),
        ]);

        return $uploads->concat($decisions)->concat($reports)->concat($users)
            ->filter(fn ($a) => $a['at'])->sortByDesc('at')->take(8)->values();
    }

    /** @return array<string,int> */
    private function perDay(Collection $dates): array
    {
        return $dates->groupBy(fn ($d) => $d->format('Y-m-d'))->map->count()->all();
    }
}
