(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  // header style on scroll
  var nav = $('.nav');
  var onScroll = function () { if (nav) nav.classList.toggle('scrolled', window.scrollY > 40); };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();

  // mobile menu
  var burger = $('.burger');
  if (burger) burger.addEventListener('click', function () {
    var open = document.body.classList.toggle('menu-open');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  $$('#menu a').forEach(function (a) { a.addEventListener('click', function () { document.body.classList.remove('menu-open'); }); });

  // hero slider
  var hero = $('[data-slider]');
  if (hero) {
    var slides = $$('.slide', hero), bars = $$('.bar', hero), count = $('.count b', hero), i = 0, timer;
    var go = function (n) {
      slides[i].classList.remove('on'); if (bars[i]) bars[i].classList.remove('on');
      i = (n + slides.length) % slides.length;
      slides[i].classList.add('on');
      if (bars[i]) { bars[i].classList.remove('on'); void bars[i].offsetWidth; bars[i].classList.add('on'); }
      if (count) count.textContent = (i < 9 ? '0' : '') + (i + 1);
      clearTimeout(timer); timer = setTimeout(function () { go(i + 1); }, 7000);
    };
    bars.forEach(function (b, n) { b.addEventListener('click', function () { go(n); }); });
    if (slides.length > 1) timer = setTimeout(function () { go(1); }, 7000);
  }

  // reveal on scroll
  var revealEls = $$('.sec-head, .tcard, .dcard, .scard, .rcard, .pcard, .why-item, .drow, .itin li, .ccard, .intro-grid > *');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { rootMargin: '0px 0px -60px 0px' });
    revealEls.forEach(function (el, n) { el.classList.add('reveal'); el.style.transitionDelay = (n % 3) * 0.08 + 's'; io.observe(el); });
  }

  // WhatsApp booking sheet
  var sheet = $('#waSheet'), form = $('#waForm');
  $$('[data-wa]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (!sheet || typeof sheet.showModal !== 'function') return; // fall back to the plain link
      e.preventDefault();
      form.elements.src.value = a.dataset.src || 'general';
      form.elements.id.value = a.dataset.id || '0';
      form.elements.item.value = a.dataset.item || '';
      form.elements.page.value = location.pathname;
      var item = $('#waItem');
      item.textContent = (['tour', 'destination', 'service', 'offer'].indexOf(a.dataset.src) > -1) ? a.dataset.item : '';
      item.hidden = !item.textContent;
      document.body.classList.remove('menu-open');
      sheet.showModal();
      setTimeout(function () { form.elements.name.focus(); }, 50);
    });
  });
  if (sheet) {
    $$('[data-close]', sheet).forEach(function (b) { b.addEventListener('click', function () { sheet.close(); }); });
    sheet.addEventListener('click', function (e) { if (e.target === sheet) sheet.close(); });
    form.addEventListener('submit', function () {
      // open WhatsApp in this tab; the server logs the lead and redirects
      setTimeout(function () { sheet.close(); }, 600);
    });
  }

  // gallery filter
  var filter = $('[data-filter]');
  if (filter) filter.addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    $$('button', filter).forEach(function (x) { x.classList.toggle('on', x === b); });
    $$('.gitem').forEach(function (g) { g.classList.toggle('hide', !!b.dataset.cat && g.dataset.cat !== b.dataset.cat); });
  });

  // lightbox
  var lb = $('#lightbox');
  if (lb) {
    var items = $$('[data-lightbox]'), cur = 0, img = $('img', lb), cap = $('figcaption', lb);
    var show = function (n) {
      var vis = items.filter(function (x) { return !x.classList.contains('hide'); });
      if (!vis.length) return;
      cur = (n + vis.length) % vis.length;
      img.src = vis[cur].getAttribute('href'); img.alt = vis[cur].dataset.caption || '';
      cap.textContent = vis[cur].dataset.caption || '';
      lb.hidden = false; document.body.style.overflow = 'hidden';
    };
    var close = function () { lb.hidden = true; document.body.style.overflow = ''; };
    items.forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var vis = items.filter(function (x) { return !x.classList.contains('hide'); });
        show(vis.indexOf(a));
      });
    });
    $('.lb-x', lb).addEventListener('click', close);
    $('.lb-prev', lb).addEventListener('click', function () { show(cur - 1); });
    $('.lb-next', lb).addEventListener('click', function () { show(cur + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') show(cur - 1);
      if (e.key === 'ArrowRight') show(cur + 1);
    });
  }
})();
