<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman: wajib login
if (empty($_SESSION['user_id'])) {
    header('Location: ../../', true, 302);
    exit;
}

// Cegah halaman tampil dari cache saat tombol Back setelah logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../includes/bookmark_store.php';
require_once __DIR__ . '/../../includes/bootcamp_store.php';

$nama_user = isset($_SESSION['nama'])
    ? htmlspecialchars($_SESSION['nama'])
    : (isset($_SESSION['user']['nama'])
        ? htmlspecialchars($_SESSION['user']['nama'])
        : 'Pengguna');

// ---------- Data kartu statistik (dihitung dari halaman masing-masing) ----------
$usernameLogin = $_SESSION['username'] ?? '';
$userIdLogin = (int) ($_SESSION['user_id'] ?? 0);

// 1) Peluang Tersimpan -> halaman Bookmark
$ringkasBookmark = bookmark_ringkasan($usernameLogin);
$totalBookmark = $ringkasBookmark['total'];
$bookmarkMepet = $ringkasBookmark['mendekati'];

// 2) Bootcamp Aktif -> halaman Katalog (deadline belum lewat)
$semuaKatalog = bootcamp_load();
$katalogAktif = 0;
foreach ($semuaKatalog as $bc) {
    $ts = !empty($bc['deadline']) ? strtotime($bc['deadline']) : false;
    if (!$ts || $ts >= strtotime('today')) {
        $katalogAktif++;
    }
}
$totalKatalog = count($semuaKatalog);

// 3) Catatan Strategi -> halaman Catatan (database)
$totalCatatan = 0;
$catatanTerakhir = null;
if ($userIdLogin > 0) {
    try {
        require_once __DIR__ . '/../../config/koneksimvc.php';
        require_once __DIR__ . '/../Models/Catatan.php';
        $statCatatan = (new Catatan($conn))->getStatsByUser($userIdLogin);
        $totalCatatan = $statCatatan['total'];
        $catatanTerakhir = $statCatatan['terakhir'];
    } catch (Throwable $e) {
        error_log('Dashboard - statistik catatan: ' . $e->getMessage());
    }
}

function waktu_lalu(?string $waktu): string
{
    $ts = $waktu ? strtotime($waktu) : false;
    if (!$ts) {
        return '-';
    }
    $selisih = max(0, time() - $ts);
    if ($selisih < 60)
        return 'baru saja';
    if ($selisih < 3600)
        return floor($selisih / 60) . ' menit lalu';
    if ($selisih < 86400)
        return floor($selisih / 3600) . ' jam lalu';
    return floor($selisih / 86400) . ' hari lalu';
}

// 4) Rating Komunitas -> data/ulasan.json (hanya yang disetujui)
$totalUlasan = 0;
$jumlahRating = 0;
$fileUlasan = __DIR__ . '/../../data/ulasan.json';
if (is_file($fileUlasan)) {
    $dataUlasan = json_decode((string) file_get_contents($fileUlasan), true);
    foreach (is_array($dataUlasan) ? $dataUlasan : [] as $u) {
        if (($u['status'] ?? 'approved') === 'approved') {
            $totalUlasan++;
            $jumlahRating += (int) ($u['rating'] ?? 0);
        }
    }
}

// 5) Tenggat untuk kalender: bookmark + pengingat milik user
require_once __DIR__ . '/../Models/Pengingat.php';

$eventsKalender = [];
if ($userIdLogin > 0 && isset($conn) && $conn instanceof mysqli) {
    foreach ((new Bookmark($conn))->getAllByUser($userIdLogin) as $b) {
        if (!empty($b['deadline'])) {
            $eventsKalender[$b['deadline']][] = [
                'judul' => mb_substr((string) $b['judul'], 0, 40),
                'kelas' => 'event-red',
            ];
        }
    }

    $hasilPengingat = (new Pengingat())->getAll($userIdLogin);
    while ($hasilPengingat && ($p = mysqli_fetch_assoc($hasilPengingat))) {
        $eventsKalender[$p['tanggal_tenggat']][] = [
            'judul' => mb_substr((string) $p['judul'], 0, 40),
            'kelas' => !empty($p['selesai']) ? 'event-gray' : 'event-yellow',
        ];
    }
}

$rataRating = $totalUlasan > 0 ? number_format($jumlahRating / $totalUlasan, 1) : '0.0';
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
    <link rel="stylesheet" href="../../public/css/dashboard.css">

</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- MAIN AREA -->
    <div class="main-area">
        <?php include __DIR__ . '/../../includes/header.php'; ?>

        <!-- DASHBOARD CONTAINER -->
        <main class="dashboard-container">

            <!-- WELCOME BANNER -->
            <section class="welcome-banner">
                <span class="badge-tag">Karir Mahasiswa & Fresh Graduate</span>
                <h1 class="banner-title">
                    Selamat Datang Kembali, <?= $nama_user; ?>!
                </h1>
                <p class="banner-subtitle">
                    Memantau karir, tenggat penting, jadwal bootcamp, dan jelajahi ribuan peluang pengembangan diri
                    dalam satu dasbor terpadu.
                </p>

                <!-- STATS GRID -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span>Tersimpan</span>
                            <i class="fa-regular fa-bookmark"></i>
                        </div>
                        <div class="stat-value-container">
                            <span class="stat-value"><?= $totalBookmark; ?></span>
                        </div>
                        <?php if ($bookmarkMepet > 0): ?>
                            <div class="stat-footer text-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i> <?= $bookmarkMepet; ?> mendekati batas
                                tenggat
                            </div>
                        <?php else: ?>
                            <div class="stat-footer text-muted">
                                <i class="fa-regular fa-circle-check"></i> Tidak ada yang mendekati tenggat
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span>Program Aktif</span>
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div class="stat-value-container">
                            <span class="stat-value"><?= $katalogAktif; ?></span> <span class="stat-unit">Program</span>
                        </div>
                        <div class="stat-footer text-muted">
                            <i class="fa-solid fa-shapes"></i> Dari <?= $totalKatalog; ?> program di katalog
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span>Catatan</span>
                            <i class="fa-regular fa-note-sticky"></i>
                        </div>
                        <div class="stat-value-container">
                            <span class="stat-value"><?= $totalCatatan; ?></span> <span class="stat-unit">Memo</span>
                        </div>
                        <div class="stat-footer text-muted">
                            <i class="fa-regular fa-clock"></i>
                            <?= $totalCatatan > 0 ? 'Diperbarui ' . htmlspecialchars(waktu_lalu($catatanTerakhir)) : 'Belum ada catatan'; ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span>Rating</span>
                            <i class="fa-solid fa-star" style="color: #F59E0B;"></i>
                        </div>
                        <div class="stat-value-container">
                            <span class="stat-value"><?= $rataRating; ?></span> <span class="stat-unit">/ 5.0</span>
                        </div>
                        <div class="stat-footer text-muted">
                            <i class="fa-regular fa-circle-check"></i> <?= $totalUlasan; ?> ulasan terverifikasi
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

                <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:12px; font-size:12px; color:#6B7280;">
                    <span><span class="event-tag event-red" style="display:inline-block;">&nbsp;</span> Tenggat
                        bookmark</span>
                    <span><span class="event-tag event-yellow" style="display:inline-block;">&nbsp;</span>
                        Pengingat</span>
                    <span><span class="event-tag event-gray" style="display:inline-block;">&nbsp;</span> Selesai</span>
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

            const events = <?= json_encode($eventsKalender, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

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

                    cell.innerHTML = cellHTML;

                    const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const list = events[key] || [];
                    list.slice(0, 2).forEach((ev) => {
                        const tag = document.createElement('div');
                        tag.className = 'event-tag ' + ev.kelas;
                        tag.textContent = ev.judul;
                        tag.title = ev.judul;          // judul lengkap muncul saat kursor diarahkan
                        cell.appendChild(tag);
                    });
                    if (list.length > 2) {
                        const more = document.createElement('div');
                        more.className = 'event-more';
                        more.textContent = '+' + (list.length - 2) + ' lagi';
                        cell.appendChild(more);
                    }
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