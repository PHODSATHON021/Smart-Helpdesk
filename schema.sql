-- Smart IT Helpdesk : ตามภาพ ER Diagram (6 ตาราง)
CREATE DATABASE IF NOT EXISTS smart_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_helpdesk;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ratings, status_logs, comments, tickets, categories, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','technician','admin') NOT NULL DEFAULT 'user',
  line_user_id  VARCHAR(64) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE tickets (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  category_id   INT UNSIGNED NOT NULL,
  technician_id INT UNSIGNED NULL,
  title         VARCHAR(150) NOT NULL,
  description   TEXT NOT NULL,
  status        ENUM('open','assigned','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  priority      ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  resolved_at   DATETIME NULL,
  closed_at     DATETIME NULL,
  INDEX idx_status (status),
  INDEX idx_user (user_id),
  INDEX idx_tech (technician_id),
  CONSTRAINT fk_t_user FOREIGN KEY (user_id)       REFERENCES users(id),
  CONSTRAINT fk_t_cat  FOREIGN KEY (category_id)   REFERENCES categories(id),
  CONSTRAINT fk_t_tech FOREIGN KEY (technician_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE comments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id  INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  body       TEXT NOT NULL,
  image_path VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_c_ticket (ticket_id),
  CONSTRAINT fk_c_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_c_user   FOREIGN KEY (user_id)   REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE status_logs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id   INT UNSIGNED NOT NULL,
  changed_by  INT UNSIGNED NOT NULL,
  from_status ENUM('open','assigned','in_progress','resolved','closed') NULL,
  to_status   ENUM('open','assigned','in_progress','resolved','closed') NOT NULL,
  note        TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_l_ticket (ticket_id),
  CONSTRAINT fk_l_ticket FOREIGN KEY (ticket_id)  REFERENCES tickets(id) ON DELETE CASCADE,
  CONSTRAINT fk_l_user   FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ER: TICKETS ||--o| RATINGS (1 ticket ให้คะแนนได้ครั้งเดียว) จึงใส่ UNIQUE
CREATE TABLE ratings (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id  INT UNSIGNED NOT NULL UNIQUE,
  score      TINYINT UNSIGNED NOT NULL,
  feedback   TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_score CHECK (score BETWEEN 1 AND 5),
  CONSTRAINT fk_r_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB;
