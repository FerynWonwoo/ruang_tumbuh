(function () {
    'use strict';

    // Cegah tampilan halaman cache lama saat tombol Back ditekan setelah logout
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        initMobileSidebar();
        initDismissAlerts();
        initFormValidation();
        initDetailModal();
        initDeleteModal();
    });

    /* =========================================
       1. DRAWER SIDEBAR RESPONSIF (MOBILE)
       ========================================= */
    function initMobileSidebar() {
        const sidebar = document.getElementById('appSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const openBtn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('sidebarCloseBtn');

        if (!sidebar || !overlay || !openBtn) return;

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            overlay.setAttribute('aria-hidden', 'false');
            openBtn.setAttribute('aria-expanded', 'true');
            document.body.classList.add('drawer-open');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            overlay.setAttribute('aria-hidden', 'true');
            openBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('drawer-open');
        }

        openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        window.addEventListener('resize', function () {
            if (window.innerWidth > 960) {
                closeSidebar();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
    }

    /* =========================================
       2. TUTUP NOTIFIKASI FLASH
       ========================================= */
    function initDismissAlerts() {
        document.querySelectorAll('.alert-close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const box = btn.closest('.alert-box');
                if (box) box.remove();
            });
        });
    }

    /* =========================================
       3. VALIDASI FORM & HITUNG KARAKTER
       ========================================= */
    function initFormValidation() {
        const form = document.getElementById('formCatatan');
        if (!form) return;

        // Aktifkan validasi kustom berbahasa Indonesia saat JS berjalan
        form.setAttribute('novalidate', 'novalidate');

        const judulInput = document.getElementById('judul');
        const kategoriSelect = document.getElementById('kategori');
        const isiTextarea = document.getElementById('isi');
        const judulCounter = document.getElementById('judulCounter');
        const isiCounter = document.getElementById('isiCounter');
        const clientErrorBox = document.getElementById('clientErrorBox');
        const clientErrorList = document.getElementById('clientErrorList');

        const JUDUL_MIN = 3;
        const JUDUL_MAX = 150;
        const ISI_MIN = 5;
        const ISI_MAX = 5000;
        const KATEGORI_VALID = ['Pribadi', 'Beasiswa', 'Bootcamp', 'Magang', 'Lomba', 'Karier'];

        function updateCounter(input, counterEl, max) {
            if (!input || !counterEl) return;
            const len = Array.from(input.value).length;
            counterEl.textContent = len.toLocaleString('id-ID') + ' / ' + max.toLocaleString('id-ID');
            counterEl.classList.toggle('near-limit', len >= Math.floor(max * 0.9));
        }

        function setFieldError(input, errorId, message) {
            const errEl = document.getElementById(errorId);
            if (input) {
                input.classList.toggle('is-invalid', Boolean(message));
                input.setAttribute('aria-invalid', message ? 'true' : 'false');
            }
            if (errEl) {
                errEl.textContent = message || '';
            }
        }

        function hasLetterOrNumber(str) {
            return /[A-Za-z0-9\u00C0-\u024F]/.test(str);
        }

        function validateJudul() {
            const val = judulInput.value.trim();
            const len = Array.from(val).length;
            if (val === '') return 'Judul catatan wajib diisi.';
            if (len < JUDUL_MIN) return 'Judul catatan minimal terdiri dari ' + JUDUL_MIN + ' karakter.';
            if (len > JUDUL_MAX) return 'Judul catatan maksimal ' + JUDUL_MAX + ' karakter.';
            if (!hasLetterOrNumber(val)) return 'Judul catatan harus mengandung huruf atau angka yang jelas.';
            return '';
        }

        function validateKategori() {
            const val = kategoriSelect.value.trim();
            if (val === '') return 'Kategori catatan wajib dipilih.';
            if (!KATEGORI_VALID.includes(val)) return 'Kategori yang dipilih tidak valid.';
            return '';
        }

        function validateIsi() {
            const val = isiTextarea.value.trim();
            const len = Array.from(val).length;
            if (val === '') return 'Isi catatan wajib diisi.';
            if (len < ISI_MIN) return 'Isi catatan minimal terdiri dari ' + ISI_MIN + ' karakter.';
            if (len > ISI_MAX) return 'Isi catatan maksimal 5.000 karakter.';
            if (!hasLetterOrNumber(val)) return 'Isi catatan harus mengandung teks yang jelas (tidak hanya simbol).';
            return '';
        }

        updateCounter(judulInput, judulCounter, JUDUL_MAX);
        updateCounter(isiTextarea, isiCounter, ISI_MAX);

        judulInput.addEventListener('input', function () {
            updateCounter(judulInput, judulCounter, JUDUL_MAX);
            if (judulInput.classList.contains('is-invalid')) {
                setFieldError(judulInput, 'error-judul', validateJudul());
            }
        });

        kategoriSelect.addEventListener('change', function () {
            if (kategoriSelect.classList.contains('is-invalid')) {
                setFieldError(kategoriSelect, 'error-kategori', validateKategori());
            }
        });

        isiTextarea.addEventListener('input', function () {
            updateCounter(isiTextarea, isiCounter, ISI_MAX);
            if (isiTextarea.classList.contains('is-invalid')) {
                setFieldError(isiTextarea, 'error-isi', validateIsi());
            }
        });

        form.addEventListener('submit', function (event) {
            const errJudul = validateJudul();
            const errKategori = validateKategori();
            const errIsi = validateIsi();

            setFieldError(judulInput, 'error-judul', errJudul);
            setFieldError(kategoriSelect, 'error-kategori', errKategori);
            setFieldError(isiTextarea, 'error-isi', errIsi);

            const errors = [errJudul, errKategori, errIsi].filter(Boolean);

            if (errors.length > 0) {
                event.preventDefault();

                if (clientErrorBox && clientErrorList) {
                    clientErrorList.textContent = '';
                    errors.forEach(function (msg) {
                        const li = document.createElement('li');
                        li.textContent = msg;
                        clientErrorList.appendChild(li);
                    });
                    clientErrorBox.hidden = false;
                }

                if (errJudul) {
                    judulInput.focus();
                } else if (errKategori) {
                    kategoriSelect.focus();
                } else if (errIsi) {
                    isiTextarea.focus();
                }
            } else if (clientErrorBox) {
                clientErrorBox.hidden = true;
            }
        });
    }

    /* =========================================
       4. MODAL DETAIL CATATAN (XSS-SAFE: textContent)
       ========================================= */
    function initDetailModal() {
        const modal = document.getElementById('modalDetail');
        if (!modal) return;

        const elKategori = document.getElementById('detailModalKategori');
        const elJudul = document.getElementById('detailModalJudul');
        const elTanggal = document.getElementById('detailModalTanggal');
        const elIsi = document.getElementById('detailModalIsi');

        document.querySelectorAll('.js-detail-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const kategori = btn.dataset.kategori || '';
                elKategori.textContent = kategori;
                elKategori.className = 'category-badge badge-' + kategori.toLowerCase().replace(/[^a-z0-9]/g, '');
                elJudul.textContent = btn.dataset.judul || '';
                elTanggal.textContent = btn.dataset.tanggal || '';
                elIsi.textContent = btn.dataset.isi || '';

                openModal(modal);
            });
        });

        bindModalClose(modal);
    }

    /* =========================================
       5. MODAL KONFIRMASI HAPUS (XSS-SAFE: textContent)
       ========================================= */
    function initDeleteModal() {
        const modal = document.getElementById('modalHapus');
        const elNama = document.getElementById('hapusNamaCatatan');
        const btnKonfirmasi = document.getElementById('btnKonfirmasiHapus');

        if (!modal || !elNama || !btnKonfirmasi) return;

        let pendingForm = null;

        document.querySelectorAll('.delete-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === 'true') {
                    return;
                }
                event.preventDefault();
                pendingForm = form;
                elNama.textContent = form.dataset.judul || 'catatan ini';
                openModal(modal);
                btnKonfirmasi.focus();
            });
        });

        btnKonfirmasi.addEventListener('click', function () {
            if (!pendingForm) return;
            pendingForm.dataset.confirmed = 'true';
            pendingForm.submit();
        });

        bindModalClose(modal, function () {
            pendingForm = null;
        });
    }

    /* =========================================
       HELPER MODAL UMUM
       ========================================= */
    function openModal(modal) {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal, onClose) {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (typeof onClose === 'function') {
            onClose();
        }
    }

    function bindModalClose(modal, onClose) {
        modal.querySelectorAll('[data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModal(modal, onClose);
            });
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal, onClose);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal(modal, onClose);
            }
        });
    }
})();
