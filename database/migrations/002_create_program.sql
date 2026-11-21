CREATE TABLE IF NOT EXISTS program (
    id VARCHAR(50) NOT NULL,
    judul VARCHAR(255) NOT NULL,
    penyelenggara VARCHAR(150) NOT NULL,
    kategori_id INT(11) NOT NULL,
    mode VARCHAR(50) NOT NULL,
    durasi VARCHAR(100) NOT NULL,
    deadline DATE NOT NULL,
    harga VARCHAR(100) NOT NULL,
    link TEXT NULL,
    deskripsi TEXT NULL,

    PRIMARY KEY (id),
    INDEX idx_program_kategori (kategori_id),

    CONSTRAINT fk_program_kategori
        FOREIGN KEY (kategori_id)
        REFERENCES kategori(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);