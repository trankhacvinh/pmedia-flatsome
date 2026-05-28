(function () {
  function qs(id) { return document.getElementById(id); }
  function val(id) { const el = qs(id); return el ? el.value : ''; }
  function setVal(id, value) { const el = qs(id); if (el) el.value = value || ''; }
  function setHtml(id, value) { const el = qs(id); if (el) el.innerHTML = value || ''; }
  function esc(s) { return String(s || '').replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m])); }
  function attr(s) { return esc(s).replace(/"/g, '&quot;'); }

  async function api(path, method, body) {
    const opts = { method: method || 'GET', headers: { 'X-WP-Nonce': PMFAI.nonce } };
    if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(PMFAI.restUrl + path, opts);
    return await res.json();
  }

  async function withLoading(button, text, fn) {
    const old = button.textContent;
    button.disabled = true;
    button.textContent = text;
    try { await fn(); } finally { button.disabled = false; button.textContent = old; }
  }

  function postId() {
    const box = document.querySelector('.pmfai-insert-box');
    return box ? parseInt(box.dataset.postId || '0', 10) : 0;
  }

  function renderResult(result) {
    if (result.code && result.message) {
      return '<div class="pmfai-status-box is-error">' + esc(result.message) + '</div>';
    }
    let h = '';
    if (result.applied && result.applied.updated) {
      h += '<div class="pmfai-status-box is-success"><strong>Đã cập nhật page thành công.</strong><div class="pmfai-created-page-actions"><a class="button button-primary" target="_blank" href="' + attr(result.applied.edit_url || '#') + '">Mở trang sửa</a><a class="button" target="_blank" href="' + attr(result.applied.view_url || '#') + '">Xem trang</a></div></div>';
    } else if (result.shortcode) {
      h += '<div class="pmfai-status-box is-success"><strong>Đã generate shortcode.</strong><br>Chưa apply vào page. Dùng nút copy bên dưới nếu cần.</div>';
    }
    if (result.warnings && result.warnings.length) {
      h += '<details open><summary>Cảnh báo</summary><ul>' + result.warnings.map(x => '<li>' + esc(x) + '</li>').join('') + '</ul></details>';
    }
    if (result.shortcode) {
      h += '<textarea class="pmfai-insert-shortcode" rows="9" readonly>' + esc(result.shortcode) + '</textarea>';
      h += '<p><button type="button" class="button pmfai-insert-copy" data-copy="' + attr(result.shortcode) + '">Copy Shortcode</button></p>';
    }
    return h;
  }

  document.addEventListener('click', async function (e) {
    const t = e.target;
    if (!t) return;

    if (t.id === 'pmfai-insert-prompt') {
      e.preventDefault();
      await withLoading(t, 'Đang tạo prompt...', async function () {
        const result = await api('/page-insert/bridge-prompt', 'POST', {
          postId: postId(),
          brief: val('pmfai-insert-brief'),
          outputMode: val('pmfai-insert-output-mode'),
          costMode: val('pmfai-insert-cost-mode')
        });
        if (result.prompt) setVal('pmfai-insert-prompt-output', result.prompt);
        if (result.code) setHtml('pmfai-insert-result', renderResult(result));
      });
    }

    if (t.id === 'pmfai-insert-generate') {
      e.preventDefault();
      const action = val('pmfai-insert-action');
      const confirmText = action === 'replace'
        ? 'Bạn chắc chắn muốn REPLACE toàn bộ nội dung page bằng kết quả AI? Nên backup trước.'
        : 'Plugin sẽ cập nhật trực tiếp nội dung page. Tiếp tục?';
      if (!confirm(confirmText)) return;
      await withLoading(t, 'Đang generate & insert...', async function () {
        setHtml('pmfai-insert-result', '<div class="pmfai-status-box">Đang gọi AI và cập nhật page...</div>');
        const result = await api('/page-insert/generate', 'POST', {
          postId: postId(),
          brief: val('pmfai-insert-brief'),
          outputMode: val('pmfai-insert-output-mode'),
          costMode: val('pmfai-insert-cost-mode'),
          action: action,
          autoApply: true
        });
        setHtml('pmfai-insert-result', renderResult(result));
      });
    }

    if (t.classList.contains('pmfai-insert-copy')) {
      e.preventDefault();
      navigator.clipboard.writeText(t.dataset.copy || '');
      const old = t.textContent;
      t.textContent = 'Copied';
      setTimeout(() => t.textContent = old, 1200);
    }
  });
})();
