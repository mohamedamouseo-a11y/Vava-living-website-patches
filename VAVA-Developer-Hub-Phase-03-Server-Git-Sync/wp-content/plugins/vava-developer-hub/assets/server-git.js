(() => {
  'use strict';

  const app = document.getElementById('vava-devhub-app');
  if (!app || typeof VavaDevHub === 'undefined') return;
  const tabs = app.querySelector('.vdh-tabs');
  if (!tabs || app.querySelector('[data-tab="server-git"]')) return;

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

  const api = async (action, data = {}) => {
    const body = new URLSearchParams({ action, nonce: VavaDevHub.nonce, ...data });
    const response = await fetch(VavaDevHub.ajaxUrl, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
    });
    let json;
    try { json = await response.json(); } catch { throw new Error('Invalid server response.'); }
    if (!json.success) {
      const error = new Error(json?.data?.message || 'Server Git request failed.');
      error.data = json?.data || {};
      throw error;
    }
    return json.data;
  };

  const tab = document.createElement('button');
  tab.className = 'vdh-tab';
  tab.dataset.tab = 'server-git';
  tab.textContent = 'Server Git Sync';
  const securityTab = tabs.querySelector('[data-tab="security"]');
  if (securityTab) tabs.insertBefore(tab, securityTab); else tabs.appendChild(tab);

  const panel = document.createElement('section');
  panel.className = 'vdh-panel vdh-git-panel';
  panel.dataset.panel = 'server-git';
  panel.innerHTML = `
    <div class="vdh-panel-head">
      <div><h2>Server → GitHub Sync</h2><p>Commit the code currently on the live server and push it to GitHub — TCRM-style, with review and safety gates.</p></div>
      <span class="vdh-badge vdh-badge-safe">Git guarded</span>
    </div>
    <div class="vdh-git-alert" id="vdh-git-alert"></div>
    <div class="vdh-git-kpis">
      <div><span>Repository</span><strong id="vdh-git-repo">Checking…</strong><small id="vdh-git-root"></small></div>
      <div><span>Branch</span><strong id="vdh-git-branch">—</strong><small id="vdh-git-head">—</small></div>
      <div><span>Local changes</span><strong id="vdh-git-changes">—</strong><small id="vdh-git-excluded">—</small></div>
      <div><span>Remote sync</span><strong id="vdh-git-sync">—</strong><small id="vdh-git-remote">—</small></div>
    </div>

    <div class="vdh-git-layout">
      <div class="vdh-git-card">
        <div class="vdh-git-card-head"><div><h3>GitHub write access</h3><p>Stored encrypted in WordPress unless VAVA_GITHUB_TOKEN is already configured at server level.</p></div><span id="vdh-git-token-badge" class="vdh-badge">Not configured</span></div>
        <div class="vdh-git-token-row">
          <input id="vdh-git-token" type="password" autocomplete="new-password" placeholder="GitHub token with repository write permission">
          <button type="button" class="button" id="vdh-git-save-token">Save & Verify</button>
          <button type="button" class="button-link-delete" id="vdh-git-remove-token">Remove stored token</button>
        </div>
      </div>

      <div class="vdh-git-card" id="vdh-git-setup-card">
        <div class="vdh-git-card-head"><div><h3>Server repository</h3><p>If this FTP-deployed WordPress root has no .git metadata, connect it without overwriting any live files.</p></div></div>
        <button type="button" class="button button-primary" id="vdh-git-connect">Connect Server Repository</button>
      </div>
    </div>

    <div class="vdh-git-card">
      <div class="vdh-git-card-head">
        <div><h3>Changes ready for Git</h3><p>Only source code under wp-content/themes, wp-content/plugins and wp-content/mu-plugins is eligible. Uploads, cache, backups and secrets are excluded.</p></div>
        <button type="button" class="button" id="vdh-git-refresh">Refresh Status</button>
      </div>
      <div class="vdh-git-changes-list" id="vdh-git-changes-list"><div class="vdh-empty">Open this tab to load server status.</div></div>
    </div>

    <div class="vdh-git-card">
      <div class="vdh-git-card-head"><div><h3>Commit & Push</h3><p>Review creates a 10-minute authorization tied to the exact server state and commit message.</p></div></div>
      <div class="vdh-git-commit-row">
        <input id="vdh-git-message" type="text" maxlength="180" placeholder="Commit message — e.g. Update Vava homepage build">
        <button type="button" class="button" id="vdh-git-review">1. Review Changes</button>
        <button type="button" class="button button-primary" id="vdh-git-commit-push" disabled>2. Commit & Push</button>
      </div>
      <div id="vdh-git-preview"></div>
    </div>

    <div class="vdh-git-card">
      <div class="vdh-git-card-head"><div><h3>Remote operations</h3><p>Pull is fast-forward only and is blocked whenever the live working tree has local changes.</p></div></div>
      <div class="vdh-git-actions">
        <button type="button" class="button" id="vdh-git-push-pending" disabled>Push Pending Commits</button>
        <button type="button" class="button" id="vdh-git-pull">Pull from GitHub (FF-only)</button>
      </div>
    </div>

    <div class="vdh-git-card">
      <div class="vdh-git-card-head"><div><h3>Operation history</h3><p>Recent Server Git actions. Tokens and credentials are never written here.</p></div></div>
      <div id="vdh-git-audit" class="vdh-git-audit"><div class="vdh-empty">No history loaded.</div></div>
    </div>`;

  const existingPanels = app.querySelectorAll('.vdh-panel');
  if (existingPanels.length) existingPanels[existingPanels.length - 1].insertAdjacentElement('afterend', panel);
  else app.appendChild(panel);

  const $ = (selector) => panel.querySelector(selector);
  let statusState = null;
  let previewToken = '';
  let loaded = false;

  const alertBox = (message = '', type = '') => {
    const el = $('#vdh-git-alert');
    el.textContent = message;
    el.className = `vdh-git-alert${message ? ' is-visible' : ''}${type ? ` is-${type}` : ''}`;
  };

  const formatDate = (value) => {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
  };

  const renderAudit = (items = []) => {
    const el = $('#vdh-git-audit');
    if (!items.length) { el.innerHTML = '<div class="vdh-empty">No Server Git operations recorded yet.</div>'; return; }
    el.innerHTML = items.map((item) => `
      <div class="vdh-git-audit-row">
        <span class="vdh-status-dot ${item.success ? 'is-ok' : ''}"></span>
        <div><strong>${escapeHtml((item.action || '').replace(/_/g, ' '))}</strong><small>${escapeHtml(formatDate(item.time))} · WP user ${Number(item.user_id || 0)}</small></div>
        <span class="vdh-badge ${item.success ? 'vdh-badge-safe' : ''}">${item.success ? 'PASS' : 'FAIL'}</span>
      </div>`).join('');
  };

  const renderChanges = (changes = []) => {
    const el = $('#vdh-git-changes-list');
    if (!changes.length) { el.innerHTML = '<div class="vdh-empty">Working tree is clean.</div>'; return; }
    el.innerHTML = changes.map((item) => `
      <div class="vdh-git-change ${item.safe ? '' : 'is-excluded'}">
        <code>${escapeHtml(item.status)}</code>
        <div><strong>${escapeHtml(item.path)}</strong>${item.original_path ? `<small>from ${escapeHtml(item.original_path)}</small>` : ''}</div>
        <span class="vdh-badge ${item.safe ? 'vdh-badge-safe' : ''}">${item.safe ? 'Will commit' : `Excluded · ${escapeHtml(item.reason)}`}</span>
      </div>`).join('');
  };

  const renderStatus = (data) => {
    statusState = data;
    const repoState = !data.git_available ? 'Git unavailable' : (data.repository_present ? (data.remote_valid ? 'Connected' : 'Check remote') : 'Not connected');
    $('#vdh-git-repo').textContent = repoState;
    $('#vdh-git-root').textContent = data.root || '';
    $('#vdh-git-branch').textContent = data.branch || '—';
    $('#vdh-git-head').textContent = data.head_short ? `HEAD ${data.head_short}` : (data.git_version || '—');
    $('#vdh-git-changes').textContent = String(data.safe_count ?? 0);
    $('#vdh-git-excluded').textContent = `${Number(data.excluded_count || 0)} excluded`;
    $('#vdh-git-sync').textContent = `${Number(data.ahead || 0)} ahead / ${Number(data.behind || 0)} behind`;
    $('#vdh-git-remote').textContent = data.remote || 'origin not available';
    renderChanges(data.changes || []);
    renderAudit(data.audit || []);

    const credentials = data.credentials || {};
    const badge = $('#vdh-git-token-badge');
    badge.textContent = credentials.configured ? `Configured · ${credentials.source}` : 'Not configured';
    badge.className = `vdh-badge${credentials.configured ? ' vdh-badge-safe' : ''}`;
    $('#vdh-git-remove-token').style.display = credentials.removable ? '' : 'none';
    $('#vdh-git-setup-card').style.display = data.repository_present ? 'none' : '';
    $('#vdh-git-connect').disabled = !data.setup_available;
    $('#vdh-git-push-pending').disabled = !data.remote_valid || Number(data.ahead || 0) < 1;
    $('#vdh-git-pull').disabled = !data.remote_valid || !data.repository_present;

    if (!data.git_available) alertBox('Server Git Sync cannot execute because PHP proc_open is unavailable or disabled.', 'error');
    else if (data.error) alertBox(data.error, 'error');
    else if (data.repository_present && !data.remote_valid) alertBox('Origin remote does not match the approved Vava GitHub repository. Git writes are blocked.', 'error');
    else alertBox('');
  };

  const loadStatus = async () => {
    previewToken = '';
    $('#vdh-git-commit-push').disabled = true;
    try {
      renderStatus(await api('vava_devhub_git_status'));
    } catch (error) { alertBox(error.message, 'error'); }
  };

  tab.addEventListener('click', () => {
    app.querySelectorAll('.vdh-tab').forEach((node) => node.classList.remove('is-active'));
    app.querySelectorAll('.vdh-panel').forEach((node) => node.classList.remove('is-active'));
    tab.classList.add('is-active');
    panel.classList.add('is-active');
    if (!loaded) { loaded = true; loadStatus(); }
  });

  $('#vdh-git-refresh').addEventListener('click', loadStatus);
  $('#vdh-git-message').addEventListener('input', () => {
    previewToken = '';
    $('#vdh-git-commit-push').disabled = true;
    $('#vdh-git-preview').innerHTML = '';
  });

  $('#vdh-git-save-token').addEventListener('click', async () => {
    const token = $('#vdh-git-token').value.trim();
    if (!token) { alertBox('Enter a GitHub token first.', 'error'); return; }
    const button = $('#vdh-git-save-token'); button.disabled = true; button.textContent = 'Verifying…';
    try {
      const data = await api('vava_devhub_token_save', { token });
      $('#vdh-git-token').value = '';
      alertBox(data.message, 'success');
      await loadStatus();
    } catch (error) { alertBox(error.message, 'error'); }
    finally { button.disabled = false; button.textContent = 'Save & Verify'; }
  });

  $('#vdh-git-remove-token').addEventListener('click', async () => {
    if (!window.confirm('Remove the encrypted GitHub token stored by Developer Hub?')) return;
    try { const data = await api('vava_devhub_token_remove'); alertBox(data.message, 'success'); await loadStatus(); }
    catch (error) { alertBox(error.message, 'error'); }
  });

  $('#vdh-git-connect').addEventListener('click', async () => {
    if (!window.confirm('Connect this live WordPress root to the approved Vava GitHub repository? This creates Git metadata but does NOT overwrite live files.')) return;
    const button = $('#vdh-git-connect'); button.disabled = true; button.textContent = 'Connecting…';
    try {
      const data = await api('vava_devhub_git_connect', { confirmation: 'CONNECT' });
      alertBox(data.message, 'success'); renderStatus(data.status);
    } catch (error) { alertBox(error.message, 'error'); await loadStatus(); }
    finally { button.textContent = 'Connect Server Repository'; }
  });

  $('#vdh-git-review').addEventListener('click', async () => {
    const message = $('#vdh-git-message').value.trim();
    previewToken = '';
    $('#vdh-git-commit-push').disabled = true;
    $('#vdh-git-preview').innerHTML = '<div class="vdh-empty">Reviewing server changes…</div>';
    try {
      const data = await api('vava_devhub_git_preview', { message });
      previewToken = data.preview_token;
      $('#vdh-git-preview').innerHTML = `
        <div class="vdh-git-review">
          <div class="vdh-git-review-head"><strong>Review passed</strong><span class="vdh-badge vdh-badge-safe">${Number(data.safe_count || 0)} safe files · 10 min authorization</span></div>
          ${Number(data.excluded_count || 0) ? `<p>${Number(data.excluded_count)} excluded server files will NOT be committed.</p>` : ''}
          <pre>${escapeHtml(data.diff || 'Tracked diff is empty; selected changes may be new/untracked files.')}</pre>
        </div>`;
      $('#vdh-git-commit-push').disabled = false;
      alertBox('Review passed. Commit & Push is authorized for this exact server state.', 'success');
    } catch (error) { $('#vdh-git-preview').innerHTML = `<div class="vdh-error-box">${escapeHtml(error.message)}</div>`; alertBox(error.message, 'error'); }
  });

  $('#vdh-git-commit-push').addEventListener('click', async () => {
    if (!previewToken || !window.confirm('Commit the reviewed live-server code and PUSH it to GitHub now?')) return;
    const button = $('#vdh-git-commit-push'); button.disabled = true; button.textContent = 'Committing & pushing…';
    try {
      const data = await api('vava_devhub_git_commit_push', { preview_token: previewToken });
      previewToken = '';
      $('#vdh-git-preview').innerHTML = data.commit_sha ? `<div class="vdh-success-box"><strong>Push complete</strong><code>${escapeHtml(data.commit_sha.substring(0, 12))}</code></div>` : '';
      alertBox(data.message, 'success'); renderStatus(data.status);
    } catch (error) {
      previewToken = '';
      alertBox(error.message, 'error');
      if (error.data?.local_commit_created) $('#vdh-git-preview').innerHTML = `<div class="vdh-warning">Local commit ${escapeHtml((error.data.commit_sha || '').substring(0, 12))} was created. Configure/fix GitHub authentication, then use Push Pending Commits.</div>`;
      await loadStatus();
    } finally { button.textContent = '2. Commit & Push'; button.disabled = true; }
  });

  $('#vdh-git-push-pending').addEventListener('click', async () => {
    if (!window.confirm('Push existing local commits on the current server branch to GitHub?')) return;
    const button = $('#vdh-git-push-pending'); button.disabled = true; button.textContent = 'Pushing…';
    try { const data = await api('vava_devhub_git_push_pending'); alertBox(data.message, 'success'); renderStatus(data.status); }
    catch (error) { alertBox(error.message, 'error'); await loadStatus(); }
    finally { button.textContent = 'Push Pending Commits'; }
  });

  $('#vdh-git-pull').addEventListener('click', async () => {
    if (!window.confirm('PULL from GitHub into the LIVE website now? Only a clean working tree and fast-forward update are allowed.')) return;
    const button = $('#vdh-git-pull'); button.disabled = true; button.textContent = 'Pulling…';
    try { const data = await api('vava_devhub_git_pull', { confirmation: 'PULL' }); alertBox(data.message, 'success'); renderStatus(data.status); }
    catch (error) { alertBox(error.message, 'error'); await loadStatus(); }
    finally { button.textContent = 'Pull from GitHub (FF-only)'; }
  });
})();
