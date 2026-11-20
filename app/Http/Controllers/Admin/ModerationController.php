<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModerationNoteRequest;
use App\Models\Karya;
use App\Services\ModerationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Antrian moderasi: karya berstatus 'menunggu' (diurutkan dari yang terlama / FIFO). */
class ModerationController extends Controller
{
    public function __construct(private ModerationService $moderation)
    {
    }

    public function index()
    {
        $karyas = Karya::where('status', Karya::MENUNGGU)
            ->with(['medias', 'user', 'kategori'])->oldest()->oldest('id')->paginate(12);

        return view('admin.moderasi.index', compact('karyas'));
    }

    public function show(Karya $karya)
    {
        $karya->load(['medias', 'user', 'kategori', 'admin'])->loadCount(['likes', 'comments']);
        $creatorWorks = Karya::where('user_id', $karya->user_id)->count();

        return view('admin.moderasi.show', compact('karya', 'creatorWorks'));
    }

    public function approve(Request $request, Karya $karya): RedirectResponse
    {
        try {
            $this->moderation->approve($karya, $request->user());
        } catch (DomainException $e) {
            return redirect()->route('admin.moderasi.index')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.moderasi.index')->with('success', "Karya \"{$karya->judul}\" disetujui dan tayang di galeri");
    }

    public function reject(ModerationNoteRequest $request, Karya $karya): RedirectResponse
    {
        try {
            $this->moderation->reject($karya, $request->user(), $request->validated('catatan_moderasi'));
        } catch (DomainException $e) {
            return redirect()->route('admin.moderasi.index')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.moderasi.index')->with('success', "Karya \"{$karya->judul}\" ditolak. Kreator diberi tahu beserta catatannya");
    }
}
