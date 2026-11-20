<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ModerationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __construct(private ModerationService $moderation)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        if (! in_array($status, [Report::BARU, Report::SELESAI], true)) {
            $status = null;
        }

        $query = Report::with(['pelapor', 'karya.user', 'komentar.user', 'penangan']);
        if ($status) {
            $query->where('status', $status);
        }

        // Laporan baru lebih dulu, lalu yang terbaru.
        $reports = $query->orderByRaw("case when status = 'baru' then 0 else 1 end")->latest()->latest('id')->paginate(15)->withQueryString();

        $counts = Report::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.laporan.index', compact('reports', 'status', 'counts'));
    }

    public function show(Report $laporan)
    {
        $laporan->load(['pelapor', 'karya.user', 'karya.medias', 'komentar.user', 'komentar.karya', 'penangan']);

        return view('admin.laporan.show', ['report' => $laporan]);
    }

    public function tangani(Request $request, Report $laporan): RedirectResponse
    {
        $data = $request->validate([
            'tindakan' => ['required', Rule::in(['abaikan', 'hapus_komentar', 'turunkan_karya'])],
            'catatan_moderasi' => ['required_if:tindakan,turunkan_karya', 'nullable', 'string', 'min:5', 'max:1000'],
        ], [
            'tindakan.required' => 'Pilih tindakan.',
            'catatan_moderasi.required_if' => 'Alasan penurunan karya wajib diisi.',
            'catatan_moderasi.min' => 'Alasan minimal 5 karakter.',
        ]);

        try {
            $this->moderation->handleReport($laporan, $request->user(), $data['tindakan'], $data['catatan_moderasi'] ?? null);
        } catch (DomainException $e) {
            return redirect()->route('admin.laporan.show', $laporan)->with('error', $e->getMessage());
        }

        $pesan = ['abaikan' => 'Laporan diabaikan', 'hapus_komentar' => 'Komentar dihapus dan laporan diselesaikan', 'turunkan_karya' => 'Karya diturunkan dan laporan diselesaikan'];

        return redirect()->route('admin.laporan.index')->with('success', $pesan[$data['tindakan']]);
    }
}
