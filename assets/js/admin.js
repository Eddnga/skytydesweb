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
  // warn about unsaved changes on edit forms
  var ef = document.querySelector('form.edit');
  if (ef) {
    var dirty = false;
    ef.addEventListener('input', function () { dirty = true; });
    ef.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }
})();
