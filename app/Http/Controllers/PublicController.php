<?php

namespace App\Http\Controllers;

use App\Models\Karya;
use App\Models\Kategori;
use App\Models\Like;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PublicController extends Controller
{
    /** Query dasar: hanya karya disetujui milik akun aktif, lengkap dengan hitungan. */
    private function publik()
    {
        return Karya::publik()->with(['medias', 'user', 'kategori'])->withCount(['likes', 'comments']);
    }


    /** Fotografi selalu di urutan pertama, sisanya urut abjad. */
    private function urutkanKategori($kategoris)
    {
        return $kategoris->sortBy(fn ($k) => [$k->slug === 'fotografi' ? 0 : 1, $k->nama_kategori])->values();
    }

    /** Pasang karya sampul (foto terpopuler yang berupa gambar) pada tiap kategori. */
    private function pasangSampul($kategoris)
    {
        foreach ($kategoris as $k) {
            $k->setRelation('sampul', $this->publik()
                ->where('kategori_id', $k->id)
                ->whereHas('medias', fn ($m) => $m->where('tipe_media', '!=', 'video'))
                ->orderByDesc('likes_count')->latest('id')->first());
        }

        return $kategoris;
    }

    public function home()
    {
        $featured = $this->publik()->whereHas('medias')
            ->orderByDesc('likes_count')->latest('published_at')->first();

        $popular = $this->publik()->orderByDesc('likes_count')->latest('published_at')->take(8)->get();
        $latest = $this->publik()->latest('published_at')->latest('id')->take(8)->get();

        $kategoris = $this->pasangSampul($this->urutkanKategori(
            Kategori::withCount(['karyas as publik_count' => fn ($q) => $q->publik()])->get()
        ));

        // Fotografi tampil di depan: 3 foto untuk hero, sisanya untuk bagian Fotografi.
        $fotoQuery = fn () => $this->publik()
            ->whereHas('medias', fn ($m) => $m->where('tipe_media', '!=', 'video'))
            ->orderByDesc('likes_count')->latest('published_at')->latest('id');
        $fotografi = $fotoQuery()->whereHas('kategori', fn ($k) => $k->where('slug', 'fotografi'))->take(12)->get();
        if ($fotografi->count() < 3) { // belum ada cukup foto: lengkapi dari karya bergambar lain
            $fotografi = $fotografi->concat($fotoQuery()->whereNotIn('id', $fotografi->pluck('id'))->take(3 - $fotografi->count())->get());
        }
        $heroFoto = $fotografi->take(3)->values();
        $galeriFoto = $fotografi->slice(3)->values();
        $totalFoto = Karya::publik()->whereHas('kategori', fn ($k) => $k->where('slug', 'fotografi'))->count();
        $totalFotografer = User::where('role', User::ROLE_KREATOR)->where('is_active', true)
            ->whereHas('karyas', fn ($q) => $q->where('status', Karya::DISETUJUI)->whereHas('kategori', fn ($k) => $k->where('slug', 'fotografi')))->count();

        $creators = User::where('role', User::ROLE_KREATOR)->where('is_active', true)
            ->whereHas('karyas', fn ($q) => $q->where('status', Karya::DISETUJUI))
            ->withCount(['karyas as publik_count' => fn ($q) => $q->where('status', Karya::DISETUJUI)])
            ->orderByDesc('publik_count')->take(4)->get();

        return view('public.home', compact('featured', 'popular', 'latest', 'kategoris', 'creators', 'heroFoto', 'galeriFoto', 'totalFoto', 'totalFotografer'));
    }

    public function explore(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'urut' => ['nullable', 'in:terbaru,populer'],
        ]);

        $q = trim($data['q'] ?? '');
        $urut = $data['urut'] ?? 'terbaru';
        $kategoris = $this->urutkanKategori(Kategori::orderBy('nama_kategori')->get());
        $aktif = $kategoris->firstWhere('slug', $data['kategori'] ?? null);

        $query = $this->publik();

        if ($q !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
            $query->where(fn ($w) => $w->where('judul', 'like', $like)->orWhere('deskripsi', 'like', $like));
        }
        if ($aktif) {
            $query->where('kategori_id', $aktif->id);
        }

        $urut === 'populer'
            ? $query->orderByDesc('likes_count')->latest('id')
            : $query->latest('published_at')->latest('id');

        $karyas = $query->paginate(12)->withQueryString();

        return view('public.explore', compact('karyas', 'kategoris', 'aktif', 'q', 'urut'));
    }

    public function categories()
    {
        $kategoris = $this->pasangSampul($this->urutkanKategori(
            Kategori::withCount(['karyas as publik_count' => fn ($q) => $q->publik()])->get()
        ));

        return view('public.categories', compact('kategoris'));
    }

    public function about()
    {
        return view('public.about', [
            'totalKarya' => Karya::publik()->count(),
            'totalKreator' => User::where('role', User::ROLE_KREATOR)->where('is_active', true)->count(),
        ]);
    }

    public function show(Karya $karya)
    {
        // Karya yang belum tayang hanya terlihat oleh pemilik & admin; selain itu 404 (tidak membocorkan keberadaannya).
        abort_unless(Gate::allows('view', $karya), 404);

        $karya->load(['medias', 'user', 'kategori'])->loadCount(['likes', 'comments']);
        $comments = $karya->comments()->with('user')->latest()->get();

        $related = $this->publik()->where('kategori_id', $karya->kategori_id)
            ->whereKeyNot($karya->id)->latest('published_at')->take(4)->get();

        $liked = auth()->check() && $karya->likes()->where('user_id', auth()->id())->exists();
        $canInteract = $karya->isPublik();

        return view('public.karya', compact('karya', 'comments', 'related', 'liked', 'canInteract'));
    }

    public function profil(User $user)
    {
        // Hanya profil publik kreator aktif.
        abort_unless($user->isKreator() && $user->is_active, 404);

        $karyas = $this->publik()->where('user_id', $user->id)->latest('published_at')->latest('id')->paginate(12);

        $totalKarya = Karya::publik()->where('user_id', $user->id)->count();
        $totalSuka = Like::whereHas('karya', fn ($q) => $q->where('user_id', $user->id)->where('status', Karya::DISETUJUI))->count();

        // Hanya portofolio publik yang memuat setidaknya satu karya tayang.
        $portofolios = $user->portofolios()->where('is_public', true)
            ->whereHas('karyas', fn ($q) => $q->where('status', Karya::DISETUJUI))
            ->withCount(['karyas as publik_count' => fn ($q) => $q->where('status', Karya::DISETUJUI)])
            ->latest()->get();

        return view('public.profil', compact('user', 'karyas', 'totalKarya', 'totalSuka', 'portofolios'));
    }
}
