<?php
// Jalankan session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Penanganan error 'Undefined array key "nama"' dengan nilai default fallback
$nama_user = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : (isset($_SESSION['user']['nama']) ? htmlspecialchars($_SESSION['user']['nama']) : 'Pengguna');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuangTumbuh - Dashboard</title>
    
    <!-- FontAwesome CDN untuk ikon sidebar & header -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS relatif dari folder page/ menuju css/dashboard.css -->
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<aside class="sidebar">

    <div>
        <div class="logo-text">
            Ruang<span>Tumbuh</span>
        </div>

        <div class="nav-group" style="margin-top: 32px;">
            <div class="nav-title">MENU UTAMA</div>

            <ul class="nav-menu">
                <li>
                    <a href="dashboard.php" class="nav-link">
                        <i class="fa-solid fa-table-cells-large"></i>
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-shapes"></i>
                        Katalog Bootcamp
                    </a>
                </li>

                <li>
                    <a href="kategori.php" class="nav-link">
                        <i class="fa-solid fa-layer-group"></i>
                        Kategori Program
                    </a>
                </li>

                <li>
                    <a href="#" class="nav-link">
                        <i class="fa-regular fa-bookmark"></i>
                        Bookmark & Tersimpan
                    </a>
                </li>

                <li>
                    <a href="#" class="nav-link">
                        <i class="fa-regular fa-clock"></i>
                        Pengingat Tenggat
                    </a>
                </li>

                <li>
                    <a href="#" class="nav-link">
                        <i class="fa-regular fa-pen-to-square"></i>
                        Catatan Pribadi
                    </a>
                </li>

                <li>
                    <a href="rating.php" class="nav-link">
                        <i class="fa-regular fa-star"></i>
                        Ulasan & Rating
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-group">
            <div class="nav-title">AKUN</div>

            <ul class="nav-menu">
                <li>
                    <a href="profile.php" class="nav-link">
                        <i class="fa-regular fa-user"></i>
                        Profil Pengguna
                    </a>
                </li>
            </ul>
        </div>
    </div>


    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>

</aside>

    <!-- MAIN AREA -->
    <div class="main-area">
        
        <!-- HEADER -->
        <header class="header">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="search-input" placeholder="Cari beasiswa, magang, bootcamp, lomba...">
            </div>

            <div class="header-actions">
                <button class="btn-simpan">
                    <i class="fa-solid fa-plus"></i> Simpan Peluang
                </button>
                
                <div class="profile-section">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($nama_user); ?>&background=10B981&color=fff" alt="Avatar" class="profile-avatar">
                </div>
            </div>
        </header>

        <!-- DASHBOARD CONTAINER -->
        <main class="dashboard-container">
            
            <!-- WELCOME BANNER -->
            <section class="welcome-banner">
                <span class="badge-tag">• Akselerasi Karir Mahasiswa & Fresh Graduate</span>
                <h1 class="banner-title">
                    Selamat Datang Kembali, <?= $nama_user; ?>!
                </h1>
                <p class="banner-subtitle">
                    Pantau akselerasi karir, tenggat penting, jadwal bootcamp, dan jelajahi ribuan peluang pengembangan diri dalam satu dasbor terpadu.
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

            <!-- KALENDER JADWAL & TENGGAT -->
            <section class="calendar-card">
                <div class="calendar-header">
                    <div class="calendar-title-group">
                        <i class="fa-regular fa-calendar-days"></i>
                        <h2 class="calendar-title">Kalender Jadwal & Tenggat</h2>
                    </div>
                    <div class="calendar-controls">
                        <button class="btn-icon" id="prevMonth"><i class="fa-solid fa-chevron-left"></i></button>
                        <span class="month-label" id="monthLabel">Okt 2025</span>
                        <button class="btn-icon" id="nextMonth"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>

                <div class="calendar-grid">
                    <!-- Day Headers -->
                    <div class="calendar-day-header">SEN</div>
                    <div class="calendar-day-header">SEL</div>
                    <div class="calendar-day-header">RAB</div>
                    <div class="calendar-day-header">KAM</div>
                    <div class="calendar-day-header">JUM</div>
                    <div class="calendar-day-header">SAB</div>
                    <div class="calendar-day-header">MIN</div>

                    <!-- Row 1 -->
                    <div class="calendar-day-cell other-month"><span>29</span></div>
                    <div class="calendar-day-cell other-month"><span>30</span></div>
                    <div class="calendar-day-cell"><span>1</span></div>
                    <div class="calendar-day-cell"><span>2</span></div>
                    <div class="calendar-day-cell"><span>3</span></div>
                    <div class="calendar-day-cell"><span>4</span></div>
                    <div class="calendar-day-cell"><span>5</span></div>

                    <!-- Row 2 -->
                    <div class="calendar-day-cell"><span>6</span></div>
                    <div class="calendar-day-cell"><span>7</span></div>
                    <div class="calendar-day-cell">
                        <span>8</span>
                        <div class="dot-indicator"></div>
                    </div>
                    <div class="calendar-day-cell"><span>9</span></div>
                    <div class="calendar-day-cell">
                        <span>10</span>
                        <div class="dot-indicator"></div>
                    </div>
                    <div class="calendar-day-cell"><span>11</span></div>
                    <div class="calendar-day-cell"><span>12</span></div>

                    <!-- Row 3 -->
                    <div class="calendar-day-cell"><span>13</span></div>
                    <div class="calendar-day-cell">
                        <span>14</span>
                        <div class="event-tag event-red">Batas LPDP</div>
                    </div>
                    <div class="calendar-day-cell">
                        <span>15</span>
                        <div class="event-tag event-green">Kick-off Cohort</div>
                    </div>
                    <div class="calendar-day-cell"><span>16</span></div>
                    <div class="calendar-day-cell">
                        <span>17</span>
                        <div class="event-tag event-yellow">Mentoring</div>
                    </div>
                    <div class="calendar-day-cell"><span>18</span></div>
                    <div class="calendar-day-cell"><span>19</span></div>

                    <!-- Row 4 -->
                    <div class="calendar-day-cell"><span>20</span></div>
                    <div class="calendar-day-cell"><span>21</span></div>
                    <div class="calendar-day-cell"><span>22</span></div>
                    <div class="calendar-day-cell">
                        <span>23</span>
                        <div class="dot-indicator"></div>
                    </div>
                    <div class="calendar-day-cell"><span>24</span></div>
                    <div class="calendar-day-cell">
                        <span>25</span>
                        <div class="dot-indicator"></div>
                    </div>
                    <div class="calendar-day-cell"><span>26</span></div>
                </div>
            </section>

        </main>
    </div>

    <!-- JS relatif dari folder page/ menuju js/dashboard.js -->
    <script src="../js/dashboard.js"></script>
</body>
</html>