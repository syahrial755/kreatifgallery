@extends('layouts.app')
@section('title', 'Tentang — KreatifGallery')
@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 pt-12 sm:pt-16">
    <h1 class="text-4xl sm:text-6xl font-extrabold leading-[1.05]">Tentang KreatifGallery</h1>
    <p class="mt-6 text-xl text-muted leading-relaxed max-w-3xl">KreatifGallery adalah platform berbasis web untuk memamerkan karya kreatif dan membangun portofolio digital. Kreator mengunggah karya, admin memoderasi sebelum tayang, dan pengunjung dapat menjelajah, menyukai, serta berkomentar.</p>

    <div class="mt-12 grid sm:grid-cols-3 gap-4">
        @foreach ([[$totalKarya, 'Karya tayang'], [$totalKreator, 'Kreator aktif'], ['100%', 'Karya dimoderasi']] as [$angka, $label])
            <div class="bg-white rounded-2xl ring-1 ring-black/5 p-6">
                <p class="font-display text-4xl font-extrabold text-primary">{{ $angka }}</p>
                <p class="text-sm text-muted mt-1">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <h2 class="text-3xl font-extrabold mt-16">Bagaimana cara kerjanya</h2>
    <ol class="mt-6 space-y-3">
        <li class="flex gap-5 bg-white rounded-2xl ring-1 ring-black/5 p-5 sm:p-6"><span class="w-10 h-10 shrink-0 rounded-full bg-ink text-white font-display font-bold flex items-center justify-center">1</span><p class="pt-1.5"><strong>Unggah.</strong> Kreator mengunggah 1–10 file gambar atau video (maks. 20 MB per file).</p></li>
        <li class="flex gap-5 bg-white rounded-2xl ring-1 ring-black/5 p-5 sm:p-6"><span class="w-10 h-10 shrink-0 rounded-full bg-ink text-white font-display font-bold flex items-center justify-center">2</span><p class="pt-1.5"><strong>Moderasi.</strong> Admin menyetujui atau menolak dengan catatan. Karya yang ditolak dapat direvisi dan diajukan ulang.</p></li>
        <li class="flex gap-5 bg-white rounded-2xl ring-1 ring-black/5 p-5 sm:p-6"><span class="w-10 h-10 shrink-0 rounded-full bg-ink text-white font-display font-bold flex items-center justify-center">3</span><p class="pt-1.5"><strong>Tayang.</strong> Karya yang disetujui muncul di galeri publik dan dapat disukai serta dikomentari.</p></li>
    </ol>
</section>
@endsection
