@php
    $c = config('kreatifgallery.upload');
    $inputCls = 'w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none';
    $err = fn ($f) => $errors->first($f);
@endphp

<div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 space-y-5">
    <div>
        <label for="judul" class="block text-sm font-semibold mb-1">Judul karya <span class="text-danger">*</span></label>
        <input id="judul" name="judul" maxlength="{{ $c['max_title'] }}" required value="{{ old('judul', $karya->judul ?? '') }}" class="{{ $inputCls }}">
        <p class="text-xs text-muted mt-1">Maksimal {{ $c['max_title'] }} karakter.</p>
        @if ($err('judul')) <p class="text-sm mt-1 text-danger" data-error-for="judul">{{ $err('judul') }}</p> @else <p class="text-sm mt-1 text-danger" data-error-for="judul"></p> @endif
    </div>

    <div>
        <label for="deskripsi" class="block text-sm font-semibold mb-1">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" rows="5" maxlength="5000" class="{{ $inputCls }}">{{ old('deskripsi', $karya->deskripsi ?? '') }}</textarea>
        <p class="text-sm mt-1 text-danger" data-error-for="deskripsi">{{ $err('deskripsi') }}</p>
    </div>

    <div>
        <label for="kategori_id" class="block text-sm font-semibold mb-1">Kategori <span class="text-danger">*</span></label>
        <select id="kategori_id" name="kategori_id" required class="{{ $inputCls }}">
            <option value="">Pilih kategori…</option>
            @foreach ($kategoris as $k)
                <option value="{{ $k->id }}" @selected((string) old('kategori_id', $karya->kategori_id ?? '') === (string) $k->id)>{{ $k->nama_kategori }}</option>
            @endforeach
        </select>
        <p class="text-sm mt-1 text-danger" data-error-for="kategori_id">{{ $err('kategori_id') }}</p>
    </div>
</div>

<div class="bg-white rounded-card border border-gray-100 shadow-sm p-6"
     data-uploader data-max="{{ $c['max_files'] }}" data-max-size="{{ $c['max_size_kb'] * 1024 }}"
     data-types="{{ implode(',', $c['mime_types']) }}" data-existing="{{ $existingCount }}"
     data-require="{{ $existingCount === 0 ? '1' : '0' }}">
    <h2 class="font-bold">Media <span class="text-danger">*</span></h2>
    <p class="text-sm text-muted mt-1">1–{{ $c['max_files'] }} file · maks. 20 MB per file · JPG, PNG, WebP, GIF, MP4, WebM.</p>

    @if (! empty($karya) && $karya->medias->isNotEmpty())
        <div class="mt-4">
            <p class="text-sm font-semibold mb-2">File saat ini</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($karya->medias as $m)
                    <label class="relative block rounded-xl overflow-hidden border border-gray-200 bg-gray-50 cursor-pointer group">
                        <input type="checkbox" name="hapus_media[]" value="{{ $m->id }}" data-existing-remove class="peer sr-only">
                        <div class="aspect-square">
                            @if ($m->isVideo())
                                <video src="{{ $m->url }}#t=0.1" preload="metadata" muted class="w-full h-full object-cover"></video>
                            @else
                                <img src="{{ $m->thumb_url ?? $m->url }}" alt="" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <span class="absolute inset-0 hidden peer-checked:flex items-center justify-center bg-danger/70 text-white text-xs font-bold text-center p-2">Akan dihapus</span>
                        <span class="absolute bottom-1.5 right-1.5 bg-white/90 rounded-lg px-2 py-1 text-xs font-semibold inline-flex items-center gap-1 group-hover:bg-white"><i data-lucide="trash-2" class="w-3 h-3"></i> Hapus</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    <div data-drop tabindex="0" role="button" aria-label="Pilih atau seret file ke sini"
         class="mt-4 rounded-2xl border-2 border-dashed border-gray-300 hover:border-primary bg-canvas p-8 text-center cursor-pointer transition">
        <i data-lucide="cloud-upload" class="w-9 h-9 text-primary mx-auto"></i>
        <p class="font-semibold mt-2">Seret &amp; lepas file di sini</p>
        <p class="text-sm text-muted">atau <span class="text-primary font-semibold">pilih dari perangkat</span></p>
        <input type="file" name="media[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" class="sr-only" tabindex="-1">
    </div>

    <ul data-errors class="mt-3 space-y-1 text-sm text-danger" role="alert">
        @foreach ($errors->get('media') as $m) <li>{{ $m }}</li> @endforeach
        @foreach ($errors->get('media.*') as $msgs) @foreach ($msgs as $m) <li>{{ $m }}</li> @endforeach @endforeach
    </ul>

    <div data-previews class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3"></div>
    <p data-counter class="text-xs text-muted mt-3"></p>

    <div data-progress class="hidden mt-4">
        <div class="flex justify-between text-xs font-semibold mb-1"><span>Mengunggah…</span><span data-progress-text>0%</span></div>
        <div class="h-2 rounded-full bg-gray-100 overflow-hidden"><div data-progress-bar class="h-full bg-primary w-0 transition-all"></div></div>
    </div>
</div>

<div class="flex items-center gap-3">
    <button type="submit" data-submit class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-secondary transition disabled:opacity-60 disabled:cursor-not-allowed">
        <i data-lucide="send" class="w-4 h-4"></i> {{ $submitLabel }}</button>
    <a href="{{ route('kreator.karya.index') }}" class="px-5 py-3 rounded-xl font-semibold text-muted hover:bg-white">Batal</a>
</div>
