<?php
declare(strict_types=1);

/**
 * Custom content sections created by the super admin (e.g. "Lodges", "Vehicles").
 * Each section gets its own database table (cx_<slug>) and plugs into the normal
 * dashboard editor, plus public pages at /sections/<slug>.
 */

const SECTION_FIELD_TYPES = [
    'text' => 'Short text',
    'textarea' => 'Paragraph',
    'md' => 'Rich text (editor)',
    'lines' => 'List (one per line)',
    'number' => 'Number',
    'money' => 'Price',
    'image' => 'Image',
    'date_opt' => 'Date',
    'bool' => 'Yes / no',
    'select' => 'Choice (dropdown)',
];

function section_sql_type(string $t): string
{
    return [
        'text' => 'VARCHAR(255) NULL', 'textarea' => 'TEXT NULL', 'lines' => 'TEXT NULL', 'md' => 'MEDIUMTEXT NULL',
        'number' => 'INT NULL', 'money' => 'DECIMAL(12,2) NULL', 'image' => 'VARCHAR(255) NULL',
        'date_opt' => 'DATETIME NULL', 'bool' => 'TINYINT(1) NOT NULL DEFAULT 0', 'select' => 'VARCHAR(120) NULL',
    ][$t] ?? 'TEXT NULL';
}

function valid_key(string $k): bool
{
    return (bool) preg_match('/^[a-z][a-z0-9_]{1,29}$/', $k);
}

/** All sections, cached per request. Returns [] before the v5 update has run. */
function custom_types(): array
{
    static $c = null;
    if ($c !== null) return $c;
    $c = [];
    try {
        foreach (rows('SELECT * FROM custom_types ORDER BY label') as $t) {
            $t['fields'] = json_decode((string) $t['fields'], true) ?: [];
            $c[$t['slug']] = $t;
        }
    } catch (Throwable $e) {
    }
    return $c;
}
/** Column that stores a field. The first image field uses `image` so lists show a thumbnail. */
function section_col(array $type, array $f): string
{
    if (!empty($f['col'])) return $f['col']; // fixed when the field was created
    foreach ($type['fields'] as $x) {
        if ($x['type'] === 'image') return $x['key'] === $f['key'] ? 'image' : 'c_' . $f['key'];
    }
    return 'c_' . $f['key'];
}

/** Turns a section into the same shape as the built-in content types in admin/_entities.php. */
function section_entity(array $t): array
{
    $fields = [['title', 'text', $t['one_label'] . ' name', ['required' => true]]];
    $list = [];
    foreach ($t['fields'] as $f) {
        $col = section_col($t, $f);
        $o = [];
        if (!empty($f['required'])) $o['required'] = true;
        if (!empty($f['hint'])) $o['hint'] = $f['hint'];
        if ($f['type'] === 'select') {
            $opts = array_values(array_filter(array_map('trim', explode(',', (string) ($f['options'] ?? '')))));
            $o['options'] = array_combine($opts, $opts) ?: ['' => '—'];
            $o['default'] = $opts[0] ?? '';
        }
        if ($f['type'] === 'date_opt') $o['end'] = false;
        $fields[] = [$col, $f['type'], $f['label'], $o];
        if ($col === 'image') $list = ['image' => 'Image'] + $list;
    }
    $list['title'] = $t['one_label'];
    foreach ($t['fields'] as $f) {
        if (count($list) >= 4) break;
        if (in_array($f['type'], ['text', 'select', 'number', 'money'], true)) $list[section_col($t, $f)] = $f['label'];
    }
    $list['active'] = 'Status';
    $fields[] = ['sort', 'number', 'Display order', ['default' => 0]];
    $fields[] = ['active', 'bool', 'Show on website', ['default' => 1]];
    return [
        'table' => 'cx_' . $t['slug'], 'label' => $t['label'], 'one' => mb_strtolower($t['one_label']), 'icon' => 'grid', 'title' => 'title',
        'slug' => true, 'timestamps' => true, 'order' => 'sort ASC, id DESC', 'custom' => true,
        'view' => 'section.php?type=' . $t['slug'] . '&slug=',
        'help' => $t['intro'] ? excerpt((string) $t['intro'], 200) : 'A content section you created in Content sections.',
        'list' => $list, 'fields' => $fields,
    ];
}

function create_section_table(array $t): void
{
    global $CONFIG;
    $cols = [];
    foreach ($t['fields'] as $f) $cols[] = section_col($t, $f) . ' ' . section_sql_type($f['type']);
    $sql = 'CREATE TABLE IF NOT EXISTS cx_' . $t['slug'] . ' (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        ' . ($cols ? implode(",\n        ", $cols) . ',' : '') . '
        sort INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    db()->exec(($CONFIG['db']['driver'] ?? 'mysql') === 'sqlite' ? to_sqlite($sql) : $sql);
}

function add_section_column(array $t, array $f): void
{
    db()->exec('ALTER TABLE cx_' . $t['slug'] . ' ADD COLUMN ' . section_col($t, $f) . ' ' . section_sql_type($f['type']));
}

/** Sections that should appear in the website menu. */
function menu_sections(): array
{
    return array_filter(custom_types(), fn($t) => $t['public'] && $t['in_menu']);
}
