CREATE TABLE IF NOT EXISTS bookmarks (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    program_id VARCHAR(50) NULL,
    judul VARCHAR(150) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    penyelenggara VARCHAR(100) NOT NULL DEFAULT '',
    deadline DATE NULL,
    deskripsi TEXT NULL,
    link TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_bookmarks_user (user_id),
    UNIQUE KEY uq_bookmarks_user_program (user_id, program_id),
    CONSTRAINT fk_bookmarks_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;