// Google Analytics 4 + SkyTydes events.
// Loaded only when a Measurement ID is set in Site settings → SEO & Google,
// and never for dashboard users. Event names follow GA4's recommended names
// where one exists, so they show up in the standard reports.
(function () {
  'use strict';
  var s = document.currentScript, id = s && s.getAttribute('data-ga');
  if (!id) return;
  var D = function (k) { return s.getAttribute('data-' + k) || ''; };
  window.dataLayer = window.dataLayer || [];
  var gtag = window.gtag = function () { window.dataLayer.push(arguments); };
  var pageType = D('page-type');
  gtag('js', new Date());
  gtag('config', id, { content_group: pageType, page_type: pageType });

  var send = function (name, params) { gtag('event', name, params || {}); };
  var text = function (el) { return (el.textContent || el.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim().slice(0, 100); };
  var where = function (el) {
    if (el.closest('#drawer')) return 'mobile_menu';
    if (el.closest('.mega')) return 'mega_menu';
    if (el.closest('header.nav')) return 'header';
    if (el.closest('.wa-float')) return 'floating_button';
    if (el.closest('footer')) return 'footer';
    if (el.closest('.hero')) return 'hero';
    if (el.closest('.mobile-book')) return 'mobile_book_bar';
    return 'page';
  };

  // ----- the page itself -----
  var item = D('item-name');
  if (item) {
    var it = { item_name: item, item_category: D('item-category') };
    var price = parseFloat(D('item-price'));
    var ev = { items: [it] };
    if (price > 0) { it.price = price; ev.value = price; ev.currency = D('item-currency') || 'KES'; }
    send('view_item', ev);
  }
  if (pageType === 'not_found') send('page_not_found', { page_path: location.pathname, page_referrer: document.referrer });

  // ----- clicks -----
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a, button');
    if (!a) return;
    var href = a.getAttribute('href') || '';

    if (a.hasAttribute('data-wa')) {
      return send('whatsapp_click', { lead_source: a.dataset.src || 'general', item_name: a.dataset.item || 'General enquiry', button_text: text(a), location: where(a) });
    }
    if (href.indexOf('tel:') === 0) return send('contact_click', { method: 'phone', number: href.slice(4), location: where(a) });
    if (href.indexOf('mailto:') === 0) return send('contact_click', { method: 'email', location: where(a) });
    if (/doc\.php/.test(href)) {
      return send('file_download', { file_name: text(a.closest('.doccard') || a).slice(0, 80), link_url: href, download: /dl=1/.test(href) ? 'yes' : 'view' });
    }
    if (a.closest('.socials')) return send('social_click', { network: a.getAttribute('aria-label') || text(a), link_url: href });
    if (a.classList.contains('plat')) return send('review_platform_click', { platform: text(a), link_url: href });
    if (a.hasAttribute('data-review-open')) return send('review_form_open', { location: where(a) });

    var card = a.closest('.tcard, .ocard, .pcard, .drow, .dgrid a, .way, .rcard');
    if (card && a.tagName === 'A') {
      var name = (card.querySelector('h3, h2, b, .way-t') || a);
      var list = card.classList.contains('tcard') ? 'tours' : card.classList.contains('ocard') ? 'offers' : card.classList.contains('pcard') ? 'journal' : card.classList.contains('way') ? 'ways_to_travel' : 'destinations';
      return send('select_item', { item_list_name: list + ' · ' + pageType, items: [{ item_name: text(name) }] });
    }
    if (a.closest('.chips, [data-filter]')) return send('filter_used', { filter_value: text(a), page_type: pageType });
    if (a.hasAttribute('data-lightbox')) return send('gallery_photo_view', { photo: (a.querySelector('img') || {}).alt || '' });
    if (a.closest('.hero') && a.classList.contains('btn')) return send('hero_cta_click', { button_text: text(a), link_url: href });
    if (a.closest('#drawer, header.nav, footer, .crumbs')) return send('navigation_click', { link_text: text(a), menu: a.closest('.crumbs') ? 'breadcrumb' : where(a), link_url: href });
    if (a.classList.contains('burger')) return send('menu_open', {});
    if (a.tagName === 'A' && a.hostname && a.hostname !== location.hostname && !/wa\.me|whatsapp/.test(a.hostname)) {
      return send('outbound_click', { link_url: href, link_text: text(a) });
    }
  }, true);

  // ----- forms -----
  document.addEventListener('submit', function (e) {
    var f = e.target, val = function (n) { var el = f.querySelector('[name="' + n + '"]'); return el ? el.value : ''; };
    var fid = f.getAttribute('id') || '';   // (f.id would return the hidden input named "id")
    if (fid === 'waForm') {
      var p = { method: 'whatsapp', lead_source: val('src') || 'general', item_name: val('item') || 'General enquiry' };
      if (val('travellers')) p.travellers = val('travellers');
      if (val('travel_date')) p.travel_date = val('travel_date').slice(0, 40);
      return send('generate_lead', p);
    }
    if ((f.getAttribute('class') || '').indexOf('join-form') > -1) return send('sign_up', { method: 'newsletter', location: where(f) });
    if ((f.getAttribute('class') || '').indexOf('rform') > -1) {
      var r = f.querySelector('input[name=rating]:checked');
      return send('review_submit', { rating: r ? r.value : '' });
    }
  }, true);

  // ----- how far people read a tour or article -----
  if (pageType === 'tour' || pageType === 'blog_post' || pageType === 'destination') {
    var marks = [25, 50, 75], done = {};
    window.addEventListener('scroll', function () {
      var h = document.documentElement, pct = (h.scrollTop + innerHeight) / h.scrollHeight * 100;
      marks.forEach(function (m) { if (pct >= m && !done[m]) { done[m] = 1; send('read_depth', { percent: m, page_type: pageType, item_name: item }); } });
    }, { passive: true });
  }
})();
