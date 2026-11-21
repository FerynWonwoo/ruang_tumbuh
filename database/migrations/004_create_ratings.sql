
CREATE TABLE IF NOT EXISTS ratings (
    id VARCHAR(16) NOT NULL,
    username VARCHAR(50) NOT NULL,
    bootcamp VARCHAR(150) NOT NULL,
    rating TINYINT(4) NOT NULL,
    komentar TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected')
        NOT NULL DEFAULT 'pending',
    tanggal DATE NOT NULL DEFAULT (CURRENT_DATE),

    PRIMARY KEY (id),
    INDEX idx_ratings_username (username),
    INDEX idx_ratings_bootcamp (bootcamp),
    INDEX idx_ratings_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;
