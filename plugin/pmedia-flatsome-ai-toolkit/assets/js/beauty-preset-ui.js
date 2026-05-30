(function () {
  function qs(id) { return document.getElementById(id); }
  function esc(s) { return String(s || '').replace(/[&<>]/g, function (m) { return {'&':'&amp;','<':'&lt;','>':'&gt;'}[m]; }); }
  async function api(path, method, body) {
    var opts = { method: method || 'GET', headers: { 'X-WP-Nonce': PMFAI.nonce } };
    if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    var res = await fetch(PMFAI.restUrl + path, opts);
    return await res.json();
  }
  function setColorInput(key, value) {
    var input = document.querySelector('input[name="pmedia_flatsome_ai_toolkit_options[' + key + ']"]');
    if (!input) return;
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }
  function setTextInput(key, value) {
    var input = document.querySelector('input[name="pmedia_flatsome_ai_toolkit_options[' + key + ']"]');
    if (!input) return;
    input.value = value;
  }
  function renderTokens(tokens) {
    if (!tokens) return '';
    var h = '<div class="pmfai-token-grid pmfai-preset-preview">';
    ['primary_color','secondary_color','accent_color','text_color','muted_color','border_color','bg_soft_color'].forEach(function (k) {
      if (tokens[k]) h += '<div class="pmfai-token-card"><span style="background:' + esc(tokens[k]) + '"></span><strong>' + esc(k) + '</strong><code>' + esc(tokens[k]) + '</code></div>';
    });
    h += '</div>';
    h += '<p><span class="pmfai-score">Radius: ' + esc(tokens.radius_sm || '') + ' / ' + esc(tokens.radius_md || '') + ' / ' + esc(tokens.radius_lg || '') + '</span> <span class="pmfai-score">Padding: ' + esc(tokens.section_padding_desktop || '') + ' / ' + esc(tokens.section_padding_mobile || '') + '</span></p>';
    return h;
  }
  function syncSettingsInputs(tokens) {
    if (!tokens) return;
    ['primary_color','secondary_color','accent_color','text_color','muted_color','border_color','bg_soft_color'].forEach(function (k) { if (tokens[k]) setColorInput(k, tokens[k]); });
    ['radius_sm','radius_md','radius_lg','section_padding_desktop','section_padding_mobile'].forEach(function (k) { if (tokens[k]) setTextInput(k, tokens[k]); });
  }
  function findPresetSelect() {
    return qs('pmfai-settings-beauty-preset') || document.querySelector('select[name="pmedia_flatsome_ai_toolkit_options[beauty_preset]"]') || qs('pmfai-page-beauty-preset') || qs('pmfai-generate-beauty-preset') || qs('pmfai-bridge-beauty-preset');
  }
  function injectApplyButton() {
    var select = findPresetSelect();
    if (!select || qs('pmfai-apply-beauty-preset')) return;
    var wrap = document.createElement('div');
    wrap.className = 'pmfai-beauty-preset-actions';
    wrap.style.marginTop = '10px';
    wrap.innerHTML = '<button type="button" class="button" id="pmfai-apply-beauty-preset">Apply Preset Colors</button><div id="pmfai-beauty-preset-result" style="margin-top:12px"></div>';
    var field = select.closest('.pmfai-field') || select.parentNode;
    field.appendChild(wrap);
  }
  document.addEventListener('DOMContentLoaded', injectApplyButton);
  setTimeout(injectApplyButton, 500);

  document.addEventListener('click', async function (e) {
    var t = e.target;
    if (!t || t.id !== 'pmfai-apply-beauty-preset') return;
    e.preventDefault();
    var select = findPresetSelect();
    var preset = select ? select.value : '';
    if (!preset) { alert('Vui lòng chọn Beauty Preset.'); return; }
    if (!confirm('Áp bộ màu/radius/spacing của Beauty Preset này vào Design System hiện tại?')) return;
    var old = t.textContent;
    t.disabled = true;
    t.textContent = 'Đang apply...';
    try {
      var result = await api('/beauty-preset/apply', 'POST', { preset: preset });
      if (result.code) { alert(result.message || 'Apply preset thất bại.'); return; }
      syncSettingsInputs(result.tokens || {});
      var box = qs('pmfai-beauty-preset-result');
      if (box) box.innerHTML = '<div class="pmfai-status-box is-success"><strong>Đã apply Beauty Preset: ' + esc(result.preset) + '</strong><br>Design System đã được cập nhật. Nếu đang ở trang Settings, form cũng đã được đồng bộ giá trị mới.</div>' + renderTokens(result.tokens || {});
    } finally {
      t.disabled = false;
      t.textContent = old;
    }
  });
})();
