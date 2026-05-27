let pmfaiLastParsedBlock = null;

document.addEventListener('click', async function (e) {
  const t = e.target;

  if (t.classList.contains('pmfai-copy')) {
    e.preventDefault();
    const el = document.getElementById(t.dataset.target);
    if (el) {
      navigator.clipboard.writeText(el.value || el.textContent || '');
      const old = t.textContent;
      t.textContent = 'Copied';
      setTimeout(() => t.textContent = old || 'Copy', 1200);
    }
  }

  if (t.classList.contains('pmfai-copy-text')) {
    e.preventDefault();
    navigator.clipboard.writeText(t.dataset.copy || '');
    const old = t.textContent;
    t.textContent = 'Copied';
    setTimeout(() => t.textContent = old || 'Copy', 1200);
  }

  if (t.id === 'pmfai-build-prompt') {
    e.preventDefault();
    const body = {
      type: document.getElementById('pmfai-bridge-type').value,
      industry: document.getElementById('pmfai-bridge-industry').value,
      style: document.getElementById('pmfai-bridge-style').value,
      goal: document.getElementById('pmfai-bridge-goal').value,
      content: document.getElementById('pmfai-bridge-content').value
    };
    const r = await pmfaiFetch('/bridge-prompt', 'POST', body);
    document.getElementById('pmfai-bridge-output').value = r.prompt || '';
  }

  if (t.id === 'pmfai-parse-chatgpt') {
    e.preventDefault();
    const j = await pmfaiFetch('/parse-chatgpt-block', 'POST', {raw: document.getElementById('pmfai-import-raw').value});
    pmfaiLastParsedBlock = j.code ? null : j;
    document.getElementById('pmfai-import-result').innerHTML = renderPmfaiResult(j, true);
  }

  if (t.id === 'pmfai-save-imported-block') {
    e.preventDefault();
    if (!pmfaiLastParsedBlock) return;
    const saved = await pmfaiFetch('/blocks', 'POST', Object.assign({}, pmfaiLastParsedBlock, {source: 'chatgpt-bridge'}));
    if (saved.id) {
      alert('Đã lưu block #' + saved.id + ' vào Library.');
    }
  }

  if (t.id === 'pmfai-validate-code') {
    e.preventDefault();
    const j = await pmfaiFetch('/validate-code', 'POST', {
      html: document.getElementById('pmfai-clean-html').value,
      css: document.getElementById('pmfai-clean-css').value
    });
    document.getElementById('pmfai-clean-result').innerHTML = renderPmfaiResult(j, false);
  }

  if (t.id === 'pmfai-auto-fix-code') {
    e.preventDefault();
    const j = await pmfaiFetch('/auto-fix-code', 'POST', {
      html: document.getElementById('pmfai-clean-html').value,
      css: document.getElementById('pmfai-clean-css').value
    });
    if (!j.code) {
      document.getElementById('pmfai-clean-html').value = j.html || '';
      document.getElementById('pmfai-clean-css').value = j.css || '';
    }
    document.getElementById('pmfai-clean-result').innerHTML = renderPmfaiResult(j, false);
  }

  if (t.id === 'pmfai-load-library') {
    e.preventDefault();
    await loadPmfaiLibrary();
  }

  if (t.classList.contains('pmfai-view-block')) {
    e.preventDefault();
    const block = await pmfaiFetch('/blocks/' + t.dataset.id, 'GET');
    document.getElementById('pmfai-library-detail').innerHTML = renderPmfaiResult(block, false) + '<p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(block.html || '') + '">Copy HTML</button> <button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(block.css || '') + '">Copy CSS</button></p>';
  }

  if (t.classList.contains('pmfai-delete-block')) {
    e.preventDefault();
    if (!confirm('Xóa block này khỏi Library?')) return;
    await pmfaiFetch('/blocks/' + t.dataset.id, 'DELETE');
    await loadPmfaiLibrary();
  }
});

document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('pmfai-library-result')) {
    loadPmfaiLibrary();
  }
});

async function pmfaiFetch(path, method, body) {
  const opts = {method: method || 'GET', headers: {'X-WP-Nonce': PMFAI.nonce}};
  if (body) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  const r = await fetch(PMFAI.restUrl + path, opts);
  return await r.json();
}

async function loadPmfaiLibrary() {
  const result = document.getElementById('pmfai-library-result');
  const s = document.getElementById('pmfai-library-search') ? document.getElementById('pmfai-library-search').value : '';
  const type = document.getElementById('pmfai-library-type') ? document.getElementById('pmfai-library-type').value : '';
  const data = await pmfaiFetch('/blocks?per_page=50&s=' + encodeURIComponent(s) + '&type=' + encodeURIComponent(type), 'GET');
  if (!data.items || !data.items.length) {
    result.innerHTML = '<p>Chưa có block nào trong Library.</p><div id="pmfai-library-detail"></div>';
    return;
  }
  let h = '<table class="widefat striped pmfai-library-table"><thead><tr><th>Tên</th><th>Loại</th><th>Style</th><th>Điểm</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead><tbody>';
  data.items.forEach(item => {
    const css = item.scores && item.scores.css_safety ? item.scores.css_safety : '-';
    const flat = item.scores && item.scores.flatsome_compatibility ? item.scores.flatsome_compatibility : '-';
    h += '<tr><td><strong>' + pmfaiEsc(item.title) + '</strong><br><small>' + pmfaiEsc(item.description || '') + '</small></td><td>' + pmfaiEsc(item.type || '') + '</td><td>' + pmfaiEsc(item.style || '') + '</td><td>CSS ' + css + ' / Flatsome ' + flat + '</td><td>' + pmfaiEsc(item.created_at || '') + '</td><td><button class="button pmfai-view-block" data-id="' + item.id + '">Xem</button> <button class="button pmfai-delete-block" data-id="' + item.id + '">Xóa</button></td></tr>';
  });
  h += '</tbody></table><div id="pmfai-library-detail" class="pmfai-panel pmfai-library-detail"></div>';
  result.innerHTML = h;
}

function pmfaiEsc(s) {
  return String(s || '').replace(/[&<>]/g, m => ({'&': '&amp;', '<': '&lt;', '>': '&gt;'}[m]));
}

function pmfaiAttr(s) {
  return pmfaiEsc(s).replace(/"/g, '&quot;');
}

function renderPmfaiResult(j, canSave) {
  if (j.code && j.message) {
    return '<div class="notice notice-error"><p>' + pmfaiEsc(j.message) + '</p></div>';
  }

  let h = '<div class="pmfai-result">';
  if (j.title) h += '<h2>' + pmfaiEsc(j.title) + '</h2>';
  if (j.scores) {
    h += '<p><span class="pmfai-score">CSS Safety: ' + j.scores.css_safety + '/100</span><span class="pmfai-score">Flatsome: ' + j.scores.flatsome_compatibility + '/100</span></p>';
  }
  if (j.changes && j.changes.length) {
    h += '<h3>Đã tự sửa</h3><ul>' + j.changes.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  }
  if (j.warnings && j.warnings.length) {
    h += '<h3>Cảnh báo</h3><ul>' + j.warnings.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  }
  if (j.suggestions && j.suggestions.length) {
    h += '<h3>Gợi ý</h3><ul>' + j.suggestions.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  }
  if (canSave && j.html) {
    h += '<p><button class="button button-primary" id="pmfai-save-imported-block">Save to Library</button></p>';
  }
  if (j.html) {
    h += '<h3>HTML</h3><pre>' + pmfaiEsc(j.html) + '</pre><p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(j.html) + '">Copy HTML</button></p>';
  }
  if (j.css) {
    h += '<h3>CSS</h3><pre>' + pmfaiEsc(j.css) + '</pre><p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(j.css) + '">Copy CSS</button></p>';
  }
  return h + '</div>';
}
