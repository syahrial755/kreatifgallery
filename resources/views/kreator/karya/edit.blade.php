@extends('layouts.dashboard')
@section('title', 'Edit karya')
@section('heading', $karya->status === 'ditolak' ? 'Revisi karya' : 'Edit karya')
@section('content')
    <div class="max-w-3xl space-y-5">
        @if ($karya->status === 'ditolak')
            <div class="rounded-xl border border-danger/30 bg-danger/10 px-5 py-4 text-sm" role="note">
                <p class="font-bold text-danger">Karya ditolak admin</p>
                <p class="mt-1 whitespace-pre-line">{{ $karya->catatan_moderasi ?: 'Tidak ada catatan.' }}</p>
                <p class="mt-2 text-muted">Perbaiki karya lalu kirim revisi; status otomatis kembali ke “menunggu”.</p>
            </div>
        @elseif ($karya->status === 'disetujui')
            <div class="rounded-xl border border-warning/40 bg-warning/10 px-5 py-4 text-sm" role="note">
                Karya ini sudah tayang. <strong>Mengubah, menambah, atau menghapus file media</strong> akan menariknya dari galeri dan mengembalikannya ke moderasi. Mengubah teks saja tidak memengaruhi status.
            </div>
        @endif

        <form method="POST" action="{{ route('kreator.karya.update', $karya) }}" enctype="multipart/form-data" novalidate class="space-y-6">
            @csrf @method('PUT')
            @include('kreator.karya._form', [
                'submitLabel' => $karya->status === 'ditolak' ? 'Kirim revisi' : 'Simpan perubahan',
                'existingCount' => $karya->medias->count(),
            ])
        </form>
    </div>
@endsection
@push('scripts') <script src="{{ asset('js/uploader.js') }}"></script> @endpush
