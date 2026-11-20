<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/** Profil (nama, bio, foto) & Pengaturan (kata sandi) untuk kreator dan admin. */
class AccountController extends Controller
{
    public function profil(Request $request)
    {
        return view('akun.profil', ['user' => $request->user()]);
    }

    public function updateProfil(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_foto' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'bio.max' => 'Biografi maksimal 500 karakter.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();
        $old = $user->foto_profil;
        $user->name = $data['name'];
        $user->bio = $data['bio'] ?? null;

        if ($request->hasFile('foto')) {
            $user->foto_profil = $request->file('foto')->storeAs('profil', Str::uuid().'.'.$request->file('foto')->guessExtension(), 'public');
        } elseif ($request->boolean('hapus_foto')) {
            $user->foto_profil = null;
        }

        $user->save();

        if ($old && $old !== $user->foto_profil) {
            Storage::disk('public')->delete($old); // tidak meninggalkan foto lama yatim
        }

        return back()->with('success', 'Profil diperbarui');
    }

    public function pengaturan()
    {
        return view('akun.pengaturan');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $request->user()->update(['password' => $request->password]); // cast "hashed"

        return back()->with('success', 'Kata sandi diperbarui');
    }
}
