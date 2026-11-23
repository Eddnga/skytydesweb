<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';
$user = require_login();

$all = entities();
$type = input('type');
if (!isset($all[$type])) redirect('admin/');
$E = $all[$type];
$T = $E['table'];
$action = input('action', 'list');
$id = (int) input('id', '0');
$self = 'admin/content.php?type=' . $type;

function field_opts(array $f): array
{
    $o = $f[3] ?? [];
    if (isset($o['options']) && $o['options'] instanceof Closure) $o['options'] = ($o['options'])();
    return $o;
}

// ---------------- POST actions ----------------
if (is_post()) {
    csrf_check();

    if ($action === 'delete' && $id) {
        $rec = row("SELECT * FROM $T WHERE id = ?", [$id]);
        if ($rec) {
            foreach ($E['fields'] as $f) {
                if ($f[1] === 'image') delete_image($rec[$f[0]] ?? null);
                if ($f[1] === 'pdf') delete_document($rec[$f[0]] ?? null);
            }
            if (!empty($E['itinerary'])) q('DELETE FROM itinerary_days WHERE tour_id = ?', [$id]);
            q("DELETE FROM $T WHERE id = ?", [$id]);
            audit('delete', $T, $id);
            flash('ok', ucfirst($E['one']) . ' deleted.');
        }
        redirect($self);
    }

    if ($action === 'toggle' && $id) {
        $col = in_array('published', array_column($E['fields'], 0), true) ? 'published' : 'active';
        q("UPDATE $T SET $col = 1 - $col WHERE id = ?", [$id]);
        audit('toggle', $T, $id);
        redirect($self);
    }

    if ($action === 'bulk' && !empty($E['bulk_upload'])) {
        $files = $_FILES['images'] ?? null;
        $n = 0; $errs = [];
        if ($files && is_array($files['name'])) {
            $cat = mb_substr(input('category'), 0, 80);
            foreach ($files['name'] as $i => $nm) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                try {
                    $path = store_image(['name' => $nm, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]]);
                    insert($T, ['image' => $path, 'caption' => pathinfo($nm, PATHINFO_FILENAME) === '' ? null : ucfirst(str_replace(['-', '_'], ' ', mb_substr(pathinfo($nm, PATHINFO_FILENAME), 0, 120))), 'category' => $cat ?: null, 'sort' => 0, 'active' => 1, 'created_at' => now()]);
                    $n++;
                } catch (RuntimeException $e) {
                    $errs[] = $nm . ': ' . $e->getMessage();
                }
            }
        }
        audit('bulk_upload', $T);
        if ($n) flash('ok', "$n photo(s) added to the gallery.");
        if ($errs) flash('error', implode(' ', $errs));
        redirect($self);
    }

    if ($action === 'edit') {
        $rec = $id ? row("SELECT * FROM $T WHERE id = ?", [$id]) : null;
        if ($id && !$rec) redirect($self);
        $data = []; $errors = [];
        foreach ($E['fields'] as $f) {
            [$name, $ftype, $label] = $f;
            $o = field_opts($f);
            $raw = $_POST[$name] ?? null;
            $raw = is_string($raw) ? trim($raw) : null;
            switch ($ftype) {
                case 'text':
                    $v = mb_substr((string) $raw, 0, 255); break;
                case 'textarea': case 'lines':
                    $v = mb_substr((string) $raw, 0, 60000); break;
                case 'md':
                    $v = sanitize_html(mb_substr((string) $raw, 0, 200000));
                    if (trim(strip_tags($v)) === '') $v = ''; break;
                case 'number':
                    $v = ($raw === '' || $raw === null) ? ($o['default'] ?? null) : (int) $raw; break;
                case 'money':
                    $v = ($raw === '' || $raw === null) ? null : round((float) str_replace(',', '', $raw), 2); break;
                case 'select':
                    $v = array_key_exists((string) $raw, $o['options']) ? (string) $raw : ($o['default'] ?? array_key_first($o['options'])); break;
                case 'relation':
                    $v = ($raw !== '' && array_key_exists((int) $raw, $o['options'])) ? (int) $raw : null; break;
                case 'bool':
                    $v = isset($_POST[$name]) ? 1 : 0; break;
                case 'date':
                    $v = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $raw) ? $raw . ' 09:00:00' : now(); break;
                case 'date_opt':
                    $v = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $raw) ? $raw . (!empty($o['end']) ? ' 23:59:59' : ' 00:00:00') : null; break;
                case 'pdf':
                    $v = $rec[$name] ?? null;
                    if (!empty($_FILES[$name]) && $_FILES[$name]['error'] !== UPLOAD_ERR_NO_FILE) {
                        try {
                            [$new, $size] = store_document($_FILES[$name]);
                            delete_document($v);
                            $v = $new;
                            $data['file_size'] = $size;
                        } catch (RuntimeException $e) {
                            $errors[] = $label . ': ' . $e->getMessage();
                        }
                    }
                    break;
                case 'image':
                    $v = $rec[$name] ?? null;
                    if (!empty($_FILES[$name]) && $_FILES[$name]['error'] !== UPLOAD_ERR_NO_FILE) {
                        try {
                            $new = store_image($_FILES[$name]);
                            delete_image($v);
                            $v = $new;
                        } catch (RuntimeException $e) {
                            $errors[] = $label . ': ' . $e->getMessage();
                        }
                    } elseif (isset($_POST[$name . '_remove'])) {
                        delete_image($v);
                        $v = null;
                    }
                    break;
                default:
                    $v = $raw;
            }
            if (!empty($o['required']) && ($v === null || $v === '')) $errors[] = $label . ' is required.';
            $data[$name] = $v === '' ? null : $v;
        }
        if (!empty($E['slug'])) {
            $want = input('slug');
            $data['slug'] = unique_slug($T, $want !== '' ? $want : (string) $data[$E['title']], $id ?: null);
        }
        if ($errors) {
            $_SESSION['old'] = $_POST;
            flash('error', implode(' ', $errors));
            if (!$id && isset($_POST['modal'])) redirect($self . '&add=1');
            redirect($self . '&action=edit' . ($id ? '&id=' . $id : ''));
        }
        if (!empty($E['timestamps'])) {
            $data['updated_at'] = now();
            if (!$id) $data['created_at'] = now();
        }
        if (!empty($E['timestamps_created']) && !$id) $data['created_at'] = now();
        if ($id) {
            update($T, $id, $data);
            audit('update', $T, $id);
        } else {
            $id = insert($T, $data);
            audit('create', $T, $id);
        }
        if (!empty($E['itinerary'])) {
            q('DELETE FROM itinerary_days WHERE tour_id = ?', [$id]);
            $titles = $_POST['day_title'] ?? [];
            $n = 0;
            if (is_array($titles)) foreach ($titles as $i => $t) {
                $t = trim((string) $t);
                if ($t === '') continue;
                insert('itinerary_days', ['tour_id' => $id, 'day_no' => ++$n, 'title' => mb_substr($t, 0, 200),
                    'body' => mb_substr(trim((string) ($_POST['day_body'][$i] ?? '')), 0, 10000),
                    'meals' => mb_substr(trim((string) ($_POST['day_meals'][$i] ?? '')), 0, 120),
                    'stay' => mb_substr(trim((string) ($_POST['day_stay'][$i] ?? '')), 0, 160)]);
            }
        }
        flash('ok', ucfirst($E['one']) . ' saved.');
        redirect($self . (isset($_POST['save_close']) ? '' : '&action=edit&id=' . $id));
    }
    redirect($self);
}

// ---------------- render ----------------
function list_cell(string $col, array $r, array $E): string
{
    $v = $r[$col] ?? null;
    if ($col === 'image') return '<img class="thumb" src="' . h(upload_url($v, 'sm')) . '" alt="">';
    if (in_array($col, ['active', 'published'], true)) return $v ? '<span class="pill ok">Live</span>' : '<span class="pill">Hidden</span>';
    if ($col === 'featured') return $v ? '<span class="pill gold">Featured</span>' : '';
    if ($col === 'access') return $v === 'request' ? '<span class="pill gold">On request</span>' : '<span class="pill ok">Public</span>';
    if ($col === 'downloads') return (int) $v . '×';
    if ($col === 'destination_id') return h((string) val('SELECT name FROM destinations WHERE id = ?', [$v]));
    if ($col === 'price_from') return $v ? h(money($v, $r['currency'] ?? 'KES')) : '<span class="muted">On request</span>';
    if ($col === 'published_at') return h(fdate($v));
    if ($col === 'valid_until') return $v ? (strtotime($v) < time() ? '<span class="pill">Expired ' . h(fdate($v)) . '</span>' : h(fdate($v))) : '<span class="muted">No end date</span>';
    if ($col === 'role' || $col === 'tag') return h($v);
    if ($col === 'icon') return '<span class="muted">' . h($v) . '</span>';
    return h(excerpt((string) $v, 70));
}

function edit_form(array $E, string $self, ?array $rec, ?array $old, int $id, bool $modal): void
{
    $val = function ($name, $default = '') use ($rec, $old) {
        if ($old !== null && array_key_exists($name, $old)) return $old[$name];
        return $rec[$name] ?? $default;
    };
    ?>
    <form method="post" action="<?= url($self . '&action=edit' . ($id ? '&id=' . $id : '')) ?>" enctype="multipart/form-data" class="form edit<?= $modal ? '' : ' card' ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit">
      <?php if ($modal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
      <?php foreach ($E['fields'] as $f):
          [$name, $ftype, $label] = $f;
          $o = field_opts($f);
          $v = $val($name, $o['default'] ?? '');
          $req = !empty($o['required']) && $ftype !== 'image' ? ' required' : '';
          ?>
        <div class="field f-<?= h($ftype) ?>">
        <?php if ($ftype === 'bool'): ?>
          <label class="check"><input type="checkbox" name="<?= h($name) ?>" value="1" <?= ($rec ? $v : ($o['default'] ?? 0)) ? 'checked' : '' ?>> <?= h($label) ?></label>
        <?php else: ?>
          <label for="f_<?= h($name) ?>"><?= h($label) ?><?= !empty($o['required']) ? ' <span class="req">*</span>' : '' ?></label>
          <?php if ($ftype === 'text'): ?>
            <input id="f_<?= h($name) ?>" name="<?= h($name) ?>" value="<?= h($v) ?>" maxlength="255"<?= $req ?>>
          <?php elseif ($ftype === 'textarea'): ?>
            <textarea id="f_<?= h($name) ?>" name="<?= h($name) ?>" rows="3"<?= $req ?>><?= h($v) ?></textarea>
          <?php elseif ($ftype === 'lines'): ?>
            <textarea id="f_<?= h($name) ?>" name="<?= h($name) ?>" rows="5"<?= $req ?>><?= h($v) ?></textarea>
          <?php elseif ($ftype === 'md'): ?>
            <textarea id="f_<?= h($name) ?>" name="<?= h($name) ?>" rows="12" class="rich" data-rich><?= h(editor_value((string) $v)) ?></textarea>
          <?php elseif ($ftype === 'number'): ?>
            <input id="f_<?= h($name) ?>" type="number" name="<?= h($name) ?>" value="<?= h($v) ?>"<?= $req ?>>
          <?php elseif ($ftype === 'money'): ?>
            <input id="f_<?= h($name) ?>" type="number" step="0.01" min="0" name="<?= h($name) ?>" value="<?= h($v === null ? '' : rtrim(rtrim((string) $v, '0'), '.')) ?>"<?= $req ?>>
          <?php elseif ($ftype === 'select' || $ftype === 'relation'): ?>
            <select id="f_<?= h($name) ?>" name="<?= h($name) ?>"<?= $req ?>>
              <?php if ($ftype === 'relation'): ?><option value="">— None —</option><?php endif; ?>
              <?php foreach ($o['options'] as $k => $lab): ?><option value="<?= h($k) ?>" <?= (string) $k === (string) $v ? 'selected' : '' ?>><?= h($lab) ?></option><?php endforeach; ?>
            </select>
          <?php elseif ($ftype === 'date_opt'): ?>
            <input id="f_<?= h($name) ?>" type="date" name="<?= h($name) ?>" value="<?= h($v ? substr((string) $v, 0, 10) : '') ?>">
          <?php elseif ($ftype === 'date'): ?>
            <input id="f_<?= h($name) ?>" type="date" name="<?= h($name) ?>" value="<?= h($v ? substr((string) $v, 0, 10) : date('Y-m-d')) ?>">
          <?php elseif ($ftype === 'pdf'): $cur = $rec[$name] ?? null; ?>
            <div class="pdffield">
              <?php if ($cur): ?><p class="pdfcur"><?= icon('file', 18) ?> <a href="<?= h(url('doc.php?id=' . (int) $rec['id'])) ?>" target="_blank" rel="noopener">Open current PDF</a> <span class="muted small"><?= h(human_size((int) ($rec['file_size'] ?? 0))) ?></span></p><?php endif; ?>
              <input id="f_<?= h($name) ?>" type="file" name="<?= h($name) ?>" accept="application/pdf"<?= (!empty($o['required']) && !$cur) ? ' required' : '' ?>>
              <p class="hint">PDF only, up to 25 MB.<?= $cur ? ' Choose a new file to replace the current one.' : '' ?></p>
            </div>
          <?php elseif ($ftype === 'image'): $cur = $rec[$name] ?? null; ?>
            <div class="imgfield">
              <img src="<?= h(upload_url($cur, 'sm')) ?>" alt="" class="preview" data-preview="f_<?= h($name) ?>">
              <div>
                <input id="f_<?= h($name) ?>" type="file" name="<?= h($name) ?>" accept="image/jpeg,image/png,image/webp"<?= (!empty($o['required']) && !$cur) ? ' required' : '' ?>>
                <p class="hint">JPG, PNG or WebP, up to <?= (int) ($GLOBALS['CONFIG']['max_upload_mb'] ?? 8) ?> MB. Images are resized and cleaned automatically.</p>
                <?php if ($cur && empty($o['required'])): ?><label class="check small"><input type="checkbox" name="<?= h($name) ?>_remove" value="1"> Remove image</label><?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if (!empty($o['hint'])): ?><p class="hint"><?= h($o['hint']) ?></p><?php endif; ?>
        <?php endif; ?>
        </div>
        <?php if (!empty($E['slug']) && $name === $E['title']): ?>
          <div class="field"><label for="f_slug">Web address (slug)</label>
            <input id="f_slug" name="slug" value="<?= h($val('slug')) ?>" placeholder="created from the title" pattern="[a-z0-9\-]*">
            <p class="hint">Lowercase letters, numbers and dashes. Leave empty to create it from the title.</p></div>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if (!empty($E['itinerary'])):
          $days = $old ? array_map(null, (array) ($old['day_title'] ?? []), (array) ($old['day_body'] ?? []), (array) ($old['day_meals'] ?? []), (array) ($old['day_stay'] ?? [])) : array_map(fn($d) => [$d['title'], $d['body'], $d['meals'], $d['stay']], $id ? rows('SELECT * FROM itinerary_days WHERE tour_id = ? ORDER BY day_no', [$id]) : []);
          if (!$days) $days = [['', '', '', '']];
          ?>
        <div class="field">
          <h2 class="sub">Itinerary</h2>
          <p class="hint">One block per day. Use the arrows to reorder; empty days are skipped.</p>
          <div id="days">
            <?php foreach ($days as $d): ?>
            <div class="day">
              <div class="day-head"><b class="dn">Day</b>
                <span class="day-tools"><button type="button" class="ib" data-up title="Move up">↑</button><button type="button" class="ib" data-down title="Move down">↓</button><button type="button" class="ib danger" data-remove title="Remove day"><?= icon('x', 16) ?></button></span></div>
              <input name="day_title[]" value="<?= h($d[0]) ?>" placeholder="Title, e.g. Nairobi to the Maasai Mara">
              <textarea name="day_body[]" rows="3" placeholder="What happens on this day"><?= h($d[1]) ?></textarea>
              <div class="grid2"><input name="day_meals[]" value="<?= h($d[2]) ?>" placeholder="Meals, e.g. Breakfast, Lunch, Dinner"><input name="day_stay[]" value="<?= h($d[3]) ?>" placeholder="Overnight stay"></div>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn ghost sm" id="addDay"><?= icon('plus', 16) ?> Add day</button>
        </div>
      <?php endif; ?>

      <?php if ($modal): ?>
      <div class="actions sticky">
        <button class="btn" name="save_close" value="1"><?= icon('check', 18) ?> Add <?= h($E['one']) ?></button>
        <button type="button" class="btn ghost" data-modal-close>Cancel</button>
      </div>
      <?php else: ?>
      <div class="actions sticky">
        <button class="btn"><?= icon('check', 18) ?> Save</button>
        <button class="btn ghost" name="save_close" value="1">Save &amp; close</button>
        <?php if ($id && !empty($E['view']) && $rec): ?><a class="btn ghost" target="_blank" rel="noopener" href="<?= url($E['view'] . rawurlencode($rec['slug'])) ?>"><?= icon('eye', 18) ?> View</a><?php endif; ?>
      </div>
      <?php endif; ?>
    </form>
    <?php
}

if ($action === 'edit') {
    $rec = $id ? row("SELECT * FROM $T WHERE id = ?", [$id]) : null;
    if ($id && !$rec) redirect($self);
    $old = $_SESSION['old'] ?? null;
    unset($_SESSION['old']);
    admin_header(($id ? 'Edit ' : 'New ') . $E['one'], $type);
    ?>
    <p><a href="<?= url($self) ?>" class="back">← All <?= h(strtolower($E['label'])) ?></a></p>
    <?php edit_form($E, $self, $rec, $old, $id, false);
    admin_footer();
    exit;
}

// list
$search = input('q');
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE ' . $E['title'] . ' LIKE ?';
    $params[] = '%' . $search . '%';
}
$page = max(1, (int) input('p', '1'));
$per = 25;
$total = (int) val("SELECT COUNT(*) FROM $T $where", $params);
$list = rows("SELECT * FROM $T $where ORDER BY {$E['order']} LIMIT $per OFFSET " . (($page - 1) * $per), $params);
admin_header($E['label'], $type);
?>
<div class="toolbar">
  <form method="get" class="search"><input type="hidden" name="type" value="<?= h($type) ?>"><input name="q" value="<?= h($search) ?>" placeholder="Search <?= h(strtolower($E['label'])) ?>…"></form>
  <a class="btn" href="<?= url($self . '&action=edit') ?>" data-modal-open="addModal"><?= icon('plus', 18) ?> Add <?= h($E['one']) ?></a>
</div>
<?php if (!empty($E['help'])): ?><p class="muted"><?= h($E['help']) ?></p><?php endif; ?>
<?php if (!empty($E['bulk_upload'])): ?>
<form method="post" enctype="multipart/form-data" class="card bulk">
  <?= csrf_field() ?><input type="hidden" name="action" value="bulk">
  <strong>Upload several photos at once</strong>
  <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" required>
  <input name="category" placeholder="Category (optional)" maxlength="80">
  <button class="btn sm">Upload</button>
</form>
<?php endif; ?>
<div class="card table-wrap">
<?php if (!$list): ?>
  <p class="empty">Nothing here yet. <a href="<?= url($self . '&action=edit') ?>" data-modal-open="addModal">Add the first <?= h($E['one']) ?></a>.</p>
<?php else: ?>
<table class="table">
  <thead><tr><?php foreach ($E['list'] as $c => $lab): ?><th><?= h($lab) ?></th><?php endforeach; ?><th class="right">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <?php foreach ($E['list'] as $c => $lab): ?><td data-l="<?= h($lab) ?>"><?php if ($c === $E['title']): ?><a href="<?= url($self . '&action=edit&id=' . $r['id']) ?>" class="strong"><?= list_cell($c, $r, $E) ?: '<span class="muted">(no caption)</span>' ?></a><?php else: ?><?= list_cell($c, $r, $E) ?><?php endif; ?></td><?php endforeach; ?>
      <td class="right nowrap">
        <a class="ib" title="Edit" href="<?= url($self . '&action=edit&id=' . $r['id']) ?>"><?= icon('edit', 18) ?></a>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="ib" title="Show / hide"><?= icon('eye', 18) ?></button></form>
        <form method="post" class="inline" data-confirm="Delete this <?= h($E['one']) ?>? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="ib danger" title="Delete"><?= icon('trash', 18) ?></button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</div>
<?php if ($total > $per): ?>
<nav class="pager"><?php for ($i = 1; $i <= ceil($total / $per); $i++): ?><a class="<?= $i === $page ? 'on' : '' ?>" href="<?= url($self . '&p=' . $i . ($search !== '' ? '&q=' . rawurlencode($search) : '')) ?>"><?= $i ?></a><?php endfor; ?></nav>
<?php endif; ?>
<?php $addOld = input('add') === '1' ? ($_SESSION['old'] ?? null) : null; unset($_SESSION['old']); ?>
<div class="modal" id="addModal" hidden<?= input('add') === '1' ? ' data-autoopen' : '' ?>>
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="addTitle">
    <div class="modal-head"><h2 id="addTitle">Add <?= h($E['one']) ?></h2><button type="button" class="ib" data-modal-close aria-label="Close"><?= icon('x', 22) ?></button></div>
    <div class="modal-body"><?php edit_form($E, $self, null, $addOld, 0, true); ?></div>
  </div>
</div>
<?php admin_footer();
