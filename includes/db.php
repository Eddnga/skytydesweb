<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    global $CONFIG;
    if ($pdo) return $pdo;
    $c = $CONFIG['db'];
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        $pdo = new PDO('sqlite:' . $c['sqlite'], null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $c['host'], $c['name'], $c['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}
function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}
function val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}
function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Insert a row from an associative array; returns new id. Column names are whitelisted by callers. */
function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    foreach ($cols as $c) if (!preg_match('/^[a-z_]+$/', $c)) throw new InvalidArgumentException('bad column');
    $sql = "INSERT INTO $table (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($data));
    return (int) db()->lastInsertId();
}
function update(string $table, int $id, array $data): void
{
    $sets = [];
    foreach (array_keys($data) as $c) {
        if (!preg_match('/^[a-z_]+$/', $c)) throw new InvalidArgumentException('bad column');
        $sets[] = "$c = ?";
    }
    q("UPDATE $table SET " . implode(',', $sets) . ' WHERE id = ?', [...array_values($data), $id]);
}
