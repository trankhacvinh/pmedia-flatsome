let pmfaiLastParsedBlock = null;
let pmfaiLastDesignSystem = null;
let pmfaiLastPage = null;
let pmfaiLastCreatedDraftId = null;

document.addEventListener('click', async function (e) {
  const t = e.target;

  if (t.classList.contains('pmfai-copy')) {
    e.preventDefault();
    const el = document.getElementById(t.dataset.target);
    if (el) copyText(el.value || el.textContent || '', t);
  }

  if (t.classList.contains('pmfai-copy-text')) {
    e.preventDefault();
    copyText(t.dataset.copy || '', t);
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

  if (t.id === 'pmfai-ds-bridge') {
    e.preventDefault();
    const r = await pmfaiFetch('/design-system/bridge-prompt', 'POST', {brief: val('pmfai-ds-brief')});
    setVal('pmfai-ds-prompt', r.prompt || '');
  }

  if (t.id === 'pmfai-ds-generate') {
    e.preventDefault();
    await withLoading(t, 'Đang generate...', async () => {
      const j = await pmfaiFetch('/design-system/generate', 'POST', {brief: val('pmfai-ds-brief')});
      pmfaiLastDesignSystem = j.code ? null : j;
      setVal('pmfai-ds-json', j.design_system ? JSON.stringify({version: j.version || '1.0', design_system: j.design_system}, null, 2) : (j.raw || ''));
      html('pmfai-ds-result', renderDesignSystemResult(j));
    });
  }

  if (t.id === 'pmfai-ds-import') {
    e.preventDefault();
    const j = await pmfaiFetch('/design-system/import', 'POST', {raw: val('pmfai-ds-json')});
    pmfaiLastDesignSystem = j.code ? null : j;
    html('pmfai-ds-result', renderDesignSystemResult(j));
  }

  if (t.id === 'pmfai-ds-apply') {
    e.preventDefault();
    let data = pmfaiLastDesignSystem;
    if (!data) {
      try { data = JSON.parse(val('pmfai-ds-json')); } catch (err) { alert('JSON không hợp lệ: ' + err.message); return; }
    }
    await withLoading(t, 'Đang apply...', async () => {
      const r = await pmfaiFetch('/design-system/apply', 'POST', data);
      if (r.applied) html('pmfai-ds-result', '<div class="pmfai-status-box is-success">Đã apply Design System vào Settings. Refresh trang để admin preview nhận token mới nhất.</div>' + renderDesignSystemResult(r));
      else html('pmfai-ds-result', renderDesignSystemResult(r));
    });
  }

  if (t.id === 'pmfai-page-bridge') {
    e.preventDefault();
    const r = await pmfaiFetch('/page-builder/bridge-prompt', 'POST', {brief: val('pmfai-page-brief'), buildMode: val('pmfai-page-build-mode'), outputMode: val('pmfai-page-output-mode')});
    setVal('pmfai-page-prompt', r.prompt || '');
  }

  if (t.id === 'pmfai-page-generate') {
    e.preventDefault();
    pmfaiLastCreatedDraftId = null;
    await withLoading(t, 'Đang generate...', async () => {
      const j = await pmfaiFetch('/page-builder/generate', 'POST', {brief: val('pmfai-page-brief'), buildMode: val('pmfai-page-build-mode'), costMode: val('pmfai-page-cost-mode'), outputMode: val('pmfai-page-output-mode')});
      pmfaiLastPage = j.code ? null : j;
      setVal('pmfai-page-json', j.page ? JSON.stringify({version: j.version || '1.0', page: j.page}, null, 2) : (j.raw || ''));
      html('pmfai-page-result', renderPageResult(j));
      renderAllPreviewIframes();
    });
  }

  if (t.id === 'pmfai-page-import') {
    e.preventDefault();
    pmfaiLastCreatedDraftId = null;
    const j = await pmfaiFetch('/page-builder/import', 'POST', {raw: val('pmfai-page-json'), outputMode: val('pmfai-page-output-mode')});
    pmfaiLastPage = j.code ? null : j;
    html('pmfai-page-result', renderPageResult(j));
    renderAllPreviewIframes();
  }

  if (t.id === 'pmfai-page-create-draft') {
    e.preventDefault();
    if (pmfaiLastCreatedDraftId) {
      html('pmfai-page-notice', '<div class="pmfai-status-box is-success">Page Draft đã được tạo rồi (#' + pmfaiLastCreatedDraftId + '). Để tạo bản mới, hãy Generate hoặc Validate lại trước.</div>');
      return;
    }
    let data = pmfaiLastPage;
    if (!data) { try { data = JSON.parse(val('pmfai-page-json')); } catch (err) { alert('JSON không hợp lệ: ' + err.message); return; } }
    await withLoading(t, 'Đang tạo draft...', async () => {
      const r = await pmfaiFetch('/page-builder/create-draft', 'POST', data);
      if (r.created) {
        pmfaiLastCreatedDraftId = r.post_id;
        t.classList.add('is-disabled');
        let notice = '<div class="pmfai-status-box is-success"><strong>Đã tạo Page Draft #' + r.post_id + ' thành công.</strong><div class="pmfai-created-page-actions"><a class="button button-primary" href="' + pmfaiAttr(r.edit_url || '#') + '" target="_blank">Mở trang chỉnh sửa</a><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(r.shortcode || '') + '">Copy Shortcode</button></div></div>';
        notice += renderQualityBox(r.ui_skill_quality || r.ui_skill_shortcode_quality || r.quality, 'UI Skill Quality');
        html('pmfai-page-notice', notice);
      } else html('pmfai-page-notice', renderError(r));
    });
  }

  if (t.id === 'pmfai-generate-block') {
    e.preventDefault();
    await withLoading(t, 'Đang generate...', async () => {
      html('pmfai-generate-result', '<div class="notice notice-info"><p>Đang gọi AI API. Vui lòng không tắt trang.</p></div>');
      const j = await pmfaiFetch('/generate-block', 'POST', {type: val('pmfai-generate-type'), costMode: val('pmfai-generate-cost-mode'), industry: val('pmfai-generate-industry'), style: val('pmfai-generate-style'), goal: val('pmfai-generate-goal'), content: val('pmfai-generate-content')});
      pmfaiLastParsedBlock = j.code ? null : j;
      html('pmfai-generate-result', renderPmfaiResult(j, true, true));
      renderAllPreviewIframes();
    });
  }

  if (t.id === 'pmfai-build-prompt') {
    e.preventDefault();
    const r = await pmfaiFetch('/bridge-prompt', 'POST', {type: val('pmfai-bridge-type'), industry: val('pmfai-bridge-industry'), style: val('pmfai-bridge-style'), goal: val('pmfai-bridge-goal'), content: val('pmfai-bridge-content')});
    setVal('pmfai-bridge-output', r.prompt || '');
  }

  if (t.id === 'pmfai-parse-chatgpt') {
    e.preventDefault();
    const j = await pmfaiFetch('/parse-chatgpt-block', 'POST', {raw: val('pmfai-import-raw')});
    pmfaiLastParsedBlock = j.code ? null : j;
    html('pmfai-import-result', renderPmfaiResult(j, true, true));
    renderAllPreviewIframes();
  }

  if (t.id === 'pmfai-save-imported-block') {
    e.preventDefault();
    if (!pmfaiLastParsedBlock) return;
    const saved = await pmfaiFetch('/blocks', 'POST', Object.assign({}, pmfaiLastParsedBlock, {source: pmfaiLastParsedBlock.source || 'chatgpt-bridge'}));
    if (saved.id) alert('Đã lưu block #' + saved.id + ' vào Library.');
    else if (saved.code) alert(saved.message || 'Lưu block thất bại.');
  }

  if (t.id === 'pmfai-validate-code') {
    e.preventDefault();
    const j = await pmfaiFetch('/validate-code', 'POST', {html: val('pmfai-clean-html'), css: val('pmfai-clean-css')});
    j.html = val('pmfai-clean-html'); j.css = val('pmfai-clean-css');
    html('pmfai-clean-result', renderPmfaiResult(j, false, true));
    renderAllPreviewIframes();
  }

  if (t.id === 'pmfai-auto-fix-code') {
    e.preventDefault();
    const j = await pmfaiFetch('/auto-fix-code', 'POST', {html: val('pmfai-clean-html'), css: val('pmfai-clean-css')});
    if (!j.code) { setVal('pmfai-clean-html', j.html || ''); setVal('pmfai-clean-css', j.css || ''); }
    html('pmfai-clean-result', renderPmfaiResult(j, false, true));
    renderAllPreviewIframes();
  }

  if (t.id === 'pmfai-load-library') { e.preventDefault(); await loadPmfaiLibrary(); }
  if (t.id === 'pmfai-load-usage') { e.preventDefault(); await loadPmfaiUsageLogs(); }

  if (t.id === 'pmfai-import-json-button') {
    e.preventDefault();
    try {
      const imported = await pmfaiFetch('/blocks/import', 'POST', JSON.parse(val('pmfai-import-json')));
      if (imported.id) { alert('Đã import block #' + imported.id); setVal('pmfai-import-json', ''); await loadPmfaiLibrary(); }
      else alert(imported.message || 'Import thất bại.');
    } catch (err) { alert('JSON không hợp lệ: ' + err.message); }
  }

  if (t.classList.contains('pmfai-view-block')) { e.preventDefault(); const block = await pmfaiFetch('/blocks/' + t.dataset.id, 'GET'); html('pmfai-library-detail', renderLibraryDetail(block)); renderAllPreviewIframes(); }
  if (t.classList.contains('pmfai-save-block-meta')) {
    e.preventDefault();
    const id = t.dataset.id;
    const updated = await pmfaiFetch('/blocks/' + id, 'PUT', {title: val('pmfai-edit-title'), type: val('pmfai-edit-type'), style: val('pmfai-edit-style'), industry: val('pmfai-edit-industry'), tags: val('pmfai-edit-tags'), description: val('pmfai-edit-description'), html: val('pmfai-edit-html'), css: val('pmfai-edit-css'), js: val('pmfai-edit-js')});
    html('pmfai-library-detail', renderLibraryDetail(updated)); await loadPmfaiLibrary(); renderAllPreviewIframes();
  }
  if (t.classList.contains('pmfai-duplicate-block')) { e.preventDefault(); const duplicated = await pmfaiFetch('/blocks/' + t.dataset.id + '/duplicate', 'POST', {}); if (duplicated.id) { alert('Đã duplicate thành block #' + duplicated.id); await loadPmfaiLibrary(); } }
  if (t.classList.contains('pmfai-export-block')) { e.preventDefault(); const exported = await pmfaiFetch('/blocks/' + t.dataset.id + '/export', 'GET'); if (exported.id) { copyText(JSON.stringify(exported, null, 2), t); } }
  if (t.classList.contains('pmfai-delete-block')) { e.preventDefault(); if (!confirm('Xóa block này khỏi Library?')) return; await pmfaiFetch('/blocks/' + t.dataset.id, 'DELETE'); await loadPmfaiLibrary(); }
});

document.addEventListener('DOMContentLoaded', function () {
  enhanceColorFields();
  if (document.getElementById('pmfai-library-result')) loadPmfaiLibrary();
  if (document.getElementById('pmfai-usage-result')) loadPmfaiUsageLogs();
});

function val(id) { const el = document.getElementById(id); return el ? el.value : ''; }
function setVal(id, v) { const el = document.getElementById(id); if (el) el.value = v; }
function html(id, v) { const el = document.getElementById(id); if (!el) return ''; if (typeof v !== 'undefined') el.innerHTML = v; return el.innerHTML; }
function copyText(text, btn) { navigator.clipboard.writeText(text || ''); if (btn) { const old = btn.textContent; btn.textContent = 'Copied'; setTimeout(() => btn.textContent = old || 'Copy', 1200); } }
async function withLoading(btn, text, fn) { btn.disabled = true; const old = btn.textContent; btn.textContent = text; try { await fn(); } finally { btn.disabled = false; btn.textContent = old; } }

function enhanceColorFields() {
  const colorNames = ['primary_color','secondary_color','accent_color','text_color','muted_color','border_color','bg_soft_color'];
  document.querySelectorAll('.pmfai-field input[name]').forEach(input => {
    const name = input.getAttribute('name') || '';
    const key = colorNames.find(k => name.indexOf('[' + k + ']') !== -1);
    if (!key || input.closest('.pmfai-color-field')) return;
    const label = input.closest('.pmfai-field'); if (!label) return;
    label.classList.add('pmfai-color-field');
    const picker = document.createElement('input'); picker.type = 'color'; picker.className = 'pmfai-color-input-native'; picker.value = isHexColor(input.value) ? input.value : '#ffffff';
    input.parentNode.appendChild(picker);
    const swatch = document.createElement('span'); swatch.className = 'pmfai-color-swatch'; swatch.style.background = isHexColor(input.value) ? input.value : '#ffffff'; picker.parentNode.insertBefore(swatch, picker);
    input.addEventListener('input', () => { if (isHexColor(input.value)) { picker.value = input.value; swatch.style.background = input.value; } });
    picker.addEventListener('input', () => { input.value = picker.value.toUpperCase(); swatch.style.background = picker.value; });
  });
}
function isHexColor(v) { return /^#[0-9a-f]{6}$/i.test(String(v || '').trim()); }

async function pmfaiFetch(path, method, body) {
  const opts = {method: method || 'GET', headers: {'X-WP-Nonce': PMFAI.nonce}};
  if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const r = await fetch(PMFAI.restUrl + path, opts);
  return await r.json();
}

async function loadPmfaiLibrary() {
  const result = document.getElementById('pmfai-library-result'); if (!result) return;
  const data = await pmfaiFetch('/blocks?per_page=50&s=' + encodeURIComponent(val('pmfai-library-search')) + '&type=' + encodeURIComponent(val('pmfai-library-type')) + '&industry=' + encodeURIComponent(val('pmfai-library-industry')), 'GET');
  if (!data.items || !data.items.length) { result.innerHTML = '<p>Chưa có block nào trong Library.</p><div id="pmfai-library-detail"></div>'; return; }
  let h = '<table class="widefat striped pmfai-library-table"><thead><tr><th>Tên</th><th>Loại</th><th>Ngành</th><th>Style</th><th>Điểm</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead><tbody>';
  data.items.forEach(item => {
    const css = item.scores && item.scores.css_safety ? item.scores.css_safety : '-';
    const flat = item.scores && item.scores.flatsome_compatibility ? item.scores.flatsome_compatibility : '-';
    const skill = item.scores && item.scores.ui_skill && item.scores.ui_skill.score ? item.scores.ui_skill.score : '-';
    h += '<tr><td><strong>' + pmfaiEsc(item.title) + '</strong><br><small>' + pmfaiEsc(item.description || '') + '</small></td><td>' + pmfaiEsc(item.type || '') + '</td><td>' + pmfaiEsc(item.industry || '') + '</td><td>' + pmfaiEsc(item.style || '') + '</td><td>CSS ' + css + ' / Flatsome ' + flat + ' / Skill ' + skill + '</td><td>' + pmfaiEsc(item.created_at || '') + '</td><td><button class="button pmfai-view-block" data-id="' + item.id + '">Xem</button> <button class="button pmfai-duplicate-block" data-id="' + item.id + '">Duplicate</button> <button class="button pmfai-export-block" data-id="' + item.id + '">Export</button> <button class="button pmfai-delete-block" data-id="' + item.id + '">Xóa</button></td></tr>';
  });
  result.innerHTML = h + '</tbody></table><div id="pmfai-library-detail" class="pmfai-panel pmfai-library-detail"></div>';
}

async function loadPmfaiUsageLogs() {
  const result = document.getElementById('pmfai-usage-result'); if (!result) return;
  const data = await pmfaiFetch('/usage-logs?per_page=100&status=' + encodeURIComponent(val('pmfai-usage-status')) + '&mode=' + encodeURIComponent(val('pmfai-usage-mode')), 'GET');
  const s = data.summary || {}; let h = '<div class="pmfai-usage-summary"><span class="pmfai-score">Logs: ' + (s.recent_count || 0) + '</span><span class="pmfai-score">Success: ' + (s.success || 0) + '</span><span class="pmfai-score">Error: ' + (s.error || 0) + '</span><span class="pmfai-score">Prompt tokens: ' + (s.prompt_tokens || 0) + '</span><span class="pmfai-score">Completion tokens: ' + (s.completion_tokens || 0) + '</span><span class="pmfai-score">Total tokens: ' + (s.total_tokens || 0) + '</span></div>';
  if (!data.items || !data.items.length) { result.innerHTML = h + '<p>Chưa có usage log nào.</p>'; return; }
  h += '<table class="widefat striped pmfai-usage-table"><thead><tr><th>Thời gian</th><th>User</th><th>Status</th><th>Mode</th><th>Model</th><th>Type</th><th>Tokens</th><th>Duration</th><th>Error</th></tr></thead><tbody>';
  data.items.forEach(item => { h += '<tr><td>' + pmfaiEsc(item.created_at || '') + '</td><td>' + pmfaiEsc(item.user_login || '') + '</td><td><strong>' + pmfaiEsc(item.status || '') + '</strong></td><td>' + pmfaiEsc(item.mode || '') + '</td><td>' + pmfaiEsc(item.model || '') + '</td><td>' + pmfaiEsc(item.type || '') + '</td><td>' + (item.total_tokens || 0) + ' <small>(' + (item.prompt_tokens || 0) + '/' + (item.completion_tokens || 0) + ')</small></td><td>' + (item.duration_ms || 0) + 'ms</td><td>' + pmfaiEsc(item.error_message || '') + '</td></tr>'; });
  result.innerHTML = h + '</tbody></table>';
}

function renderDesignSystemResult(j) {
  if (j.code && j.message) return renderError(j);
  const ds = j.design_system || j;
  let h = '<div class="pmfai-result"><h2>' + pmfaiEsc(ds.name || 'Design System') + '</h2><p>' + pmfaiEsc(ds.description || '') + '</p>';
  if (j.warnings && j.warnings.length) h += '<h3>Cảnh báo</h3><ul>' + j.warnings.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  h += '<div class="pmfai-token-grid">';
  ['primary_color','secondary_color','accent_color','text_color','muted_color','border_color','bg_soft_color'].forEach(k => { if (ds[k]) h += '<div class="pmfai-token-card"><span style="background:' + pmfaiAttr(ds[k]) + '"></span><strong>' + k + '</strong><code>' + pmfaiEsc(ds[k]) + '</code></div>'; });
  h += '</div><p><span class="pmfai-score">Radius: ' + pmfaiEsc(ds.radius_sm || '') + ' / ' + pmfaiEsc(ds.radius_md || '') + ' / ' + pmfaiEsc(ds.radius_lg || '') + '</span><span class="pmfai-score">Padding: ' + pmfaiEsc(ds.section_padding_desktop || '') + ' / ' + pmfaiEsc(ds.section_padding_mobile || '') + '</span></p></div>';
  return h;
}

function renderPageResult(j) {
  if (j.code && j.message) return renderError(j);
  const page = j.page || {};
  let h = '<div id="pmfai-page-notice"></div><div class="pmfai-result"><h2>' + pmfaiEsc(page.title || 'Generated Page') + '</h2><p>' + pmfaiEsc(page.description || '') + '</p>';
  h += renderQualityBox(j.ui_skill_quality, 'UI Skill Page Quality');
  if (j.cost_mode || j.model || j.output_mode || page.output_mode) h += '<p><span class="pmfai-score">Mode: ' + pmfaiEsc(j.cost_mode || '-') + '</span><span class="pmfai-score">Output: ' + pmfaiEsc(j.output_mode || page.output_mode || '-') + '</span><span class="pmfai-score">Model: ' + pmfaiEsc(j.model || '-') + '</span></p>';
  if (j.visual_quality) h += '<div class="pmfai-quality-box"><strong>Visual Quality Score: ' + (j.visual_quality.score || 0) + '/100</strong></div>';
  if (j.warnings && j.warnings.length) h += '<h3>Cảnh báo</h3><ul>' + j.warnings.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  if (j.suggestions && j.suggestions.length) h += '<h3>Gợi ý cải thiện layout</h3><ul>' + j.suggestions.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  (page.sections || []).forEach(s => { h += '<div class="pmfai-page-section-preview"><h3>' + pmfaiEsc(s.title || s.id || 'Section') + '</h3><p><span class="pmfai-score">Pattern: ' + pmfaiEsc(s.pattern || '-') + '</span><span class="pmfai-score">Type: ' + pmfaiEsc(s.type || '-') + '</span></p>' + renderQualityBox(s.ui_skill_quality, 'Section Quality') + '<p>' + pmfaiEsc(s.goal || '') + '</p>' + renderPreviewBox(s.html || s.shortcode || '', s.css || '') + '</div>'; });
  if (j.shortcode) h += '<h3>Flatsome Shortcode</h3><pre>' + pmfaiEsc(j.shortcode) + '</pre><p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(j.shortcode) + '">Copy Shortcode</button></p>';
  if (j.raw && !j.page) h += '<h3>Raw AI Response</h3><pre>' + pmfaiEsc(j.raw) + '</pre>';
  return h + '</div>';
}

function renderLibraryDetail(block) {
  if (block.code && block.message) return renderError(block);
  let h = '<div class="pmfai-library-editor"><h2>Chỉnh sửa block #' + block.id + '</h2>' + renderQualityBox(block.scores && block.scores.ui_skill, 'UI Skill Quality') + '<div class="pmfai-grid-2">' + editField('pmfai-edit-title', 'Tên block', block.title || '') + editField('pmfai-edit-type', 'Loại block', block.type || '') + editField('pmfai-edit-style', 'Style', block.style || '') + editField('pmfai-edit-industry', 'Ngành nghề', block.industry || '') + editField('pmfai-edit-tags', 'Tags', block.tags || '') + '</div>';
  h += '<label class="pmfai-field"><span>Mô tả</span><textarea id="pmfai-edit-description" rows="3">' + pmfaiEsc(block.description || '') + '</textarea></label>' + renderPreviewBox(block.html || '', block.css || '') + '<label class="pmfai-field"><span>HTML</span><textarea id="pmfai-edit-html" rows="10">' + pmfaiEsc(block.html || '') + '</textarea></label><label class="pmfai-field"><span>CSS</span><textarea id="pmfai-edit-css" rows="8">' + pmfaiEsc(block.css || '') + '</textarea></label><label class="pmfai-field"><span>JS</span><textarea id="pmfai-edit-js" rows="5">' + pmfaiEsc(block.js || '') + '</textarea></label><p><button class="button button-primary pmfai-save-block-meta" data-id="' + block.id + '">Lưu thay đổi</button> <button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(block.html || '') + '">Copy HTML</button> <button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(block.css || '') + '">Copy CSS</button></p></div>';
  return h;
}

function editField(id, label, value) { return '<label class="pmfai-field"><span>' + pmfaiEsc(label) + '</span><input id="' + id + '" value="' + pmfaiAttr(value) + '"></label>'; }
function renderError(j) {
  if (j && j.code === 'ui_quality_gate_failed') return renderQualityGateError(j);
  return '<div class="pmfai-status-box is-error">' + pmfaiEsc((j && (j.message || j.code)) || 'Có lỗi xảy ra.') + '</div>';
}
function pmfaiEsc(s) { return String(s || '').replace(/[&<>]/g, m => ({'&': '&amp;', '<': '&lt;', '>': '&gt;'}[m])); }
function pmfaiAttr(s) { return pmfaiEsc(s).replace(/"/g, '&quot;'); }

function renderQualityGateError(j) {
  const q = j && j.data && j.data.quality ? j.data.quality : null;
  let h = '<div class="pmfai-status-box is-error"><strong>Quality Gate đã chặn thao tác.</strong><br>' + pmfaiEsc((j && j.message) || 'Output chưa đủ an toàn để lưu vào website.') + '</div>';
  if (q) {
    h += renderQualityBox(q, 'UI Skill Quality');
    if (q.section_scores) {
      h += '<details open><summary>Điểm từng section</summary><ul>';
      Object.keys(q.section_scores).forEach(key => { const s = q.section_scores[key] || {}; h += '<li><strong>' + pmfaiEsc(key) + '</strong>: ' + pmfaiEsc(s.score || 0) + '/100 — ' + pmfaiEsc(s.gate || '-') + '</li>'; });
      h += '</ul></details>';
    }
  }
  h += '<div class="pmfai-status-box"><strong>Hướng xử lý:</strong><br>1. Chọn High Quality rồi Generate lại.<br>2. Ưu tiên Section + HTML Block nếu Native Shortcode lỗi.<br>3. Regenerate riêng section có điểm thấp.<br>4. Bổ sung brief rõ hơn về nội dung, CTA, số card và phong cách.</div>';
  return h;
}

function renderQualityBox(quality, label) {
  if (!quality) return '';
  const score = typeof quality.score !== 'undefined' ? quality.score : '-';
  const gate = quality.gate || '-';
  const cls = gate === 'pass' ? 'is-success' : (gate === 'fail' ? 'is-error' : '');
  let h = '<div class="pmfai-status-box ' + cls + '"><strong>' + pmfaiEsc(label || 'UI Skill Quality') + ': ' + pmfaiEsc(score) + '/100</strong><br>Gate: ' + pmfaiEsc(gate) + '</div>';
  if (quality.warnings && quality.warnings.length) h += '<details><summary>Quality warnings</summary><ul>' + quality.warnings.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul></details>';
  return h;
}

function renderPmfaiResult(j, canSave, withPreview) {
  if (j.code && j.message) return renderError(j);
  let h = '<div class="pmfai-result">';
  h += renderQualityBox(j.quality || j.ui_skill_quality || j.ui_skill_shortcode_quality, 'UI Skill Quality');
  if (j.title) h += '<h2>' + pmfaiEsc(j.title) + '</h2>';
  if (j.cost_mode || j.model) h += '<p><span class="pmfai-score">Mode: ' + pmfaiEsc(j.cost_mode || '-') + '</span><span class="pmfai-score">Model: ' + pmfaiEsc(j.model || '-') + '</span></p>';
  if (j.usage && j.usage.total_tokens) h += '<p><span class="pmfai-score">Tokens: ' + j.usage.total_tokens + '</span></p>';
  if (j.scores) h += '<p><span class="pmfai-score">CSS Safety: ' + (j.scores.css_safety || '-') + '/100</span><span class="pmfai-score">Flatsome: ' + (j.scores.flatsome_compatibility || '-') + '/100</span></p>';
  if (j.parse_error) h += '<div class="notice notice-warning"><p>AI trả về chưa parse được: ' + pmfaiEsc(j.parse_error) + '</p></div>';
  if (j.changes && j.changes.length) h += '<h3>Đã tự sửa</h3><ul>' + j.changes.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  if (j.warnings && j.warnings.length) h += '<h3>Cảnh báo</h3><ul>' + j.warnings.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  if (j.suggestions && j.suggestions.length) h += '<h3>Gợi ý</h3><ul>' + j.suggestions.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  if (canSave && j.html) h += '<p><button class="button button-primary" id="pmfai-save-imported-block">Save to Library</button></p>';
  if (withPreview && j.html) h += renderPreviewBox(j.html || '', j.css || '');
  if (j.html) h += '<h3>HTML</h3><pre>' + pmfaiEsc(j.html) + '</pre><p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(j.html) + '">Copy HTML</button></p>';
  if (j.css) h += '<h3>CSS</h3><pre>' + pmfaiEsc(j.css) + '</pre><p><button class="button pmfai-copy-text" data-copy="' + pmfaiAttr(j.css) + '">Copy CSS</button></p>';
  if (j.raw && !j.html) h += '<h3>Raw AI Response</h3><pre>' + pmfaiEsc(j.raw) + '</pre>';
  if (j.prompt) h += '<details><summary>Prompt đã gửi</summary><pre>' + pmfaiEsc(j.prompt) + '</pre></details>';
  return h + '</div>';
}

function renderPreviewBox(html, css) { return '<div class="pmfai-preview-wrap" data-size="desktop"><div class="pmfai-preview-toolbar"><strong>Preview Sandbox</strong><span><button class="button button-primary pmfai-preview-size" data-size="desktop">Desktop</button> <button class="button pmfai-preview-size" data-size="tablet">Tablet</button> <button class="button pmfai-preview-size" data-size="mobile">Mobile</button></span></div><iframe class="pmfai-preview-frame" data-html="' + pmfaiAttr(html) + '" data-css="' + pmfaiAttr(css || '') + '" sandbox="allow-same-origin"></iframe></div>'; }
function renderAllPreviewIframes() { document.querySelectorAll('.pmfai-preview-frame').forEach(frame => { frame.srcdoc = buildPreviewDocument(frame.dataset.html || '', frame.dataset.css || ''); }); }
function buildPreviewDocument(html, css) {
  const tokenCss = (window.PMFAI && PMFAI.tokensCss) ? PMFAI.tokensCss : ':root{--pm-color-primary:#e31e24;--pm-color-secondary:#111827;--pm-color-accent:#f59e0b;--pm-color-text:#1f2937;--pm-color-muted:#6b7280;--pm-color-border:#e5e7eb;--pm-color-bg-soft:#f9fafb;--pm-radius-sm:8px;--pm-radius-md:16px;--pm-radius-lg:24px;--pm-section-padding:72px;--pm-section-padding-mobile:42px;--pm-shadow-sm:0 4px 16px rgba(15,23,42,.08);--pm-shadow-md:0 14px 36px rgba(15,23,42,.12)}';
  const baseCss = `${tokenCss} body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--pm-color-text);background:#fff}.row{display:flex;flex-wrap:wrap;gap:24px;max-width:1180px;margin:0 auto}.col{box-sizing:border-box;flex:1 1 0}.col-inner{width:100%}.small-12{flex-basis:100%}.medium-6,.large-6{flex-basis:calc(50% - 12px)}.button{display:inline-flex;align-items:center;justify-content:center;padding:12px 20px;border-radius:var(--pm-radius-sm);text-decoration:none;font-weight:700}.button.primary{background:var(--pm-color-primary);color:#fff}.pm-section{padding:var(--pm-section-padding) 20px}.pm-section-soft{background:var(--pm-color-bg-soft)}.pm-section-title{text-align:center;max-width:780px;margin:0 auto 36px}.pm-eyebrow{display:inline-block;color:var(--pm-color-primary);font-weight:800;text-transform:uppercase;font-size:13px;letter-spacing:.08em;margin-bottom:10px}.pm-lead{font-size:18px;line-height:1.7;color:var(--pm-color-muted)}.pm-card,.pm-cta-box,.pm-pricing-card,.pm-step{background:#fff;border:1px solid var(--pm-color-border);border-radius:var(--pm-radius-md);box-shadow:var(--pm-shadow-sm);padding:24px}.pm-hero-split,.pm-service-grid,.pm-pricing,.pm-process,.pm-stats{display:grid;gap:24px}.pm-hero-split{grid-template-columns:1.05fr .95fr;align-items:center}.pm-service-grid,.pm-pricing,.pm-process,.pm-stats{grid-template-columns:repeat(3,1fr)}.pm-feature-list{margin:0;padding:0;list-style:none}.pm-feature-list li{margin:0 0 10px;padding-left:24px;position:relative}.pm-feature-list li:before{content:"✓";position:absolute;left:0;color:var(--pm-color-primary);font-weight:900}@media(max-width:849px){.pm-section{padding:var(--pm-section-padding-mobile) 16px}.pm-hero-split,.pm-service-grid,.pm-pricing,.pm-process,.pm-stats{grid-template-columns:1fr}.medium-6,.large-6{flex-basis:100%}}`;
  return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>' + baseCss + '\n' + css + '</style></head><body>' + html + '</body></html>';
}
