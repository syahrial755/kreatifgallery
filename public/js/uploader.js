/* KreatifGallery — uploader media: drag & drop, pratinjau gambar/video, validasi sisi klien, progres unggah.
 * Validasi sisi klien hanya kenyamanan; server tetap memvalidasi semuanya. Tanpa JS, form tetap bisa disubmit biasa. */
(function () {
    'use strict';

    var root = document.querySelector('[data-uploader]');
    if (!root) return;

    var form = root.closest('form');
    var input = root.querySelector('input[type=file]');
    var drop = root.querySelector('[data-drop]');
    var previews = root.querySelector('[data-previews]');
    var errBox = root.querySelector('[data-errors]');
    var counter = root.querySelector('[data-counter]');
    var progress = root.querySelector('[data-progress]');
    var bar = root.querySelector('[data-progress-bar]');
    var barText = root.querySelector('[data-progress-text]');
    var submitBtn = form.querySelector('[data-submit]');

    var MAX = parseInt(root.dataset.max, 10);
    var MAX_SIZE = parseInt(root.dataset.maxSize, 10);
    var TYPES = root.dataset.types.split(',');
    var EXISTING = parseInt(root.dataset.existing, 10) || 0;
    var REQUIRE = root.dataset.require === '1';

    var files = [];
    var urls = [];

    function fmt(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function removedExisting() { return form.querySelectorAll('[data-existing-remove]:checked').length; }
    function capacity() { return MAX - (EXISTING - removedExisting()) - files.length; }

    function sync() {
        var dt = new DataTransfer();
        files.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
    }

    function showErrors(list) {
        errBox.innerHTML = '';
        list.forEach(function (m) { var li = document.createElement('li'); li.textContent = m; errBox.appendChild(li); });
    }

    function updateCounter() {
        var total = EXISTING - removedExisting() + files.length;
        counter.textContent = total + ' dari ' + MAX + ' file' + (capacity() > 0 ? ' · sisa ' + capacity() : ' · batas tercapai');
    }

    function render() {
        urls.forEach(function (u) { URL.revokeObjectURL(u); });
        urls = [];
        previews.innerHTML = '';

        files.forEach(function (f, i) {
            var card = document.createElement('div');
            card.className = 'relative rounded-xl border border-gray-200 bg-white overflow-hidden';
            var url = URL.createObjectURL(f); urls.push(url);

            var media;
            if (f.type.indexOf('video/') === 0) {
                media = document.createElement('video'); media.src = url + '#t=0.1'; media.muted = true; media.preload = 'metadata'; media.controls = true;
            } else {
                media = document.createElement('img'); media.src = url; media.alt = f.name;
            }
            media.className = 'w-full aspect-square object-cover bg-gray-100';
            card.appendChild(media);

            var info = document.createElement('div');
            info.className = 'p-2.5';
            var name = document.createElement('p'); name.className = 'text-xs font-semibold truncate'; name.textContent = f.name;
            var size = document.createElement('p'); size.className = 'text-xs text-gray-500'; size.textContent = fmt(f.size);
            info.appendChild(name); info.appendChild(size); card.appendChild(info);

            var rm = document.createElement('button');
            rm.type = 'button'; rm.setAttribute('aria-label', 'Hapus ' + f.name);
            rm.className = 'absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-dark/75 text-white flex items-center justify-center hover:bg-danger';
            rm.innerHTML = '&times;';
            rm.addEventListener('click', function () { files.splice(i, 1); sync(); render(); showErrors([]); });
            card.appendChild(rm);

            previews.appendChild(card);
        });
        updateCounter();
    }

    function addFiles(list) {
        var errs = [];
        Array.prototype.forEach.call(list, function (f) {
            if (TYPES.indexOf(f.type) === -1) { errs.push('“' + f.name + '”: format harus JPG, PNG, WebP, GIF, MP4, atau WebM.'); return; }
            if (f.size > MAX_SIZE) { errs.push('“' + f.name + '”: ukuran ' + fmt(f.size) + ' melebihi batas 20 MB.'); return; }
            if (files.some(function (x) { return x.name === f.name && x.size === f.size && x.lastModified === f.lastModified; })) return;
            if (capacity() <= 0) { errs.push('Maksimal ' + MAX + ' file per karya. “' + f.name + '” tidak ditambahkan.'); return; }
            files.push(f);
        });
        sync(); render(); showErrors(errs);
    }

    drop.addEventListener('click', function () { input.click(); });
    drop.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
    input.addEventListener('click', function (e) { e.stopPropagation(); });
    input.addEventListener('change', function () { addFiles(Array.prototype.slice.call(input.files)); });

    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('border-primary', 'bg-primary/5'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('border-primary', 'bg-primary/5'); });
    });
    drop.addEventListener('drop', function (e) { addFiles(Array.prototype.slice.call(e.dataTransfer.files)); });

    form.querySelectorAll('[data-existing-remove]').forEach(function (cb) { cb.addEventListener('change', updateCounter); });

    function clearFieldErrors() {
        form.querySelectorAll('[data-error-for]').forEach(function (el) { el.textContent = ''; });
    }

    function applyServerErrors(json) {
        var errs = (json && json.errors) || {};
        var mediaMsgs = [];
        clearFieldErrors();
        Object.keys(errs).forEach(function (k) {
            if (k === 'media' || k.indexOf('media.') === 0) { errs[k].forEach(function (m) { if (mediaMsgs.indexOf(m) === -1) mediaMsgs.push(m); }); return; }
            var el = form.querySelector('[data-error-for="' + k + '"]');
            if (el) el.textContent = errs[k][0];
            else mediaMsgs.push(errs[k][0]);
        });
        if (!Object.keys(errs).length && json && json.message) mediaMsgs.push(json.message);
        showErrors(mediaMsgs);
    }

    function busy(on) {
        submitBtn.disabled = on;
        progress.classList.toggle('hidden', !on);
        if (!on) { bar.style.width = '0%'; barText.textContent = '0%'; }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var total = EXISTING - removedExisting() + files.length;
        if (REQUIRE && files.length === 0) { showErrors(['Pilih minimal 1 file media.']); drop.focus(); return; }
        if (total < 1) { showErrors(['Karya harus memiliki minimal 1 file media.']); return; }

        busy(true); showErrors([]); clearFieldErrors();

        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.onprogress = function (ev) {
            if (!ev.lengthComputable) return;
            var pct = Math.round(ev.loaded / ev.total * 100);
            bar.style.width = pct + '%'; barText.textContent = pct + '%';
        };
        xhr.onload = function () {
            var json = null; try { json = JSON.parse(xhr.responseText); } catch (err) {}
            if (xhr.status >= 200 && xhr.status < 300 && json && json.redirect) { window.location.href = json.redirect; return; }
            busy(false);
            if (xhr.status === 422) applyServerErrors(json);
            else if (xhr.status === 429) showErrors(['Terlalu banyak unggahan. Maksimal 6 unggahan per menit — coba lagi sebentar lagi.']);
            else if (xhr.status === 413) showErrors(['Total ukuran unggahan melebihi batas server (post_max_size). Kurangi jumlah/ukuran file.']);
            else if (xhr.status === 419) showErrors(['Sesi berakhir. Muat ulang halaman lalu coba lagi.']);
            else if (xhr.status === 403) showErrors(['Anda tidak memiliki izin untuk aksi ini.']);
            else showErrors(['Terjadi kesalahan pada server. Tidak ada data yang tersimpan; silakan coba lagi.']);
        };
        xhr.onerror = function () { busy(false); showErrors(['Koneksi terputus. Periksa jaringan Anda lalu coba lagi.']); };
        xhr.send(new FormData(form));
    });

    updateCounter();
})();
