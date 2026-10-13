// Simpan di: js/rating.js
// Dipakai oleh rating.php (user) dan adminrating.php (admin).
// Tiap bagian hanya aktif kalau elemennya ada di halaman.
(function () {
    'use strict';

    // =========================
    // USER: modal edit & filter bintang
    // =========================
    var modal = document.getElementById('modalEdit');

    function bukaModal(d) {
        if (!modal) return;

        document.getElementById('editId').value = d.id || '';
        document.getElementById('editKomentar').value = d.komentar || '';

        var pilih = document.getElementById('editBootcamp');
        pilih.value = d.bootcamp || '';
        if (pilih.selectedIndex === -1) pilih.value = ''; // kategori lama tidak ada di daftar

        modal.querySelectorAll('input[name="rating"]').forEach(function (r) {
            r.checked = r.value === String(d.rating);
        });

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        pilih.focus();
    }

    function tutupModal() {
        if (!modal) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (location.search) history.replaceState(null, '', 'rating.php');
    }

    function terapkanFilter(nilai) {
        var tampil = 0;

        document.querySelectorAll('.review-card').forEach(function (kartu) {
            var cocok = nilai === 'all' || kartu.dataset.rating === nilai;
            kartu.style.display = cocok ? '' : 'none';
            if (cocok) tampil++;
        });

        var jumlah = document.getElementById('jumlahUlasan');
        if (jumlah) jumlah.textContent = tampil;

        var kosong = document.getElementById('emptyFilter');
        if (kosong) kosong.hidden = tampil !== 0;
    }

    // =========================
    // ADMIN: pilih semua & aksi massal
    // =========================
    var semua  = document.getElementById('pilihSemua');
    var info   = document.getElementById('jumlahDipilih');
    var items  = Array.prototype.slice.call(document.querySelectorAll('[data-item]'));
    var tombol = Array.prototype.slice.call(document.querySelectorAll('[data-bulk]'));

    function segarkan() {
        var n = items.filter(function (c) { return c.checked; }).length;

        if (info) info.textContent = n;
        tombol.forEach(function (b) { b.disabled = n === 0; });

        if (semua) {
            semua.checked = n > 0 && n === items.length;
            semua.indeterminate = n > 0 && n < items.length;
        }
    }

    if (items.length) {
        if (semua) {
            semua.addEventListener('change', function () {
                items.forEach(function (c) { c.checked = semua.checked; });
                segarkan();
            });
        }
        items.forEach(function (c) { c.addEventListener('change', segarkan); });
        segarkan();
    }

    // =========================
    // BERSAMA: klik (delegasi)
    // =========================
    document.addEventListener('click', function (e) {

        // Konfirmasi hapus / tarik
        var konfirmasi = e.target.closest('[data-confirm]');
        if (konfirmasi && !confirm(konfirmasi.dataset.confirm)) {
            e.preventDefault();
            return;
        }

        // Buka modal edit
        var edit = e.target.closest('[data-edit]');
        if (edit) {
            bukaModal(edit.dataset);
            return;
        }

        // Tutup modal (tombol Batal, X, atau klik latar)
        if (modal && (e.target.closest('[data-tutup]') || e.target === modal)) {
            tutupModal();
            return;
        }

        // Baca selengkapnya
        var more = e.target.closest('.read-more');
        if (more) {
            var teks = more.previousElementSibling;
            var terbuka = teks.classList.toggle('open');
            more.textContent = terbuka ? 'Tutup' : 'Baca selengkapnya';
            return;
        }

        // Filter bintang (hanya tombol filter user, bukan link tab admin)
        var chip = e.target.closest('button.chip[data-filter]');
        if (chip) {
            document.querySelectorAll('button.chip[data-filter]').forEach(function (c) {
                c.classList.remove('active');
            });
            chip.classList.add('active');
            terapkanFilter(chip.dataset.filter);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('open')) tutupModal();
    });

    // Tombol "Baca selengkapnya" hanya muncul jika teks terpotong
    document.querySelectorAll('.review-text').forEach(function (p) {
        var btn = p.nextElementSibling;
        if (btn && btn.classList.contains('read-more') && p.scrollHeight > p.clientHeight + 1) {
            btn.hidden = false;
        }
    });
})();