/* ============================================================
   GEO-EXPLORER — main.js
   JavaScript principal: menus, alertas, confirmações
   ============================================================ */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    // ── Hamburger menu ──────────────────────────────────────
    var hamburger = document.getElementById('hamburger');
    var mainNav   = document.getElementById('mainNav');
    if (hamburger && mainNav) {
      hamburger.addEventListener('click', function () {
        var open = mainNav.classList.toggle('open');
        hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      // Fecha ao clicar fora
      document.addEventListener('click', function (e) {
        if (!hamburger.contains(e.target) && !mainNav.contains(e.target)) {
          mainNav.classList.remove('open');
          hamburger.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // ── User dropdown ───────────────────────────────────────
    var userBtn      = document.getElementById('userMenuBtn');
    var userDropdown = document.getElementById('userDropdown');
    if (userBtn && userDropdown) {
      userBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var isHidden = userDropdown.hasAttribute('hidden');
        if (isHidden) {
          userDropdown.removeAttribute('hidden');
          userBtn.setAttribute('aria-expanded', 'true');
        } else {
          userDropdown.setAttribute('hidden', '');
          userBtn.setAttribute('aria-expanded', 'false');
        }
      });
      document.addEventListener('click', function () {
        userDropdown.setAttribute('hidden', '');
        userBtn.setAttribute('aria-expanded', 'false');
      });
    }

    // ── Auto-fecha alertas após 5 s ─────────────────────────
    document.querySelectorAll('.alert').forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity .5s ease';
        el.style.opacity    = '0';
        setTimeout(function () { el.remove(); }, 500);
      }, 5000);
    });

    // ── Confirmação de exclusão ─────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        var msg = el.getAttribute('data-confirm') || 'Tem certeza?';
        if (!confirm(msg)) {
          e.preventDefault();
          e.stopImmediatePropagation();
        }
      });
    });

    // ── Marcar nav link ativo ───────────────────────────────
    var currentPath = window.location.pathname;
    document.querySelectorAll('.main-nav a').forEach(function (a) {
      if (a.getAttribute('href') && currentPath.endsWith(a.getAttribute('href').split('/').pop())) {
        a.classList.add('active');
      }
    });

    // ── Preview de upload de foto ───────────────────────────
    var fotoInput   = document.getElementById('fotoInput');
    var fotoPreview = document.getElementById('fotoPreview');
    if (fotoInput && fotoPreview) {
      fotoInput.addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
          alert('Selecione um arquivo de imagem.');
          return;
        }
        if (file.size > 2 * 1024 * 1024) {
          alert('A imagem deve ter no máximo 2 MB.');
          return;
        }
        var reader = new FileReader();
        reader.onload = function (e) { fotoPreview.src = e.target.result; };
        reader.readAsDataURL(file);
      });
    }

    // ── Notas: colorir chips ────────────────────────────────
    document.querySelectorAll('.nota-chip').forEach(function (el) {
      var nota = parseFloat(el.textContent);
      el.classList.remove('nota-alta', 'nota-media', 'nota-baixa');
      if (nota >= 7)      el.classList.add('nota-alta');
      else if (nota >= 5) el.classList.add('nota-media');
      else                el.classList.add('nota-baixa');
    });

    // ── Progress bar animação ───────────────────────────────
    document.querySelectorAll('.progress-bar-fill').forEach(function (el) {
      var pct = el.getAttribute('data-pct') || '0';
      el.style.width = '0';
      requestAnimationFrame(function () {
        setTimeout(function () { el.style.width = pct + '%'; }, 100);
      });
    });

    // ── Tabs (se existirem) ─────────────────────────────────
    document.querySelectorAll('[data-tabs]').forEach(function (wrapper) {
      var tabs    = wrapper.querySelectorAll('[data-tab]');
      var panels  = wrapper.querySelectorAll('[data-panel]');

      function activateTab(id) {
        tabs.forEach(function (t) {
          t.classList.toggle('active', t.getAttribute('data-tab') === id);
        });
        panels.forEach(function (p) {
          p.hidden = p.getAttribute('data-panel') !== id;
        });
      }

      if (tabs.length) {
        activateTab(tabs[0].getAttribute('data-tab'));
        tabs.forEach(function (t) {
          t.addEventListener('click', function () {
            activateTab(t.getAttribute('data-tab'));
          });
        });
      }
    });

  });

}());
