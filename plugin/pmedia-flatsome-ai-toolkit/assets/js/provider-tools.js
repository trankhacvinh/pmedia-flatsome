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

  function inputByKey(key) {
    return document.querySelector('input[name="pmedia_flatsome_ai_toolkit_options[' + key + ']"]');
  }

  function fieldByKey(key) {
    var input = inputByKey(key);
    return input ? input.closest('.pmfai-field') : null;
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

  function insertAnthropicMaxTokensField() {
    var modelInput = inputByKey('anthropic_model');
    if (!modelInput || inputByKey('anthropic_max_tokens')) return;
    var label = document.createElement('label');
    label.className = 'pmfai-field';
    label.innerHTML = '<span>Anthropic max tokens</span><input type="text" name="pmedia_flatsome_ai_toolkit_options[anthropic_max_tokens]" value="4096"><small class="pmfai-provider-hint">Gợi ý: 4096 cho bình thường, 8192+ cho Page Builder dài nếu model/gói hỗ trợ.</small>';
    var modelField = modelInput.closest('.pmfai-field');
    if (modelField && modelField.parentNode) {
      modelField.parentNode.insertBefore(label, modelField.nextSibling);
    }
  }

  function buildProviderGroups() {
    var providerSelect = document.querySelector('select[name="pmedia_flatsome_ai_toolkit_options[ai_provider]"]');
    if (!providerSelect || document.querySelector('.pmfai-provider-groups')) return;
    insertAnthropicMaxTokensField();

    var providerField = providerSelect.closest('.pmfai-field');
    if (!providerField || !providerField.parentNode) return;

    var warning = document.createElement('div');
    warning.className = 'pmfai-provider-warning';
    warning.id = 'pmfai-provider-warning';
    providerField.parentNode.insertBefore(warning, providerField.nextSibling);

    var groups = document.createElement('div');
    groups.className = 'pmfai-provider-groups';
    groups.innerHTML = '' +
      '<div class="pmfai-provider-group" data-provider-group="openai"><h3>OpenAI / ChatGPT</h3><p>Dùng endpoint Chat Completions chính thức của OpenAI.</p></div>' +
      '<div class="pmfai-provider-group" data-provider-group="openai_compatible"><h3>OpenAI-compatible</h3><p>Dùng cho OpenRouter, DeepSeek, Groq, Together, LM Studio proxy hoặc endpoint tương thích.</p></div>' +
      '<div class="pmfai-provider-group" data-provider-group="anthropic"><h3>Anthropic Claude</h3><p>Dùng Claude Messages API. Phù hợp với prompt dài và copy/layout chất lượng cao.</p></div>';
    providerField.parentNode.insertBefore(groups, warning.nextSibling);

    var map = {
      openai: ['api_endpoint', 'api_key', 'api_model'],
      openai_compatible: ['compatible_endpoint', 'compatible_api_key', 'compatible_model'],
      anthropic: ['anthropic_endpoint', 'anthropic_api_key', 'anthropic_model', 'anthropic_max_tokens']
    };

    Object.keys(map).forEach(function (provider) {
      var group = groups.querySelector('[data-provider-group="' + provider + '"]');
      map[provider].forEach(function (key) {
        var field = fieldByKey(key);
        if (field && group) {
          group.appendChild(field);
        }
      });
    });

    addProviderHints();
    providerSelect.addEventListener('change', refreshProviderGroups);
    refreshProviderGroups();
  }

  function addProviderHints() {
    var hints = {
      api_endpoint: 'Mặc định: https://api.openai.com/v1/chat/completions',
      api_model: 'Ví dụ: gpt-4.1-mini hoặc model OpenAI anh đang dùng.',
      compatible_endpoint: 'Ví dụ OpenRouter: https://openrouter.ai/api/v1/chat/completions',
      compatible_model: 'Ví dụ: deepseek/deepseek-chat, openai/gpt-4.1-mini, qwen/qwen3-coder.',
      anthropic_endpoint: 'Mặc định: https://api.anthropic.com/v1/messages',
      anthropic_model: 'Ví dụ: claude-3-5-sonnet-latest.',
      anthropic_max_tokens: 'Tăng khi sinh page dài, nhưng token cao hơn có thể tốn phí hơn.'
    };
    Object.keys(hints).forEach(function (key) {
      var field = fieldByKey(key);
      if (!field || field.querySelector('.pmfai-provider-hint')) return;
      var small = document.createElement('small');
      small.className = 'pmfai-provider-hint';
      small.textContent = hints[key];
      field.appendChild(small);
    });
  }

  function refreshProviderGroups() {
    var providerSelect = document.querySelector('select[name="pmedia_flatsome_ai_toolkit_options[ai_provider]"]');
    if (!providerSelect) return;
    var active = providerSelect.value || 'openai';
    document.querySelectorAll('.pmfai-provider-group').forEach(function (group) {
      group.classList.toggle('is-active', group.getAttribute('data-provider-group') === active);
    });

    var keyMap = {
      openai: ['api_endpoint', 'api_key', 'api_model'],
      openai_compatible: ['compatible_endpoint', 'compatible_api_key', 'compatible_model'],
      anthropic: ['anthropic_endpoint', 'anthropic_api_key', 'anthropic_model']
    };
    var missing = [];
    (keyMap[active] || []).forEach(function (key) {
      var input = inputByKey(key);
      if (!input || !String(input.value || '').trim()) missing.push(key);
    });

    var warning = qs('pmfai-provider-warning');
    if (warning) {
      if (missing.length) {
        warning.classList.remove('is-ok');
        warning.textContent = 'Provider đang chọn còn thiếu cấu hình: ' + missing.join(', ') + '. Hãy điền và lưu Settings trước khi test/generate.';
      } else {
        warning.classList.add('is-ok');
        warning.textContent = 'Provider đang chọn đã có đủ endpoint/API key/model. Hãy lưu Settings rồi bấm Test AI Provider để kiểm tra thực tế.';
      }
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
    insertAnthropicMaxTokensField();
    buildProviderGroups();
    enhanceUsageLogs();
  });

  document.addEventListener('input', function (e) {
    if (e.target && e.target.name && e.target.name.indexOf('pmedia_flatsome_ai_toolkit_options[') === 0) {
      refreshProviderGroups();
    }
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
