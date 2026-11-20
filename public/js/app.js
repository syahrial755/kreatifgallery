/* KreatifGallery — perilaku UI bersama (menu, dropdown, flash, konfirmasi, galeri, salin tautan) */
(function () {
    'use strict';

    function icons() { if (window.lucide) window.lucide.createIcons(); }

    document.addEventListener('DOMContentLoaded', function () {
        icons();

        // Menu mobile
        var toggle = document.getElementById('menu-toggle');
        var menu = document.getElementById('mobile-menu');
        if (toggle && menu) {
            toggle.addEventListener('click', function () {
                var open = menu.classList.toggle('hidden') === false;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        // Dropdown generik
        document.querySelectorAll('[data-dropdown]').forEach(function (dd) {
            var btn = dd.querySelector('[data-dropdown-toggle]');
            var panel = dd.querySelector('[data-dropdown-menu]');
            if (!btn || !panel) return;
            btn.addEventListener('click', function (e) { e.stopPropagation(); panel.classList.toggle('hidden'); });
            document.addEventListener('click', function (e) { if (!dd.contains(e.target)) panel.classList.add('hidden'); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') panel.classList.add('hidden'); });
        });

        // Flash message -> toast SweetAlert2
        var f = window.__flash || {};
        [['success', f.success], ['error', f.error], ['info', f.info]].forEach(function (p) {
            if (p[1] && window.Swal) {
                window.Swal.fire({ toast: true, position: 'top-end', icon: p[0], title: p[1],
                    showConfirmButton: false, timer: 4000, timerProgressBar: true });
            }
        });

        // Konfirmasi sebelum submit: <form data-confirm="Pesan" data-confirm-button="Ya">
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (form.dataset.confirmed === '1') return;
                e.preventDefault();
                if (!window.Swal) { if (confirm(form.dataset.confirm)) { form.dataset.confirmed = '1'; form.submit(); } return; }
                window.Swal.fire({
                    title: form.dataset.confirm, icon: form.dataset.confirmIcon || 'warning',
                    showCancelButton: true, confirmButtonColor: form.dataset.confirmColor || '#635BDB',
                    confirmButtonText: form.dataset.confirmButton || 'Ya, lanjutkan', cancelButtonText: 'Batal'
                }).then(function (r) { if (r.isConfirmed) { form.dataset.confirmed = '1'; form.submit(); } });
            });
        });

        // Dialog alasan wajib: <form data-reason="Judul"> dengan <input type="hidden" name="catatan_moderasi">
        document.querySelectorAll('form[data-reason]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (form.dataset.reasonDone === '1') return;
                e.preventDefault();
                if (!window.Swal) return;
                window.Swal.fire({
                    title: form.dataset.reason, input: 'textarea', inputAttributes: { maxlength: 1000 },
                    inputPlaceholder: 'Tulis alasan (minimal 5 karakter)…', showCancelButton: true,
                    confirmButtonText: form.dataset.reasonButton || 'Kirim', cancelButtonText: 'Batal', confirmButtonColor: '#E05252',
                    inputValidator: function (v) { return (!v || v.trim().length < 5) ? 'Alasan wajib diisi (minimal 5 karakter).' : undefined; }
                }).then(function (r) {
                    if (r.isConfirmed) {
                        form.querySelector('[name=catatan_moderasi]').value = r.value.trim();
                        form.dataset.reasonDone = '1';
                        form.submit();
                    }
                });
            });
        });

        // Sidebar dashboard: ciutkan (desktop) / drawer (mobile)
        var sbToggle = document.getElementById('sidebar-toggle');
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        if (sbToggle && sidebar) {
            var closeDrawer = function () { sidebar.classList.add('-translate-x-full'); if (overlay) overlay.classList.add('hidden'); };
            sbToggle.addEventListener('click', function () {
                if (window.matchMedia('(min-width: 1024px)').matches) {
                    var c = document.documentElement.classList.toggle('sidebar-collapsed');
                    try { localStorage.setItem('kg-sidebar', c ? 'collapsed' : 'open'); } catch (e) {}
                } else {
                    var hidden = sidebar.classList.toggle('-translate-x-full');
                    if (overlay) overlay.classList.toggle('hidden', hidden);
                }
            });
            if (overlay) overlay.addEventListener('click', closeDrawer);
        }

        // Tombol catatan moderasi: <button data-note="..." data-note-title="...">
        document.querySelectorAll('[data-note]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!window.Swal) { alert(btn.dataset.note); return; }
                window.Swal.fire({ title: btn.dataset.noteTitle || 'Catatan', text: btn.dataset.note,
                    confirmButtonColor: '#635BDB', confirmButtonText: 'Tutup',
                    customClass: { htmlContainer: 'whitespace-pre-line text-left' } });
            });
        });

        // Galeri media pada detail karya
        var gallery = document.querySelector('[data-gallery]');
        if (gallery) {
            var stage = gallery.querySelector('[data-gallery-stage]');
            gallery.querySelectorAll('[data-gallery-thumb]').forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    gallery.querySelectorAll('[data-gallery-thumb]').forEach(function (t) { t.removeAttribute('aria-current'); });
                    thumb.setAttribute('aria-current', 'true');
                    stage.innerHTML = '';
                    var el;
                    if (thumb.dataset.type === 'video') {
                        el = document.createElement('video');
                        el.controls = true; el.playsInline = true; el.src = thumb.dataset.src;
                    } else {
                        el = document.createElement('img');
                        el.src = thumb.dataset.src; el.alt = thumb.dataset.alt || '';
                    }
                    el.className = 'w-full max-h-[78vh] object-contain mx-auto rounded-xl';
                    stage.appendChild(el);
                });
            });
        }

        // Salin tautan karya (use case: Membagikan tautan karya)
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.dataset.copy;
                var done = function () {
                    if (window.Swal) window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                        title: 'Tautan disalin', showConfirmButton: false, timer: 2000 });
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(done);
                } else {
                    var ta = document.createElement('textarea'); ta.value = url; document.body.appendChild(ta);
                    ta.select(); document.execCommand('copy'); document.body.removeChild(ta); done();
                }
            });
        });
    });
})();
