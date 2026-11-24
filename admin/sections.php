<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$me = require_role('superadmin');

$types = custom_types();
$reserved = ['tours', 'tour', 'destinations', 'destination', 'blog', 'post', 'posts', 'offers', 'gallery', 'reviews', 'about', 'contact', 'services', 'faqs', 'documents', 'users', 'settings', 'admin', 'install', 'uploads', 'assets', 'sections'];

/** Reads the field rows posted by the form into clean field definitions. */
function posted_fields(): array
{
    $out = [];
    $labels = (array) ($_POST['f_label'] ?? []);
    foreach ($labels as $i => $label) {
        $label = mb_substr(trim((string) $label), 0, 80);
        if ($label === '') continue;
        $type = (string) ($_POST['f_type'][$i] ?? 'text');
        if (!isset(SECTION_FIELD_TYPES[$type])) $type = 'text';
        $key = trim((string) ($_POST['f_key'][$i] ?? ''));
        if ($key === '') $key = substr(trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_'), 0, 28);
        if (!preg_match('/^[a-z]/', $key)) $key = 'f_' . $key;
        $out[] = ['key' => $key, 'label' => $label, 'type' => $type,
            'required' => !empty($_POST['f_req'][$i]),
            'hint' => mb_substr(trim((string) ($_POST['f_hint'][$i] ?? '')), 0, 160),
            'options' => mb_substr(trim((string) ($_POST['f_options'][$i] ?? '')), 0, 600)];
    }
    return $out;
}

if (is_post()) {
    csrf_check();
    $do = input('do');

    if ($do === 'create') {
        $label = mb_substr(input('label'), 0, 80);
        $one = mb_substr(input('one_label'), 0, 80) ?: $label;
        $slug = input('slug') ?: trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');
        $slug = substr($slug, 0, 30);
        $fields = posted_fields();
        $keys = array_column($fields, 'key');
        $err = null;
        if ($label === '') $err = 'Give the section a name.';
        elseif (!valid_key($slug) || in_array($slug, $reserved, true)) $err = 'That short name can\'t be used. Use lowercase letters, numbers and underscores (e.g. lodges).';
        elseif (isset($types[$slug])) $err = 'A section with that short name already exists.';
        elseif (count($keys) !== count(array_unique($keys))) $err = 'Two fields have the same name.';
        else foreach ($keys as $k) if (!valid_key($k) || in_array($k, ['title', 'slug', 'sort', 'active', 'image', 'id'], true)) { $err = "Field name \"$k\" can't be used."; break; }
        if ($err) {
            flash('error', $err);
            $_SESSION['old_section'] = $_POST;
            redirect('admin/sections.php?add=1');
        }
        $t = ['slug' => $slug, 'label' => $label, 'one_label' => $one, 'intro' => input('intro'), 'fields' => $fields];
        foreach ($t['fields'] as &$f) $f['col'] = section_col($t, $f);
        unset($f);
        try {
            create_section_table($t);
            insert('custom_types', ['slug' => $slug, 'label' => $label, 'one_label' => $one, 'intro' => input('intro') ?: null,
                'fields' => json_encode($t['fields'], JSON_UNESCAPED_UNICODE), 'in_menu' => isset($_POST['in_menu']) ? 1 : 0,
                'public' => isset($_POST['public']) ? 1 : 0, 'created_at' => now()]);
            audit('section_create', 'custom_types', null, ['name' => $label, 'fields' => count($fields)]);
            flash('ok', "\"$label\" created. It's now in the sidebar under Website — add your first " . mb_strtolower($one) . '.');
        } catch (Throwable $e) {
            error_log('section create: ' . $e->getMessage());
            flash('error', 'The section could not be created: ' . $e->getMessage());
        }
        redirect('admin/sections.php');
    }

    $t = $types[input('slug')] ?? null;
    if (!$t) redirect('admin/sections.php');

    if ($do === 'update') {
        $old = $t['fields'];
        $byKey = array_column($old, null, 'key');
        $new = [];
        // existing fields: label, hint, required, options can change; type and key stay
        foreach ((array) ($_POST['e_key'] ?? []) as $i => $k) {
            if (!isset($byKey[$k])) continue;
            if (!empty($_POST['e_remove'][$i])) continue; // removed from the form; the data stays in the database
            $f = $byKey[$k];
            $f['label'] = mb_substr(trim((string) ($_POST['e_label'][$i] ?? $f['label'])), 0, 80) ?: $f['label'];
            $f['hint'] = mb_substr(trim((string) ($_POST['e_hint'][$i] ?? '')), 0, 160);
            $f['required'] = !empty($_POST['e_req'][$i]);
            $f['options'] = mb_substr(trim((string) ($_POST['e_options'][$i] ?? ($f['options'] ?? ''))), 0, 600);
            $f['col'] = section_col($t, $f);
            $new[] = $f;
        }
        $added = [];
        $allKeys = array_column($old, 'key');
        foreach (posted_fields() as $f) {
            if (!valid_key($f['key']) || in_array($f['key'], $allKeys, true) || in_array($f['key'], ['title', 'slug', 'sort', 'active', 'image', 'id'], true)) {
                flash('error', "Field \"{$f['label']}\" needs a different name.");
                redirect('admin/sections.php?edit=' . $t['slug']);
            }
            $probe = $t;
            $probe['fields'] = array_merge($old, [$f]);
            $f['col'] = section_col($probe, $f);
            if ($f['col'] === 'image' && in_array('image', array_map(fn($x) => section_col($t, $x), $old), true)) $f['col'] = 'c_' . $f['key'];
            try {
                add_section_column($t, $f);
            } catch (Throwable $e) {
                flash('error', 'Could not add "' . $f['label'] . '": ' . $e->getMessage());
                redirect('admin/sections.php?edit=' . $t['slug']);
            }
            $new[] = $f;
            $allKeys[] = $f['key'];
            $added[] = $f['label'];
        }
        q('UPDATE custom_types SET label = ?, one_label = ?, intro = ?, fields = ?, in_menu = ?, public = ? WHERE id = ?', [
            mb_substr(input('label'), 0, 80) ?: $t['label'], mb_substr(input('one_label'), 0, 80) ?: $t['one_label'], input('intro') ?: null,
            json_encode($new, JSON_UNESCAPED_UNICODE), isset($_POST['in_menu']) ? 1 : 0, isset($_POST['public']) ? 1 : 0, $t['id']]);
        audit('section_update', 'custom_types', (int) $t['id'], ['name' => $t['label'], 'changed' => $added ? ['added fields: ' . implode(', ', $added)] : ['settings / field labels']]);
        flash('ok', 'Section saved.');
        redirect('admin/sections.php');
    }

    if ($do === 'delete') {
        if (input('confirm') !== $t['slug']) {
            flash('error', 'Type the short name exactly to confirm deleting.');
            redirect('admin/sections.php?edit=' . $t['slug']);
        }
        foreach (rows('SELECT * FROM cx_' . $t['slug']) as $r) {
            foreach ($t['fields'] as $f) if ($f['type'] === 'image') delete_image($r[section_col($t, $f)] ?? null);
        }
        $count = (int) val('SELECT COUNT(*) FROM cx_' . $t['slug']);
        db()->exec('DROP TABLE IF EXISTS cx_' . $t['slug']);
        q('DELETE FROM custom_types WHERE id = ?', [$t['id']]);
        audit('section_delete', 'custom_types', (int) $t['id'], ['name' => $t['label'], 'note' => "$count item(s) removed"]);
        flash('ok', "\"{$t['label']}\" and its $count item(s) were deleted.");
        redirect('admin/sections.php');
    }
    redirect('admin/sections.php');
}

$edit = $types[input('edit')] ?? null;
$old = $_SESSION['old_section'] ?? null;
unset($_SESSION['old_section']);

function field_row(string $p, array $f = [], bool $existing = false): void
{ ?>
  <div class="frow">
    <?php if ($existing): ?><input type="hidden" name="e_key[]" value="<?= h($f['key']) ?>"><?php endif; ?>
    <label>Field name<input name="<?= $p ?>_label[]" value="<?= h($f['label'] ?? '') ?>" placeholder="e.g. Price per night" maxlength="80"></label>
    <label>Type<?php if ($existing): ?><input value="<?= h(SECTION_FIELD_TYPES[$f['type']] ?? $f['type']) ?>" disabled><?php else: ?>
      <select name="<?= $p ?>_type[]" data-ftype><?php foreach (SECTION_FIELD_TYPES as $k => $l): ?><option value="<?= h($k) ?>" <?= ($f['type'] ?? 'text') === $k ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select><?php endif; ?></label>
    <label class="wide">Help text <span class="opt">(optional)</span><input name="<?= $p ?>_hint[]" value="<?= h($f['hint'] ?? '') ?>" maxlength="160"></label>
    <label class="wide fopts" <?= ($f['type'] ?? '') === 'select' ? '' : 'hidden' ?>>Choices <span class="opt">(comma separated)</span><input name="<?= $p ?>_options[]" value="<?= h($f['options'] ?? '') ?>" placeholder="e.g. Budget, Mid-range, Luxury"></label>
    <div class="frow-end">
      <label class="check small"><input type="checkbox" name="<?= $p ?>_req[__i__]" value="1" <?= !empty($f['required']) ? 'checked' : '' ?>> Required</label>
      <?php if ($existing): ?><label class="check small danger-text"><input type="checkbox" name="e_remove[__i__]" value="1"> Remove</label>
      <?php else: ?><button type="button" class="ib danger" data-frow-remove title="Remove"><?= icon('x', 16) ?></button><?php endif; ?>
    </div>
  </div>
<?php }

admin_header('Content sections', 'sections');
?>
<p class="muted">Add new kinds of content to the website without a developer — for example <b>Lodges</b>, <b>Vehicles</b>, <b>Park fees</b> or <b>Partners</b>. Each section gets its own page in the dashboard, and (if you choose) public pages on the website.</p>
<div class="toolbar"><span></span><button type="button" class="btn" data-modal-open="addModal"><?= icon('plus', 18) ?> New section</button></div>

<?php if (!$types): ?>
  <div class="card empty-state"><p><b>No custom sections yet.</b><br><span class="muted">Click “New section” to create your first one.</span></p></div>
<?php else: ?>
<div class="card table-wrap">
<table class="table">
  <thead><tr><th>Section</th><th>Fields</th><th>Items</th><th>On website</th><th class="right">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($types as $t): $n = (int) val('SELECT COUNT(*) FROM cx_' . $t['slug']); ?>
    <tr>
      <td data-l="Section"><strong><?= h($t['label']) ?></strong><br><span class="muted small"><?= h($t['slug']) ?></span></td>
      <td data-l="Fields" class="small"><?= h(implode(', ', array_column($t['fields'], 'label')) ?: 'Name only') ?></td>
      <td data-l="Items"><?= $n ?></td>
      <td data-l="On website"><?php if ($t['public']): ?><a href="<?= url('section.php?type=' . $t['slug']) ?>" target="_blank" rel="noopener"><?= h(url('section.php?type=' . $t['slug'])) ?></a><?= $t['in_menu'] ? ' <span class="pill ok">In menu</span>' : '' ?><?php else: ?><span class="pill">Dashboard only</span><?php endif; ?></td>
      <td class="right nowrap"><a class="btn ghost sm" href="<?= url('admin/content.php?type=sec_' . $t['slug']) ?>">Open</a> <a class="btn ghost sm" href="<?= url('admin/sections.php?edit=' . $t['slug']) ?>"><?= icon('edit', 16) ?> Fields</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<?php if ($edit): ?>
<div class="modal" id="editModal" hidden data-autoopen>
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="editTitle">
  <div class="modal-head"><h2 id="editTitle">Edit “<?= h($edit['label']) ?>”</h2><button type="button" class="ib" data-modal-close aria-label="Close"><?= icon('x', 22) ?></button></div>
  <div class="modal-body">
  <form method="post" class="form" data-fields-form>
    <?= csrf_field() ?><input type="hidden" name="do" value="update"><input type="hidden" name="slug" value="<?= h($edit['slug']) ?>">
    <div class="grid2">
      <label>Section name (plural)<input name="label" value="<?= h($edit['label']) ?>" required maxlength="80"></label>
      <label>One item is called<input name="one_label" value="<?= h($edit['one_label']) ?>" required maxlength="80"></label>
    </div>
    <label>Intro text for the public page <span class="opt">(optional)</span><textarea name="intro" rows="2"><?= h($edit['intro']) ?></textarea></label>
    <div class="row-gap"><label class="check"><input type="checkbox" name="public" value="1" <?= $edit['public'] ? 'checked' : '' ?>> Show on the website</label>
    <label class="check"><input type="checkbox" name="in_menu" value="1" <?= $edit['in_menu'] ? 'checked' : '' ?>> Add to the website menu</label></div>
    <h3 class="sub">Fields</h3>
    <p class="hint">Every item has a name, a web address, display order and a show/hide switch. Removing a field hides it but keeps its data.</p>
    <div class="frows" data-frows><?php foreach ($edit['fields'] as $f) field_row('e', $f, true); ?></div>
    <h3 class="sub">Add fields</h3>
    <div class="frows" data-frows data-new><?php field_row('f'); ?></div>
    <button type="button" class="btn ghost sm" data-frow-add><?= icon('plus', 16) ?> Another field</button>
    <div class="actions"><button class="btn"><?= icon('check', 18) ?> Save section</button><button type="button" class="btn ghost" data-modal-close>Cancel</button></div>
  </form>
  <details class="danger-zone">
    <summary>Delete this section</summary>
    <form method="post" class="form" data-confirm="Delete this section and all its items for good?">
      <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="slug" value="<?= h($edit['slug']) ?>">
      <p class="hint">This deletes every item and photo in “<?= h($edit['label']) ?>”. It can't be undone — download a database backup first. Type <b><?= h($edit['slug']) ?></b> to confirm.</p>
      <div class="row-gap"><input name="confirm" autocomplete="off" required><button class="btn danger">Delete section</button></div>
    </form>
  </details>
  </div></div>
</div>
<?php endif; ?>

<div class="modal" id="addModal" hidden <?= input('add') ? 'data-autoopen' : '' ?>>
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="addTitle">
  <div class="modal-head"><h2 id="addTitle">New content section</h2><button type="button" class="ib" data-modal-close aria-label="Close"><?= icon('x', 22) ?></button></div>
  <div class="modal-body">
  <form method="post" class="form" data-fields-form>
    <?= csrf_field() ?><input type="hidden" name="do" value="create">
    <div class="grid2">
      <label>Section name (plural)<input name="label" value="<?= h($old['label'] ?? '') ?>" required maxlength="80" placeholder="e.g. Lodges"></label>
      <label>One item is called<input name="one_label" value="<?= h($old['one_label'] ?? '') ?>" required maxlength="80" placeholder="e.g. Lodge"></label>
      <label>Short name <span class="opt">(used in the web address)</span><input name="slug" value="<?= h($old['slug'] ?? '') ?>" pattern="[a-z][a-z0-9_]{1,29}" placeholder="e.g. lodges"></label>
    </div>
    <label>Intro text for the public page <span class="opt">(optional)</span><textarea name="intro" rows="2"><?= h($old['intro'] ?? '') ?></textarea></label>
    <div class="row-gap"><label class="check"><input type="checkbox" name="public" value="1" checked> Show on the website</label>
    <label class="check"><input type="checkbox" name="in_menu" value="1"> Add to the website menu</label></div>
    <h3 class="sub">Fields</h3>
    <p class="hint">Every item already gets a name, web address, display order and show/hide switch. Add whatever else it needs.</p>
    <div class="frows" data-frows data-new>
      <?php field_row('f', ['label' => 'Photo', 'type' => 'image']); field_row('f', ['label' => 'Description', 'type' => 'md']); ?>
    </div>
    <button type="button" class="btn ghost sm" data-frow-add><?= icon('plus', 16) ?> Another field</button>
    <div class="actions"><button class="btn"><?= icon('check', 18) ?> Create section</button><button type="button" class="btn ghost" data-modal-close>Cancel</button></div>
  </form>
  </div></div>
</div>
<template id="frowTpl"><?php field_row('f'); ?></template>
<?php admin_footer();
