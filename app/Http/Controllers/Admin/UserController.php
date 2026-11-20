<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Karya;
use App\Models\Like;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:admin,kreator,pengunjung'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        $query = User::query()->withCount('karyas');

        if ($q = trim($data['q'] ?? '')) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }
        if (! empty($data['role'])) {
            $query->where('role', $data['role']);
        }
        if (! empty($data['status'])) {
            $query->where('is_active', $data['status'] === 'aktif');
        }

        return view('admin.pengguna.index', [
            'users' => $query->latest()->latest('id')->paginate(15)->withQueryString(),
            'filters' => ['q' => $q ?? '', 'role' => $data['role'] ?? null, 'status' => $data['status'] ?? null],
        ]);
    }

    public function show(User $user)
    {
        $byStatus = $user->karyas()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $stats = [
            'karya' => (int) $byStatus->sum(),
            'disetujui' => (int) ($byStatus[Karya::DISETUJUI] ?? 0),
            'menunggu' => (int) ($byStatus[Karya::MENUNGGU] ?? 0),
            'ditolak' => (int) ($byStatus[Karya::DITOLAK] ?? 0),
            'suka_diterima' => Like::whereHas('karya', fn ($q) => $q->where('user_id', $user->id))->count(),
            'komentar_ditulis' => Comment::where('user_id', $user->id)->count(),
            'laporan_dikirim' => Report::where('user_id', $user->id)->count(),
        ];
        $karyas = $user->karyas()->with('kategori')->latest()->take(8)->get();

        return view('admin.pengguna.show', compact('user', 'stats', 'karyas'));
    }

    /** Aktifkan / nonaktifkan akun. Admin tidak dapat menonaktifkan dirinya sendiri (mencegah terkunci). */
    public function status(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? "Akun {$user->name} diaktifkan" : "Akun {$user->name} dinonaktifkan");
    }
}
