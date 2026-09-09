/* ============================================================
   GEO-EXPLORER — theme.js
   Gerencia tema claro/escuro e configurações de fonte
   ============================================================ */
(function () {
  'use strict';

  // ── Persistência ──────────────────────────────────────────
  const STORAGE_KEY = 'geo_theme';
  const FONT_KEY    = 'geo_font';
  const SIZE_KEY    = 'geo_size';

  const fontMap = {
    Inter:  'Inter, sans-serif',
    Roboto: '"Roboto", sans-serif',
    Mono:   '"Roboto Mono", monospace',
  };
  const sizeMap = { pequeno: '13px', medio: '15px', grande: '17px' };

  // ── Aplicar configurações ─────────────────────────────────
  function applyTheme(tema) {
    document.documentElement.setAttribute('data-theme', tema);
    localStorage.setItem(STORAGE_KEY, tema);
  }

  function applyFont(fonte) {
    document.documentElement.style.setProperty(
      '--font-family', fontMap[fonte] || fontMap.Inter
    );
    localStorage.setItem(FONT_KEY, fonte);
  }

  function applySize(tamanho) {
    document.documentElement.style.setProperty(
      '--font-size-base', sizeMap[tamanho] || sizeMap.medio
    );
    localStorage.setItem(SIZE_KEY, tamanho);
  }

  // ── Inicialização ─────────────────────────────────────────
  function init() {
    // Tema — prioridade: localStorage → atributo do servidor → 'claro'
    const storedTheme = localStorage.getItem(STORAGE_KEY);
    if (storedTheme) applyTheme(storedTheme);

    const storedFont = localStorage.getItem(FONT_KEY);
    if (storedFont) applyFont(storedFont);

    const storedSize = localStorage.getItem(SIZE_KEY);
    if (storedSize) applySize(storedSize);
  }

  // ── Botão de toggle no header ─────────────────────────────
  function bindToggle() {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
      const current = document.documentElement.getAttribute('data-theme') || 'claro';
      const next = current === 'claro' ? 'escuro' : 'claro';
      applyTheme(next);
      // Persiste via AJAX para o servidor (atualiza configuracoes_usuario)
      saveThemeServer(next);
    });
  }

  function saveThemeServer(tema) {
    fetch(window.GEO_BASE_URL + '/aluno/perfil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=save_config&tema=' + encodeURIComponent(tema)
             + '&fonte=' + encodeURIComponent(localStorage.getItem(FONT_KEY) || 'Inter')
             + '&tamanho=' + encodeURIComponent(localStorage.getItem(SIZE_KEY) || 'medio')
    }).catch(function () { /* silencioso */ });
  }

  // ── Seletores de fonte/tamanho (se existirem na página) ────
  function bindFontSelects() {
    var fontSel = document.getElementById('selectFonte');
    if (fontSel) {
      fontSel.value = localStorage.getItem(FONT_KEY) || 'Inter';
      fontSel.addEventListener('change', function () { applyFont(this.value); });
    }

    var sizeSel = document.getElementById('selectTamanho');
    if (sizeSel) {
      sizeSel.value = localStorage.getItem(SIZE_KEY) || 'medio';
      sizeSel.addEventListener('change', function () { applySize(this.value); });
    }
  }

  // ── Expõe API global ──────────────────────────────────────
  window.GeoTheme = { applyTheme, applyFont, applySize };

  // ── Bootstrap ────────────────────────────────────────────
  init();
  document.addEventListener('DOMContentLoaded', function () {
    bindToggle();
    bindFontSelects();
  });
}());
