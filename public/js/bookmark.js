document.addEventListener("DOMContentLoaded", () => {
    // Fitur Pencarian Realtime pada Bookmark
    const searchInput = document.getElementById("searchBookmark");
    const cards = document.querySelectorAll(".bookmark-card");

    if (searchInput) {
        searchInput.addEventListener("input", (e) => {
            const query = e.target.value.toLowerCase();

            cards.forEach((card) => {
                const title = card.querySelector(".card-title")?.textContent.toLowerCase() || "";
                const category = card.querySelector(".kategori-tag")?.textContent.toLowerCase() || "";
                const organizer = card.querySelector(".penyelenggara")?.textContent.toLowerCase() || "";

                if (title.includes(query) || category.includes(query) || organizer.includes(query)) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });
        });
    }

    // Konfirmasi sebelum menghapus bookmark
    const deleteForms = document.querySelectorAll("form");
    deleteForms.forEach((form) => {
        form.addEventListener("submit", (e) => {
            const isConfirmed = confirm("Apakah Anda yakin ingin menghapus program ini dari bookmark?");
            if (!isConfirmed) {
                e.preventDefault();
            }
        });
    });
});