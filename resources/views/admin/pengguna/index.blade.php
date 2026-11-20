@extends('layouts.dashboard')
@section('title', 'Users')
@section('heading', 'Users')
@section('content')
@php $inp = 'rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none'; @endphp
<form method="GET" action="{{ route('admin.pengguna.index') }}" class="flex flex-col lg:flex-row gap-3">
    <div class="relative flex-1">
        <i data-lucide="search" class="w-4 h-4 text-muted absolute left-4 top-1/2 -translate-y-1/2"></i>
        <input type="search" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Cari nama atau email…" class="{{ $inp }} w-full pl-11">
    </div>
    <select name="role" aria-label="Peran" class="{{ $inp }}">
        <option value="">Semua peran</option>
        @foreach (['admin' => 'Admin', 'kreator' => 'Kreator', 'pengunjung' => 'Pengunjung'] as $v => $l)
            <option value="{{ $v }}" @selected($filters['role'] === $v)>{{ $l }}</option>
        @endforeach
    </select>
    <select name="status" aria-label="Status akun" class="{{ $inp }}">
        <option value="">Semua status</option>
        <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Nonaktif</option>
    </select>
    <button class="px-6 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-secondary transition">Cari</button>
    @if ($filters['q'] || $filters['role'] || $filters['status'])
        <a href="{{ route('admin.pengguna.index') }}" class="px-4 py-2.5 rounded-xl font-semibold text-muted hover:bg-white text-center">Reset</a>
    @endif
</form>

<div class="mt-5 bg-white rounded-card border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm min-w-[760px]">
        <thead class="text-left text-xs uppercase tracking-wide text-muted border-b border-gray-100">
            <tr><th class="px-5 py-3">Pengguna</th><th class="px-3 py-3">Peran</th><th class="px-3 py-3 text-center">Karya</th><th class="px-3 py-3">Bergabung</th><th class="px-3 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr>
        </thead>
        <tbody>
        @forelse ($users as $u)
            <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ $u->foto_url }}" alt="" class="w-10 h-10 rounded-full object-cover">
                        <div class="min-w-0"><p class="font-bold truncate">{{ $u->name }}</p><p class="text-xs text-muted truncate">{{ $u->email }}</p></div>
                    </div>
                </td>
                <td class="px-3 py-3 capitalize">{{ $u->role }}</td>
                <td class="px-3 py-3 text-center">{{ $u->karyas_count }}</td>
                <td class="px-3 py-3 text-muted">{{ $u->created_at->translatedFormat('d M Y') }}</td>
                <td class="px-3 py-3">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $u->is_active ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </td>
                <td class="px-5 py-3">
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.pengguna.show', $u) }}" class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold hover:border-primary hover:text-primary">Detail</a>
                        @if ($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.pengguna.status', $u) }}"
                                  data-confirm="{{ $u->is_active ? 'Nonaktifkan akun '.$u->name.'? Pengguna tidak dapat masuk dan karyanya disembunyikan dari galeri.' : 'Aktifkan kembali akun '.$u->name.'?' }}"
                                  data-confirm-button="{{ $u->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan' }}" data-confirm-color="{{ $u->is_active ? '#E05252' : '#16A36A' }}">
                                @csrf @method('PATCH')
                                <button class="px-3 py-1.5 rounded-lg border text-xs font-semibold {{ $u->is_active ? 'border-danger/40 text-danger hover:bg-danger/10' : 'border-success/40 text-success hover:bg-success/10' }}">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-14 text-center text-muted">Tidak ada pengguna yang cocok.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
