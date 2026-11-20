<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifikasi.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(15),
        ]);
    }

    /** Tandai dibaca lalu buka tautan notifikasi. Hanya notifikasi milik sendiri (findOrFail pada relasi user). */
    public function baca(Request $request, string $id): RedirectResponse
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();

        return redirect($n->data['url'] ?? route('notifikasi.index'));
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai dibaca');
    }
}
