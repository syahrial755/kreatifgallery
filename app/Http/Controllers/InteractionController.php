<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreReportRequest;
use App\Models\Comment;
use App\Models\Karya;
use App\Models\Like;
use App\Models\Report;
use App\Services\ModerationService;
use App\Services\NotificationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Interaksi pengguna login pada karya yang tayang: suka, komentar, laporan. */
class InteractionController extends Controller
{
    private function publicOr404(Karya $karya): void
    {
        abort_unless($karya->isPublik(), 404);
    }

    public function toggleLike(Request $request, Karya $karya)
    {
        $this->publicOr404($karya);
        $user = $request->user();

        $existing = Like::where('user_id', $user->id)->where('karya_id', $karya->id)->first();
        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            try {
                Like::create(['user_id' => $user->id, 'karya_id' => $karya->id]);
            } catch (UniqueConstraintViolationException) {
                // Klik ganda bersamaan: baris unik sudah ada, hasilnya tetap "disukai".
            }
            $liked = true;
        }
        $count = $karya->likes()->count();

        if ($request->expectsJson()) {
            return response()->json(['liked' => $liked, 'count' => $count]);
        }

        return redirect()->to(route('karya.show', $karya).'#aksi');
    }

    public function storeComment(StoreCommentRequest $request, Karya $karya, NotificationService $notif)
    {
        $this->publicOr404($karya);

        $comment = $karya->comments()->create([
            'user_id' => $request->user()->id,
            'isi_komentar' => $request->validated('isi_komentar'),
        ]);
        $notif->newComment($comment->load(['user', 'karya.user']));

        return redirect()->to(route('karya.show', $karya).'#komentar-'.$comment->id)->with('success', 'Komentar terkirim.');
    }

    public function destroyComment(Comment $comment, ModerationService $moderation)
    {
        Gate::authorize('delete', $comment);
        $karya = $comment->karya;

        DB::transaction(function () use ($comment, $moderation) {
            $moderation->closeCommentReports($comment, auth()->user());
            $comment->delete();
        });

        return redirect()->to(route('karya.show', $karya).'#komentar')->with('success', 'Komentar dihapus.');
    }

    public function reportKarya(StoreReportRequest $request, Karya $karya)
    {
        $this->publicOr404($karya);
        abort_if($karya->user_id === $request->user()->id, 403, 'Anda tidak dapat melaporkan karya sendiri.');

        $dup = Report::where('user_id', $request->user()->id)->where('karya_id', $karya->id)->where('status', Report::BARU)->exists();
        if ($dup) {
            return back()->with('info', 'Anda sudah melaporkan karya ini; laporan sedang ditinjau admin.');
        }

        Report::create(['user_id' => $request->user()->id, 'karya_id' => $karya->id, 'alasan' => $request->validated('alasan')]);

        return back()->with('success', 'Terima kasih, laporan Anda dikirim ke admin.');
    }

    public function reportComment(StoreReportRequest $request, Comment $comment)
    {
        $this->publicOr404($comment->karya);
        abort_if($comment->user_id === $request->user()->id, 403, 'Anda tidak dapat melaporkan komentar sendiri.');

        $dup = Report::where('user_id', $request->user()->id)->where('komentar_id', $comment->id)->where('status', Report::BARU)->exists();
        if ($dup) {
            return back()->with('info', 'Anda sudah melaporkan komentar ini; laporan sedang ditinjau admin.');
        }

        Report::create([
            'user_id' => $request->user()->id, 'komentar_id' => $comment->id,
            'alasan' => $request->validated('alasan'), 'snapshot' => $comment->isi_komentar,
        ]);

        return back()->with('success', 'Terima kasih, laporan Anda dikirim ke admin.');
    }
}
