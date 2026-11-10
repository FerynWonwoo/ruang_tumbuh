<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nama_user = isset($_SESSION['nama']) 
    ? htmlspecialchars($_SESSION['nama']) 
    : (isset($_SESSION['user']['nama']) 
        ? htmlspecialchars($_SESSION['user']['nama']) 
        : 'Pengguna');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Dashboard</title>
    
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS relatif -->
    <link rel="stylesheet" href="../css/dashboard.css">
    
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN AREA -->
    <div class="main-area">
        <?php include __DIR__ . '/../includes/header.php'; ?>

        <!-- DASHBOARD CONTAINER -->
        <main class="dashboard-container">
            
            <!-- WELCOME BANNER -->
            <section class="welcome-banner">
                <span class="badge-tag">Karir Mahasiswa & Fresh Graduate</span>
                <h1 class="banner-title">
                    Selamat Datang Kembali, <?= $nama_user; ?>!
                </h1>
                <p class="banner-subtitle">
                    Memantau karir, tenggat penting, jadwal bootcamp, dan jelajahi ribuan peluang pengembangan diri dalam satu dasbor terpadu.
                </p>

                <!-- STATS GRID -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span>Peluang Tersimpan</span>
                            <i class="fa-regular fa-bookmark"></i>
                        </div>
                        <div class="stat-value-container">
                            <span class="stat-value">14</span>
                        </div>
                        <div class="stat-footer text-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i> 3 mendekati batas tenggat
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span>Bootcamp Aktif</span>
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div class="stat-value-container">
                            <span class="stat-value">2</span> <span class="stat-unit">Program</span>
                        </div>
                        <div class="stat-footer text-success">
                            <i class="fa-solid fa-arrow-trend-up"></i> Progress mingguan +18%
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span>Catatan Strategi</span>
                            <i class="fa-regular fa-note-sticky"></i>
                        </div>
                        <div class="stat-value-container">
                            <span class="stat-value">5</span> <span class="stat-unit">Memo</span>
                        </div>
                        <div class="stat-footer text-muted">
                            <i class="fa-regular fa-clock"></i> Diperbarui 3 jam lalu
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span>Rating Komunitas</span>
                            <i class="fa-solid fa-star" style="color: #F59E0B;"></i>
                        </div>
                        <div class="stat-value-container">
                            <span class="stat-value">4.9</span> <span class="stat-unit">/ 5.0</span>
                        </div>
                        <div class="stat-footer text-muted">
                            <i class="fa-regular fa-circle-check"></i> 1,840+ Ulasan Terverifikasi
                        </div>
                    </div>
                </div>
            </section>

            <!-- KALENDER JADWAL & TENGGAT REALTIME -->
            <section class="calendar-card">
                <div class="calendar-header">
                    <div class="calendar-title-group">
                        <i class="fa-regular fa-calendar-days"></i>
                        <h2 class="calendar-title">Kalender Jadwal & Tenggat</h2>
                    </div>
                    <div class="calendar-controls">
                        <button class="btn-icon" id="prevMonth"><i class="fa-solid fa-chevron-left"></i></button>
                        <span class="month-label" id="monthLabel">Memuat...</span>
                        <button class="btn-icon" id="nextMonth"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>

                <div class="calendar-grid" id="calendarGrid">
                    <!-- Hari dan tanggal akan digenerate otomatis melalui JS secara Realtime -->
                </div>
            </section>

        </main>
    </div>

    <!-- JS relatif -->
    <!-- SCRIPT KALENDER REALTIME LANGSUNG DI DALAM DASHBOARD.PHP -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const monthLabel = document.getElementById('monthLabel');
        const prevBtn = document.getElementById('prevMonth');
        const nextBtn = document.getElementById('nextMonth');
        const calendarGrid = document.getElementById('calendarGrid');

        if (!calendarGrid || !monthLabel) return;

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const daysHeader = ['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MIN'];

        const today = new Date();
        let currentDate = new Date(today.getFullYear(), today.getMonth(), 1);

        const sampleEvents = {
            "14": { type: "event-red", label: "Batas LPDP" },
            "15": { type: "event-green", label: "Kick-off Cohort" },
            "17": { type: "event-yellow", label: "Mentoring" }
        };

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            monthLabel.textContent = `${months[month]} ${year}`;
            calendarGrid.innerHTML = '';

            // 1. Header Hari (SEN - MIN)
            daysHeader.forEach(day => {
                const headerCell = document.createElement('div');
                headerCell.className = 'calendar-day-header';
                headerCell.textContent = day;
                calendarGrid.appendChild(headerCell);
            });

            // 2. Hitung Sisa Hari Bulan Lalu
            let firstDayIndex = new Date(year, month, 1).getDay();
            firstDayIndex = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

            const totalDaysInMonth = new Date(year, month + 1, 0).getDate();
            const prevMonthLastDay = new Date(year, month, 0).getDate();

            for (let i = firstDayIndex; i > 0; i--) {
                const cell = document.createElement('div');
                cell.className = 'calendar-day-cell other-month';
                cell.innerHTML = `<span>${prevMonthLastDay - i + 1}</span>`;
                calendarGrid.appendChild(cell);
            }

            // 3. Tanggal Bulan Ini (Realtime)
            for (let day = 1; day <= totalDaysInMonth; day++) {
                const cell = document.createElement('div');
                cell.className = 'calendar-day-cell';

                if (
                    day === today.getDate() &&
                    month === today.getMonth() &&
                    year === today.getFullYear()
                ) {
                    cell.classList.add('today-cell');
                }

                let cellHTML = `<span>${day}</span>`;

                if (sampleEvents[day]) {
                    const event = sampleEvents[day];
                    cellHTML += `<div class="event-tag ${event.type}">${event.label}</div>`;
                }

                cell.innerHTML = cellHTML;
                calendarGrid.appendChild(cell);
            }

            // 4. Tanggal Bulan Depan
            const totalCells = calendarGrid.children.length - 7;
            const remainingCells = (totalCells <= 35) ? (35 - totalCells) : (42 - totalCells);

            for (let day = 1; day <= remainingCells; day++) {
                const cell = document.createElement('div');
                cell.className = 'calendar-day-cell other-month';
                cell.innerHTML = `<span>${day}</span>`;
                calendarGrid.appendChild(cell);
            }
        }

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

        renderCalendar();
    });
    </script>
</body>
</html>
</body>
</html>