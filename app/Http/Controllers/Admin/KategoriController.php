<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KategoriRequest;
use App\Models\Kategori;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CRUD kategori (relasi "mengelola": admin_id mencatat admin terakhir yang mengelola).
 * Konsistensi referensi: kategori yang masih dipakai karya TIDAK dapat dihapus (aplikasi + FK restrict).
 */
class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::withCount('karyas')->with('admin')->orderBy('nama_kategori')->get();

        return view('admin.kategori.index', compact('kategoris'));
    }

    public function store(KategoriRequest $request): RedirectResponse
    {
        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
            'slug' => $this->uniqueSlug($request->nama_kategori),
            'deskripsi' => $request->deskripsi,
            'admin_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori dibuat');
    }

    public function edit(Kategori $kategori)
    {
        return view('admin.kategori.edit', compact('kategori'));
    }

    /** Slug sengaja tidak berubah saat nama diubah, supaya tautan /jelajah?kategori=slug tetap valid. */
    public function update(KategoriRequest $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'admin_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori diperbarui');
    }

    public function destroy(Request $request, Kategori $kategori): RedirectResponse
    {
        $dipakai = $kategori->karyas()->count();
        if ($dipakai > 0) {
            return back()->with('error', "Kategori \"{$kategori->nama_kategori}\" masih dipakai {$dipakai} karya sehingga tidak dapat dihapus.");
        }

        try {
            $kategori->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'Kategori baru saja dipakai karya lain dan tidak dapat dihapus.');
        }

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori dihapus');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        for ($i = 2; Kategori::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
