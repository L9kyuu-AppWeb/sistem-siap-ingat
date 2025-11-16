CREATE TABLE murid (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    no_hp VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL,
    tahun_ajaran VARCHAR(10) NOT NULL,
    pj_id INT NOT NULL,             -- murid yang jadi PJ kelas
    token VARCHAR(20) UNIQUE NOT NULL, -- token untuk murid masuk kelas
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pj_id) REFERENCES murid(id)
);


CREATE TABLE murid_kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    murid_id INT NOT NULL,
    kelas_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (murid_id) REFERENCES murid(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    UNIQUE KEY unique_murid_kelas (murid_id, kelas_id)
);


CREATE TABLE reminder_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
);


CREATE TABLE reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NULL,   -- NULL = reminder global
    category_id INT NOT NULL,
    deskripsi TEXT NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    FOREIGN KEY (category_id) REFERENCES reminder_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);


CREATE TABLE murid_kategori_preference (
    id INT AUTO_INCREMENT PRIMARY KEY,
    murid_id INT NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (murid_id) REFERENCES murid(id),
    FOREIGN KEY (category_id) REFERENCES reminder_categories(id)
);