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

  if (t.id === 'pmfai-build-prompt') {
    e.preventDefault();
    const body = {
      type: document.getElementById('pmfai-bridge-type').value,
      industry: document.getElementById('pmfai-bridge-industry').value,
      style: document.getElementById('pmfai-bridge-style').value,
      goal: document.getElementById('pmfai-bridge-goal').value,
      content: document.getElementById('pmfai-bridge-content').value
    };
    const r = await fetch(PMFAI.restUrl + '/bridge-prompt', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-WP-Nonce': PMFAI.nonce},
      body: JSON.stringify(body)
    });
    const j = await r.json();
    document.getElementById('pmfai-bridge-output').value = j.prompt || '';
  }

  if (t.id === 'pmfai-parse-chatgpt') {
    e.preventDefault();
    const r = await fetch(PMFAI.restUrl + '/parse-chatgpt-block', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-WP-Nonce': PMFAI.nonce},
      body: JSON.stringify({raw: document.getElementById('pmfai-import-raw').value})
    });
    const j = await r.json();
    document.getElementById('pmfai-import-result').innerHTML = renderPmfaiResult(j);
  }

  if (t.id === 'pmfai-validate-code') {
    e.preventDefault();
    const r = await fetch(PMFAI.restUrl + '/validate-code', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-WP-Nonce': PMFAI.nonce},
      body: JSON.stringify({
        html: document.getElementById('pmfai-clean-html').value,
        css: document.getElementById('pmfai-clean-css').value
      })
    });
    const j = await r.json();
    document.getElementById('pmfai-clean-result').innerHTML = renderPmfaiResult(j);
  }
});

function pmfaiEsc(s) {
  return String(s || '').replace(/[&<>]/g, m => ({'&': '&amp;', '<': '&lt;', '>': '&gt;'}[m]));
}

function renderPmfaiResult(j) {
  if (j.code && j.message) {
    return '<div class="notice notice-error"><p>' + pmfaiEsc(j.message) + '</p></div>';
  }

  let h = '<div class="pmfai-result">';
  if (j.scores) {
    h += '<p><span class="pmfai-score">CSS Safety: ' + j.scores.css_safety + '/100</span><span class="pmfai-score">Flatsome: ' + j.scores.flatsome_compatibility + '/100</span></p>';
  }
  if (j.warnings && j.warnings.length) {
    h += '<h3>Cảnh báo</h3><ul>' + j.warnings.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  }
  if (j.suggestions && j.suggestions.length) {
    h += '<h3>Gợi ý</h3><ul>' + j.suggestions.map(x => '<li>' + pmfaiEsc(x) + '</li>').join('') + '</ul>';
  }
  if (j.html) {
    h += '<h3>HTML</h3><pre>' + pmfaiEsc(j.html) + '</pre>';
  }
  if (j.css) {
    h += '<h3>CSS</h3><pre>' + pmfaiEsc(j.css) + '</pre>';
  }
  return h + '</div>';
}
