
document.addEventListener("DOMContentLoaded", function () {

    // 1. FORMAT INPUT TANGGAL (DD/MM/YYYY)
    function formatTanggal(input) {
        if (!input) return;

        input.addEventListener("input", function () {
            let angka = this.value.replace(/\D/g, "").slice(0, 8);

            if (angka.length > 4) {
                angka = angka.slice(0, 2) + "/" +
                        angka.slice(2, 4) + "/" +
                        angka.slice(4);
            } else if (angka.length > 2) {
                angka = angka.slice(0, 2) + "/" +
                        angka.slice(2);
            }

            this.value = angka;
        });
    }

    // 2. VALIDASI DAN KONVERSI TANGGAL
    function validasiTanggal(input) {
        const nilai = input.value.trim();

        if (!/^\d{2}\/\d{2}\/\d{4}$/.test(nilai)) {
            alert("Format tanggal harus DD/MM/YYYY.");
            input.focus();
            return false;
        }

        const [hari, bulan, tahun] = nilai.split("/").map(Number);
        const tanggal = new Date(tahun, bulan - 1, hari);

        if (
            tanggal.getFullYear() !== tahun ||
            tanggal.getMonth() !== bulan - 1 ||
            tanggal.getDate() !== hari
        ) {
            alert("Tanggal yang dimasukkan tidak valid.");
            input.focus();
            return false;
        }

        input.value =
            tahun + "-" +
            String(bulan).padStart(2, "0") + "-" +
            String(hari).padStart(2, "0");

        return true;
    }

    // 3. FORM TAMBAH PENGINGAT
    const tanggalTambah = document.getElementById("tanggal");
    const formTambah = document.getElementById("formPengingat");

    if (tanggalTambah) {
        formatTanggal(tanggalTambah);
    }

    if (formTambah && tanggalTambah) {
        formTambah.addEventListener("submit", function (event) {
            if (!validasiTanggal(tanggalTambah)) {
                event.preventDefault();
            }
        });
    }

    // 4. MENGAMBIL ELEMEN MODAL EDIT
    const modal = document.getElementById("modalEditPengingat");
    const editId = document.getElementById("editId");
    const editJudul = document.getElementById("editJudul");
    const editTanggal = document.getElementById("editTanggal");
    const formEdit = document.getElementById("formEditPengingat");

    // 5. MEMBUKA MODAL DAN MENGISI DATA
    window.bukaModalEdit = function (id, judul, tanggal) {

        if (!modal || !editId || !editJudul || !editTanggal) {
            alert("Elemen modal edit tidak ditemukan.");
            console.error("Elemen modal edit pengingat tidak ditemukan.");
            return;
        }

        editId.value = id;
        editJudul.value = judul;
        editTanggal.value = tanggal;

        modal.classList.add("active");
        editJudul.focus();
    };

    // 6. MENGHUBUNGKAN TOMBOL EDIT KE MODAL
    const tombolEdit = document.querySelectorAll(".btn-edit");

    tombolEdit.forEach(function (tombol) {
        tombol.addEventListener("click", function () {

            const id = this.dataset.id;
            const judul = this.dataset.judul;
            const tanggal = this.dataset.tanggal;

            window.bukaModalEdit(id, judul, tanggal);
        });
    });

    // 7. FORMAT TANGGAL PADA FORM EDIT
    if (editTanggal) {
        formatTanggal(editTanggal);
    }

    // 8. FORM SIMPAN PERUBAHAN
    if (formEdit && editTanggal) {
        formEdit.addEventListener("submit", function (event) {
            if (!validasiTanggal(editTanggal)) {
                event.preventDefault();
            }
        });
    }

    // 9. MENUTUP MODAL
    window.tutupModalEdit = function () {
        if (modal) {
            modal.classList.remove("active");
        }
    };

    // Menutup modal ketika area luar diklik
    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                window.tutupModalEdit();
            }
        });
    }

    // Menutup modal menggunakan tombol Escape
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal) {
            window.tutupModalEdit();
        }
    });

});
