/* =============================================================
   Gas @ Midway Mews — front-end behaviour
   ============================================================= */

(function () {
  'use strict';
  document.documentElement.classList.add('js');

  // ---------- Configuration ----------
  // Front-end defaults; the real values come from /api/settings.php
  // when the backend is available, otherwise we fall back to these.
  window.GAS_CONFIG = window.GAS_CONFIG || {
    business_name:    'Gas @ Midway Mews',
    primary_phone:    '073 068 1590',
    secondary_phone:  '079 107 5377',
    whatsapp_number:  '27730681590',
    whatsapp_alt:     '27791075377',
    address:          'Midway Mews',
    trading_hours:    'Monday to Saturday: 08:00 - 17:00',
    latitude:         '-25.98688339781942',
    longitude:        '28.111762832127948',
    google_maps_url:  'https://www.google.com/maps/dir/?api=1&destination=-25.98688339781942,28.111762832127948'
  };

  // ---------- Helpers ----------
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

  function formatRand(value) {
    var n = Number(value || 0);
    return 'R' + n.toFixed(0);
  }

  function buildWhatsAppUrl(number, message) {
    var msg = encodeURIComponent(message || '');
    return 'https://wa.me/' + number + (msg ? '?text=' + msg : '');
  }

  function requestAnimationReset(el, className) {
    if (!el) return;
    el.classList.remove(className);
    void el.offsetWidth;
    el.classList.add(className);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // Status display map — keep labels short and not too loud.
  var STATUS_LABELS = {
    available:     'Available today',
    low_stock:     'Low stock',
    confirm_first: 'Confirm first',
    out_of_stock:  'Out of stock'
  };
  var STATUS_ICONS = {
    available:     'bi-check-circle',
    low_stock:     'bi-exclamation-circle',
    confirm_first: 'bi-chat-dots',
    out_of_stock:  'bi-x-circle'
  };

  function statusBadge(status) {
    var s = STATUS_LABELS[status] ? status : 'available';
    return '<span class="availability ' + s + '">' +
             '<i class="bi ' + STATUS_ICONS[s] + '"></i>' +
             STATUS_LABELS[s] +
           '</span>';
  }

  // Static fallback data — used when the backend is not deployed.
  // 19kg and 48kg default to "confirm_first" — bigger sizes typically need
  // a quick check before customers travel.
  var STATIC_PRICES = [
    { id: 1,  cylinder_size: '1.5kg', price:   70, is_popular: false, stock_status: 'available' },
    { id: 2,  cylinder_size: '1.7kg', price:   75, is_popular: false, stock_status: 'available' },
    { id: 3,  cylinder_size: '2.5kg', price:  110, is_popular: false, stock_status: 'available' },
    { id: 4,  cylinder_size: '3kg',   price:  125, is_popular: true,  stock_status: 'available' },
    { id: 5,  cylinder_size: '4.5kg', price:  170, is_popular: false, stock_status: 'available' },
    { id: 6,  cylinder_size: '5kg',   price:  200, is_popular: true,  stock_status: 'available' },
    { id: 7,  cylinder_size: '6kg',   price:  230, is_popular: false, stock_status: 'available' },
    { id: 8,  cylinder_size: '7kg',   price:  285, is_popular: false, stock_status: 'available' },
    { id: 9,  cylinder_size: '9kg',   price:  330, is_popular: true,  stock_status: 'available' },
    { id: 10, cylinder_size: '12kg',  price:  450, is_popular: false, stock_status: 'available' },
    { id: 11, cylinder_size: '14kg',  price:  555, is_popular: false, stock_status: 'available' },
    { id: 12, cylinder_size: '19kg',  price:  660, is_popular: true,  stock_status: 'confirm_first' },
    { id: 13, cylinder_size: '48kg',  price: 1660, is_popular: true,  stock_status: 'confirm_first' }
  ];
  var reservationPrices = STATIC_PRICES.slice();
  var revealObserver = null;
  var isFileMode = window.location.protocol === 'file:';

  function fetchPrices() {
    if (isFileMode) {
      return Promise.resolve(STATIC_PRICES);
    }

    return fetch('/api/prices.php', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
      .then(function (data) {
        if (!data || !Array.isArray(data.prices) || data.prices.length === 0) {
          throw new Error('empty');
        }
        return data.prices;
      })
      .catch(function () { return STATIC_PRICES; });
  }

  function fetchSettings() {
    if (isFileMode) {
      return Promise.resolve();
    }

    return fetch('/api/settings.php', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
      .then(function (data) {
        if (data && data.settings) {
          Object.assign(window.GAS_CONFIG, data.settings);
        }
      })
      .catch(function () { /* keep defaults */ });
  }

  // ---------- Render: selectable price grid ----------
  function renderPriceGrid(target, prices, opts) {
    if (!target) return;
    opts = opts || {};
    target.innerHTML = '';
    prices.forEach(function (p) {
      if (p.is_active === false) return;
      if (opts.popularOnly && !p.is_popular) return;
      if (opts.otherOnly && p.is_popular) return;

      var card = document.createElement('button');
      card.type = 'button';
      card.className = 'price-card';
      var stock = p.stock_status || 'available';
      var disabled = stock === 'out_of_stock';
      if (disabled) card.classList.add('disabled');
      card.setAttribute('data-price-card', '');
      card.setAttribute('data-size', p.cylinder_size);
      card.setAttribute('data-price', String(p.price));
      card.setAttribute('aria-pressed', 'false');
      card.setAttribute('aria-label',
        p.cylinder_size + ' refill, ' + formatRand(p.price) + ', ' + STATUS_LABELS[stock]);

      card.innerHTML =
        '<div class="size">' + p.cylinder_size + '</div>' +
        '<div class="price">' + formatRand(p.price) + '</div>' +
        statusBadge(stock);

      target.appendChild(card);
    });
    bindCardSelection(target);
    observeRevealTargets(target);
  }

  // ---------- Render: full size list (compact rows) ----------
  function renderSizeList(target, prices) {
    if (!target) return;
    target.innerHTML = '';
    prices.forEach(function (p) {
      if (p.is_active === false) return;

      var stock = p.stock_status || 'available';
      var disabled = stock === 'out_of_stock';

      var row = document.createElement('button');
      row.type = 'button';
      row.className = 'row-item';
      if (disabled) row.classList.add('disabled');
      row.setAttribute('data-price-card', '');
      row.setAttribute('data-size', p.cylinder_size);
      row.setAttribute('data-price', String(p.price));
      row.setAttribute('aria-pressed', 'false');
      row.style.background = 'transparent';
      row.style.border = 'none';
      row.style.width = '100%';
      row.style.textAlign = 'left';

      row.innerHTML =
        '<div class="col-size">' + p.cylinder_size +
          (p.is_popular ? ' <span class="pop-tag">Popular</span>' : '') +
        '</div>' +
        '<div class="col-price">' + formatRand(p.price) + '</div>' +
        '<div class="col-status">' + statusBadge(stock) + '</div>';

      target.appendChild(row);
    });
    bindCardSelection(target);
    observeRevealTargets(target);
  }

  // ---------- Render: full price table (used on prices.html) ----------
  function renderPriceTable(target, prices) {
    if (!target) return;
    var html = '<table class="table price-table align-middle mb-0">' +
      '<thead><tr>' +
        '<th scope="col">Cylinder size</th>' +
        '<th scope="col">Price</th>' +
        '<th scope="col">Availability</th>' +
        '<th scope="col" class="text-end">Action</th>' +
      '</tr></thead><tbody>';
    prices.forEach(function (p) {
      if (p.is_active === false) return;
      var stock = p.stock_status || 'available';
      html +=
        '<tr>' +
          '<td><strong>' + p.cylinder_size + '</strong>' +
            (p.is_popular ? ' <span class="badge bg-danger ms-1">Popular</span>' : '') +
          '</td>' +
          '<td><strong class="text-brand-red">' + formatRand(p.price) + '</strong></td>' +
          '<td>' + statusBadge(stock) + '</td>' +
          '<td class="text-end">' +
            (stock === 'out_of_stock'
              ? '<span class="text-muted small">Not available</span>'
              : '<a class="btn btn-sm btn-brand-red price-table-action" href="reserve.html?size=' +
                encodeURIComponent(p.cylinder_size) + '">Reserve</a>') +
          '</td>' +
        '</tr>';
    });
    html += '</tbody></table>';
    target.innerHTML = html;
    observeRevealTargets(target);
  }

  // ---------- Card selection logic ----------
  // Tracks the currently selected size across all selectable surfaces on the page
  // (popular grid + all-sizes list). Click again to deselect.
  function bindCardSelection(scope) {
    var cards = $$('[data-price-card]', scope);
    cards.forEach(function (card) {
      // Avoid double-binding when re-rendered.
      if (card.__selectionBound) return;
      card.__selectionBound = true;
      card.addEventListener('click', function (e) {
        e.preventDefault();
        if (card.classList.contains('disabled')) return;
        var size  = card.getAttribute('data-size');
        var price = card.getAttribute('data-price');
        var nowSelected = !card.classList.contains('selected');
        // Deselect everything across the page so popular + full list stay in sync.
        $$('[data-price-card]').forEach(function (c) {
          c.classList.remove('selected');
          c.setAttribute('aria-pressed', 'false');
        });
        if (nowSelected) {
          // Mark every matching card with the same size, in any list on the page.
          $$('[data-price-card][data-size="' + cssEscape(size) + '"]').forEach(function (c) {
            c.classList.add('selected');
            c.setAttribute('aria-pressed', 'true');
          });
          updateReserveCta(size, price);
          requestAnimationReset(card, 'summary-updated');
        } else {
          updateReserveCta(null, null);
        }
      });
    });
  }

  // Minimal CSS.escape polyfill for older browsers.
  function cssEscape(value) {
    if (window.CSS && CSS.escape) return CSS.escape(value);
    return String(value).replace(/(["\\.[\]:])/g, '\\$1');
  }

  function updateReserveCta(size, price) {
    var btn = document.getElementById('reserveSelectedBtn');
    var label = document.getElementById('reserveSelectionLabel');
    if (!btn) return;
    if (size) {
      btn.textContent = 'Reserve ' + size + ' for Collection';
      btn.disabled = false;
      btn.setAttribute('data-selected-size', size);
      if (label) {
        label.innerHTML = 'You\'ve selected <strong>' + size + '</strong>' +
                          (price ? ' at <strong>' + formatRand(price) + '</strong>' : '') + '.';
      }
    } else {
      btn.textContent = 'Reserve selected cylinder';
      btn.disabled = true;
      btn.removeAttribute('data-selected-size');
      if (label) {
        label.textContent = 'Tap a cylinder above to start a collection request.';
      }
    }
  }

  function bindReserveSelectedButton() {
    var btn = document.getElementById('reserveSelectedBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var size = btn.getAttribute('data-selected-size');
      if (!size) return;
      // Pre-fill the form, scroll to it, focus first field.
      var sizeSelect = document.getElementById('cylinder_size');
      var form = document.getElementById('reservationForm');

      if (!sizeSelect || !form) {
        window.location.href = 'reserve.html?size=' + encodeURIComponent(size);
        return;
      }

      if (sizeSelect) {
        Array.from(sizeSelect.options).forEach(function (o) {
          if (o.value.toLowerCase() === size.toLowerCase()) o.selected = true;
        });
        sizeSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
      if (form && form.scrollIntoView) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      setTimeout(function () {
        var first = document.getElementById('customer_name');
        if (first) first.focus();
      }, 450);
    });
  }

  // ---------- Render: reservation size dropdown ----------
  function renderReservationSizes(target, prices) {
    if (!target) return;
    reservationPrices = prices || STATIC_PRICES;
    var preselect = new URLSearchParams(window.location.search).get('size') || '';
    target.innerHTML = '<option value="">Choose size</option>';
    prices.forEach(function (p) {
      if (p.is_active === false) return;
      if (p.stock_status === 'out_of_stock') return;
      var opt = document.createElement('option');
      opt.value = p.cylinder_size;
      opt.textContent = p.cylinder_size + ' — ' + formatRand(p.price);
      if (preselect && preselect.toLowerCase() === p.cylinder_size.toLowerCase()) {
        opt.selected = true;
      }
      target.appendChild(opt);
    });
  }

  function findReservationPrice(size) {
    if (!size) return null;
    var wanted = String(size).toLowerCase();
    return reservationPrices.find(function (p) {
      return String(p.cylinder_size).toLowerCase() === wanted;
    }) || null;
  }

  function updateQuickSizeButtons(size) {
    $$('[data-quick-size]').forEach(function (btn) {
      var selected = size &&
        btn.getAttribute('data-quick-size').toLowerCase() === String(size).toLowerCase();
      btn.classList.toggle('active', !!selected);
      btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
  }

  function updateSelectedPriceSummary(size) {
    var summary = document.getElementById('selectedPriceSummary');
    var hiddenPrice = document.getElementById('selected_price');
    if (!summary) return;

    var item = findReservationPrice(size);
    if (!item) {
      summary.classList.add('d-none');
      summary.innerHTML = '';
      if (hiddenPrice) hiddenPrice.value = '';
      updateQuickSizeButtons('');
      return;
    }

    var stock = item.stock_status || 'available';
    var price = formatRand(item.price);
    summary.classList.remove('d-none');
    summary.innerHTML =
      '<dl>' +
        '<dt>Selected cylinder</dt><dd>' + escapeHtml(item.cylinder_size) + '</dd>' +
        '<dt>Refill price</dt><dd class="summary-price">' + escapeHtml(price) + '</dd>' +
        '<dt>Availability</dt><dd>' + statusBadge(stock) + '</dd>' +
      '</dl>';
    if (hiddenPrice) hiddenPrice.value = price;
    updateQuickSizeButtons(item.cylinder_size);
    requestAnimationReset(summary, 'summary-updated');
  }

  function bindReservePageControls(prices) {
    var select = document.getElementById('cylinder_size');
    if (!select) return;
    reservationPrices = prices || reservationPrices;

    if (!select.__summaryBound) {
      select.__summaryBound = true;
      select.addEventListener('change', function () {
        updateSelectedPriceSummary(select.value);
      });
    }

    $$('[data-quick-size]').forEach(function (btn) {
      if (btn.__quickSizeBound) return;
      btn.__quickSizeBound = true;
      btn.setAttribute('aria-pressed', 'false');
      btn.addEventListener('click', function () {
        var size = btn.getAttribute('data-quick-size');
        var option = Array.from(select.options).find(function (o) {
          return o.value.toLowerCase() === String(size).toLowerCase();
        });
        if (!option) return;
        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });

    updateSelectedPriceSummary(select.value);
  }

  function getReservationFormData(form) {
    return Object.fromEntries(new FormData(form).entries());
  }

  function buildReservationMessage(data) {
    var cfg = window.GAS_CONFIG;
    var selected = findReservationPrice(data.cylinder_size);
    var selectedPrice = selected ? formatRand(selected.price) : (data.selected_price || '');
    var requestType = (data.request_type || 'refill').replace(/_/g, ' ');
    var collectionTime = data.preferred_collection_time || 'Please suggest the best collection time';

    return {
      text:
        'Hi ' + cfg.business_name + ', I would like to reserve a ' +
        (data.cylinder_size || 'gas cylinder') + ' ' + requestType + ' for collection. ' +
        'My name is ' + (data.customer_name || '') + '. ' +
        'Preferred collection time: ' + collectionTime + '. ' +
        'Please confirm availability.\n\n' +
        'Name: ' + (data.customer_name || '') + '\n' +
        'Phone: ' + (data.phone_number || '') + '\n' +
        'Cylinder size: ' + (data.cylinder_size || '') + '\n' +
        'Request type: ' + requestType + '\n' +
        'Preferred collection time: ' + collectionTime + '\n' +
        (selectedPrice ? 'Selected price: ' + selectedPrice + '\n' : '') +
        (data.notes ? 'Notes: ' + data.notes + '\n' : '') +
        'Collection only. Payment will be completed in store.',
      selectedPrice: selectedPrice,
      collectionTime: collectionTime
    };
  }

  function ensureReservePreview(form) {
    var existing = document.getElementById('reserveMessagePreview');
    if (existing) return existing;

    var submit = form.querySelector('[type="submit"]');
    if (!submit) return null;

    var preview = document.createElement('div');
    preview.id = 'reserveMessagePreview';
    preview.className = 'reserve-preview d-none';
    preview.setAttribute('aria-live', 'polite');
    submit.parentNode.insertBefore(preview, submit);
    return preview;
  }

  function updateReserveMessagePreview(form) {
    var preview = ensureReservePreview(form);
    if (!preview) return;

    var data = getReservationFormData(form);
    if (!data.cylinder_size && !data.customer_name && !data.preferred_collection_time) {
      preview.classList.add('d-none');
      preview.innerHTML = '';
      return;
    }

    var built = buildReservationMessage(data);
    var size = data.cylinder_size || 'your cylinder';
    var time = data.preferred_collection_time || 'collection time to confirm';
    preview.classList.remove('d-none');
    preview.innerHTML =
      '<strong>WhatsApp preview:</strong> Reserve ' + escapeHtml(size) +
      (built.selectedPrice ? ' (' + escapeHtml(built.selectedPrice) + ')' : '') +
      ' for collection. Preferred time: ' + escapeHtml(time) + '.';
    requestAnimationReset(preview, 'preview-updated');
  }

  // ---------- Hydrate WhatsApp / phone / map links ----------
  function hydrateContactLinks() {
    var cfg = window.GAS_CONFIG;

    $$('[data-tel]').forEach(function (a) {
      var which = a.getAttribute('data-tel') || 'primary_phone';
      var num = (cfg[which] || cfg.primary_phone || '').replace(/\s+/g, '');
      a.setAttribute('href', 'tel:' + num);
    });

    $$('[data-tel-display]').forEach(function (el) {
      var which = el.getAttribute('data-tel-display') || 'primary_phone';
      el.textContent = cfg[which] || '';
    });

    $$('[data-wa]').forEach(function (a) {
      var which = a.getAttribute('data-wa') || 'whatsapp_number';
      var num = cfg[which] || cfg.whatsapp_number;
      var msg = a.getAttribute('data-wa-msg') ||
        'Hi ' + cfg.business_name + ', I would like to check availability for a gas refill.';
      a.setAttribute('href', buildWhatsAppUrl(num, msg));
      a.setAttribute('target', '_blank');
      a.setAttribute('rel', 'noopener');
    });

    // Build best-available "navigate to us" link.
    // Priority: explicit google_maps_url → coordinates → address text.
    var hasCoords = cfg.latitude && cfg.longitude &&
                    !isNaN(parseFloat(cfg.latitude)) &&
                    !isNaN(parseFloat(cfg.longitude));
    var directionsUrl = cfg.google_maps_url;
    if (!directionsUrl) {
      directionsUrl = hasCoords
        ? 'https://www.google.com/maps/dir/?api=1&destination=' +
          encodeURIComponent(cfg.latitude + ',' + cfg.longitude)
        : 'https://www.google.com/maps?q=' +
          encodeURIComponent(cfg.address || cfg.business_name);
    }
    $$('[data-map]').forEach(function (a) {
      a.setAttribute('href', directionsUrl);
      a.setAttribute('target', '_blank');
      a.setAttribute('rel', 'noopener');
    });

    $$('[data-business-name]').forEach(function (el) { el.textContent = cfg.business_name; });
    $$('[data-trading-hours]').forEach(function (el) { el.textContent = cfg.trading_hours; });
    $$('[data-address]').forEach(function (el) { el.textContent = cfg.address; });

    // Embed the actual location, not a search result.
    var mapFrame = document.querySelector('[data-map-embed]');
    if (mapFrame) {
      var src;
      if (hasCoords) {
        // Use lat,lng so the pin lands on the exact point.
        src = 'https://www.google.com/maps?q=' +
              encodeURIComponent(cfg.latitude + ',' + cfg.longitude) +
              '&z=17&output=embed';
      } else {
        var q = encodeURIComponent(cfg.address || cfg.business_name);
        src = 'https://www.google.com/maps?q=' + q + '&output=embed';
      }
      mapFrame.setAttribute('src', src);
    }
  }

  // ---------- Reservation form ----------
  function bindReservationForm() {
    var form = document.getElementById('reservationForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }
      var data = getReservationFormData(form);
      var cfg = window.GAS_CONFIG;
      var msg = buildReservationMessage(data).text;

      var status = document.getElementById('reservationStatus');
      if (status) {
        status.classList.remove('d-none', 'alert-danger', 'alert-success');
        status.classList.add('alert-info');
        status.textContent = 'Sending your reservation…';
      }

      // Try to save to the backend; in either case, open WhatsApp afterwards.
      var saveAttempt = isFileMode
        ? Promise.resolve(false)
        : fetch('/api/reservations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
          }).then(function (r) { return r.ok; }).catch(function () { return false; });

      saveAttempt.then(function (saved) {
        if (status) {
          status.classList.remove('alert-info', 'alert-danger');
          status.classList.add('alert-success');
          status.innerHTML = saved
            ? 'Reservation received. We\'ll confirm availability shortly. ' +
              'Opening WhatsApp so you can message us directly.'
            : 'Opening WhatsApp so you can send us your request directly.';
        }

        // Send to WhatsApp after a brief delay so the user sees the confirmation.
        setTimeout(function () {
          window.location.href = buildWhatsAppUrl(cfg.whatsapp_number, msg);
        }, 600);
      });
    });

    ensureReservePreview(form);
    ['input', 'change'].forEach(function (eventName) {
      form.addEventListener(eventName, function () {
        updateReserveMessagePreview(form);
      });
    });
    updateReserveMessagePreview(form);
  }

  // ---------- Enquiry form ----------
  function bindEnquiryForm() {
    var form = document.getElementById('enquiryForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var data = Object.fromEntries(new FormData(form).entries());
      var status = document.getElementById('enquiryStatus');

      if (!data.message || !data.message.trim()) {
        if (status) {
          status.classList.remove('d-none', 'alert-success', 'alert-info');
          status.classList.add('alert-danger');
          status.textContent = 'Please write a message.';
        }
        return;
      }

      var submitAttempt = isFileMode
        ? Promise.reject()
        : fetch('/api/enquiries.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
          });

      submitAttempt.then(function (r) {
        return r.ok ? r.json() : Promise.reject();
      }).then(function () {
        form.reset();
        if (status) {
          status.classList.remove('d-none', 'alert-danger', 'alert-info');
          status.classList.add('alert-success');
          status.textContent = 'Thanks — we received your message. We\'ll be in touch soon.';
        }
      }).catch(function () {
        // Backend not available — fall back to WhatsApp.
        var cfg = window.GAS_CONFIG;
        var msg = 'Hi ' + cfg.business_name + ', ' + (data.message || '');
        if (status) {
          status.classList.remove('d-none', 'alert-danger', 'alert-info');
          status.classList.add('alert-success');
          status.textContent = 'Opening WhatsApp so you can send your message directly.';
        }
        setTimeout(function () {
          window.location.href = buildWhatsAppUrl(cfg.whatsapp_number, msg);
        }, 600);
      });
    });
  }

  function bindMessageTemplateButtons() {
    var message = document.getElementById('message');
    if (!message) return;

    $$('[data-message-template]').forEach(function (btn) {
      if (btn.__messageTemplateBound) return;
      btn.__messageTemplateBound = true;
      btn.addEventListener('click', function () {
        var template = btn.getAttribute('data-message-template') || '';
        message.value = template;
        message.focus();
        requestAnimationReset(message, 'message-flash');
        $$('[data-message-template]').forEach(function (b) {
          b.classList.remove('active');
          b.setAttribute('aria-pressed', 'false');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-pressed', 'true');
      });
    });
  }

  function bindStickyHeader() {
    var navbars = $$('.navbar.sticky-top');
    if (!navbars.length) return;

    function update() {
      var scrolled = window.scrollY > 8;
      navbars.forEach(function (nav) {
        nav.classList.toggle('navbar-scrolled', scrolled);
      });
    }

    update();
    window.addEventListener('scroll', update, { passive: true });
  }

  function observeRevealTargets(root) {
    root = root || document;
    var targets = $$('.info-card, .price-card, .form-card, .proof-card, .trust-strip, .size-list, .price-table-card, .selection-bar, .why-card, .contact-map-block, .contact-travel-note, .contact-proof-section, .price-final-cta, .safety-proof-section', root)
      .filter(function (el) { return !el.__revealObserved; });

    if (!targets.length) return;

    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      targets.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }

    if (!revealObserver) {
      revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    }

    targets.forEach(function (el) {
      el.__revealObserved = true;
      el.classList.add('reveal-on-scroll');
      revealObserver.observe(el);
    });
  }

  // ---------- Active nav link ----------
  function highlightActiveNav() {
    var path = (window.location.pathname.split('/').pop() || 'index.html').toLowerCase();
    if (path === '') path = 'index.html';
    $$('.navbar a.nav-link').forEach(function (a) {
      var href = (a.getAttribute('href') || '').toLowerCase();
      if (href === path) a.classList.add('active');
    });
  }

  // ---------- Init ----------
  document.addEventListener('DOMContentLoaded', function () {
    window.requestAnimationFrame(function () {
      document.documentElement.classList.add('is-loaded');
    });
    highlightActiveNav();
    bindStickyHeader();
    fetchSettings().then(hydrateContactLinks);

    var popularGrid = document.getElementById('popularGrid');
    var allSizes    = document.getElementById('allSizesList');
    var pricePopularGrid = document.getElementById('popularPriceGrid');
    var priceOtherGrid   = document.getElementById('otherPriceGrid');
    var legacyGrid  = document.getElementById('priceGrid');     // older pages
    var table       = document.getElementById('priceTable');
    var sizes       = document.getElementById('cylinder_size');

    if (popularGrid || allSizes || pricePopularGrid || priceOtherGrid || legacyGrid || table || sizes) {
      fetchPrices().then(function (prices) {
        renderPriceGrid(popularGrid, prices, { popularOnly: true });
        renderPriceGrid(pricePopularGrid, prices, { popularOnly: true });
        renderPriceGrid(priceOtherGrid, prices, { otherOnly: true });
        renderSizeList(allSizes, prices);
        renderPriceGrid(legacyGrid, prices); // back-compat for prices.html
        renderPriceTable(table, prices);
        renderReservationSizes(sizes, prices);
        bindReservePageControls(prices);
        updateReserveCta(null, null);
      });
    }

    bindReserveSelectedButton();
    bindReservationForm();
    bindEnquiryForm();
    bindMessageTemplateButtons();
    observeRevealTargets(document);
  });

  // Expose for admin pages that load this script.
  window.MidwayGas = {
    fetchPrices: fetchPrices,
    fetchSettings: fetchSettings,
    formatRand: formatRand,
    buildWhatsAppUrl: buildWhatsAppUrl
  };
})();
