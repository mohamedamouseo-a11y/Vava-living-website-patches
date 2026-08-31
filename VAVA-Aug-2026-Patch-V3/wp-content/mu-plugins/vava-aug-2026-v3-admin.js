(function () {
  'use strict';

  var cfg = window.VAVA_AUG_V3_ADMIN || {};

  function all(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function post(data) {
    var body = new URLSearchParams();
    Object.keys(data || {}).forEach(function (key) { body.set(key, String(data[key] == null ? '' : data[key])); });
    return fetch(cfg.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    }).then(function (response) { return response.json(); });
  }

  function savePathsImage(input) {
    if (!input || !cfg.postId || !cfg.nonce) return;
    var name = String(input.getAttribute('name') || '');
    var keyMap = {
      '_vava_home_paths_image_id': 'vava_home_paths_image_id',
      '_vava_paths_hero_image_id': 'vava_paths_hero_image_id'
    };
    if (!keyMap[name]) return;
    var attachmentId = parseInt(input.value || '0', 10) || 0;
    input.setAttribute('data-vava-v3-saving', '1');
    post({
      action: 'vava_aug_v3_save_paths_image',
      nonce: cfg.nonce,
      post_id: cfg.postId,
      meta_key: keyMap[name],
      attachment_id: attachmentId
    }).then(function (result) {
      input.removeAttribute('data-vava-v3-saving');
      input.setAttribute('data-vava-v3-saved', result && result.success ? '1' : '0');
    }).catch(function () {
      input.removeAttribute('data-vava-v3-saving');
      input.setAttribute('data-vava-v3-saved', '0');
    });
  }

  document.addEventListener('change', function (event) {
    var input = event.target;
    if (!input || !input.matches) return;
    if (input.matches('input[name="_vava_home_paths_image_id"],input[name="_vava_paths_hero_image_id"]')) {
      window.setTimeout(function () { savePathsImage(input); }, 0);
    }
  }, true);

  function fullDescriptionName(shortName) {
    return String(shortName || '').replace(/\[description\]$/, '[full_description]');
  }

  function existingByName(name) {
    return all('textarea,input').some(function (el) { return String(el.getAttribute('name') || '') === name; });
  }

  function ensureFullDescriptions() {
    all('textarea').forEach(function (short) {
      var name = String(short.getAttribute('name') || '');
      var isDigitalProduct = name.indexOf('_vava_selections_products_') !== -1 && name.indexOf('[digital]') !== -1 && /\[description\]$/.test(name);
      if (!isDigitalProduct) return;
      var fullName = fullDescriptionName(name);
      if (!fullName || existingByName(fullName)) {
        var existing = all('textarea').find(function (el) { return String(el.getAttribute('name') || '') === fullName; });
        if (existing) {
          var existingWrap = existing.closest('.vava-repeater-field') || existing.parentElement;
          if (existingWrap) existingWrap.classList.add('vava-v3-full-description');
        }
        return;
      }

      var wrap = document.createElement('div');
      wrap.className = 'vava-repeater-field vava-repeater-field-wide vava-selections-product-field vava-v3-full-description';
      var label = document.createElement('label');
      var title = document.createElement('span');
      var area = document.createElement('textarea');
      title.textContent = document.documentElement.lang === 'en' ? 'Full product description' : 'الوصف الكامل للمنتج';
      area.rows = 9;
      area.name = fullName;
      area.setAttribute('data-product-local-field', 'full_description');
      label.appendChild(title);
      label.appendChild(area);
      wrap.appendChild(label);

      var shortWrap = short.closest('.vava-repeater-field') || short.parentElement;
      if (shortWrap && shortWrap.parentNode) shortWrap.parentNode.insertBefore(wrap, shortWrap.nextSibling);
    });
  }

  function start() {
    ensureFullDescriptions();
    var observer = new MutationObserver(function () { ensureFullDescriptions(); });
    observer.observe(document.body || document.documentElement, { childList: true, subtree: true });

    all('input[name="_vava_home_paths_image_id"],input[name="_vava_paths_hero_image_id"]').forEach(function (input) {
      var last = String(input.value || '');
      window.setInterval(function () {
        var current = String(input.value || '');
        if (current !== last) { last = current; savePathsImage(input); }
      }, 750);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
}());
