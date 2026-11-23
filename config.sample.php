<?php
/**
 * SkyTydes Safaris — site configuration.
 * The installer (/install/) writes config.php from this template.
 * You can also copy this file to config.php and fill it in by hand.
 */
return [
    // Database (cPanel → MySQL Databases)
    'db' => [
        'driver'   => 'mysql',          // 'mysql' on your host
        'host'     => 'localhost',
        'name'     => 'cpaneluser_skytydes',
        'user'     => 'cpaneluser_skyuser',
        'pass'     => 'change-me',
        'charset'  => 'utf8mb4',
        'sqlite'   => __DIR__ . '/storage/dev.sqlite', // local testing only
    ],

    // Full address of the site, no trailing slash, e.g. https://skytydessafaris.com
    'base_url' => 'https://skytydessafaris.com',

    // 'production' hides errors from visitors and logs them to storage/logs
    'env' => 'production',

    // Force HTTPS redirects and secure cookies (keep true once SSL is active)
    'force_https' => true,

    // Random secret created by the installer (used for CSRF/session hardening)
    'app_key' => 'replace-with-random-64-chars',

    // Upload limits
    'max_upload_mb' => 8,
];
