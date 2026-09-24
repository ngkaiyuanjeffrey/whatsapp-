CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator') NOT NULL DEFAULT 'operator',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE workers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_heartbeat_at DATETIME NULL,
    whatsapp_status ENUM('unknown', 'qr_required', 'connected', 'disconnected') NOT NULL DEFAULT 'unknown',
    current_job_id BIGINT UNSIGNED NULL,
    worker_version VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_workers_heartbeat (last_heartbeat_at, is_active)
) ENGINE=InnoDB;

CREATE TABLE contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(32) NOT NULL UNIQUE,
    company VARCHAR(150) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    message_template TEXT NOT NULL,
    status ENUM('draft', 'queued', 'running', 'paused', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE message_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NULL,
    contact_id BIGINT UNSIGNED NOT NULL,
    rendered_message TEXT NOT NULL,
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts INT UNSIGNED NOT NULL DEFAULT 3,
    worker_id BIGINT UNSIGNED NULL,
    claimed_at DATETIME NULL,
    scheduled_at DATETIME NULL,
    next_attempt_at DATETIME NULL,
    last_error VARCHAR(1000) NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id),
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE SET NULL,
    INDEX idx_jobs_claim (status, next_attempt_at, scheduled_at),
    INDEX idx_jobs_worker (worker_id, status, claimed_at)
) ENGINE=InnoDB;
