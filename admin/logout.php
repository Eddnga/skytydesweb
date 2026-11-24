<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
if (is_post()) {
    csrf_check();
    audit('logout');
    logout();
}
redirect(''); // back to the website home
