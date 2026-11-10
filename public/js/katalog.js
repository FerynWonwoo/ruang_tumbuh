
const searchInput = document.getElementById("searchBootcamp");
const filterKategori = document.getElementById("filterKategori");
const filterMode = document.getElementById("filterMode");
const cards = document.querySelectorAll(".bootcamp-card");
const jumlahHasil = document.getElementById("jumlahHasil");

function filterBootcamp() {
    const keyword = searchInput.value.toLowerCase().trim();
    const kategori = filterKategori.value;
    const mode = filterMode.value;
    let jumlah = 0;

    cards.forEach(card => {
        const text = card.dataset.search.toLowerCase();
        const cardKategori = card.dataset.kategori;
        const cardMode = card.dataset.mode;

        const cocokKeyword = text.includes(keyword);
        const cocokKategori = kategori === "" || cardKategori === kategori;
        const cocokMode = mode === "" || cardMode === mode;

        if (cocokKeyword && cocokKategori && cocokMode) {
            card.style.display = "";
            jumlah++;
        } else {
            card.style.display = "none";
        }
    });

    jumlahHasil.textContent = jumlah + " program";
}

searchInput.addEventListener("input", filterBootcamp);
filterKategori.addEventListener("change", filterBootcamp);
filterMode.addEventListener("change", filterBootcamp);

function bukaModalTambah() {
    const modal = document.getElementById("modalForm");
    const form = document.getElementById("formBootcamp");

    form.reset();

    document.getElementById("judulModal").textContent = "Tambah Bootcamp";
    document.getElementById("btnSubmit").name = "tambah";
    document.getElementById("btnSubmit").innerHTML =
        '<i class="fa-solid fa-floppy-disk"></i> Simpan';

    modal.classList.add("active");
}

function bukaModalEdit(data) {
    document.getElementById("id").value = data.id;
    document.getElementById("judul").value = data.judul;
    document.getElementById("penyelenggara").value = data.penyelenggara;
    document.getElementById("kategori").value = data.kategori;
    document.getElementById("mode").value = data.mode;
    document.getElementById("durasi").value = data.durasi;
    document.getElementById("deadline").value = data.deadline;
    document.getElementById("harga").value = data.harga;
    document.getElementById("link").value = data.link;
    document.getElementById("deskripsi").value = data.deskripsi;

    document.getElementById("judulModal").textContent = "Edit Bootcamp";

    const button = document.getElementById("btnSubmit");
    button.name = "edit";
    button.innerHTML = '<i class="fa-solid fa-pen"></i> Update';

    document.getElementById("modalForm").classList.add("active");
}

function tutupModal() {
    document.getElementById("modalForm").classList.remove("active");
}

function lihatDetail(data) {
    document.getElementById("detailKategori").textContent = data.kategori;
    document.getElementById("detailJudul").textContent = data.judul;
    document.getElementById("detailPenyelenggara").innerHTML =
        '<i class="fa-regular fa-building"></i> ' + data.penyelenggara;
    document.getElementById("detailMode").textContent = data.mode;
    document.getElementById("detailDurasi").textContent = data.durasi;
    document.getElementById("detailHarga").textContent = data.harga;
    document.getElementById("detailDeskripsi").textContent = data.deskripsi;

    const tanggal = new Date(data.deadline);
    document.getElementById("detailDeadline").textContent =
        tanggal.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });

    document.getElementById("detailLink").href = data.link;
    document.getElementById("modalDetail").classList.add("active");
}

function tutupDetail() {
    document.getElementById("modalDetail").classList.remove("active");
}

window.addEventListener("click", function(event) {
    const modalForm = document.getElementById("modalForm");
    const modalDetail = document.getElementById("modalDetail");

    if (event.target === modalForm) {
        tutupModal();
    }

    if (event.target === modalDetail) {
        tutupDetail();
    }
});

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        tutupModal();
        tutupDetail();
    }
});


