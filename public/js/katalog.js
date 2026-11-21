/**
 * Katalog RuangTumbuh (halaman user & admin).
 *
 * State halaman disimpan di satu objek `state` dan disinkronkan ke URL
 * (?q=...&urut=...), sehingga hasil pencarian/urutan bisa di-refresh atau dibagikan.
 * Kategori pada halaman user dipilih lewat link (?kategori=...), difilter di server.
 */
(function () {
    'use strict';

    var URUT_VALID = ['mulai-tercepat', 'mulai-terjauh', 'deadline-terdekat', 'nama'];

    var grid = document.getElementById('bootcampGrid');
    var inputCari = document.getElementById('searchBootcamp');
    var selectUrut = document.getElementById('sortBootcamp');
    var selectKategori = document.getElementById('filterKategori'); // hanya admin
    var selectMode = document.getElementById('filterMode');         // hanya admin
    var selectJenis = document.getElementById('filterJenis');       // hanya admin
    var kosong = document.getElementById('katalogEmpty');
    var jumlahHasil = document.getElementById('jumlahHasil');
    var jumlahTeks = document.getElementById('jumlahProgramText');
    var modalDetail = document.getElementById('modalDetail');
    var modalForm = document.getElementById('modalForm');
    var isAdmin = !!modalForm;

    /* ---------- Data program (dari PHP) ---------- */
    var program = [];
    var dataEl = document.getElementById('programData');
    if (dataEl) {
        try { program = JSON.parse(dataEl.textContent) || []; } catch (e) { program = []; }
    }
    var programById = {};
    program.forEach(function (p) { programById[String(p.id)] = p; });

    /* ---------- State ---------- */
    var params = new URLSearchParams(window.location.search);
    var state = {
        q: (params.get('q') || '').trim(),
        urut: URUT_VALID.indexOf(params.get('urut')) >= 0 ? params.get('urut') : 'mulai-tercepat',
        kategori: isAdmin ? (params.get('kategori') || '') : '',
        mode: isAdmin ? (params.get('mode') || '') : '',
        jenis: isAdmin ? (params.get('jenis') || '') : ''
    };

    var hariIni = grid ? parseInt(grid.dataset.today, 10) || 0 : 0;

    /* ---------- Urutan (sama dengan bootcamp_urut di PHP) ---------- */
    function kunciUrut(card) {
        var mulai = parseInt(card.dataset.mulai, 10) || 0;
        var batas = parseInt(card.dataset.deadline, 10) || 0;

        if (state.urut === 'nama') return [0, 0];

        if (state.urut === 'deadline-terdekat') {
            if (!batas) return [1, 0];
            return batas >= hariIni ? [0, batas] : [2, -batas];
        }
        if (state.urut === 'mulai-terjauh') {
            if (!mulai) return [1, 0];
            return mulai >= hariIni ? [0, -mulai] : [2, -mulai];
        }
        // mulai-tercepat
        if (!mulai) return [1, 0];
        return mulai >= hariIni ? [0, mulai] : [2, -mulai];
    }

    function bandingkan(a, b) {
        var ka = kunciUrut(a), kb = kunciUrut(b);
        if (ka[0] !== kb[0]) return ka[0] - kb[0];
        if (ka[1] !== kb[1]) return ka[1] - kb[1];
        return (a.dataset.title || '').localeCompare(b.dataset.title || '', 'id');
    }

    /* ---------- Render: filter + urutkan kartu sesuai state ---------- */
    function render() {
        if (!grid) return;

        var kata = state.q.toLowerCase().split(/\s+/).filter(Boolean);
        var kartu = Array.prototype.slice.call(grid.querySelectorAll('.bootcamp-card'));
        var tampil = 0;

        kartu.forEach(function (card) {
            var teks = card.dataset.search || '';
            var cocok = kata.every(function (k) { return teks.indexOf(k) !== -1; });

            if (cocok && state.kategori) {
                cocok = (card.dataset.kategori || '').toLowerCase() === state.kategori.toLowerCase();
            }
            if (cocok && state.jenis) {
                cocok = (card.dataset.jenis || '') === state.jenis;
            }
            if (cocok && state.mode) {
                cocok = (card.dataset.mode || '') === state.mode;
            }
            card.hidden = !cocok;
            if (cocok) tampil++;
        });

        kartu.sort(bandingkan).forEach(function (card) { grid.appendChild(card); });

        if (jumlahHasil) jumlahHasil.textContent = isAdmin ? String(tampil) : tampil + ' program';
        if (jumlahTeks) jumlahTeks.textContent = tampil + (isAdmin ? ' program' : ' program tersedia');

        if (kosong) {
            // Jika halaman memang kosong dari server, pesan bawaan server dipertahankan.
            var adaDariServer = kartu.length > 0;
            kosong.hidden = tampil !== 0;
            if (adaDariServer && tampil === 0) {
                var j = document.getElementById('emptyJudul');
                var t = document.getElementById('emptyTeks');
                if (j) j.textContent = 'Program tidak ditemukan';
                if (t) t.textContent = state.q
                    ? 'Tidak ada program yang cocok dengan “' + state.q + '”. Coba kata kunci lain.'
                    : 'Tidak ada program yang cocok dengan filter ini.';
            }
        }

        sinkronUrl();
    }

    function sinkronUrl() {
        var p = new URLSearchParams(window.location.search);
        function atur(kunci, nilai, bawaan) {
            if (nilai && nilai !== bawaan) p.set(kunci, nilai); else p.delete(kunci);
        }
        atur('q', state.q, '');
        atur('urut', state.urut, 'mulai-tercepat');
        if (isAdmin) {
            atur('kategori', state.kategori, '');
            atur('mode', state.mode, '');
            atur('jenis', state.jenis, '');
        }
        var qs = p.toString();
        history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));

        // Chip jenis (halaman user) membawa serta bidang, pencarian & urutan yang aktif
        var nav = document.getElementById('kategoriChips');
        if (nav) {
            nav.querySelectorAll('.chip').forEach(function (chip) {
                var cp = new URLSearchParams();
                if (nav.dataset.kategori) cp.set('kategori', nav.dataset.kategori);
                if (chip.dataset.jenis) cp.set('jenis', chip.dataset.jenis);
                if (state.q) cp.set('q', state.q);
                if (state.urut !== 'mulai-tercepat') cp.set('urut', state.urut);
                var s = cp.toString();
                chip.setAttribute('href', 'katalog.php' + (s ? '?' + s : ''));
            });
        }
    }

    /* ---------- Event: pencarian & urutan ---------- */
    if (inputCari) {
        inputCari.value = state.q;
        inputCari.addEventListener('input', function () {
            state.q = inputCari.value.trim();
            render();
        });
        inputCari.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') e.preventDefault();
        });
    }
    if (selectUrut) {
        selectUrut.value = state.urut;
        selectUrut.addEventListener('change', function () {
            state.urut = selectUrut.value;
            render();
        });
    }
    if (selectKategori) {
        selectKategori.value = state.kategori;
        selectKategori.addEventListener('change', function () {
            state.kategori = selectKategori.value;
            render();
        });
    }
    if (selectJenis) {
        selectJenis.value = state.jenis;
        selectJenis.addEventListener('change', function () {
            state.jenis = selectJenis.value;
            render();
        });
    }
    if (selectMode) {
        selectMode.value = state.mode;
        selectMode.addEventListener('change', function () {
            state.mode = selectMode.value;
            render();
        });
    }

    /* ---------- Modal ---------- */
    function pindahKeBody(el) {
        if (el && el.parentElement !== document.body) document.body.appendChild(el);
    }
    pindahKeBody(modalDetail);
    pindahKeBody(modalForm);

    function setTeks(id, nilai) {
        var el = document.getElementById(id);
        if (el) el.textContent = nilai;
    }

    function bukaDetail(id) {
        var p = programById[String(id)];
        if (!p || !modalDetail) return;

        setTeks('detailKategori', (p.jenis ? p.jenis + ' · ' : '') + (p.kategori || 'Program'));
        setTeks('detailJudul', p.judul);
        setTeks('detailPenyelenggara', p.penyelenggara);
        setTeks('detailMode', p.mode || '-');
        setTeks('detailDurasi', p.durasi || '-');
        setTeks('detailMulai', p.mulai_tampil || 'Belum diisi');
        setTeks('detailDeadline', p.deadline_tampil || 'Belum diisi');
        setTeks('detailHarga', p.harga_tampil);
        setTeks('detailDeskripsi', p.deskripsi || '-');

        var poster = document.getElementById('detailPoster');
        var ikon = document.getElementById('detailIcon');
        if (poster && ikon) {
            var img = poster.querySelector('img');
            if (p.gambar_src) {
                img.src = p.gambar_src;
                img.alt = 'Poster ' + p.judul;
                poster.hidden = false;
                ikon.hidden = true;
            } else {
                img.removeAttribute('src');
                poster.hidden = true;
                ikon.hidden = false;
            }
        }

        var link = document.getElementById('detailLink');
        if (link) {
            if (/^https?:\/\//i.test(p.link || '')) {
                link.href = p.link;
                link.hidden = false;
            } else {
                link.removeAttribute('href');
                link.hidden = true;
            }
        }

        modalDetail.classList.add('active');
        modalDetail.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function tutupDetail() {
        if (!modalDetail) return;
        modalDetail.classList.remove('active');
        modalDetail.setAttribute('aria-hidden', 'true');
        if (!modalForm || !modalForm.classList.contains('active')) document.body.style.overflow = '';
    }

    /* ---------- Form admin (tambah / edit + poster) ---------- */
    var form = document.getElementById('formBootcamp');
    var fileInput = document.getElementById('gambarFile');
    var previewBox = document.getElementById('uploadPreview');
    var previewImg = document.getElementById('uploadPreviewImg');
    var hapusWrap = document.getElementById('hapusGambarWrap');
    var hapusCek = document.getElementById('hapusGambar');
    var pesanClient = document.getElementById('pesanClient');
    var gambarSaatIni = '';
    var urlPreviewLokal = null;

    function tampilPesan(teks) {
        if (!pesanClient) return;
        pesanClient.textContent = teks || '';
        pesanClient.hidden = !teks;
    }

    function setPreview(src) {
        if (!previewBox || !previewImg) return;
        if (src) {
            previewImg.src = src;
            previewBox.hidden = false;
        } else {
            previewImg.removeAttribute('src');
            previewBox.hidden = true;
        }
    }

    function bersihkanPreviewLokal() {
        if (urlPreviewLokal) {
            URL.revokeObjectURL(urlPreviewLokal);
            urlPreviewLokal = null;
        }
    }

    function isiForm(aksi, data) {
        if (!modalForm || !form) return;
        form.reset();
        tampilPesan('');
        bersihkanPreviewLokal();

        document.getElementById('aksi').value = aksi;
        document.getElementById('id').value = aksi === 'edit' ? (data.id || '') : '';

        ['judul', 'penyelenggara', 'kategori', 'jenis', 'mode', 'durasi',
         'tanggal_mulai', 'deadline', 'harga', 'link', 'deskripsi'].forEach(function (k) {
            var el = document.getElementById(k);
            if (!el) return;
            var nilai = data[k] || '';
            if (el.tagName === 'SELECT' && nilai && !Array.prototype.some.call(el.options, function (o) { return o.value === nilai; })) {
                var opt = document.createElement('option');
                opt.value = nilai;
                opt.textContent = nilai;
                el.appendChild(opt);
            }
            el.value = nilai;
        });

        gambarSaatIni = aksi === 'edit' ? (data.gambar_src || '') : '';
        setPreview(gambarSaatIni);
        if (hapusWrap) hapusWrap.hidden = !gambarSaatIni;

        document.getElementById('judulModal').textContent = aksi === 'edit' ? 'Edit Program' : 'Tambah Program';
        document.getElementById('btnSubmit').innerHTML = aksi === 'edit'
            ? '<i class="fa-solid fa-pen"></i> Update'
            : '<i class="fa-solid fa-floppy-disk"></i> Simpan';

        modalForm.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function tutupForm() {
        if (!modalForm) return;
        modalForm.classList.remove('active');
        bersihkanPreviewLokal();
        document.body.style.overflow = '';
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            tampilPesan('');
            bersihkanPreviewLokal();
            var file = fileInput.files && fileInput.files[0];

            if (!file) {
                setPreview(hapusCek && hapusCek.checked ? '' : gambarSaatIni);
                return;
            }
            if (['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) === -1) {
                fileInput.value = '';
                tampilPesan('Format poster harus JPG, PNG, atau WebP.');
                setPreview(gambarSaatIni);
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                fileInput.value = '';
                tampilPesan('Ukuran poster maksimal 2 MB.');
                setPreview(gambarSaatIni);
                return;
            }
            urlPreviewLokal = URL.createObjectURL(file);
            setPreview(urlPreviewLokal);
            if (hapusCek) hapusCek.checked = false;
        });
    }

    if (hapusCek) {
        hapusCek.addEventListener('change', function () {
            if (hapusCek.checked) {
                if (fileInput) fileInput.value = '';
                bersihkanPreviewLokal();
                setPreview('');
            } else {
                setPreview(gambarSaatIni);
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            tampilPesan('');
            var mulai = document.getElementById('tanggal_mulai').value;
            var batas = document.getElementById('deadline').value;
            if (mulai && batas && batas > mulai) {
                e.preventDefault();
                tampilPesan('Deadline pendaftaran tidak boleh setelah tanggal mulai program.');
            }
        });
    }

    // Konfirmasi hapus: Batal = form tidak dikirim
    document.querySelectorAll('.form-hapus').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!confirm('Yakin ingin menghapus "' + (f.dataset.nama || 'program ini') + '"?')) e.preventDefault();
        });
    });

    /* ---------- Klik tombol aksi (event delegation) ---------- */
    document.addEventListener('click', function (e) {
        var tombol = e.target.closest('[data-aksi]');
        if (tombol) {
            var aksi = tombol.dataset.aksi;
            if (aksi === 'detail') bukaDetail(tombol.dataset.id);
            else if (aksi === 'tutup-detail') tutupDetail();
            else if (aksi === 'tambah') isiForm('tambah', {});
            else if (aksi === 'edit') {
                var p = programById[String(tombol.dataset.id)];
                if (p) isiForm('edit', p);
            }
            else if (aksi === 'tutup-form') tutupForm();
            return;
        }
        // Klik area gelap di luar kotak modal
        if (e.target === modalDetail) tutupDetail();
        if (e.target === modalForm) tutupForm();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            tutupDetail();
            tutupForm();
        }
    });

    /* ---------- Bookmark (halaman user) ---------- */
    document.querySelectorAll('.btn-bookmark').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.disabled) return;
            btn.disabled = true;

            var fd = new FormData();
            ['id', 'judul', 'kategori', 'penyelenggara', 'deadline', 'deskripsi', 'link'].forEach(function (k) {
                fd.append(k, btn.dataset[k] || '');
            });

            fetch('proses_bookmark.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (hasil) {
                    if (!hasil.ok) throw new Error(hasil.pesan || 'Gagal menyimpan bookmark');
                    btn.classList.toggle('active', !!hasil.saved);
                    btn.setAttribute('aria-pressed', hasil.saved ? 'true' : 'false');
                    btn.setAttribute('aria-label', hasil.saved ? 'Hapus bookmark' : 'Simpan bookmark');
                    var ikon = btn.querySelector('i');
                    if (ikon) ikon.className = (hasil.saved ? 'fa-solid' : 'fa-regular') + ' fa-bookmark';
                })
                .catch(function (err) { alert(err.message || 'Gagal memproses bookmark'); })
                .then(function () { btn.disabled = false; });
        });
    });

    /* ---------- Mulai ---------- */
    render();

    // Setelah validasi server gagal, buka lagi form dengan isian sebelumnya
    var oldEl = document.getElementById('oldData');
    if (oldEl && isAdmin) {
        try {
            var old = JSON.parse(oldEl.textContent);
            var asli = programById[String((old.data || {}).id)] || {};
            var data = Object.assign({}, old.data);
            if (old.aksi === 'edit') data.gambar_src = asli.gambar_src || '';
            isiForm(old.aksi === 'edit' ? 'edit' : 'tambah', data);
        } catch (err) { /* abaikan */ }
    }
})();
