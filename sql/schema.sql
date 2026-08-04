-- NFC Inventory — Physical-to-Digital State Tracker Database Schema
-- Designed for MySQL PDO with strict prepared statements

CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    markdown_content TEXT NOT NULL,
    rating INT DEFAULT NULL,
    comments TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
    uid VARCHAR(64) PRIMARY KEY,
    slug VARCHAR(128) UNIQUE DEFAULT NULL,
    friendly_name VARCHAR(128) DEFAULT NULL,
    post_id INT UNSIGNED DEFAULT NULL,
    target_url VARCHAR(512) DEFAULT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_post_id (post_id),
    INDEX idx_status (status),
    CONSTRAINT fk_tags_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
