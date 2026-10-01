document.addEventListener("DOMContentLoaded", function () {

    // =====================================
    // USER: KONFIRMASI TARIK ULASAN
    // =====================================

    const tombolKonfirmasi =
        document.querySelectorAll("[data-confirm]");

    tombolKonfirmasi.forEach(function (tombol) {

        tombol.addEventListener("click", function (event) {

            const pesan =
                tombol.dataset.confirm;

            const yakin =
                confirm(pesan);

            if (!yakin) {
                event.preventDefault();
            }

        });

    });


    // =====================================
    // USER: AUTO RESIZE TEXTAREA
    // =====================================

    const textarea =
        document.getElementById("komentar");

    if (textarea) {

        function aturTinggiTextarea() {

            textarea.style.height = "auto";

            textarea.style.height =
                textarea.scrollHeight + "px";

        }

        textarea.addEventListener(
            "input",
            aturTinggiTextarea
        );

        if (textarea.value.trim() !== "") {
            aturTinggiTextarea();
        }

    }


    // =====================================
    // USER: PILIHAN RATING
    // =====================================

    const ratingInputs =
        document.querySelectorAll(
            '.rating-option input[type="radio"]'
        );

    function updateRating() {

        ratingInputs.forEach(function (radio) {

            const label =
                radio.closest(".rating-option");

            if (!label) {
                return;
            }

            if (radio.checked) {

                label.classList.add("selected");

            } else {

                label.classList.remove("selected");

            }

        });

    }

    ratingInputs.forEach(function (radio) {

        radio.addEventListener(
            "change",
            updateRating
        );

    });

    updateRating();


    // =====================================
    // ADMIN: SEARCH ULASAN
    // =====================================

    const searchInput =
        document.getElementById("searchReview");

    const statusFilter =
        document.getElementById("filterStatus");

    const reviewCards =
        document.querySelectorAll(
            ".admin-review-card"
        );

    const emptyFilter =
        document.getElementById("emptyFilter");


    function filterReviews() {

        const keyword =
            searchInput
                ? searchInput.value
                    .toLowerCase()
                    .trim()
                : "";

        const status =
            statusFilter
                ? statusFilter.value
                : "all";

        let jumlahTampil = 0;


        reviewCards.forEach(function (card) {

            const dataSearch =
                (
                    card.dataset.search || ""
                ).toLowerCase();

            const dataStatus =
                card.dataset.status || "";


            const cocokPencarian =
                dataSearch.includes(keyword);

            const cocokStatus =
                status === "all" ||
                dataStatus === status;


            if (
                cocokPencarian &&
                cocokStatus
            ) {

                card.style.display = "";

                jumlahTampil++;

            } else {

                card.style.display = "none";

            }

        });


        if (emptyFilter) {

            emptyFilter.hidden =
                jumlahTampil !== 0;

        }

    }


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            filterReviews
        );

    }


    if (statusFilter) {

        statusFilter.addEventListener(
            "change",
            filterReviews
        );

    }


    // =====================================
    // ADMIN: KONFIRMASI HAPUS
    // =====================================

    const deleteForms =
        document.querySelectorAll(
            ".delete-form"
        );

    deleteForms.forEach(function (form) {

        form.addEventListener(
            "submit",
            function (event) {

                const yakin = confirm(
                    "Yakin ingin menghapus ulasan ini? Ulasan yang sudah dihapus tidak dapat dikembalikan."
                );

                if (!yakin) {
                    event.preventDefault();
                }

            }
        );

    });

});