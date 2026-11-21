document.addEventListener('DOMContentLoaded', () => {
    // Navigasi aktif di sidebar
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            navLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Kalender realtime
    const monthLabel = document.getElementById('monthLabel');
    const prevBtn = document.getElementById('prevMonth');
    const nextBtn = document.getElementById('nextMonth');
    const calendarGrid = document.getElementById('calendarGrid');

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const daysHeader = ['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MIN'];

    // Inisialisasi tanggal realtime saat ini
    const today = new Date();
    let currentDate = new Date(today.getFullYear(), today.getMonth(), 1);

    function renderCalendar() {
        if (!calendarGrid || !monthLabel) return;

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        // Tampilkan bulan dan tahun di header kalender
        monthLabel.textContent = `${months[month]} ${year}`;

        // Kosongkan grid sebelum render ulang
        calendarGrid.innerHTML = '';

        // Render day headers (SEN - MIN)
        daysHeader.forEach(day => {
            const headerCell = document.createElement('div');
            headerCell.className = 'calendar-day-header';
            headerCell.textContent = day;
            calendarGrid.appendChild(headerCell);
        });

        // Hitung hari pertama bulan ini (0 = Minggu, 1 = Senin, dst.)
        let firstDayIndex = new Date(year, month, 1).getDay();
        // Sesuaikan agar Senin = 0, Minggu = 6
        firstDayIndex = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

        // Jumlah hari di bulan ini & bulan sebelumnya
        const totalDaysInMonth = new Date(year, month + 1, 0).getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        // 1. Render tanggal bulan sebelumnya
        for (let i = firstDayIndex; i > 0; i--) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell other-month';
            cell.innerHTML = `<span>${prevMonthLastDay - i + 1}</span>`;
            calendarGrid.appendChild(cell);
        }

        // 2. Render tanggal bulan ini (Realtime)
        for (let day = 1; day <= totalDaysInMonth; day++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell';

            // highlight hari ini
            if (
                day === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear()
            ) {
                cell.classList.add('today-cell');
            }

            let cellHTML = `<span>${day}</span>`;

            // Tambahkan sampel event jika ada
            if (sampleEvents[day]) {
                const event = sampleEvents[day];
                cellHTML += `<div class="event-tag ${event.type}">${event.label}</div>`;
            }

            cell.innerHTML = cellHTML;
            calendarGrid.appendChild(cell);
        }

        // 3. Render tanggal bulan berikutnya
        const totalCells = calendarGrid.children.length - 7;
        const remainingCells = 35 - totalCells > 0 ? 35 - totalCells : 42 - totalCells;

        for (let day = 1; day <= remainingCells; day++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell other-month';
            cell.innerHTML = `<span>${day}</span>`;
            calendarGrid.appendChild(cell);
        }
    }

    // Navigasi Bulan
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });

        nextBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });
    }

    // Jalankan render kalender awal
    renderCalendar();
});