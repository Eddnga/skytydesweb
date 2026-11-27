<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_entities.php';
header('X-Robots-Tag: noindex, nofollow');
// The dashboard loads the Summernote editor (and jQuery) from jsDelivr, pinned to exact versions.
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css; font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/font/; script-src 'self' https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'", true);
