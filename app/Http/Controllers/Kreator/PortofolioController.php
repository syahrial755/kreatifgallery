<?php

namespace App\Http\Controllers\Kreator;

use App\Http\Controllers\Controller;
use App\Models\Portofolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Use case "Mengelola portofolio": kumpulan karya milik kreator (relasi N:M via karya_portofolio). */
class PortofolioController extends Controller
{
    public function index(Request $request)
    {
        $portofolios = $request->user()->portofolios()->withCount('karyas')->latest()->get();

        return view('kreator.portofolio.index', compact('portofolios'));
    }

    public function create(Request $request)
    {
        return view('kreator.portofolio.form', [
            'portofolio' => new Portofolio(['is_public' => true]),
            'karyas' => $request->user()->karyas()->orderBy('judul')->get(),
            'dipilih' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data) {
            $p = $request->user()->portofolios()->create([
                'judul' => $data['judul'], 'deskripsi' => $data['deskripsi'] ?? null,
                'is_public' => $request->boolean('is_public'),
            ]);
            $p->karyas()->sync($data['karya_ids'] ?? []);
        });

        return redirect()->route('kreator.portofolio.index')->with('success', 'Portofolio dibuat');
    }

    public function edit(Request $request, Portofolio $portofolio)
    {
        Gate::authorize('manage', $portofolio);

        return view('kreator.portofolio.form', [
            'portofolio' => $portofolio,
            'karyas' => $request->user()->karyas()->orderBy('judul')->get(),
            'dipilih' => $portofolio->karyas()->pluck('karyas.id')->all(),
        ]);
    }

    public function update(Request $request, Portofolio $portofolio): RedirectResponse
    {
        Gate::authorize('manage', $portofolio);
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $portofolio, $data) {
            $portofolio->update([
                'judul' => $data['judul'], 'deskripsi' => $data['deskripsi'] ?? null,
                'is_public' => $request->boolean('is_public'),
            ]);
            $portofolio->karyas()->sync($data['karya_ids'] ?? []);
        });

        return redirect()->route('kreator.portofolio.index')->with('success', 'Portofolio diperbarui');
    }

    public function destroy(Portofolio $portofolio): RedirectResponse
    {
        Gate::authorize('manage', $portofolio);
        $portofolio->delete(); // pivot ikut terhapus; karya TIDAK terhapus

        return redirect()->route('kreator.portofolio.index')->with('success', 'Portofolio dihapus');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'karya_ids' => ['nullable', 'array'],
            // Hanya karya milik sendiri yang boleh dimasukkan.
            'karya_ids.*' => ['integer', Rule::exists('karyas', 'id')->where('user_id', $request->user()->id)],
        ], [
            'judul.required' => 'Judul portofolio wajib diisi.',
            'karya_ids.*.exists' => 'Karya yang dipilih tidak valid.',
        ]);
    }
}
