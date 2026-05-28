(function () {
  function qs(id) { return document.getElementById(id); }
  function getOutputMode() { return qs('pmfai-page-output-mode') ? qs('pmfai-page-output-mode').value : 'html-block'; }
  function getCostMode() { return qs('pmfai-page-cost-mode') ? qs('pmfai-page-cost-mode').value : 'balanced'; }
  function getBrief() { return qs('pmfai-page-brief') ? qs('pmfai-page-brief').value : ''; }

  function parsePageJson() {
    const el = qs('pmfai-page-json');
    if (!el || !el.value.trim()) return null;
    try {
      const parsed = JSON.parse(el.value);
      if (parsed.page && Array.isArray(parsed.page.sections)) return parsed;
      if (Array.isArray(parsed.sections)) return { version: '1.0', page: parsed };
      return null;
    } catch (err) {
      return null;
    }
  }

  function writePageJson(data) {
    const el = qs('pmfai-page-json');
    if (el) el.value = JSON.stringify(data, null, 2);
  }

  function ensureButtons() {
    const data = parsePageJson();
    if (!data || !data.page || !Array.isArray(data.page.sections)) return;
    document.querySelectorAll('.pmfai-page-section-preview').forEach(function (box, index) {
      if (box.querySelector('.pmfai-regenerate-section')) return;
      const section = data.page.sections[index];
      if (!section) return;
      const actions = document.createElement('div');
      actions.className = 'pmfai-section-actions';
      actions.innerHTML = '<button type="button" class="button pmfai-regenerate-section" data-section-index="' + index + '">Regenerate section</button>';
      const head = box.querySelector('h3');
      if (head && head.parentNode) {
        head.parentNode.insertBefore(actions, head.nextSibling);
      } else {
        box.insertBefore(actions, box.firstChild);
      }
    });
  }

  async function api(path, body) {
    const response = await fetch(PMFAI.restUrl + path, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': PMFAI.nonce
      },
      body: JSON.stringify(body || {})
    });
    return await response.json();
  }

  function showNotice(message, type) {
    let target = qs('pmfai-page-notice');
    if (!target) {
      target = document.createElement('div');
      target.id = 'pmfai-page-notice';
      const result = qs('pmfai-page-result');
      if (result) result.prepend(target);
    }
    target.innerHTML = '<div class="pmfai-status-box ' + (type === 'error' ? 'is-error' : 'is-success') + '">' + escapeHtml(message) + '</div>';
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>]/g, function (m) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;'}[m];
    });
  }

  async function regenerateSection(index, button) {
    const data = parsePageJson();
    if (!data || !data.page || !Array.isArray(data.page.sections)) {
      showNotice('Không đọc được Page JSON hiện tại. Hãy bấm Validate/Ghép shortcode trước.', 'error');
      return;
    }
    const section = data.page.sections[index];
    if (!section) {
      showNotice('Không tìm thấy section cần regenerate.', 'error');
      return;
    }

    const oldText = button.textContent;
    button.disabled = true;
    button.textContent = 'Đang sinh lại...';

    try {
      const result = await api('/page-builder/regenerate-section', {
        page: data.page,
        section: section,
        brief: getBrief(),
        costMode: getCostMode(),
        outputMode: getOutputMode()
      });

      if (result.code && result.message) {
        throw new Error(result.message);
      }
      if (!result.section) {
        throw new Error('API không trả về section mới.');
      }

      data.page.sections[index] = result.section;
      writePageJson(data);
      showNotice('Đã regenerate section #' + (index + 1) + '. Plugin sẽ validate lại page.', 'success');

      const importButton = qs('pmfai-page-import');
      if (importButton) importButton.click();
    } catch (err) {
      showNotice(err.message || 'Regenerate section thất bại.', 'error');
    } finally {
      button.disabled = false;
      button.textContent = oldText;
    }
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('.pmfai-regenerate-section');
    if (!button) return;
    event.preventDefault();
    regenerateSection(parseInt(button.dataset.sectionIndex || '0', 10), button);
  });

  const observer = new MutationObserver(function () { ensureButtons(); });
  document.addEventListener('DOMContentLoaded', function () {
    const result = qs('pmfai-page-result');
    if (result) observer.observe(result, { childList: true, subtree: true });
    ensureButtons();
  });
})();
