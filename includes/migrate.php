<?php
declare(strict_types=1);

const DB_VERSION = 8;

/** Tables added in version 2 (offers, hosts, newsletter). */
function schema_v2(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS offers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            tag VARCHAR(80) NULL,
            summary TEXT NULL,
            terms TEXT NULL,
            image VARCHAR(255) NULL,
            tour_id INT UNSIGNED NULL,
            valid_from DATETIME NULL,
            valid_until DATETIME NULL,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS team (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            role VARCHAR(120) NULL,
            bio TEXT NULL,
            image VARCHAR(255) NULL,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS subscribers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            name VARCHAR(120) NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

/** Version 3: documents library (company profile, rates, brochures). */
function schema_v3(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS documents (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            category VARCHAR(80) NULL,
            summary TEXT NULL,
            file VARCHAR(255) NULL,
            file_size INT UNSIGNED NULL,
            image VARCHAR(255) NULL,
            access VARCHAR(12) NOT NULL DEFAULT 'public',
            featured TINYINT(1) NOT NULL DEFAULT 0,
            downloads INT UNSIGNED NOT NULL DEFAULT 0,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

/** Version 4: where a review came from (website, Google, TripAdvisor, Facebook). */
function add_review_source_columns(): void
{
    foreach (["ALTER TABLE reviews ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'website'", "ALTER TABLE reviews ADD COLUMN source_url VARCHAR(255) NULL"] as $sql) {
        try {
            db()->exec($sql);
        } catch (Throwable $e) {
            // column already exists
        }
    }
}

/** Version 5: super admin, visitor insights, fuller audit trail, custom sections. */
function schema_v5(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS page_views (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            path VARCHAR(255) NOT NULL,
            title VARCHAR(160) NULL,
            ip VARCHAR(45) NULL,
            visitor CHAR(16) NULL,
            referrer VARCHAR(120) NULL,
            device VARCHAR(12) NULL,
            browser VARCHAR(20) NULL,
            ms INT UNSIGNED NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE INDEX idx_views_time ON page_views (created_at)",
        "CREATE TABLE IF NOT EXISTS ip_geo (
            ip VARCHAR(45) NOT NULL PRIMARY KEY,
            country VARCHAR(80) NULL,
            country_code CHAR(2) NULL,
            region VARCHAR(80) NULL,
            city VARCHAR(80) NULL,
            org VARCHAR(120) NULL,
            looked_up_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS custom_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(40) NOT NULL UNIQUE,
            label VARCHAR(80) NOT NULL,
            one_label VARCHAR(80) NOT NULL,
            intro TEXT NULL,
            fields MEDIUMTEXT NOT NULL,
            in_menu TINYINT(1) NOT NULL DEFAULT 0,
            public TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}
function upgrade_v5(): void
{
    global $CONFIG;
    $sqlite = ($CONFIG['db']['driver'] ?? 'mysql') === 'sqlite';
    foreach (schema_v5() as $sql) {
        try {
            db()->exec($sqlite ? to_sqlite($sql) : $sql);
        } catch (Throwable $e) {
            // index already exists
        }
    }
    try {
        db()->exec('ALTER TABLE audit_log ADD COLUMN details TEXT NULL');
    } catch (Throwable $e) {
        // column already exists
    }
    // the first administrator becomes the super admin
    if (!(int) val("SELECT COUNT(*) FROM users WHERE role = 'superadmin'")) {
        $first = val("SELECT id FROM users WHERE role = 'admin' AND active = 1 ORDER BY id LIMIT 1");
        if ($first) q("UPDATE users SET role = 'superadmin' WHERE id = ?", [(int) $first]);
    }
}

/** Version 6: optional search-engine title & description on tours, destinations and blog posts. */
function upgrade_v6(): void
{
    foreach (['tours', 'destinations', 'posts'] as $t) {
        foreach (["ALTER TABLE $t ADD COLUMN seo_title VARCHAR(255) NULL", "ALTER TABLE $t ADD COLUMN seo_description TEXT NULL"] as $sql) {
            try {
                db()->exec($sql);
            } catch (Throwable $e) {
                // column already exists
            }
        }
    }
}

/** Version 7: email log and "welcome email sent" on subscribers. */
function upgrade_v7(): void
{
    global $CONFIG;
    $sql = "CREATE TABLE IF NOT EXISTS email_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        to_email VARCHAR(190) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        template VARCHAR(40) NULL,
        status VARCHAR(10) NOT NULL,
        error VARCHAR(300) NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    try {
        db()->exec(($CONFIG['db']['driver'] ?? 'mysql') === 'sqlite' ? to_sqlite($sql) : $sql);
    } catch (Throwable $e) {
    }
    try {
        db()->exec('ALTER TABLE subscribers ADD COLUMN welcomed_at DATETIME NULL');
    } catch (Throwable $e) {
    }
}

/** Version 8: email address on WhatsApp leads. */
function upgrade_v8(): void
{
    try {
        db()->exec('ALTER TABLE leads ADD COLUMN email VARCHAR(190) NULL');
    } catch (Throwable $e) {
    }
}

function to_sqlite(string $sql): string
{
    $sql = preg_replace('/INT UNSIGNED AUTO_INCREMENT PRIMARY KEY/', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    $sql = preg_replace('/\)\s*ENGINE=.*$/s', ')', $sql);
    $sql = str_replace(['MEDIUMTEXT', 'TINYINT(1)', 'TINYINT', 'INT UNSIGNED'], ['TEXT', 'INTEGER', 'INTEGER', 'INTEGER'], $sql);
    $sql = preg_replace('/CREATE INDEX (\w+)/', 'CREATE INDEX IF NOT EXISTS $1', $sql);
    return $sql;
}

/** Brings an existing database up to date (runs once, after you upload a new version). */
function maybe_migrate(): void
{
    global $CONFIG;
    if (!is_file(ROOT . '/config.php')) return;
    try {
        $v = (int) (settings()['db_version'] ?? 1);
        if ($v >= DB_VERSION) return;
        $sqlite = ($CONFIG['db']['driver'] ?? 'mysql') === 'sqlite';
        if ($v < 2) {
            foreach (schema_v2() as $sql) db()->exec($sqlite ? to_sqlite($sql) : $sql);
            seed_v2_defaults();
        }
        if ($v < 3) foreach (schema_v3() as $sql) db()->exec($sqlite ? to_sqlite($sql) : $sql);
        if ($v < 4) add_review_source_columns();
        if ($v < 5) upgrade_v5();
        if ($v < 6) upgrade_v6();
        if ($v < 7) upgrade_v7();
        if ($v < 8) upgrade_v8();
        set_setting('db_version', (string) DB_VERSION);
    } catch (Throwable $e) {
        error_log('migration failed: ' . $e->getMessage());
    }
}

/** Starter values for the new sections (only filled if empty). */
function seed_v2_defaults(): void
{
    $defaults = [
        'ways' => "Group departures|Join a small group on set dates — the easiest way to safari on a budget.|tours.php\nPrivate safaris|Your own vehicle and guide, on your dates, at your pace.|tours.php\nTailor-made journeys|Tell us your dream trip and we'll design it from scratch.|contact.php",
        'credentials' => '',
        'impact_title' => 'Travel that gives *back*',
        'impact_body' => "We work with local guides, drivers and community-run experiences, and we follow park rules that keep wildlife wild. Every trip is planned to leave the places we love better than we found them.",
        'newsletter_title' => 'Join the SkyTydes *family*',
        'newsletter_body' => 'Special offers, travel tips and new trips — a few times a month, never spam.',
        'offers_intro' => 'Limited-time deals on our favourite trips. Offers depend on your travel dates — message us on WhatsApp to check availability.',
    ];
    foreach ($defaults as $k => $v) if (setting($k) === '' && !array_key_exists($k, settings())) set_setting($k, $v);
    if (!(int) val('SELECT COUNT(*) FROM team')) {
        insert('team', ['name' => 'Samuel Mwangi', 'role' => 'Director', 'bio' => null, 'sort' => 0, 'active' => 1]);
        insert('team', ['name' => 'Susan Muthoni', 'role' => 'Director', 'bio' => null, 'sort' => 1, 'active' => 1]);
    }
    if (!(int) val('SELECT COUNT(*) FROM offers')) {
        $tid = val('SELECT id FROM tours ORDER BY id LIMIT 1');
        insert('offers', ['title' => '[Example] Kids travel free', 'tag' => 'Kids stay free', 'summary' => 'Children under 12 sharing with two adults travel free on selected dates. Edit or delete this example before publishing.', 'terms' => "Valid for children under 12 sharing a room with two paying adults.\nSubject to availability on your travel dates.", 'image' => 'seed/amboseli.jpg', 'tour_id' => $tid ?: null, 'valid_until' => date('Y-m-d 23:59:59', strtotime('+3 months')), 'sort' => 0, 'active' => 0]);
        insert('offers', ['title' => '[Example] Book early, save more', 'tag' => '10% off', 'summary' => 'Confirm your migration safari early and save. Edit or delete this example before publishing.', 'terms' => "Deposit required within 7 days of confirmation.\nNot combinable with other offers.", 'image' => 'seed/mara.jpg', 'tour_id' => null, 'valid_until' => date('Y-m-d 23:59:59', strtotime('+2 months')), 'sort' => 1, 'active' => 0]);
    }
}
