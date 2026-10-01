const STORAGE_KEY = "ruangTumbuhPengingat";
let pengingat = [];

function simpanData() {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify(pengingat)
    );
}

function ambilData() {
    try {
        pengingat = JSON.parse(
            localStorage.getItem(STORAGE_KEY)
        ) || [];
    } catch {
        pengingat = [];
    }
}

function formatTanggal(tanggal) {
    const [tahun, bulan, hari] = tanggal.split("-");

    return `${hari}/${bulan}/${tahun}`;
}

function keFormatData(tanggal) {
    const [hari, bulan, tahun] =
        tanggal.split("/");

    return `${tahun}-${bulan}-${hari}`;
}

function validasiTanggal(tanggal) {
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(tanggal)) {
        return false;
    }

    const [hari, bulan, tahun] =
        tanggal.split("/").map(Number);

    const date =
        new Date(tahun, bulan - 1, hari);

    return date.getFullYear() === tahun &&
        date.getMonth() === bulan - 1 &&
        date.getDate() === hari;
}

function tampilkanPengingat() {

    const daftar =
        document.getElementById("daftarPengingat");

    document.getElementById("jumlah")
        .textContent = pengingat.length;

    if (!pengingat.length) {

        daftar.innerHTML = `
            <div class="empty-state">
                <i class="fa-regular fa-calendar-check"></i>
                <h3>Belum ada pengingat</h3>
                <p>Tambahkan aktivitas dan tanggal tenggatmu.</p>
            </div>
        `;

        return;
    }

    daftar.innerHTML = pengingat.map((item, index) => `

        <div class="reminder-item
            ${item.selesai ? "completed" : ""}">

            <div class="reminder-info">

                <div class="reminder-icon">
                    <i class="fa-regular fa-clock"></i>
                </div>

                <div class="reminder-details">

                    <div class="reminder-name">
                        ${item.judul}
                    </div>

                    <div class="reminder-date">
                        <i class="fa-regular fa-calendar"></i>
                        ${formatTanggal(item.tanggal)}
                    </div>

                </div>

            </div>

            <div class="reminder-actions">

                <button
                    class="btn-selesai"
                    onclick="selesai(${index})">

                    <i class="fa-solid fa-check"></i>
                    ${item.selesai ? "Batalkan" : "Selesai"}

                </button>

                <button
                    class="btn-hapus"
                    onclick="hapus(${index})">

                    <i class="fa-solid fa-trash"></i>
                    Hapus

                </button>

            </div>

        </div>

    `).join("");
}

function tambahPengingat() {

    const judul =
        document.getElementById("judul");

    const tanggal =
        document.getElementById("tanggal");

    const nilaiTanggal =
        tanggal.value.trim();

    if (!judul.value.trim() || !nilaiTanggal) {

        alert(
            "Nama aktivitas dan tanggal harus diisi."
        );

        return;
    }

    if (!validasiTanggal(nilaiTanggal)) {

        alert(
            "Format tanggal harus dd/mm/yyyy."
        );

        return;
    }

    pengingat.push({
        judul: judul.value.trim(),
        tanggal: keFormatData(nilaiTanggal),
        selesai: false
    });

    simpanData();
    tampilkanPengingat();

    judul.value = "";
    tanggal.value = "";

    judul.focus();
}

function selesai(index) {

    pengingat[index].selesai =
        !pengingat[index].selesai;

    simpanData();
    tampilkanPengingat();
}

function hapus(index) {

    if (!confirm(
        "Yakin ingin menghapus pengingat ini?"
    )) {
        return;
    }

    pengingat.splice(index, 1);

    simpanData();
    tampilkanPengingat();
}


document.addEventListener(
    "DOMContentLoaded",
    () => {

        ambilData();
        tampilkanPengingat();

        const tanggal =
            document.getElementById("tanggal");

        tanggal.addEventListener(
            "input",
            function () {

                let angka =
                    this.value
                        .replace(/\D/g, "")
                        .slice(0, 8);

                if (angka.length > 4) {

                    angka =
                        angka.slice(0, 2) + "/" +
                        angka.slice(2, 4) + "/" +
                        angka.slice(4);

                } else if (angka.length > 2) {

                    angka =
                        angka.slice(0, 2) + "/" +
                        angka.slice(2);
                }

                this.value = angka;
            }
        );


        document
            .getElementById("formPengingat")
            .addEventListener(
                "submit",
                event => {

                    event.preventDefault();

                    tambahPengingat();
                }
            );
    }
);