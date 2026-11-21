(function () {
    'use strict';

    let formHapusAktif = null;

    window.openModal = function (modalId) {
        const el = document.getElementById(modalId);
        if (el) {
            el.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function (modalId) {
        const el = document.getElementById(modalId);
        if (el) {
            el.style.display = 'none';
            document.body.style.overflow = '';
        }
        if (modalId === 'modalHapusBookmark') {
            formHapusAktif = null;
        }
    };

    function showClientError(msg) {
        const box = document.getElementById('clientFormError');
        if (!box) return;
        if (!msg) {
            box.style.display = 'none';
            box.textContent = '';
        } else {
            box.style.display = 'flex';
            box.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><span>' + msg + '</span>';
        }
    }

    window.openForm = function (item) {
        const ubah = !!item;
        showClientError('');
        document.getElementById('formTitle').textContent = ubah ? 'Edit Bookmark' : 'Tambah Bookmark';
        document.getElementById('fAksi').value = ubah ? 'ubah' : 'tambah';
        document.getElementById('fId').value = ubah ? (item.id || '') : '';
        document.getElementById('fJudul').value = ubah ? (item.judul || '') : '';
        document.getElementById('fKategori').value = ubah ? (item.kategori || '') : '';
        document.getElementById('fPenyelenggara').value = ubah ? (item.penyelenggara || '') : '';
        document.getElementById('fDeadline').value = ubah ? (item.deadline_raw || '') : '';
        document.getElementById('fLink').value = ubah ? (item.link || '') : '';
        document.getElementById('fDeskripsi').value = ubah ? (item.deskripsi || '') : '';
        window.openModal('formModal');
    };

    window.openDetailModal = function (item) {
        document.getElementById('detailKategori').textContent = item.kategori || '-';
        document.getElementById('detailJudul').textContent = item.judul || '-';
        document.getElementById('detailPenyelenggara').textContent = item.penyelenggara || '-';
        document.getElementById('detailDeskripsi').textContent = item.deskripsi || 'Tidak ada deskripsi tambahan.';
        document.getElementById('detailDeadline').textContent = item.deadline || '-';

        const statusBadge = document.getElementById('detailStatusBadge');
        const statusText = document.getElementById('detailStatusText');
        if (statusBadge && statusText) {
            statusBadge.className = 'status-tag ' + (item.status_class || 'active');
            statusText.textContent = item.status || 'Aktif';
        }

        const linkEl = document.getElementById('detailLink');
        if (linkEl) {
            if (item.link && /^https?:\/\//i.test(item.link)) {
                linkEl.href = item.link;
                linkEl.style.display = 'inline-flex';
            } else {
                linkEl.removeAttribute('href');
                linkEl.style.display = 'none';
            }
        }

        window.openModal('detailModal');
    };

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Validasi sisi klien sebelum submit form
        const formBookmark = document.getElementById('formBookmark');
        if (formBookmark) {
            formBookmark.addEventListener('submit', (e) => {
                showClientError('');
                const judul = document.getElementById('fJudul').value.trim();
                const kategori = document.getElementById('fKategori').value.trim();
                const deadline = document.getElementById('fDeadline').value.trim();
                const link = document.getElementById('fLink').value.trim();

                if (judul.length < 3) {
                    e.preventDefault();
                    showClientError('Judul program minimal 3 karakter.');
                    return;
                }
                if (!kategori) {
                    e.preventDefault();
                    showClientError('Kategori program wajib diisi.');
                    return;
                }
                if (deadline && !/^\d{4}-\d{2}-\d{2}$/.test(deadline)) {
                    e.preventDefault();
                    showClientError('Format tanggal batas tenggat tidak valid.');
                    return;
                }
                if (link && !/^https?:\/\/[^\s]+$/i.test(link)) {
                    e.preventDefault();
                    showClientError('Link pendaftaran harus diawali http:// atau https://');
                }
            });
        }

        // 2. Modal Konfirmasi Hapus Custom (sesuai screenshot)
        const namaTarget = document.getElementById('hapusNamaBookmark');
        const btnKonfirmasiHapus = document.getElementById('btnKonfirmasiHapusBookmark');

        document.querySelectorAll('.form-hapus-bookmark').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                formHapusAktif = form;
                if (namaTarget) {
                    namaTarget.textContent = form.getAttribute('data-judul') || 'program ini';
                }
                window.openModal('modalHapusBookmark');
                if (btnKonfirmasiHapus) {
                    btnKonfirmasiHapus.focus();
                }
            });
        });

        if (btnKonfirmasiHapus) {
            btnKonfirmasiHapus.addEventListener('click', () => {
                if (formHapusAktif) {
                    const target = formHapusAktif;
                    formHapusAktif = null;
                    target.submit();
                }
            });
        }

        // 3. Live Search & Filter Kategori
        const searchInput = document.getElementById('searchBookmark');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const cards = document.querySelectorAll('.bookmark-card');

        let currentFilter = 'Semua';
        let currentKeyword = '';

        function filterCards() {
            cards.forEach((card) => {
                const kategori = card.getAttribute('data-kategori') || '';
                const judul = card.getAttribute('data-judul') || '';
                const penyelenggara = card.getAttribute('data-penyelenggara') || '';

                const matchCategory = (currentFilter === 'Semua' || kategori === currentFilter);
                const matchKeyword = (judul.includes(currentKeyword) || penyelenggara.includes(currentKeyword));

                card.style.display = (matchCategory && matchKeyword) ? 'flex' : 'none';
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                currentKeyword = e.target.value.toLowerCase().trim();
                filterCards();
            });
        }

        filterBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                filterBtns.forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.getAttribute('data-filter') || 'Semua';
                filterCards();
            });
        });

        // 4. Tutup modal saat klik area gelap atau tekan ESC
        ['detailModal', 'formModal', 'modalHapusBookmark'].forEach((id) => {
            const overlay = document.getElementById(id);
            if (overlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        window.closeModal(id);
                    }
                });
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.closeModal('detailModal');
                window.closeModal('formModal');
                window.closeModal('modalHapusBookmark');
            }
        });

        // 5. Buka ulang form jika validasi server gagal
        const oldEl = document.getElementById('oldBookmarkData');
        if (oldEl) {
            try {
                const o = JSON.parse(oldEl.textContent);
                window.openForm(o.aksi === 'ubah' ? {
                    id: o.id,
                    judul: o.judul,
                    kategori: o.kategori,
                    penyelenggara: o.penyelenggara,
                    deadline_raw: o.deadline,
                    link: o.link,
                    deskripsi: o.deskripsi
                } : null);

                if (o.aksi !== 'ubah') {
                    document.getElementById('fJudul').value = o.judul || '';
                    document.getElementById('fKategori').value = o.kategori || '';
                    document.getElementById('fPenyelenggara').value = o.penyelenggara || '';
                    document.getElementById('fDeadline').value = o.deadline || '';
                    document.getElementById('fLink').value = o.link || '';
                    document.getElementById('fDeskripsi').value = o.deskripsi || '';
                }
            } catch (err) {
                // abaikan
            }
        }
    });
})();