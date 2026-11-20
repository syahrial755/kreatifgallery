<?php

namespace App\Http\Controllers\Kreator;

use App\Exceptions\MediaProcessingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKaryaRequest;
use App\Http\Requests\UpdateKaryaRequest;
use App\Models\Karya;
use App\Models\Kategori;
use App\Services\KaryaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class KaryaController extends Controller
{
    public function __construct(private KaryaService $service)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = $request->user()->karyas()->with(['medias', 'kategori', 'admin'])->withCount(['likes', 'comments']);

        if (in_array($status, [Karya::MENUNGGU, Karya::DISETUJUI, Karya::DITOLAK], true)) {
            $query->where('status', $status);
        } else {
            $status = null;
        }

        $karyas = $query->latest()->latest('id')->paginate(10)->withQueryString();
        $counts = $request->user()->karyas()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('kreator.karya.index', compact('karyas', 'status', 'counts'));
    }

    public function create()
    {
        return view('kreator.karya.create', ['kategoris' => Kategori::orderBy('nama_kategori')->get()]);
    }

    public function store(StoreKaryaRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $this->service->create($request->user(), $request->validated(), $request->file('media', []));
        } catch (MediaProcessingException $e) {
            return $this->mediaFailure($request, $e);
        }

        return $this->done($request, route('kreator.dashboard'), 'Karya terunggah dan menunggu moderasi admin');
    }

    public function edit(Karya $karya)
    {
        Gate::authorize('update', $karya);

        return view('kreator.karya.edit', [
            'karya' => $karya->load('medias'),
            'kategoris' => Kategori::orderBy('nama_kategori')->get(),
        ]);
    }

    public function update(UpdateKaryaRequest $request, Karya $karya): RedirectResponse|JsonResponse
    {
        $statusAwal = $karya->status;

        try {
            $karya = $this->service->update($karya, $request->validated(), $request->file('media', []), $request->removeIds());
        } catch (MediaProcessingException $e) {
            return $this->mediaFailure($request, $e);
        }

        $pesan = $karya->status === Karya::MENUNGGU && $statusAwal !== Karya::MENUNGGU
            ? 'Revisi terkirim. Karya kembali menunggu moderasi admin'
            : 'Karya berhasil diperbarui';

        return $this->done($request, route('kreator.karya.index'), $pesan);
    }

    public function destroy(Karya $karya): RedirectResponse
    {
        Gate::authorize('delete', $karya);
        $this->service->delete($karya);

        return redirect()->route('kreator.karya.index')->with('success', 'Karya dihapus');
    }

    private function done(Request $request, string $url, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            session()->flash('success', $message);

            return response()->json(['redirect' => $url]);
        }

        return redirect($url)->with('success', $message);
    }

    private function mediaFailure(Request $request, MediaProcessingException $e): RedirectResponse|JsonResponse
    {
        report($e);
        $msg = 'Gagal memproses file media: '.$e->getMessage().' Tidak ada data yang tersimpan.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $msg, 'errors' => ['media' => [$msg]]], 422);
        }

        throw ValidationException::withMessages(['media' => $msg]);
    }
}
