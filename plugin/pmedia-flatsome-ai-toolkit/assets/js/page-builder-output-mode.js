(function () {
  function patchPmfaiFetch() {
    if (typeof window.pmfaiFetch !== 'function' || window.pmfaiFetch.__pmfaiOutputModePatched) {
      return;
    }

    const originalFetch = window.pmfaiFetch;
    window.pmfaiFetch = async function (path, method, body) {
      if (typeof path === 'string' && path.indexOf('/page-builder/') === 0 && body && typeof body === 'object') {
        const outputMode = document.getElementById('pmfai-page-output-mode');
        body.outputMode = outputMode && outputMode.value ? outputMode.value : (body.outputMode || 'html-block');
      }
      return originalFetch(path, method, body);
    };
    window.pmfaiFetch.__pmfaiOutputModePatched = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', patchPmfaiFetch);
  } else {
    patchPmfaiFetch();
  }
})();
