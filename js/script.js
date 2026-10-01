// Toggle Password Universal (Mendukung Kata Sandi & Konfirmasi Kata Sandi)
const toggleButtons = document.querySelectorAll(".toggle-password");

toggleButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
        const inputContainer = this.closest(".password-field");
        const passwordInput = inputContainer ? inputContainer.querySelector("input") : null;
        const garisMata = this.querySelector(".garis-mata");

        if (passwordInput) {
            const tampilkan = passwordInput.type === "password";
            passwordInput.type = tampilkan ? "text" : "password";
            if (garisMata) {
                garisMata.toggleAttribute("hidden", !tampilkan);
            }
            this.setAttribute("aria-pressed", String(tampilkan));
            this.setAttribute(
                "aria-label",
                tampilkan ? "Sembunyikan kata sandi" : "Tampilkan kata sandi"
            );
        }
    });
});

const video = document.querySelector(".background-video");
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
        tampilkanInfo("Lupa kata sandi?", "Pemulihan kata sandi belum tersedia.");
    });
}

if (daftarAkun) {
    daftarAkun.addEventListener("click", function () {
        tampilkanInfo("Daftar akun", "Pendaftaran belum tersedia.");
    });
}