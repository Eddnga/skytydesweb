<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/schema.php';
require __DIR__ . '/seed.php';

$lock = STORAGE . '/installed.lock';
if (is_file($lock)) {
    http_response_code(403);
    exit('SkyTydes is already installed. For safety, delete the /install folder from your server.');
}

$errors = [];
$done = false;
$dev = isset($_GET['dev']) && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$scheme = is_https() ? 'https' : 'http';
$guessUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(preg_replace('#/install$#', '', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');

if (is_post()) {
    csrf_check();
    $f = fn($k) => trim((string) ($_POST[$k] ?? ''));
    $driver = $dev && $f('driver') === 'sqlite' ? 'sqlite' : 'mysql';
    $cfg = [
        'db' => ['driver' => $driver, 'host' => $f('db_host') ?: 'localhost', 'name' => $f('db_name'), 'user' => $f('db_user'), 'pass' => (string) ($_POST['db_pass'] ?? ''), 'charset' => 'utf8mb4', 'sqlite' => STORAGE . '/dev.sqlite'],
        'base_url' => rtrim($f('base_url'), '/'),
        'env' => $dev ? 'development' : 'production',
        'force_https' => $f('force_https') === '1',
        'app_key' => bin2hex(random_bytes(32)),
        'max_upload_mb' => 8,
    ];
    $name = $f('admin_name');
    $email = strtolower($f('admin_email'));
    $pass = (string) ($_POST['admin_pass'] ?? '');
    if (!filter_var($cfg['base_url'], FILTER_VALIDATE_URL)) $errors[] = 'Enter the full website address, e.g. https://skytydessafaris.com';
    if ($driver === 'mysql' && ($cfg['db']['name'] === '' || $cfg['db']['user'] === '')) $errors[] = 'Enter the database name and user.';
    if ($name === '') $errors[] = 'Enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email for the admin login.';
    if ($p = password_problems($pass)) $errors[] = 'Password: ' . $p;
    if ($pass !== (string) ($_POST['admin_pass2'] ?? '')) $errors[] = 'The two passwords do not match.';

    if (!$errors) {
        try {
            if (!is_dir(STORAGE)) mkdir(STORAGE, 0750, true);
            $GLOBALS['CONFIG'] = $cfg;
            $pdo = db();
            foreach (schema_sql() as $sql) {
                try {
                    $pdo->exec($driver === 'sqlite' ? to_sqlite($sql) : $sql);
                } catch (PDOException $e) {
                    if (stripos($sql, 'CREATE INDEX') === false) throw $e;
                }
            }
            if ((int) val('SELECT COUNT(*) FROM users') > 0) throw new RuntimeException('This database already has users. Use an empty database.');
            seed_settings();
            if ($f('sample') === '1') seed_content();
            seed_v2_defaults();
            set_setting('db_version', (string) DB_VERSION);
            insert('users', ['name' => $name, 'email' => $email, 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'admin', 'active' => 1, 'created_at' => now()]);

            $php = "<?php\n// Written by the SkyTydes installer on " . date('Y-m-d H:i') . ". Keep this file private.\nreturn " . var_export($cfg, true) . ";\n";
            $php = str_replace("'" . STORAGE . "/dev.sqlite'", "__DIR__ . '/storage/dev.sqlite'", $php);
            if (file_put_contents(ROOT . '/config.php', $php) === false) throw new RuntimeException('Could not write config.php. Make the site folder writable, or create config.php by hand from config.sample.php.');
            @chmod(ROOT . '/config.php', 0640);
            file_put_contents($lock, date('c'));
            $done = true;
        } catch (Throwable $e) {
            @unlink(ROOT . '/config.php');
            $errors[] = $e instanceof PDOException ? 'Database error: ' . $e->getMessage() : $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>Install · SkyTydes Safaris</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head><body class="auth">
<main class="auth-card wide">
  <img src="../assets/img/logo-color.svg" alt="SkyTydes Safaris" class="auth-logo">
  <?php if ($done): ?>
    <h1>All set!</h1>
    <p>Your website and dashboard are installed.</p>
    <div class="notice warn"><strong>Important:</strong> delete the <code>/install</code> folder from your server now.</div>
    <p class="row-gap"><a class="btn" href="../admin/login.php">Open the dashboard</a> <a class="btn ghost" href="../">View the website</a></p>
  <?php else: ?>
    <h1>Install SkyTydes</h1>
    <p class="muted">Create a MySQL database and user in cPanel first, then fill this in.</p>
    <?php foreach ($errors as $e): ?><div class="notice error"><?= h($e) ?></div><?php endforeach; ?>
    <form method="post" class="form" autocomplete="off">
      <?= csrf_field() ?>
      <h2>Website</h2>
      <label>Website address<input name="base_url" required value="<?= h($_POST['base_url'] ?? $guessUrl) ?>"></label>
      <label class="check"><input type="checkbox" name="force_https" value="1" <?= is_https() ? 'checked' : '' ?>> Always use HTTPS (tick once SSL is active)</label>
      <h2>Database</h2>
      <?php if ($dev): ?>
        <label>Driver<select name="driver"><option value="sqlite">SQLite (local testing)</option><option value="mysql">MySQL</option></select></label>
      <?php endif; ?>
      <div class="grid2">
        <label>Host<input name="db_host" value="<?= h($_POST['db_host'] ?? 'localhost') ?>"></label>
        <label>Database name<input name="db_name" value="<?= h($_POST['db_name'] ?? '') ?>"></label>
        <label>Database user<input name="db_user" value="<?= h($_POST['db_user'] ?? '') ?>"></label>
        <label>Database password<input type="password" name="db_pass"></label>
      </div>
      <h2>Your admin account</h2>
      <div class="grid2">
        <label>Your name<input name="admin_name" required value="<?= h($_POST['admin_name'] ?? '') ?>"></label>
        <label>Email (used to log in)<input type="email" name="admin_email" required value="<?= h($_POST['admin_email'] ?? '') ?>"></label>
        <label>Password<input type="password" name="admin_pass" required minlength="10"></label>
        <label>Repeat password<input type="password" name="admin_pass2" required minlength="10"></label>
      </div>
      <p class="hint">At least 10 characters, with letters and numbers.</p>
      <label class="check"><input type="checkbox" name="sample" value="1" checked> Add starter content (destinations, tours, FAQs, blog posts) that you can edit</label>
      <button class="btn">Install</button>
    </form>
  <?php endif; ?>
</main>
</body></html>
