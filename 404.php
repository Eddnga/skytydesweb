<?php
declare(strict_types=1);
if (!defined('ROOT')) require __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
site_header('Page not found', ['solid' => true]);
?>
<section class="sec notfound"><div class="wrap narrow center">
  <p class="eyebrow">404</p><h1 class="h2">This trail went cold.</h1>
  <p class="muted">The page you were looking for has moved or no longer exists.</p>
  <div class="row-btns center"><a class="btn dark" href="<?= url('') ?>">Back to home</a><a class="btn ghost" href="<?= url('tours.php') ?>">Browse tours</a></div>
</div></section>
<?php site_footer();
