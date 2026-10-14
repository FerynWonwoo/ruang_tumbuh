document.addEventListener('DOMContentLoaded', () => {
    // Navigasi aktif di sidebar
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Integrated Realtime Calendar Logic
    const monthLabel = document.getElementById('monthLabel');
    const prevBtn = document.getElementById('prevMonth');
    const nextBtn = document.getElementById('nextMonth');
    const calendarGrid = document.getElementById('calendarGrid');

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const daysHeader = ['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MIN'];

    // Inisialisasi tanggal realtime saat ini
    const today = new Date();
    let currentDate = new Date(today.getFullYear(), today.getMonth(), 1);

    // Sampel Event Dummy untuk tanggal tertentu
    const sampleEvents = {
        "14": { type: "event-red", label: "Batas LPDP" },
        "15": { type: "event-green", label: "Kick-off Cohort" },
        "17": { type: "event-yellow", label: "Mentoring" }
    };

    function renderCalendar() {
        if (!calendarGrid || !monthLabel) return;

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        // Tampilkan Bulan dan Tahun di Header Kalender
        monthLabel.textContent = `${months[month]} ${year}`;

        // Kosongkan grid sebelum render ulang
        calendarGrid.innerHTML = '';

        // Render Day Headers (SEN - MIN)
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

        // 1. Render Tanggal Bulan Sebelumnya (Other Month)
        for (let i = firstDayIndex; i > 0; i--) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell other-month';
            cell.innerHTML = `<span>${prevMonthLastDay - i + 1}</span>`;
            calendarGrid.appendChild(cell);
        }

        // 2. Render Tanggal Bulan Ini (Realtime)
        for (let day = 1; day <= totalDaysInMonth; day++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-day-cell';

            // Tandai hari ini (Today Highlight)
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

        // 3. Render Tanggal Bulan Berikutnya (Sisa Grid)
        const totalCells = calendarGrid.children.length - 7; // Kurangi header
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