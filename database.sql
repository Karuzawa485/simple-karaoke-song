CREATE DATABASE IF NOT EXISTS karaoke_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE karaoke_db;

CREATE TABLE IF NOT EXISTS songs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    artist VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    lyrics MEDIUMTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Keep installations created with the older schema compatible with this app.
ALTER TABLE songs
    MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    MODIFY lyrics MEDIUMTEXT NULL;
