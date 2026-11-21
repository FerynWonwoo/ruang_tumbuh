<?php
// Fungsi bantu untuk modul Ulasan & Rating.
// Sengaja TIDAK mendefinisikan aman() karena view lain (register, kategori)
// sudah mendefinisikannya sendiri dan akan bentrok.

if (!function_exists('rating_e')) {
    // Escape teks untuk keluaran HTML
    function rating_e($teks): string
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('bintang')) {
    // Bintang terisi + bintang kosong (abu-abu, lihat .stars span di rating.css)
    function bintang($nilai): string
    {
        $nilai = max(0, min(5, (int) $nilai));

        return str_repeat('★', $nilai)
            . '<span>' . str_repeat('★', 5 - $nilai) . '</span>';
    }
}

if (!function_exists('rating_tanggal')) {
    function rating_tanggal($tanggal): string
    {
        $ts = strtotime((string) $tanggal);

        return $ts ? date('d M Y', $ts) : '';
    }
}
