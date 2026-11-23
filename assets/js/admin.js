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
          ['view', ['undo', 'redo', 'fullscreen']]
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
        if (el.next('.note-editor').length) el.val(el.summernote('isEmpty') ? '' : el.summernote('code'));
      });
    });
  } else {
    document.querySelectorAll('[data-rich]').forEach(function (t) { t.classList.add('rich-fallback'); });
  }
  // warn about unsaved changes on edit forms
  var ef = document.querySelector('form.edit') || document.querySelector('form.form textarea[data-rich]') && document.querySelector('form.form');
  if (ef) {
    ef.addEventListener('input', function () { dirty = true; });
    ef.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }
})();
