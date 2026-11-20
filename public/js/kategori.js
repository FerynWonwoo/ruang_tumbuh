document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formKategori");
    const pesan = document.getElementById("pesanClient");

    function tampilkan(teks) {
        pesan.textContent = teks;
        pesan.style.display = "block";
    }

    // Validasi sisi client
    if (form) {
        form.addEventListener("submit", function (e) {
            const nama = form.nama.value.trim();
            const deskripsi = form.deskripsi.value.trim();
            pesan.style.display = "none";

            if (nama === "") { e.preventDefault(); return tampilkan("Nama kategori wajib diisi."); }
            if (nama.length < 3) { e.preventDefault(); return tampilkan("Nama kategori minimal 3 karakter."); }
            if (nama.length > 100) { e.preventDefault(); return tampilkan("Nama kategori maksimal 100 karakter."); }
            if (deskripsi.length > 500) { e.preventDefault(); return tampilkan("Deskripsi maksimal 500 karakter."); }
        });
    }

    // Konfirmasi sebelum hapus: Batal = form tidak dikirim, data tetap aman
    document.querySelectorAll(".form-hapus").forEach(function (f) {
        f.addEventListener("submit", function (e) {
            const nama = f.getAttribute("data-nama");
            if (!confirm('Apakah Anda yakin ingin menghapus kategori "' + nama + '"?')) {
                e.preventDefault();
            }
        });
    });
});
