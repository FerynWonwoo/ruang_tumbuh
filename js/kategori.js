document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchKategori");
    const kategoriCards = document.querySelectorAll(".kategori-card");

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const keyword = this.value.toLowerCase().trim();

            kategoriCards.forEach((card) => {
                const title = card.querySelector(".kategori-title").textContent.toLowerCase();
                const desc = card.querySelector(".kategori-desc").textContent.toLowerCase();

                if (title.includes(keyword) || desc.includes(keyword)) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });
        });
    }
});