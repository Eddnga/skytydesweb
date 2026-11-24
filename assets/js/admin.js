(function () {
  // confirm before destructive actions
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });
  // selects that save on change
  document.querySelectorAll('select[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });
  // image preview
  document.querySelectorAll('input[type=file][id]').forEach(function (inp) {
    var img = document.querySelector('img[data-preview="' + inp.id + '"]');
    if (!img) return;
    inp.addEventListener('change', function () {
      if (inp.files && inp.files[0]) img.src = URL.createObjectURL(inp.files[0]);
    });
  });
  // itinerary repeater
  var days = document.getElementById('days');
  if (days) {
    var renumber = function () {
      days.querySelectorAll('.day').forEach(function (d, i) { d.querySelector('.dn').textContent = 'Day ' + (i + 1); });
    };
    days.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      var day = b.closest('.day');
      if (b.hasAttribute('data-remove')) {
        if (days.querySelectorAll('.day').length > 1) day.remove();
        else day.querySelectorAll('input,textarea').forEach(function (x) { x.value = ''; });
      }
      if (b.hasAttribute('data-up') && day.previousElementSibling) days.insertBefore(day, day.previousElementSibling);
      if (b.hasAttribute('data-down') && day.nextElementSibling) days.insertBefore(day.nextElementSibling, day);
      renumber();
    });
    document.getElementById('addDay').addEventListener('click', function () {
      var last = days.querySelector('.day:last-child');
      var c = last.cloneNode(true);
      c.querySelectorAll('input,textarea').forEach(function (x) { x.value = ''; });
      days.appendChild(c); renumber();
      c.querySelector('input').focus();
    });
    renumber();
  }
  // rich text editor (Summernote) for long text fields
  var dirty = false;
  if (window.jQuery && jQuery.fn.summernote) {
    jQuery('[data-rich]').each(function () {
      var ta = jQuery(this);
      ta.summernote({
        height: ta.closest('.card').length && ta.attr('rows') > 10 ? 380 : 240,
        placeholder: 'Start writing…',
        disableDragAndDrop: true,
        dialogsInBody: false,
        codeviewFilter: true,
        codeviewIframeFilter: true,
        styleTags: [
          { title: 'Paragraph', tag: 'p', value: 'p' },
          { title: 'Heading', tag: 'h2', value: 'h2' },
          { title: 'Subheading', tag: 'h3', value: 'h3' },
          { title: 'Quote', tag: 'blockquote', value: 'blockquote' }
        ],
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'italic', 'underline', 'clear']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['insert', ['link', 'table', 'hr']],
          ['view', ['undo', 'redo', 'codeview', 'fullscreen']]
        ],
        callbacks: {
          onChange: function () { dirty = true; },
          onImageUpload: function () { alert('Add photos with the image fields instead.'); }
        }
      });
    });
    // always copy the editor's latest content into the form before saving
    jQuery('form').on('submit', function () {
      jQuery(this).find('[data-rich]').each(function () {
        var el = jQuery(this);
        if (!el.next('.note-editor').length) return;
        if (el.summernote('codeview.isActivated')) el.summernote('codeview.deactivate');
        el.val(el.summernote('isEmpty') ? '' : el.summernote('code'));
      });
    });
  } else {
    document.querySelectorAll('[data-rich]').forEach(function (t) { t.classList.add('rich-fallback'); });
  }
  // warn about unsaved changes on edit forms
  var ef = document.querySelector('.content > form.edit, .content > form.form');
  if (ef) {
    ef.addEventListener('input', function () { dirty = true; });
    ef.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }
  // pop-up (modal) forms
  var lastFocus = null;
  var openModal = function (m) {
    if (!m) return;
    lastFocus = document.activeElement;
    m.hidden = false;
    document.body.classList.add('modal-open');
    var first = m.querySelector('input:not([type=hidden]):not([type=checkbox]), select, textarea');
    setTimeout(function () { if (first) first.focus(); }, 60);
  };
  var closeModal = function (m) {
    if (!m || m.hidden) return;
    var f = m.querySelector('form');
    if (f && f.dataset.dirty === '1' && !confirm('Close without saving?')) return;
    m.hidden = true;
    document.body.classList.remove('modal-open');
    if (lastFocus) lastFocus.focus();
  };
  document.querySelectorAll('[data-modal-open]').forEach(function (b) {
    b.addEventListener('click', function (e) { e.preventDefault(); openModal(document.getElementById(b.dataset.modalOpen)); });
  });
  document.querySelectorAll('.modal').forEach(function (m) {
    m.querySelectorAll('[data-modal-close]').forEach(function (b) { b.addEventListener('click', function () { closeModal(m); }); });
    m.addEventListener('mousedown', function (e) { if (e.target === m) closeModal(m); });
    var f = m.querySelector('form');
    if (f) {
      f.addEventListener('input', function () { f.dataset.dirty = '1'; });
      f.addEventListener('submit', function () { f.dataset.dirty = ''; dirty = false; });
    }
    if (m.hasAttribute('data-autoopen')) openModal(m);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var m = document.querySelector('.modal:not([hidden])');
    if (m && !document.querySelector('.note-editor.fullscreen')) closeModal(m);
  });
})();

// close the mobile sidebar with Escape or after picking a page
(function () {
  var t = document.getElementById('navtoggle');
  if (!t) return;
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') t.checked = false; });
  document.querySelectorAll('.side nav a').forEach(function (a) { a.addEventListener('click', function () { t.checked = false; }); });
})();

// content-section field builder
(function () {
  var tpl = document.getElementById('frowTpl');
  if (!tpl) return;
  var sync = function (row) {
    var t = row.querySelector('[data-ftype]'), o = row.querySelector('.fopts');
    if (t && o) o.hidden = t.value !== 'select';
  };
  document.querySelectorAll('.frow').forEach(sync);
  document.addEventListener('change', function (e) { if (e.target.matches('[data-ftype]')) sync(e.target.closest('.frow')); });
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-frow-add]');
    if (add) {
      var box = add.closest('form').querySelector('[data-frows][data-new]');
      var row = tpl.content.firstElementChild.cloneNode(true);
      box.appendChild(row); sync(row);
      var first = row.querySelector('input'); if (first) first.focus();
    }
    var rm = e.target.closest('[data-frow-remove]');
    if (rm) rm.closest('.frow').remove();
  });
  document.querySelectorAll('[data-fields-form]').forEach(function (f) {
    f.addEventListener('submit', function () {
      f.querySelectorAll('[data-frows]').forEach(function (box) {
        box.querySelectorAll('.frow').forEach(function (row, i) {
          row.querySelectorAll('[name]').forEach(function (el) { el.name = el.name.replace(/\[(__i__|\d*)\]$/, '[' + i + ']'); });
        });
      });
    });
  });
})();

// Gallery bulk upload: previews, optional captions, one-by-one upload with progress,
// big photos resized in the browser first so they fit the server's upload limit.
(function () {
  var form = document.querySelector('[data-bulk]');
  if (!form) return;
  var input = form.querySelector('[data-bulk-input]'), drop = form.querySelector('[data-drop]'),
      tiles = form.querySelector('[data-tiles]'), bar = form.querySelector('[data-bar]'),
      count = form.querySelector('[data-count]'), prog = form.querySelector('[data-progress]'),
      start = form.querySelector('[data-start]'), url = form.getAttribute('data-bulk'),
      maxBytes = Math.max(1, parseInt(form.getAttribute('data-max-mb'), 10) || 2) * 1048576,
      csrf = form.querySelector('[name=_csrf]').value, queue = [], busy = false;

  var refresh = function () {
    var n = queue.filter(function (q) { return q.state === 'ready'; }).length;
    tiles.hidden = bar.hidden = queue.length === 0;
    count.textContent = n + ' photo' + (n === 1 ? '' : 's') + ' ready';
    start.disabled = busy || n === 0;
  };
  var add = function (files) {
    Array.prototype.forEach.call(files, function (f) {
      if (!/^image\/(jpeg|png|webp)$/.test(f.type)) return;
      var q = { file: f, state: 'ready' };
      var el = document.createElement('div'); el.className = 'btile';
      var img = document.createElement('img'); img.alt = ''; img.src = URL.createObjectURL(f);
      var cap = document.createElement('input'); cap.placeholder = 'Caption (optional)'; cap.maxLength = 160; cap.setAttribute('aria-label', 'Caption for ' + f.name);
      var st = document.createElement('span'); st.className = 'bst'; st.textContent = (f.size / 1048576).toFixed(1) + ' MB';
      var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'brm'; rm.setAttribute('aria-label', 'Remove'); rm.textContent = '×';
      rm.addEventListener('click', function () { if (busy) return; queue.splice(queue.indexOf(q), 1); el.remove(); refresh(); });
      el.appendChild(img); el.appendChild(rm); el.appendChild(cap); el.appendChild(st);
      q.el = el; q.cap = cap; q.st = st; queue.push(q); tiles.appendChild(el);
    });
    input.value = '';
    refresh();
  };
  // shrink big photos to max 2400px JPEG so they upload fast and stay under the limit
  var prepare = function (f) {
    if (f.size < 2.5 * 1048576 && f.size < maxBytes * 0.9) return Promise.resolve(f);
    if (!window.createImageBitmap) return Promise.resolve(f);
    return createImageBitmap(f).then(function (bmp) {
      var k = Math.min(1, 2400 / Math.max(bmp.width, bmp.height));
      var c = document.createElement('canvas'); c.width = Math.round(bmp.width * k); c.height = Math.round(bmp.height * k);
      c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
      return new Promise(function (res) { c.toBlob(function (b) { res(b ? new File([b], f.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : f); }, 'image/jpeg', 0.86); });
    }).catch(function () { return f; });
  };
  var send = function (q) {
    q.state = 'uploading'; q.el.className = 'btile up'; q.st.textContent = 'Uploading…';
    return prepare(q.file).then(function (file) {
      var fd = new FormData();
      fd.append('_csrf', csrf); fd.append('action', 'bulk_one'); fd.append('image', file);
      fd.append('caption', q.cap.value); fd.append('category', form.elements.category.value);
      return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
        return r.json().catch(function () { return { ok: false, error: r.status === 413 ? 'Too large for the server.' : 'Server error (' + r.status + ').' }; });
      });
    }).then(function (j) {
      q.state = j.ok ? 'done' : 'failed';
      q.el.className = 'btile ' + (j.ok ? 'ok' : 'bad');
      q.st.textContent = j.ok ? 'Added ✓' : (j.error || 'Failed');
    }).catch(function () { q.state = 'failed'; q.el.className = 'btile bad'; q.st.textContent = 'Network error — try again'; });
  };
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var todo = queue.filter(function (q) { return q.state === 'ready'; });
    if (!todo.length || busy) return;
    busy = true; refresh(); prog.hidden = false;
    var done = 0, total = todo.length, fill = prog.querySelector('i'), lab = prog.querySelector('span');
    var tick = function () { fill.style.width = Math.round(done / total * 100) + '%'; lab.textContent = done + ' of ' + total + ' uploaded'; };
    tick();
    var next = function () {
      var q = todo.shift();
      if (!q) return null;
      return send(q).then(function () { done++; tick(); return next(); });
    };
    Promise.all([next(), next()]).then(function () {
      var ok = queue.filter(function (q) { return q.state === 'done'; }).length, bad = queue.filter(function (q) { return q.state === 'failed'; }).length;
      var fd = new FormData(); fd.append('_csrf', csrf); fd.append('action', 'bulk_done'); fd.append('added', ok); fd.append('failed', bad); fd.append('category', form.elements.category.value);
      return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function () {
        busy = false;
        if (!bad) { location.reload(); return; }
        lab.textContent = ok + ' added, ' + bad + ' failed — failed photos are marked in red.';
        queue.forEach(function (q) { if (q.state === 'failed') q.state = 'ready'; });
        start.textContent = 'Retry failed photos'; refresh();
      });
    });
  });
  input.addEventListener('change', function () { add(input.files); });
  form.querySelector('[data-clear]').addEventListener('click', function () { if (busy) return; queue = []; tiles.innerHTML = ''; refresh(); });
  ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add('over'); }); });
  ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.remove('over'); }); });
  drop.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files) add(e.dataTransfer.files); });
  refresh();
})();
