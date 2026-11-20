<?php

use App\Http\Controllers\Admin\AnalyticsController as AdminAnalytics;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\KaryaController as AdminKarya;
use App\Http\Controllers\Admin\KategoriController as AdminKategori;
use App\Http\Controllers\Admin\ModerationController as AdminModeration;
use App\Http\Controllers\Admin\ReportController as AdminReport;
use App\Http\Controllers\Admin\UserController as AdminUser;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Kreator\DashboardController as KreatorDashboard;
use App\Http\Controllers\Kreator\KaryaController;
use App\Http\Controllers\Kreator\PortofolioController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// Situs publik (tanpa login): Home, Explore, Categories, About, detail karya, profil kreator
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/jelajah', [PublicController::class, 'explore'])->name('explore');
Route::get('/kategori', [PublicController::class, 'categories'])->name('categories');
Route::get('/tentang', [PublicController::class, 'about'])->name('about');
Route::get('/karya/{karya}', [PublicController::class, 'show'])->whereNumber('karya')->name('karya.show');
Route::get('/profil/{user}', [PublicController::class, 'profil'])->whereNumber('user')->name('profil');

// Autentikasi (diagram Use Case: Login/Logout, Registrasi kreator/pengunjung)
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.store');
});
Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Semua pengguna login & aktif: notifikasi (Use Case PENGGUNA: Melihat notifikasi)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    // Interaksi pada karya tayang: suka, komentar, laporan (Use Case PENGGUNA)
    Route::post('/karya/{karya}/suka', [InteractionController::class, 'toggleLike'])->middleware('throttle:60,1')->whereNumber('karya')->name('karya.suka');
    Route::post('/karya/{karya}/komentar', [InteractionController::class, 'storeComment'])->middleware('throttle:10,1')->whereNumber('karya')->name('karya.komentar.store');
    Route::delete('/komentar/{comment}', [InteractionController::class, 'destroyComment'])->whereNumber('comment')->name('komentar.destroy');
    Route::post('/karya/{karya}/laporkan', [InteractionController::class, 'reportKarya'])->middleware('throttle:5,1')->whereNumber('karya')->name('karya.laporkan');
    Route::post('/komentar/{comment}/laporkan', [InteractionController::class, 'reportComment'])->middleware('throttle:5,1')->whereNumber('comment')->name('komentar.laporkan');

    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('/notifikasi/{id}/baca', [NotificationController::class, 'baca'])->name('notifikasi.baca');

    // Profil & pengaturan akun (kreator dan admin)
    Route::middleware('role:kreator,admin')->prefix('akun')->name('akun.')->group(function () {
        Route::get('/profil', [AccountController::class, 'profil'])->name('profil');
        Route::put('/profil', [AccountController::class, 'updateProfil'])->name('profil.update');
        Route::get('/pengaturan', [AccountController::class, 'pengaturan'])->name('pengaturan');
        Route::put('/pengaturan', [AccountController::class, 'updatePassword'])->name('password.update');
    });
});

// KREATOR: auth -> active -> role:kreator (diagram Sequence: Middleware auth · role:kreator · throttle:unggah)
Route::middleware(['auth', 'active', 'role:kreator'])->prefix('kreator')->name('kreator.')->group(function () {
    Route::get('/dashboard', [KreatorDashboard::class, 'index'])->name('dashboard');

    Route::get('/karya', [KaryaController::class, 'index'])->name('karya.index');
    Route::get('/karya/unggah', [KaryaController::class, 'create'])->name('karya.create');
    Route::post('/karya', [KaryaController::class, 'store'])->middleware('throttle:unggah')->name('karya.store');
    Route::get('/karya/{karya}/ubah', [KaryaController::class, 'edit'])->whereNumber('karya')->name('karya.edit');
    Route::put('/karya/{karya}', [KaryaController::class, 'update'])->middleware('throttle:unggah')->whereNumber('karya')->name('karya.update');
    Route::delete('/karya/{karya}', [KaryaController::class, 'destroy'])->whereNumber('karya')->name('karya.destroy');

    Route::resource('portofolio', PortofolioController::class)->except('show')->parameters(['portofolio' => 'portofolio']);
});

// ADMIN: auth -> active -> role:admin
Route::middleware(['auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');
    Route::get('/analitik', [AdminAnalytics::class, 'index'])->name('analitik');

    // Moderasi karya (Activity: Tinjau antrian -> Karya layak tayang? -> Setujui / Tolak + catatan wajib)
    Route::get('/moderasi', [AdminModeration::class, 'index'])->name('moderasi.index');
    Route::get('/moderasi/{karya}', [AdminModeration::class, 'show'])->whereNumber('karya')->name('moderasi.show');
    Route::post('/moderasi/{karya}/setujui', [AdminModeration::class, 'approve'])->whereNumber('karya')->name('moderasi.approve');
    Route::post('/moderasi/{karya}/tolak', [AdminModeration::class, 'reject'])->whereNumber('karya')->name('moderasi.reject');

    Route::get('/karya', [AdminKarya::class, 'index'])->name('karya.index');
    Route::post('/karya/{karya}/turunkan', [AdminKarya::class, 'turunkan'])->whereNumber('karya')->name('karya.turunkan');

    // {kategori:id}: admin memakai id (model Kategori memakai slug untuk rute publik)
    Route::get('/kategori', [AdminKategori::class, 'index'])->name('kategori.index');
    Route::post('/kategori', [AdminKategori::class, 'store'])->name('kategori.store');
    Route::get('/kategori/{kategori:id}/ubah', [AdminKategori::class, 'edit'])->whereNumber('kategori')->name('kategori.edit');
    Route::put('/kategori/{kategori:id}', [AdminKategori::class, 'update'])->whereNumber('kategori')->name('kategori.update');
    Route::delete('/kategori/{kategori:id}', [AdminKategori::class, 'destroy'])->whereNumber('kategori')->name('kategori.destroy');

    Route::get('/pengguna', [AdminUser::class, 'index'])->name('pengguna.index');
    Route::get('/pengguna/{user}', [AdminUser::class, 'show'])->whereNumber('user')->name('pengguna.show');
    Route::patch('/pengguna/{user}/status', [AdminUser::class, 'status'])->whereNumber('user')->name('pengguna.status');

    Route::get('/laporan', [AdminReport::class, 'index'])->name('laporan.index');
    Route::get('/laporan/{laporan}', [AdminReport::class, 'show'])->whereNumber('laporan')->name('laporan.show');
    Route::post('/laporan/{laporan}/tangani', [AdminReport::class, 'tangani'])->whereNumber('laporan')->name('laporan.tangani');
});
