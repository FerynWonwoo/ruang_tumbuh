
CREATE TABLE IF NOT EXISTS kategori (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT NULL,
    ikon VARCHAR(100) NULL,
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_kategori_user (user_id),

    CONSTRAINT fk_kategori_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
);
