<?php
declare(strict_types=1);

/**
 * Securely store an uploaded image.
 * - checks the upload error, size and the real file type (not the extension)
 * - re-encodes the image with GD, which drops any hidden scripts and EXIF/GPS data
 * - saves a large copy (max 2000px) and a small copy (max 760px) under a random name
 * Returns the stored path relative to /uploads, or throws RuntimeException with a friendly message.
 */
function store_image(array $f): string
{
    global $CONFIG;
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $codes = [UPLOAD_ERR_INI_SIZE => 'The image is larger than the server allows.', UPLOAD_ERR_FORM_SIZE => 'The image is too large.', UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Try again.', UPLOAD_ERR_NO_FILE => 'No image was chosen.'];
        throw new RuntimeException($codes[$f['error']] ?? 'The upload failed.');
    }
    if (!is_uploaded_file($f['tmp_name'])) throw new RuntimeException('Invalid upload.');
    $max = (int) ($CONFIG['max_upload_mb'] ?? 8) * 1024 * 1024;
    if ($f['size'] > $max) throw new RuntimeException('Images must be under ' . ($CONFIG['max_upload_mb'] ?? 8) . ' MB.');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG or WebP images are allowed.');
    $info = @getimagesize($f['tmp_name']);
    if (!$info || $info[0] < 10 || $info[1] < 10 || $info[0] * $info[1] > 40000000) throw new RuntimeException('That file is not a usable image.');

    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($f['tmp_name']),
        'image/png'  => @imagecreatefrompng($f['tmp_name']),
        'image/webp' => @imagecreatefromwebp($f['tmp_name']),
    };
    if (!$src) throw new RuntimeException('That image could not be read.');
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($f['tmp_name']);
        $o = $exif['Orientation'] ?? 1;
        if ($o == 3) $src = imagerotate($src, 180, 0);
        elseif ($o == 6) $src = imagerotate($src, -90, 0);
        elseif ($o == 8) $src = imagerotate($src, 90, 0);
    }

    $keepPng = $mime === 'image/png' && image_has_alpha($src);
    $ext = $keepPng ? 'png' : 'jpg';
    $dir = date('Y/m');
    $abs = ROOT . '/uploads/' . $dir;
    if (!is_dir($abs) && !mkdir($abs, 0755, true)) throw new RuntimeException('The uploads folder is not writable.');
    $name = bin2hex(random_bytes(12));

    save_resized($src, "$abs/$name.$ext", 2000, $keepPng);
    save_resized($src, "$abs/{$name}_sm.$ext", 760, $keepPng);
    imagedestroy($src);
    return "$dir/$name.$ext";
}

function image_has_alpha($im): bool
{
    $w = imagesx($im); $h = imagesy($im);
    for ($i = 0; $i < 40; $i++) {
        $c = imagecolorat($im, random_int(0, $w - 1), random_int(0, $h - 1));
        if ((($c >> 24) & 0x7F) > 0) return true;
    }
    return false;
}

function save_resized($src, string $path, int $maxW, bool $png): void
{
    $w = imagesx($src); $h = imagesy($src);
    $s = min(1, $maxW / $w);
    $nw = max(1, (int) round($w * $s)); $nh = max(1, (int) round($h * $s));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($png) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    } else {
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $png ? imagepng($dst, $path, 7) : imagejpeg($dst, $path, 84);
    imagedestroy($dst);
}

/** Securely store an uploaded PDF (company profile, rates, brochure). Returns [path, size]. */
function store_document(array $f): array
{
    $max = 25 * 1024 * 1024;
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $codes = [UPLOAD_ERR_INI_SIZE => 'The file is larger than your hosting allows (ask your host to raise upload_max_filesize).', UPLOAD_ERR_FORM_SIZE => 'The file is too large.', UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Try again.', UPLOAD_ERR_NO_FILE => 'No file was chosen.'];
        throw new RuntimeException($codes[$f['error']] ?? 'The upload failed.');
    }
    if (!is_uploaded_file($f['tmp_name'])) throw new RuntimeException('Invalid upload.');
    if ($f['size'] > $max) throw new RuntimeException('PDFs must be under 25 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $head = (string) file_get_contents($f['tmp_name'], false, null, 0, 5);
    if ($mime !== 'application/pdf' || $head !== '%PDF-') throw new RuntimeException('Only PDF files can be uploaded here.');
    $dir = ROOT . '/uploads/docs';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('The uploads folder is not writable.');
    $name = bin2hex(random_bytes(12)) . '.pdf';
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) throw new RuntimeException('Could not save the file.');
    @chmod("$dir/$name", 0644);
    return ['docs/' . $name, (int) $f['size']];
}

function delete_document(?string $rel): void
{
    if (!$rel || !preg_match('#^docs/[a-f0-9]{24}\.pdf$#', $rel)) return;
    $abs = ROOT . '/uploads/' . $rel;
    if (is_file($abs)) @unlink($abs);
}

function human_size(?int $b): string
{
    if (!$b) return '';
    return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB';
}

function delete_image(?string $rel): void
{
    if (!$rel || strpos($rel, 'seed/') === 0 || strpos($rel, '..') !== false) return;
    foreach ([$rel, preg_replace('/\.(\w+)$/', '_sm.$1', $rel)] as $p) {
        $abs = ROOT . '/uploads/' . $p;
        if (is_file($abs)) @unlink($abs);
    }
}
