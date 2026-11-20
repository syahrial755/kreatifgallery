<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        // Activity: "Login valid & akun aktif?" -> bila tidak, tampilkan pesan error.
        $user = User::where('email', $credentials['email'])->first();
        if ($user && Hash::check($credentials['password'], $user->password) && ! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Akun Anda dinonaktifkan oleh admin.']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeFor(Auth::user()));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->homeFor($user))->with('success', 'Selamat datang di KreatifGallery!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function homeFor(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => route('admin.dashboard'),
            User::ROLE_KREATOR => route('kreator.dashboard'),
            default => route('home'),
        };
    }
}
