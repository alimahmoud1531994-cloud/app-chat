CREATE DATABASE IF NOT EXISTS messenger_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE messenger_pro;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    bio TEXT NULL,
    status_message VARCHAR(160) NULL,
    avatar VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    last_seen DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_users_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    body TEXT NULL,
    attachment_name VARCHAR(255) NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_type VARCHAR(160) NULL,
    attachment_is_image TINYINT(1) NOT NULL DEFAULT 0,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_messages_pair (sender_id, receiver_id, created_at),
    INDEX idx_messages_receiver_read (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (full_name, username, email, password_hash, bio, status_message, created_at, updated_at, last_seen) VALUES
('Ahmed Ali', 'ahmed', 'ahmed@example.com', '$2y$12$SFiaZTC1nK1VPJTgheoBmOrSZiGttwsquKOR7xbieS/zZAZxQuJGG', 'مطور واجهات وتجارب استخدام.', 'متاح الآن', NOW(), NOW(), NOW()),
('Sara Mohamed', 'sara', 'sara@example.com', '$2y$12$LW5dz2gBzYrskfM1/ZJlVOqlj345YClml8Qo4TUmi9JGSpdO8Qg8K', 'مصممة واجهات وتهتم بالشكل النهائي.', 'في وضع الإبداع', NOW(), NOW(), NOW()),
('Omar Khaled', 'omar', 'omar@example.com', '$2y$12$PaZev2EgJPIgoEJu5iZYDeYE3.fyXiwGhy.eadX8ZVZpnANlvts.q', 'مهتم بالبرمجة الخلفية وبناء الأنظمة.', 'أراجع المشروع', NOW(), NOW(), NOW());

INSERT INTO messages (sender_id, receiver_id, body, attachment_name, attachment_path, attachment_type, attachment_is_image, is_read, created_at) VALUES
(1, 2, 'أهلاً سارة، هذا نموذج أولي لتطبيق المحادثة.', NULL, NULL, NULL, 0, 1, DATE_SUB(NOW(), INTERVAL 40 MINUTE)),
(2, 1, 'الشكل جميل جداً، أضفنا أيضاً صفحة بروفايل جاهزة.', NULL, NULL, NULL, 0, 1, DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
(3, 1, 'تم تجهيز قاعدة البيانات والملفات المرفقة.', NULL, NULL, NULL, 0, 0, DATE_SUB(NOW(), INTERVAL 18 MINUTE));
