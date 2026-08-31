(() => {
  'use strict';

  const app = document.getElementById('vava-devhub-app');
  if (!app || typeof VavaDevHub === 'undefined') return;

  const $ = (selector) => app.querySelector(selector);
  const $$ = (selector) => Array.from(app.querySelectorAll(selector));
  const state = { snapshot: null, previewToken: '', loading: false };

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const toast = (message, type = 'info') => {
    const el = $('#vdh-toast');
    el.textContent = message;
    el.className = `vdh-toast is-visible is-${type}`;
    window.clearTimeout(toast.timer);
    toast.timer = window.setTimeout(() => { el.className = 'vdh-toast'; }, 4500);
  };

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
    if (!json.success) throw new Error(json?.data?.message || 'Developer Hub request failed.');
    return json.data;
  };

  const formatDate = (value) => {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
  };

  const branchOptions = (items, selected) => items.map((branch) =>
    `<option value="${escapeHtml(branch.name)}"${branch.name === selected ? ' selected' : ''}>${escapeHtml(branch.name)}${branch.protected ? ' · protected' : ''}</option>`
  ).join('');

  const renderCommits = (commits) => {
    const el = $('#vdh-commits');
    if (!commits?.items?.length) {
      el.innerHTML = `<div class="vdh-empty">${escapeHtml(commits?.error || 'No commits found.')}</div>`;
      return;
    }
    el.innerHTML = commits.items.map((commit) => `
      <a class="vdh-row" href="${escapeHtml(commit.url)}" target="_blank" rel="noopener noreferrer">
        <div class="vdh-row-main"><strong>${escapeHtml(commit.message || 'Commit')}</strong><span>${escapeHtml(commit.author || 'Unknown')} · ${escapeHtml(formatDate(commit.date))}</span></div>
        <code>${escapeHtml(commit.short_sha)}</code>
      </a>`).join('');
  };

  const renderPulls = (pulls) => {
    const el = $('#vdh-pulls');
    if (!pulls?.items?.length) {
      el.innerHTML = `<div class="vdh-empty">${escapeHtml(pulls?.error || 'No open pull requests.')}</div>`;
      return;
    }
    el.innerHTML = pulls.items.map((pr) => `
      <a class="vdh-row" href="${escapeHtml(pr.url)}" target="_blank" rel="noopener noreferrer">
        <div class="vdh-row-main"><strong>#${pr.number} · ${escapeHtml(pr.title)}</strong><span>${escapeHtml(pr.head)} → ${escapeHtml(pr.base)} · ${escapeHtml(pr.user)} · ${escapeHtml(formatDate(pr.updated_at))}</span></div>
        <span class="vdh-badge ${pr.draft ? '' : 'vdh-badge-safe'}">${pr.draft ? 'Draft' : 'Open'}</span>
      </a>`).join('');
  };

  const renderSnapshot = (data) => {
    state.snapshot = data;
    const connection = data.connection || {};
    const repo = data.repository || {};
    const branches = data.branches?.items || [];
    const selected = data.selected_branch || repo.default_branch || 'main';

    $('#vdh-connection').innerHTML = `<span class="vdh-status-dot ${connection.authenticated ? 'is-ok' : ''}"></span>${escapeHtml(connection.message || 'GitHub status unavailable')}`;
    $('#vdh-card-github').textContent = connection.authenticated ? 'Connected' : (connection.configured ? 'Check token' : 'Read only');
    $('#vdh-card-github-sub').textContent = connection.login || 'GitHub API';
    $('#vdh-card-repo').textContent = repo.name || 'Unavailable';
    $('#vdh-card-repo-sub').textContent = repo.private ? 'Private repository' : 'Public repository';
    $('#vdh-card-branch').textContent = selected;
    $('#vdh-card-branch-sub').textContent = `${branches.length} branches`;
    $('#vdh-card-prs').textContent = String(data.pulls?.items?.length || 0);

    if (branches.length) {
      $('#vdh-branch').innerHTML = branchOptions(branches, selected);
      $('#vdh-compare-base').innerHTML = branchOptions(branches, repo.default_branch || 'main');
      $('#vdh-compare-head').innerHTML = branchOptions(branches, selected);
    }
    renderCommits(data.commits);
    renderPulls(data.pulls);
    $('#vdh-token-warning').classList.toggle('is-hidden', !!connection.authenticated);
  };

  const loadSnapshot = async (keepBranch = true) => {
    if (state.loading) return;
    state.loading = true;
    $('#vdh-refresh').disabled = true;
    $('#vdh-refresh').textContent = 'Refreshing…';
    try {
      const repository = $('#vdh-repository').value;
      const branch = keepBranch ? $('#vdh-branch').value : '';
      const data = await api('vava_devhub_snapshot', { repository, branch });
      renderSnapshot(data);
    } catch (error) {
      toast(error.message, 'error');
      $('#vdh-connection').textContent = error.message;
    } finally {
      state.loading = false;
      $('#vdh-refresh').disabled = false;
      $('#vdh-refresh').textContent = 'Refresh';
    }
  };

  const renderCompare = (data) => {
    const files = data.files || [];
    $('#vdh-compare-result').innerHTML = `
      <div class="vdh-compare-summary">
        <div><span>Status</span><strong>${escapeHtml(data.status || '—')}</strong></div>
        <div><span>Ahead</span><strong>${Number(data.ahead_by || 0)}</strong></div>
        <div><span>Behind</span><strong>${Number(data.behind_by || 0)}</strong></div>
        <div><span>Commits</span><strong>${Number(data.total_commits || 0)}</strong></div>
      </div>
      <div class="vdh-list">${files.length ? files.map((file) => `
        <details class="vdh-diff">
          <summary><strong>${escapeHtml(file.filename)}</strong><span>${escapeHtml(file.status)} · <b>+${file.additions}</b> / <b>-${file.deletions}</b></span></summary>
          ${file.patch ? `<pre>${escapeHtml(file.patch)}</pre>` : '<div class="vdh-empty">Patch preview is not available for this file.</div>'}
        </details>`).join('') : '<div class="vdh-empty">No file changes between these branches.</div>'}
      </div>`;
  };

  const reviewPush = async () => {
    state.previewToken = '';
    $('#vdh-confirm-push').disabled = true;
    $('#vdh-push-review').innerHTML = '';
    const payload = {
      repository: $('#vdh-repository').value,
      branch: $('#vdh-branch').value,
      path: $('#vdh-push-path').value,
      message: $('#vdh-push-message').value,
      content: $('#vdh-push-content').value,
    };
    $('#vdh-review-push').disabled = true;
    try {
      const data = await api('vava_devhub_review', payload);
      state.previewToken = data.preview_token;
      const checks = Object.entries(data.checks || {}).map(([name, status]) => `<span class="vdh-check"><b>✓</b>${escapeHtml(name.replace(/_/g, ' '))}: ${escapeHtml(status)}</span>`).join('');
      $('#vdh-push-review').innerHTML = `
        <div class="vdh-review-card">
          <div class="vdh-review-title"><strong>${data.is_new ? 'New file' : 'Existing file'} · ${escapeHtml(data.path)}</strong><span class="vdh-badge vdh-badge-safe">Review passed</span></div>
          <div class="vdh-review-metrics"><span>+${data.additions} additions</span><span>-${data.deletions} deletions</span><span>${data.bytes} bytes</span><span>Authorization: 10 min</span></div>
          <div class="vdh-checks">${checks}</div>
          <code class="vdh-hash">SHA-256 ${escapeHtml(data.content_sha256)}</code>
        </div>`;
      $('#vdh-confirm-push').disabled = false;
      toast('Review passed. Controlled push is authorized for this exact content.', 'success');
    } catch (error) {
      toast(error.message, 'error');
      $('#vdh-push-review').innerHTML = `<div class="vdh-error-box">${escapeHtml(error.message)}</div>`;
    } finally {
      $('#vdh-review-push').disabled = false;
    }
  };

  const confirmPush = async () => {
    if (!state.previewToken) return;
    const button = $('#vdh-confirm-push');
    button.disabled = true;
    button.textContent = 'Pushing…';
    try {
      const data = await api('vava_devhub_push', {
        preview_token: state.previewToken,
        repository: $('#vdh-repository').value,
        branch: $('#vdh-branch').value,
        path: $('#vdh-push-path').value,
        message: $('#vdh-push-message').value,
        content: $('#vdh-push-content').value,
      });
      state.previewToken = '';
      $('#vdh-push-review').innerHTML = `<div class="vdh-success-box"><strong>Push completed.</strong>${data.commit_sha ? `<code>${escapeHtml(data.commit_sha.substring(0, 12))}</code>` : ''}${data.commit_url ? `<a href="${escapeHtml(data.commit_url)}" target="_blank" rel="noopener noreferrer">Open commit ↗</a>` : ''}</div>`;
      toast(data.message || 'Controlled push completed.', 'success');
      await loadSnapshot(true);
    } catch (error) {
      state.previewToken = '';
      toast(error.message, 'error');
      $('#vdh-push-review').innerHTML = `<div class="vdh-error-box">${escapeHtml(error.message)}</div>`;
    } finally {
      button.textContent = '2. Authorize Controlled Push';
      button.disabled = true;
    }
  };

  $$('.vdh-tab').forEach((tab) => tab.addEventListener('click', () => {
    $$('.vdh-tab').forEach((item) => item.classList.remove('is-active'));
    $$('.vdh-panel').forEach((panel) => panel.classList.remove('is-active'));
    tab.classList.add('is-active');
    $(`[data-panel="${tab.dataset.tab}"]`).classList.add('is-active');
  }));

  $('#vdh-refresh').addEventListener('click', () => loadSnapshot(true));
  $('#vdh-repository').addEventListener('change', () => { state.previewToken = ''; loadSnapshot(false); });
  $('#vdh-branch').addEventListener('change', () => { state.previewToken = ''; loadSnapshot(true); });
  $('#vdh-run-compare').addEventListener('click', async () => {
    const button = $('#vdh-run-compare');
    button.disabled = true;
    $('#vdh-compare-result').innerHTML = '<div class="vdh-empty">Comparing…</div>';
    try {
      const data = await api('vava_devhub_compare', {
        repository: $('#vdh-repository').value,
        base: $('#vdh-compare-base').value,
        head: $('#vdh-compare-head').value,
      });
      renderCompare(data);
    } catch (error) {
      $('#vdh-compare-result').innerHTML = `<div class="vdh-error-box">${escapeHtml(error.message)}</div>`;
      toast(error.message, 'error');
    } finally {
      button.disabled = false;
    }
  });
  $('#vdh-review-push').addEventListener('click', reviewPush);
  $('#vdh-confirm-push').addEventListener('click', confirmPush);
  ['#vdh-push-path', '#vdh-push-message', '#vdh-push-content'].forEach((selector) => {
    $(selector).addEventListener('input', () => {
      state.previewToken = '';
      $('#vdh-confirm-push').disabled = true;
    });
  });

  loadSnapshot(false);
})();