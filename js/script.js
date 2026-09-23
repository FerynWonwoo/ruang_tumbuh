const password = document.getElementById("password");
const lihatPassword = document.getElementById("lihat-password");
const garisMata = document.getElementById("garis-mata");

if (lihatPassword && password) {
    lihatPassword.addEventListener("click", function () {
        const tampilkan = password.type === "password";
        password.type = tampilkan ? "text" : "password";
        garisMata.toggleAttribute("hidden", !tampilkan);
        lihatPassword.setAttribute("aria-pressed", String(tampilkan));
        lihatPassword.setAttribute("aria-label",
            tampilkan ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
    });
}

const video = document.querySelector(".background-video");
const tombolVideo = document.querySelector(".video-control");
const gerakanMinimal = window.matchMedia("(prefers-reduced-motion: reduce)");

const dialogInfo = document.getElementById("info-akun");

function tampilkanInfo(judul, isi) {
    document.getElementById("judul-info").textContent = judul;
    document.getElementById("isi-info").textContent = isi;
    dialogInfo.showModal();
}

const lupaPassword = document.getElementById("lupa-password");
const daftarAkun = document.getElementById("daftar-akun");

if (lupaPassword) {
    lupaPassword.addEventListener("click", function () {
        tampilkanInfo("Lupa kata sandi?",
            "Pemulihan kata sandi belum tersedia.");
    });
}

if (daftarAkun) {
    daftarAkun.addEventListener("click", function () {
        tampilkanInfo("Daftar akun",
            "Pendaftaran belum tersedia.");
    });
}
