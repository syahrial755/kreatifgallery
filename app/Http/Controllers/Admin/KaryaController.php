<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModerationNoteRequest;
use App\Models\Karya;
use App\Models\Kategori;
use App\Services\ModerationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "All Works": semua karya (semua status) untuk ditinjau; karya tayang dapat diturunkan. */
class KaryaController extends Controller
{
    public function __construct(private ModerationService $moderation)
    {
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:menunggu,disetujui,ditolak'],
            'kategori' => ['nullable', 'integer'],
        ]);

        $query = Karya::with(['medias', 'user', 'kategori'])->withCount(['likes', 'comments']);

        if ($q = trim($data['q'] ?? '')) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $query->where(fn ($w) => $w->where('judul', 'like', $like)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)));
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['kategori'])) {
            $query->where('kategori_id', $data['kategori']);
        }

        return view('admin.karya.index', [
            'karyas' => $query->latest()->latest('id')->paginate(15)->withQueryString(),
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
            'filters' => ['q' => $q ?? '', 'status' => $data['status'] ?? null, 'kategori' => $data['kategori'] ?? null],
        ]);
    }

    public function turunkan(ModerationNoteRequest $request, Karya $karya): RedirectResponse
    {
        try {
            $this->moderation->unpublish($karya, $request->user(), $request->validated('catatan_moderasi'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Karya \"{$karya->judul}\" diturunkan dari galeri");
    }
}
