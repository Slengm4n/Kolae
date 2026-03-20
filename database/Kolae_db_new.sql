-- ==========================================================
-- 1. TABELAS DE GEOGRAFIA E ENDEREÇO (Hierarquia)
-- ==========================================================

CREATE TABLE country (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL
);

CREATE TABLE state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_country INT NOT NULL,
    name VARCHAR(255) UNIQUE NOT NULL,
    FOREIGN KEY (id_country) REFERENCES country(id)
);

CREATE TABLE city (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_state INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_state) REFERENCES state(id)
);

CREATE TABLE neighborhood (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_city INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_city) REFERENCES city(id)
);

CREATE TABLE street (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_neighborhood INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_neighborhood) REFERENCES neighborhood(id)
);

CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cep VARCHAR(9),
    id_street INT NOT NULL,
    number VARCHAR(50),
    complement VARCHAR(255),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_street) REFERENCES street(id)
);

-- ==========================================================
-- 2. TABELAS DE USUÁRIOS E AUTENTICAÇÃO
-- ==========================================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    birthdate DATE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'owner', 'player') DEFAULT 'player',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    avatar_path VARCHAR(255),
    force_password_change TINYINT(1) DEFAULT 0,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email) REFERENCES users(email) ON DELETE CASCADE
);

CREATE TABLE session (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload LONGTEXT,
    last_activity INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- 3. TABELAS DE LOCAIS (VENUES) E ESPORTES
-- ==========================================================

CREATE TABLE venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    average_price_per_hour DECIMAL(10,2),
    court_capacity INT,
    has_leisure_area TINYINT(1) DEFAULT 0,
    leisure_area_capacity INT,
    floor_type ENUM('grama natural', 'grama sintetica', 'cimento', 'madeira', 'outro') NULL DEFAULT NULL,
    has_lighting TINYINT(1) DEFAULT 0,
    is_covered TINYINT(1) DEFAULT 0,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id)
);

CREATE TABLE venues_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
);

CREATE TABLE sports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    description TEXT,
    icon_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================================
-- 4. TABELA DE PARTIDAS (MATCHES)
-- ==========================================================

CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    sport_id INT NOT NULL,
    creator_user_id INT NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'canceled') DEFAULT 'scheduled',
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(id),
    FOREIGN KEY (sport_id) REFERENCES sports(id),
    FOREIGN KEY (creator_user_id) REFERENCES users(id)
);