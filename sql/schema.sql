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
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tag_activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tag_uid VARCHAR(64) NOT NULL,
    action_type VARCHAR(32) NOT NULL,
    old_target_url VARCHAR(512) DEFAULT NULL,
    new_target_url VARCHAR(512) DEFAULT NULL,
    old_friendly_name VARCHAR(128) DEFAULT NULL,
    new_friendly_name VARCHAR(128) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tag_uid (tag_uid),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
