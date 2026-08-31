(function () {
  'use strict';

  var cfg = window.VAVA_DIGITAL_PRODUCTS_ADMIN || {};
  var CHUNK_SIZE = 4 * 1024 * 1024;
  var activeUploads = {};

  function q(root, sel) { return (root || document).querySelector(sel); }
  function qa(root, sel) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function pct(field, value, label, meta, state) {
    var box = q(field, '[data-vava-upload-progress]');
    if (!box) return;
    box.hidden = false;
    box.classList.add('is-active');
    box.classList.remove('is-error', 'is-complete');
    if (state === 'error') box.classList.add('is-error');
    if (state === 'complete') box.classList.add('is-complete');
    var bar = q(box, '[data-upload-progress-bar]');
    var num = q(box, '[data-upload-progress-percent]');
    var text = q(box, '[data-upload-progress-label]');
    var info = q(box, '[data-upload-progress-meta]');
    if (bar) bar.style.width = Math.max(0, Math.min(100, value)) + '%';
    if (num) num.textContent = Math.round(value) + '%';
    if (text && label) text.textContent = label;
    if (info && typeof meta !== 'undefined') info.textContent = meta || '';
  }
  function randomId() {
    if (window.crypto && window.crypto.getRandomValues) {
      var arr = new Uint32Array(4); window.crypto.getRandomValues(arr);
      return Array.prototype.map.call(arr, function (x) { return x.toString(16); }).join('');
    }
    return String(Date.now()) + Math.random().toString(16).slice(2);
  }
  function request(field, action, data) {
    var fd = new FormData();
    fd.append('action', action);
    fd.append('nonce', cfg.nonce || '');
    fd.append('post_id', field.getAttribute('data-post-id') || cfg.postId || '');
    fd.append('uid', field.getAttribute('data-product-uid') || '');
    Object.keys(data || {}).forEach(function (key) { fd.append(key, data[key]); });
    return fetch(cfg.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php', { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.text().then(function (t) { var j = {}; try { j = JSON.parse(t || '{}'); } catch (e) {} if (!r.ok || !j.success) throw new Error(j && j.data && j.data.message ? j.data.message : 'تعذر تنفيذ العملية.'); return j.data || {}; }); });
  }
  function renderRecord(field, record) {
    record = record || {};
    field.setAttribute('data-processing-status', record.processing_status || '');
    var state = q(field, '.vava-private-file-state');
    if (state) state.classList.add('has-file');
    var select = q(field, '[data-private-file-select]');
    if (select) select.textContent = document.documentElement.lang === 'en' ? 'Replace' : 'استبدال';
    var status = String(record.processing_status || '');
    var progress = Number(record.processing_progress || 0);
    var msg = record.processing_message || '';
    if (status === 'ready') pct(field, 100, document.documentElement.lang === 'en' ? 'Ready' : 'الملف جاهز', '', 'complete');
    else if (status === 'failed') pct(field, progress, msg || 'تعذر تجهيز الملف', '', 'error');
    else pct(field, Math.max(82, Math.min(99, progress)), msg || 'جارٍ تجهيز صفحات المشاهدة…', '', '');
  }
  function poll(field) {
    var uid = field.getAttribute('data-product-uid') || '';
    if (!uid) return;
    var key = uid + ':' + (field.getAttribute('data-post-id') || cfg.postId || '');
    if (activeUploads[key] && activeUploads[key].poller) return;
    activeUploads[key] = activeUploads[key] || {};
    activeUploads[key].poller = window.setInterval(function () {
      request(field, 'vava_digital_private_pdf_status', {}).then(function (data) {
        var rec = data.record || {};
        qa(document, '[data-vava-private-pdf-field][data-product-uid="' + uid + '"]').forEach(function (target) { renderRecord(target, rec); });
        if (rec.processing_status === 'ready' || rec.processing_status === 'failed' || !rec.processing_status) {
          window.clearInterval(activeUploads[key].poller); delete activeUploads[key];
        }
      }).catch(function (err) {
        window.clearInterval(activeUploads[key].poller); delete activeUploads[key];
        pct(field, 0, err.message, '', 'error');
      });
    }, 1600);
  }
  function uploadChunk(field, file, uploadId, index, total) {
    var start = index * CHUNK_SIZE;
    var end = Math.min(file.size, start + CHUNK_SIZE);
    var chunk = file.slice(start, end, 'application/octet-stream');
    var fd = new FormData();
    fd.append('action', 'vava_aug_v2_pdf_chunk_upload');
    fd.append('nonce', cfg.nonce || '');
    fd.append('post_id', field.getAttribute('data-post-id') || cfg.postId || '');
    fd.append('uid', field.getAttribute('data-product-uid') || '');
    fd.append('upload_id', uploadId);
    fd.append('chunk_index', String(index));
    fd.append('total_chunks', String(total));
    fd.append('file_name', file.name);
    fd.append('file_size', String(file.size));
    fd.append('chunk', chunk, file.name + '.part' + index);

    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php', true);
      xhr.withCredentials = true;
      xhr.upload.addEventListener('progress', function (e) {
        if (!e.lengthComputable) return;
        var overall = ((index + (e.loaded / e.total)) / total) * 80;
        pct(field, overall, document.documentElement.lang === 'en' ? 'Uploading PDF…' : 'جارٍ رفع ملف PDF…', Math.round((index + e.loaded / e.total) / total * 100) + '% من الرفع');
      });
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        var j = {}; try { j = JSON.parse(xhr.responseText || '{}'); } catch (e) {}
        if (xhr.status >= 200 && xhr.status < 300 && j.success) resolve(j.data || {});
        else reject(new Error(j && j.data && j.data.message ? j.data.message : 'تعذر رفع الملف.'));
      };
      xhr.send(fd);
    });
  }
  function upload(field, file) {
    if (!file) return;
    if (!/\.pdf$/i.test(file.name)) { pct(field, 0, 'يُسمح بملفات PDF فقط', file.name, 'error'); return; }
    if (file.size > 50 * 1024 * 1024) { pct(field, 0, 'حجم الملف يتجاوز 50 ميجابايت', '', 'error'); return; }
    var uploadId = randomId();
    var total = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));
    field.classList.add('is-uploading');
    qa(field, 'button').forEach(function (b) { b.disabled = true; });
    pct(field, 1, 'جارٍ بدء رفع ملف PDF…', file.name);

    var chain = Promise.resolve();
    for (var i = 0; i < total; i++) {
      (function (idx) { chain = chain.then(function () { return uploadChunk(field, file, uploadId, idx, total); }); }(i));
    }
    chain.then(function (data) {
      field.classList.remove('is-uploading');
      qa(field, 'button').forEach(function (b) { b.disabled = false; });
      pct(field, 82, 'اكتمل رفع الملف. جاري تجهيز صفحات المشاهدة…', '100% من الرفع');
      if (data.record) renderRecord(field, data.record);
      poll(field);
    }).catch(function (err) {
      field.classList.remove('is-uploading');
      qa(field, 'button').forEach(function (b) { b.disabled = false; });
      pct(field, 0, err.message, '', 'error');
    });
  }

  /** Intercept only PDF file changes before the legacy jQuery delegated handler. */
  document.addEventListener('change', function (event) {
    var input = event.target && event.target.closest ? event.target.closest('.vava-private-file-input') : null;
    if (!input) return;
    event.stopImmediatePropagation();
    event.preventDefault();
    var field = input.closest('[data-vava-private-pdf-field]');
    var file = input.files && input.files[0] ? input.files[0] : null;
    input.value = '';
    if (field && file) upload(field, file);
  }, true);

  /** Add the missing full-description textarea only on older server copies. */
  function ensureFullDescriptions() {
    qa(document, '[data-selections-product-item], .vava-selections-product-item').forEach(function (item) {
      if (q(item, '[data-product-local-field="full_description"]')) return;
      var short = q(item, 'textarea[data-product-local-field="description"]');
      if (!short || !short.name) return;
      var wrap = document.createElement('div');
      wrap.className = 'vava-repeater-field vava-repeater-field-wide vava-selections-product-field vava-v2-full-description';
      var label = document.createElement('label');
      var title = document.createElement('span');
      var area = document.createElement('textarea');
      title.textContent = document.documentElement.lang === 'en' ? 'Full product description' : 'الوصف الكامل للمنتج';
      area.rows = 9;
      area.setAttribute('data-product-local-field', 'full_description');
      area.name = short.name.replace(/\[description\]$/, '[full_description]');
      var saved = item.getAttribute('data-vava-full-description') || '';
      area.value = saved;
      label.appendChild(title); label.appendChild(area); wrap.appendChild(label);
      short.closest('.vava-repeater-field').insertAdjacentElement('afterend', wrap);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ensureFullDescriptions); else ensureFullDescriptions();
  new MutationObserver(ensureFullDescriptions).observe(document.documentElement, { childList: true, subtree: true });
}());
