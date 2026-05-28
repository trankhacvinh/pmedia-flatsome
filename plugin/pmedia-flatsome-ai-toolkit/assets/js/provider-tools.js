(function () {
  function qs(id) { return document.getElementById(id); }
  function esc(value) { return String(value || '').replace(/[&<>]/g, function (m) { return {'&':'&amp;','<':'&lt;','>':'&gt;'}[m]; }); }
  function api(path, method, body) {
    var opts = { method: method || 'GET', headers: { 'X-WP-Nonce': PMFAI.nonce } };
    if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    return fetch(PMFAI.restUrl + path, opts).then(function (res) { return res.json(); });
  }

  function insertProviderTester() {
    var providerSelect = document.querySelector('select[name="pmedia_flatsome_ai_toolkit_options[ai_provider]"]');
    if (!providerSelect || qs('pmfai-provider-test')) return;
    var wrap = document.createElement('div');
    wrap.className = 'pmfai-status-box';
    wrap.innerHTML = '<strong>Kiểm tra AI Provider</strong><p>Hãy lưu Settings trước, sau đó bấm test để kiểm tra endpoint/API key/model hiện tại.</p><p><button type="button" class="button button-primary" id="pmfai-provider-test">Test AI Provider</button></p><div id="pmfai-provider-test-result"></div>';
    var field = providerSelect.closest('.pmfai-field');
    if (field && field.parentNode) {
      field.parentNode.insertBefore(wrap, field.nextSibling);
    }
  }

  function renderProviderTestResult(result) {
    if (result.code && result.message) {
      return '<div class="pmfai-status-box is-error"><strong>Test thất bại:</strong> ' + esc(result.message) + '</div>';
    }
    return '<div class="pmfai-status-box is-success"><strong>Kết nối thành công.</strong><br>Provider: ' + esc(result.provider_label || result.provider) + '<br>Model: ' + esc(result.model) + '<br>Duration: ' + esc(result.duration_ms) + 'ms<br><small>' + esc(result.content || '') + '</small></div>';
  }

  function enhanceUsageLogs() {
    var result = qs('pmfai-usage-result');
    var loadButton = qs('pmfai-load-usage');
    if (!result || !loadButton || loadButton.__pmfaiProviderEnhanced) return;
    loadButton.__pmfaiProviderEnhanced = true;

    var toolbar = loadButton.closest('.pmfai-toolbar');
    if (toolbar && !qs('pmfai-usage-provider')) {
      var select = document.createElement('select');
      select.id = 'pmfai-usage-provider';
      select.innerHTML = '<option value="">Tất cả provider</option><option value="openai">OpenAI</option><option value="openai_compatible">OpenAI-compatible</option><option value="anthropic">Anthropic Claude</option>';
      toolbar.insertBefore(select, loadButton);
    }

    loadButton.addEventListener('click', function (e) {
      e.preventDefault();
      loadUsageLogsV2();
    }, true);
  }

  function loadUsageLogsV2() {
    var status = qs('pmfai-usage-status') ? qs('pmfai-usage-status').value : '';
    var mode = qs('pmfai-usage-mode') ? qs('pmfai-usage-mode').value : '';
    var provider = qs('pmfai-usage-provider') ? qs('pmfai-usage-provider').value : '';
    var result = qs('pmfai-usage-result');
    if (!result) return;
    result.innerHTML = '<p>Đang tải logs...</p>';
    api('/usage-logs-v2?per_page=100&status=' + encodeURIComponent(status) + '&mode=' + encodeURIComponent(mode) + '&provider=' + encodeURIComponent(provider), 'GET').then(function (data) {
      var s = data.summary || {};
      var providerSummary = '';
      if (s.providers) {
        Object.keys(s.providers).forEach(function (k) { providerSummary += '<span class="pmfai-score">' + esc(k) + ': ' + esc(s.providers[k]) + '</span>'; });
      }
      var h = '<div class="pmfai-usage-summary"><span class="pmfai-score">Logs: ' + (s.recent_count || 0) + '</span><span class="pmfai-score">Success: ' + (s.success || 0) + '</span><span class="pmfai-score">Error: ' + (s.error || 0) + '</span><span class="pmfai-score">Total tokens: ' + (s.total_tokens || 0) + '</span>' + providerSummary + '</div>';
      if (!data.items || !data.items.length) {
        result.innerHTML = h + '<p>Chưa có usage log nào.</p>';
        return;
      }
      h += '<table class="widefat striped pmfai-usage-table"><thead><tr><th>Thời gian</th><th>User</th><th>Status</th><th>Provider</th><th>Mode</th><th>Model</th><th>Type</th><th>Tokens</th><th>Duration</th><th>Error</th></tr></thead><tbody>';
      data.items.forEach(function (item) {
        h += '<tr><td>' + esc(item.created_at) + '</td><td>' + esc(item.user_login) + '</td><td><strong>' + esc(item.status) + '</strong></td><td>' + esc(item.provider || '-') + '</td><td>' + esc(item.mode) + '</td><td>' + esc(item.model) + '</td><td>' + esc(item.type) + '</td><td>' + (item.total_tokens || 0) + ' <small>(' + (item.prompt_tokens || 0) + '/' + (item.completion_tokens || 0) + ')</small></td><td>' + (item.duration_ms || 0) + 'ms</td><td>' + esc(item.error_message) + '</td></tr>';
      });
      result.innerHTML = h + '</tbody></table>';
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    insertProviderTester();
    enhanceUsageLogs();
  });

  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('#pmfai-provider-test') : null;
    if (!btn) return;
    e.preventDefault();
    var old = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Đang test...';
    var result = qs('pmfai-provider-test-result');
    if (result) result.innerHTML = '<p>Đang kiểm tra provider...</p>';
    api('/provider/test', 'POST', {}).then(function (data) {
      if (result) result.innerHTML = renderProviderTestResult(data);
    }).catch(function (err) {
      if (result) result.innerHTML = '<div class="pmfai-status-box is-error">' + esc(err.message || 'Test thất bại.') + '</div>';
    }).finally(function () {
      btn.disabled = false;
      btn.textContent = old;
    });
  });
})();
