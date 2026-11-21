-- Data profil tambahan pengguna (nama dan email tetap di tabel users).
-- Satu baris per pengguna. Dihapus otomatis jika akun pengguna dihapus.
CREATE TABLE IF NOT EXISTS profil (
    user_id INT(11) NOT NULL,
    telepon VARCHAR(20) NOT NULL DEFAULT '',
    domisili VARCHAR(120) NOT NULL DEFAULT '',
    institusi VARCHAR(150) NOT NULL DEFAULT '',
    status VARCHAR(30) NOT NULL DEFAULT 'Mahasiswa',
    bio TEXT NULL,
    bidang VARCHAR(500) NOT NULL DEFAULT '[]',
    kategori VARCHAR(300) NOT NULL DEFAULT '[]',
    foto VARCHAR(100) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),

    CONSTRAINT fk_profil_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);
