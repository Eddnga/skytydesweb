<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
if (is_post()) {
    csrf_check();
    audit('logout');
    logout();
}
redirect('admin/login.php');
