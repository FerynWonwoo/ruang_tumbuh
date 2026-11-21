
-- Seeder data contoh program RuangTumbuh
-- Jalankan setelah migration dan seeder kategori selesai.

INSERT INTO program (
    id,
    judul,
    penyelenggara,
    kategori_id,
    jenis,
    mode,
    durasi,
    tanggal_mulai,
    deadline,
    harga,
    link,
    deskripsi,
    gambar
)

SELECT
    'seed-lomba-uiux',
    'Lomba UI/UX Design',
    'Panitia RuangTumbuh',
    k.id,
    'Lomba',
    'Online',
    '1 bulan',
    DATE_ADD(CURDATE(), INTERVAL 21 DAY),
    DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    'Gratis',
    'https://example.com/lomba-uiux',
    'Kompetisi desain UI/UX untuk mengembangkan kreativitas dan kemampuan desain digital.',
    NULL
FROM kategori k
WHERE k.nama_kategori = 'Design'
AND NOT EXISTS (
    SELECT 1 FROM program
    WHERE id = 'seed-lomba-uiux'
)
LIMIT 1;


INSERT INTO program (
    id, judul, penyelenggara, kategori_id, jenis,
    mode, durasi, tanggal_mulai, deadline,
    harga, link, deskripsi, gambar
)
SELECT
    'seed-bootcamp-web',
    'Bootcamp Pemrograman Web',
    'Akademi RuangTumbuh',
    k.id,
    'Bootcamp',
    'Online',
    '2 bulan',
    DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    DATE_ADD(CURDATE(), INTERVAL 7 DAY),
    '500.000',
    'https://example.com/bootcamp-web',
    'Program pembelajaran pengembangan web dari dasar hingga pembuatan proyek.',
    NULL
FROM kategori k
WHERE k.nama_kategori = 'Teknologi'
AND NOT EXISTS (
    SELECT 1 FROM program
    WHERE id = 'seed-bootcamp-web'
)
LIMIT 1;


INSERT INTO program (
    id, judul, penyelenggara, kategori_id, jenis,
    mode, durasi, tanggal_mulai, deadline,
    harga, link, deskripsi, gambar
)
SELECT
    'seed-magang-marketing',
    'Program Magang Digital Marketing',
    'RuangTumbuh Career',
    k.id,
    'Magang',
    'Hybrid',
    '3 bulan',
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    DATE_ADD(CURDATE(), INTERVAL 20 DAY),
    'Gratis',
    'https://example.com/magang-marketing',
    'Program magang untuk mempelajari strategi pemasaran digital dan pengelolaan konten.',
    NULL
FROM kategori k
WHERE k.nama_kategori = 'Marketing'
AND NOT EXISTS (
    SELECT 1 FROM program
    WHERE id = 'seed-magang-marketing'
)
LIMIT 1;


INSERT INTO program (
    id, judul, penyelenggara, kategori_id, jenis,
    mode, durasi, tanggal_mulai, deadline,
    harga, link, deskripsi, gambar
)
SELECT
    'seed-loker-data',
    'Junior Data Analyst',
    'RuangTumbuh Career',
    k.id,
    'Loker',
    'Onsite',
    'Full Time',
    DATE_ADD(CURDATE(), INTERVAL 45 DAY),
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    'Gratis',
    'https://example.com/lowongan-data',
    'Kesempatan berkarier sebagai Junior Data Analyst untuk mengolah dan menganalisis data.',
    NULL
FROM kategori k
WHERE k.nama_kategori = 'Data'
AND NOT EXISTS (
    SELECT 1 FROM program
    WHERE id = 'seed-loker-data'
)
LIMIT 1;
