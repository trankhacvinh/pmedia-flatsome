(function () {
  function esc(value) {
    return String(value || '').replace(/[&<>]/g, function (m) { return {'&':'&amp;','<':'&lt;','>':'&gt;'}[m]; });
  }

  function renderQualityGateError(result) {
    var q = result && result.data && result.data.quality ? result.data.quality : null;
    var h = '<div class="pmfai-status-box is-error"><strong>Quality Gate đã chặn thao tác.</strong><br>' + esc(result.message || 'Output chưa đủ an toàn để lưu vào website.') + '</div>';
    if (q) {
      h += '<div class="pmfai-status-box is-error"><strong>Điểm UI Skill: ' + esc(q.score || 0) + '/100</strong><br>Gate: ' + esc(q.gate || 'fail') + '</div>';
      if (q.warnings && q.warnings.length) {
        h += '<details open><summary>Lý do bị chặn</summary><ul>' + q.warnings.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul></details>';
      }
      if (q.section_scores) {
        h += '<details open><summary>Điểm từng section</summary><ul>';
        Object.keys(q.section_scores).forEach(function (key) {
          var s = q.section_scores[key] || {};
          h += '<li><strong>' + esc(key) + '</strong>: ' + esc(s.score || 0) + '/100 — ' + esc(s.gate || '-') + '</li>';
        });
        h += '</ul></details>';
      }
    }
    h += '<div class="pmfai-status-box"><strong>Hướng xử lý nhanh:</strong><br>1. Chọn High Quality rồi Generate lại.<br>2. Ưu tiên mode Section + HTML Block nếu Native Shortcode bị lỗi.<br>3. Regenerate riêng section bị điểm thấp.<br>4. Bổ sung brief rõ hơn: ngành nghề, số section/card, CTA, tone màu, nội dung chính.</div>';
    return h;
  }

  function renderQualityBox(quality, label) {
    if (!quality) return '';
    var cls = quality.gate === 'pass' ? 'is-success' : (quality.gate === 'warning' ? '' : 'is-error');
    var h = '<div class="pmfai-status-box ' + cls + '"><strong>' + esc(label || 'UI Skill Quality') + ': ' + esc(quality.score || 0) + '/100</strong> — Gate: ' + esc(quality.gate || '-') + '</div>';
    if (quality.warnings && quality.warnings.length) {
      h += '<details><summary>Quality warnings</summary><ul>' + quality.warnings.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul></details>';
    }
    return h;
  }

  var oldRenderError = window.renderError;
  window.renderError = function (result) {
    if (result && result.code === 'ui_quality_gate_failed') {
      return renderQualityGateError(result);
    }
    if (typeof oldRenderError === 'function') return oldRenderError(result);
    return '<div class="pmfai-status-box is-error">' + esc((result && result.message) || 'Có lỗi xảy ra.') + '</div>';
  };

  var oldRenderPageResult = window.renderPageResult;
  window.renderPageResult = function (result) {
    var h = typeof oldRenderPageResult === 'function' ? oldRenderPageResult(result) : '';
    if (!result || result.code) return h;
    var qualityHtml = '';
    if (result.ui_skill_quality) qualityHtml += renderQualityBox(result.ui_skill_quality, 'UI Skill Page Quality');
    if (result.ui_skill_shortcode_quality) qualityHtml += renderQualityBox(result.ui_skill_shortcode_quality, 'UI Skill Shortcode Quality');
    if (!qualityHtml) return h;
    return h.replace('<div class="pmfai-result">', '<div class="pmfai-result">' + qualityHtml);
  };

  var oldRenderPmfaiResult = window.renderPmfaiResult;
  window.renderPmfaiResult = function (result, canSave, withPreview) {
    var h = typeof oldRenderPmfaiResult === 'function' ? oldRenderPmfaiResult(result, canSave, withPreview) : '';
    if (!result || result.code) return h;
    var quality = result.quality || result.ui_skill_quality || result.ui_skill_shortcode_quality;
    if (!quality) return h;
    return h.replace('<div class="pmfai-result">', '<div class="pmfai-result">' + renderQualityBox(quality, 'UI Skill Quality'));
  };
})();
