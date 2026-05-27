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

  if (t.classList.contains('pmfai-preview-size')) {
    e.preventDefault();
    const wrap = t.closest('.pmfai-preview-wrap');
    if (wrap) {
      wrap.dataset.size = t.dataset.size || 'desktop';
      wrap.querySelectorAll('.pmfai-preview-size').forEach(btn => btn.classList.remove('button-primary'));
      t.classList.add('button-primary');
    }
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
    document.getElementById('pmfai-import-result').innerHTML = renderPmfaiResult(j, true, true);
    renderAllPreviewIframes();
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
    j.html = document.getElementById('pmfai-clean-html').value;
    j.css = document.getElementById('pmfai-clean-css').value;
    document.getElementById('pmfai-clean-result').innerHTML = renderPmfaiResult(j, false, true);
    renderAllPreviewIframes();
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
    document.getElementById('pmfai-clean-result').innerHTML = renderPmfaiResult(j, false, true);
    renderAllPreviewIframes();
  }

  if (t.id === 'pmfai-load-library') {
    e.preventDefault();
    await loadPmfaiLibrary();
  }

  if (t.classList.contains('pmfai-view-block')) {
    e.preventDefault();
    const block = await pmfaiFetch('/blocks/' + t.dataset.id, 'GET');
    document.getElementById('pmfai-library-detail').innerHTML = renderPmfaiResult(block, false, true) + '<p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(block.html || '') + '">Copy HTML</button> <button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(block.css || '') + '">Copy CSS</button></p>';
    renderAllPreviewIframes();
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

function renderPmfaiResult(j, canSave, withPreview) {
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
  if (withPreview && j.html) {
    h += renderPreviewBox(j.html || '', j.css || '');
  }
  if (j.html) {
    h += '<h3>HTML</h3><pre>' + pmfaiEsc(j.html) + '</pre><p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(j.html) + '">Copy HTML</button></p>';
  }
  if (j.css) {
    h += '<h3>CSS</h3><pre>' + pmfaiEsc(j.css) + '</pre><p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(j.css) + '">Copy CSS</button></p>';
  }
  return h + '</div>';
}

function renderPreviewBox(html, css) {
  return '<div class="pmfai-preview-wrap" data-size="desktop">' +
    '<div class="pmfai-preview-toolbar"><strong>Preview Sandbox</strong><span>' +
    '<button class="button button-primary pmfai-preview-size" data-size="desktop">Desktop</button> ' +
    '<button class="button pmfai-preview-size" data-size="tablet">Tablet</button> ' +
    '<button class="button pmfai-preview-size" data-size="mobile">Mobile</button>' +
    '</span></div>' +
    '<iframe class="pmfai-preview-frame" data-html="' + pmfaiAttr(html) + '" data-css="' + pmfaiAttr(css || '') + '" sandbox="allow-same-origin"></iframe>' +
    '</div>';
}

function renderAllPreviewIframes() {
  document.querySelectorAll('.pmfai-preview-frame').forEach(frame => {
    const html = frame.dataset.html || '';
    const css = frame.dataset.css || '';
    frame.srcdoc = buildPreviewDocument(html, css);
  });
}

function buildPreviewDocument(html, css) {
  const baseCss = `
    :root{--pm-color-primary:#e31e24;--pm-color-secondary:#111827;--pm-color-accent:#f59e0b;--pm-color-text:#1f2937;--pm-color-muted:#6b7280;--pm-color-border:#e5e7eb;--pm-color-bg-soft:#f9fafb;--pm-radius-sm:8px;--pm-radius-md:16px;--pm-radius-lg:24px;--pm-section-padding:72px;--pm-section-padding-mobile:42px;--pm-shadow-sm:0 4px 16px rgba(15,23,42,.08);--pm-shadow-md:0 14px 36px rgba(15,23,42,.12)}
    body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--pm-color-text);background:#fff}.row{display:flex;flex-wrap:wrap;gap:24px;max-width:1180px;margin:0 auto}.col{box-sizing:border-box;flex:1 1 0}.col-inner{width:100%}.small-12{flex-basis:100%}.medium-6,.large-6{flex-basis:calc(50% - 12px)}.button{display:inline-flex;align-items:center;justify-content:center;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:700}.button.primary{background:var(--pm-color-primary);color:#fff}.pm-section{padding:var(--pm-section-padding) 20px}.pm-section-soft{background:var(--pm-color-bg-soft)}.pm-section-title{text-align:center;max-width:780px;margin:0 auto 36px}.pm-eyebrow{display:inline-block;color:var(--pm-color-primary);font-weight:800;text-transform:uppercase;font-size:13px;letter-spacing:.08em;margin-bottom:10px}.pm-lead{font-size:18px;line-height:1.7;color:var(--pm-color-muted)}.pm-card,.pm-cta-box,.pm-pricing-card,.pm-step{background:#fff;border:1px solid var(--pm-color-border);border-radius:var(--pm-radius-md);box-shadow:var(--pm-shadow-sm);padding:24px}.pm-hero-split,.pm-service-grid,.pm-pricing,.pm-process,.pm-stats{display:grid;gap:24px}.pm-hero-split{grid-template-columns:1.05fr .95fr;align-items:center}.pm-service-grid,.pm-pricing,.pm-process,.pm-stats{grid-template-columns:repeat(3,1fr)}.pm-feature-list{margin:0;padding:0;list-style:none}.pm-feature-list li{margin:0 0 10px;padding-left:24px;position:relative}.pm-feature-list li:before{content:"✓";position:absolute;left:0;color:var(--pm-color-primary);font-weight:900}@media(max-width:849px){.pm-section{padding:var(--pm-section-padding-mobile) 16px}.pm-hero-split,.pm-service-grid,.pm-pricing,.pm-process,.pm-stats{grid-template-columns:1fr}.medium-6,.large-6{flex-basis:100%}}
  `;
  return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>' + baseCss + '\n' + css + '</style></head><body>' + html + '</body></html>';
}
