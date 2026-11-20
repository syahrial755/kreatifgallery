@extends('layouts.dashboard')
@section('title', 'Upload Work')
@section('heading', 'Upload Work')
@section('content')
    @if ($kategoris->isEmpty())
        <div class="bg-white rounded-card border border-gray-100 p-10 text-center">
            <p class="font-bold">Belum ada kategori</p>
            <p class="text-sm text-muted mt-1">Admin perlu membuat kategori sebelum karya dapat diunggah.</p>
        </div>
    @else
        <form method="POST" action="{{ route('kreator.karya.store') }}" enctype="multipart/form-data" novalidate class="max-w-3xl space-y-6">
            @csrf
            @include('kreator.karya._form', ['karya' => null, 'submitLabel' => 'Unggah karya', 'existingCount' => 0])
        </form>
    @endif
@endsection
@push('scripts') <script src="{{ asset('js/uploader.js') }}"></script> @endpush
