<?php
declare(strict_types=1);
/** Opens a published document and counts it. "On request" documents go to WhatsApp instead. */
require __DIR__ . '/includes/bootstrap.php';
$d = row('SELECT * FROM documents WHERE id = ? AND active = 1', [(int) input('id')]);
if (!$d) not_found();
$staff = current_user() !== null; // dashboard users can preview every document
if ($d['access'] === 'request' && !$staff) redirect('wa.php?src=document&item=' . rawurlencode($d['title']));
$path = $d['file'] && preg_match('#^docs/[a-f0-9]{24}\.pdf$#', $d['file']) ? ROOT . '/uploads/' . $d['file'] : null;
if (!$path || !is_file($path)) not_found();
if (!$staff && throttle('doc:' . $d['id'] . ':' . client_ip(), 1, 600)) q('UPDATE documents SET downloads = downloads + 1 WHERE id = ?', [$d['id']]);
$name = slugify($d['title']) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . (input('dl') === '1' ? 'attachment' : 'inline') . '; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:", true);
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;
