<?php
// Local development only: lets `php -S localhost:8000 router.php` behave like the live
// server's .htaccess (clean URLs + blocked private files). Never used on the live site.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(403);
    exit;
}
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$rel = ltrim($path, '/');
$deny = ['#(^|/)\.(?!well-known/)#', '#^(includes|storage)(/|$)#', '#^install/(schema|seed)\.php$#', '#^uploads/docs/#',
    '#^uploads/.*\.(php\d?|phtml|phar|pl|py|cgi|sh|html?|js|svg)$#i', '#^(config|config\.sample|router)\.php$#', '#\.(sqlite|log|lock|md|ya?ml|sql|zip|bak|env)$#i'];
foreach ($deny as $re) {
    if (preg_match($re, $rel)) {
        http_response_code(403);
        exit('Forbidden');
    }
}
$routes = [
    '#^sitemap\.xml$#' => 'sitemap.php',
    '#^tours/([a-z0-9-]+)/?$#' => 'tour.php?slug=$1',
    '#^destinations/([a-z0-9-]+)/?$#' => 'destination.php?slug=$1',
    '#^blog/([a-z0-9-]+)/?$#' => 'post.php?slug=$1',
    '#^sections/([a-z0-9_]+)/([a-z0-9-]+)/?$#' => 'section.php?type=$1&slug=$2',
    '#^sections/([a-z0-9_]+)/?$#' => 'section.php?type=$1',
    '#^(tours|destinations|offers|gallery|blog|reviews|about|contact|services|faqs|documents)/?$#' => '$1.php',
];
foreach ($routes as $re => $to) {
    if (preg_match($re, $rel)) {
        $target = preg_replace($re, $to, $rel);
        [$file, $qs] = array_pad(explode('?', $target, 2), 2, '');
        if ($qs !== '') {
            parse_str($qs, $extra);
            $_GET = $extra + $_GET;
        }
        $_SERVER['SCRIPT_NAME'] = '/' . $file;
        chdir(__DIR__);
        require __DIR__ . '/' . $file;
        return true;
    }
}
$file = __DIR__ . '/' . $rel;
if ($rel === '' || is_dir($file)) {
    $file = rtrim($file, '/') . '/index.php';
    if (is_file($file)) {
        $_SERVER['SCRIPT_NAME'] = '/' . ltrim(substr($file, strlen(__DIR__)), '/');
        chdir(dirname($file));
        require $file;
        return true;
    }
}
if (is_file($file)) return false; // let the built-in server send the file
require __DIR__ . '/404.php';
return true;
