document.addEventListener("DOMContentLoaded", () => {
    const tanggal = document.getElementById("tanggal");

    tanggal.addEventListener("input", function () {
        let angka = this.value.replace(/\D/g, "").slice(0, 8);

        if (angka.length > 4) {
            angka = angka.slice(0, 2) + "/" +
                    angka.slice(2, 4) + "/" +
                    angka.slice(4);
        } else if (angka.length > 2) {
            angka = angka.slice(0, 2) + "/" + angka.slice(2);
        }

        this.value = angka;
    });

    document.getElementById("formPengingat")
        .addEventListener("submit", function (event) {
            const nilai = tanggal.value.trim();

            if (!/^\d{2}\/\d{2}\/\d{4}$/.test(nilai)) {
                event.preventDefault();
                alert("Format tanggal harus dd/mm/yyyy.");
                return;
            }

            const [hari, bulan, tahun] = nilai.split("/").map(Number);
            const date = new Date(tahun, bulan - 1, hari);

            if (
                date.getFullYear() !== tahun ||
                date.getMonth() !== bulan - 1 ||
                date.getDate() !== hari
            ) {
                event.preventDefault();
                alert("Tanggal yang kamu masukkan tidak valid.");
                return;
            }

            tanggal.value = `${tahun}-${String(bulan).padStart(2, "0")}-${String(hari).padStart(2, "0")}`;
        });
});