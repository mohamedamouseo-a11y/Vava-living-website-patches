(() => {
  'use strict';

  const app = document.getElementById('vava-devhub-app');
  if (!app || typeof VavaDevHub === 'undefined') return;

  const pathInput = app.querySelector('#vdh-push-path');
  const contentInput = app.querySelector('#vdh-push-content');
  const repositoryInput = app.querySelector('#vdh-repository');
  const branchInput = app.querySelector('#vdh-branch');
  const pushGrid = app.querySelector('.vdh-push-grid');
  if (!pathInput || !contentInput || !repositoryInput || !branchInput || !pushGrid) return;

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const api = async (action, data = {}) => {
    const body = new URLSearchParams({ action, nonce: VavaDevHub.nonce, ...data });
    const response = await fetch(VavaDevHub.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
    });
    let json;
    try { json = await response.json(); } catch (error) { throw new Error('Invalid server response.'); }
    if (!json.success) throw new Error(json?.data?.message || 'File Browser request failed.');
    return json.data;
  };

  const pathLabel = pathInput.closest('label');
  const picker = document.createElement('div');
  picker.className = 'vdh-path-picker';
  pathInput.parentNode.insertBefore(picker, pathInput);
  picker.appendChild(pathInput);

  const browseButton = document.createElement('button');
  browseButton.type = 'button';
  browseButton.className = 'button vdh-browse-button';
  browseButton.innerHTML = '<span class="dashicons dashicons-open-folder"></span> Browse files';
  picker.appendChild(browseButton);

  const browser = document.createElement('div');
  browser.id = 'vdh-file-browser';
  browser.className = 'vdh-file-browser is-hidden';
  browser.innerHTML = `
    <div class="vdh-browser-head">
      <div>
        <strong>GitHub File Browser</strong>
        <span>Choose a file and its full path + current content will load automatically.</span>
      </div>
      <button type="button" class="button-link vdh-browser-close" aria-label="Close file browser">×</button>
    </div>
    <div class="vdh-browser-nav">
      <button type="button" class="button vdh-browser-up" disabled><span class="dashicons dashicons-arrow-up-alt2"></span> Up</button>
      <code class="vdh-browser-path">/</code>
      <button type="button" class="button vdh-browser-refresh"><span class="dashicons dashicons-update"></span> Refresh</button>
    </div>
    <div class="vdh-browser-list"><div class="vdh-browser-empty">Open the browser to load files.</div></div>
    <div class="vdh-browser-status" aria-live="polite"></div>`;
  pushGrid.insertAdjacentElement('afterend', browser);

  const list = browser.querySelector('.vdh-browser-list');
  const pathView = browser.querySelector('.vdh-browser-path');
  const status = browser.querySelector('.vdh-browser-status');
  const upButton = browser.querySelector('.vdh-browser-up');
  const refreshButton = browser.querySelector('.vdh-browser-refresh');
  const closeButton = browser.querySelector('.vdh-browser-close');
  let currentPath = '';
  let currentParent = '';
  let loading = false;

  const resetReview = () => {
    pathInput.dispatchEvent(new Event('input', { bubbles: true }));
    contentInput.dispatchEvent(new Event('input', { bubbles: true }));
  };

  const setStatus = (message, type = '') => {
    status.textContent = message || '';
    status.className = `vdh-browser-status${type ? ` is-${type}` : ''}`;
  };

  const formatSize = (bytes) => {
    const value = Number(bytes || 0);
    if (!value) return '';
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${Math.round(value / 1024)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
  };

  const loadFile = async (path) => {
    if (loading) return;
    loading = true;
    setStatus(`Loading ${path}…`);
    try {
      const data = await api('vava_devhub_load_file', {
        repository: repositoryInput.value,
        branch: branchInput.value,
        path,
      });
      pathInput.value = data.path;
      contentInput.value = data.content;
      resetReview();
      browser.classList.add('is-hidden');
      setStatus('');
      if (pathLabel) pathLabel.classList.add('vdh-path-selected');
    } catch (error) {
      setStatus(error.message, 'error');
    } finally {
      loading = false;
    }
  };

  const renderItems = (items) => {
    if (!items.length) {
      list.innerHTML = '<div class="vdh-browser-empty">No editable files or folders are available here.</div>';
      return;
    }

    list.innerHTML = items.map((item) => {
      const isDir = item.type === 'dir';
      return `
        <button type="button" class="vdh-browser-item" data-type="${escapeHtml(item.type)}" data-path="${escapeHtml(item.path)}">
          <span class="dashicons ${isDir ? 'dashicons-category' : 'dashicons-media-code'}"></span>
          <span class="vdh-browser-item-main"><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.path)}</small></span>
          <span class="vdh-browser-meta">${isDir ? 'Folder' : escapeHtml(formatSize(item.size))}</span>
          <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>`;
    }).join('');

    list.querySelectorAll('.vdh-browser-item').forEach((item) => {
      item.addEventListener('click', () => {
        if (item.dataset.type === 'dir') {
          browsePath(item.dataset.path);
        } else {
          loadFile(item.dataset.path);
        }
      });
    });
  };

  const browsePath = async (path = '') => {
    if (loading) return;
    loading = true;
    list.innerHTML = '<div class="vdh-browser-empty">Loading GitHub files…</div>';
    setStatus('');
    try {
      const data = await api('vava_devhub_browse', {
        repository: repositoryInput.value,
        branch: branchInput.value,
        path,
      });
      currentPath = data.path || '';
      currentParent = data.parent || '';
      pathView.textContent = currentPath ? `/${currentPath}` : '/';
      upButton.disabled = currentPath === '';
      renderItems(data.items || []);
    } catch (error) {
      list.innerHTML = `<div class="vdh-browser-empty is-error">${escapeHtml(error.message)}</div>`;
      setStatus(error.message, 'error');
    } finally {
      loading = false;
    }
  };

  browseButton.addEventListener('click', () => {
    browser.classList.toggle('is-hidden');
    if (!browser.classList.contains('is-hidden')) browsePath('');
  });
  closeButton.addEventListener('click', () => browser.classList.add('is-hidden'));
  upButton.addEventListener('click', () => browsePath(currentParent));
  refreshButton.addEventListener('click', () => browsePath(currentPath));

  repositoryInput.addEventListener('change', () => {
    currentPath = '';
    currentParent = '';
    browser.classList.add('is-hidden');
  });
  branchInput.addEventListener('change', () => {
    currentPath = '';
    currentParent = '';
    browser.classList.add('is-hidden');
  });
})();
