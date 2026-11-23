<?php
declare(strict_types=1);

/** Table definitions (MySQL). Converted on the fly for SQLite during local testing. */
function schema_sql(): array
{
    $t = [];
    $t[] = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'editor',
        active TINYINT(1) NOT NULL DEFAULT 1,
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        attempted_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS throttle (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        k VARCHAR(190) NOT NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS settings (
        k VARCHAR(80) NOT NULL PRIMARY KEY,
        v TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS hero_slides (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        subtitle VARCHAR(300) NULL,
        eyebrow VARCHAR(120) NULL,
        caption VARCHAR(120) NULL,
        image VARCHAR(255) NULL,
        cta_label VARCHAR(60) NULL,
        cta_link VARCHAR(255) NULL,
        sort INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS destinations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        slug VARCHAR(180) NOT NULL UNIQUE,
        region VARCHAR(120) NULL,
        tagline VARCHAR(200) NULL,
        summary TEXT NULL,
        body MEDIUMTEXT NULL,
        best_time VARCHAR(160) NULL,
        coords VARCHAR(60) NULL,
        image VARCHAR(255) NULL,
        sort INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS tours (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(220) NOT NULL UNIQUE,
        destination_id INT UNSIGNED NULL,
        duration_days INT NULL,
        tour_type VARCHAR(80) NULL,
        price_from DECIMAL(12,2) NULL,
        currency VARCHAR(8) NOT NULL DEFAULT 'KES',
        price_note VARCHAR(160) NULL,
        summary TEXT NULL,
        body MEDIUMTEXT NULL,
        highlights TEXT NULL,
        includes TEXT NULL,
        excludes TEXT NULL,
        image VARCHAR(255) NULL,
        featured TINYINT(1) NOT NULL DEFAULT 0,
        sort INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS itinerary_days (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tour_id INT UNSIGNED NOT NULL,
        day_no INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        body TEXT NULL,
        meals VARCHAR(120) NULL,
        stay VARCHAR(160) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(220) NOT NULL,
        slug VARCHAR(240) NOT NULL UNIQUE,
        excerpt TEXT NULL,
        body MEDIUMTEXT NULL,
        image VARCHAR(255) NULL,
        author VARCHAR(120) NULL,
        category VARCHAR(80) NULL,
        published TINYINT(1) NOT NULL DEFAULT 1,
        published_at DATETIME NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS services (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(160) NOT NULL,
        icon VARCHAR(40) NULL,
        summary TEXT NULL,
        body TEXT NULL,
        image VARCHAR(255) NULL,
        sort INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS faqs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(255) NOT NULL,
        answer TEXT NOT NULL,
        category VARCHAR(80) NULL,
        sort INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS gallery (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image VARCHAR(255) NOT NULL,
        caption VARCHAR(200) NULL,
        category VARCHAR(80) NULL,
        sort INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS reviews (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        country VARCHAR(80) NULL,
        rating TINYINT NOT NULL DEFAULT 5,
        title VARCHAR(160) NULL,
        body TEXT NOT NULL,
        tour_id INT UNSIGNED NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'pending',
        ip VARCHAR(45) NULL,
        created_at DATETIME NOT NULL,
        approved_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS leads (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(40) NOT NULL,
        item VARCHAR(220) NULL,
        name VARCHAR(120) NULL,
        travel_date VARCHAR(40) NULL,
        travellers VARCHAR(20) NULL,
        message TEXT NULL,
        page VARCHAR(255) NULL,
        ip VARCHAR(45) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t[] = "CREATE TABLE IF NOT EXISTS audit_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        action VARCHAR(60) NOT NULL,
        entity VARCHAR(60) NULL,
        entity_id INT UNSIGNED NULL,
        ip VARCHAR(45) NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $t = array_merge($t, schema_v2(), schema_v3());
    $t[] = "CREATE INDEX idx_throttle ON throttle (k, created_at)";
    $t[] = "CREATE INDEX idx_login ON login_attempts (email, attempted_at)";
    $t[] = "CREATE INDEX idx_days ON itinerary_days (tour_id, day_no)";
    $t[] = "CREATE INDEX idx_reviews ON reviews (status, created_at)";
    return $t;
}
