document.addEventListener('DOMContentLoaded', () => {
    // Navigasi aktif di sidebar
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Kontrol Bulan Sederhana untuk Kalender
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    let currentMonthIndex = 9; // Oktober
    let currentYear = 2025;

    const monthLabel = document.getElementById('monthLabel');
    const prevBtn = document.getElementById('prevMonth');
    const nextBtn = document.getElementById('nextMonth');

    function updateMonthDisplay() {
        if(monthLabel) {
            monthLabel.textContent = `${months[currentMonthIndex]} ${currentYear}`;
        }
    }

    if(prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
            currentMonthIndex--;
            if (currentMonthIndex < 0) {
                currentMonthIndex = 11;
                currentYear--;
            }
            updateMonthDisplay();
        });

        nextBtn.addEventListener('click', () => {
            currentMonthIndex++;
            if (currentMonthIndex > 11) {
                currentMonthIndex = 0;
                currentYear++;
            }
            updateMonthDisplay();
        });
    }
});