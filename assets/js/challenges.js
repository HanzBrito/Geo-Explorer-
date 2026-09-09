/* ============================================================
   GEO-EXPLORER — challenges.js
   Lógica do editor de desafios de código
   ============================================================ */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var editor    = document.getElementById('codeEditor');
    var btnRun    = document.getElementById('btnRunCode');
    var btnReset  = document.getElementById('btnResetCode');
    var btnSubmit = document.getElementById('btnSubmitCode');
    var output    = document.getElementById('codeOutput');
    var codigoInicialEl = document.getElementById('codigoInicial');

    if (!editor) return;

    var codigoInicial = codigoInicialEl ? codigoInicialEl.value : '';

    // ── Tab key no editor ───────────────────────────────────
    editor.addEventListener('keydown', function (e) {
      if (e.key === 'Tab') {
        e.preventDefault();
        var start = this.selectionStart;
        var end   = this.selectionEnd;
        this.value = this.value.substring(0, start) + '  ' + this.value.substring(end);
        this.selectionStart = this.selectionEnd = start + 2;
      }
    });

    // ── Reset ao código inicial ─────────────────────────────
    if (btnReset) {
      btnReset.addEventListener('click', function () {
        if (confirm('Resetar para o código inicial?')) {
          editor.value = codigoInicial;
          clearOutput();
        }
      });
    }

    // ── Executar código ─────────────────────────────────────
    // (somente HTML/CSS pode ser visualizado com segurança no iframe)
    if (btnRun) {
      btnRun.addEventListener('click', function () {
        var code    = editor.value;
        var tipo    = editor.getAttribute('data-tipo') || 'html';
        runPreview(code, tipo);
      });
    }

    // ── Submeter resposta ───────────────────────────────────
    if (btnSubmit) {
      btnSubmit.addEventListener('click', function () {
        var code      = editor.value.trim();
        var desafioId = editor.getAttribute('data-desafio-id');

        if (!code) {
          showOutput('⚠️ Escreva seu código antes de enviar.', 'warning');
          return;
        }

        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Enviando…';

        var formData = new FormData();
        formData.append('desafio_id', desafioId);
        formData.append('codigo',     code);

        fetch(window.GEO_BASE_URL + '/aluno/desafios.php', {
          method: 'POST',
          body: formData
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.sucesso) {
            showOutput('✅ ' + data.mensagem, 'success');
          } else {
            showOutput('❌ ' + data.mensagem, 'error');
          }
        })
        .catch(function () {
          showOutput('Erro de conexão. Tente novamente.', 'error');
        })
        .finally(function () {
          btnSubmit.disabled = false;
          btnSubmit.textContent = 'Enviar Resposta';
        });
      });
    }

    // ── Helpers ─────────────────────────────────────────────
    function runPreview(code, tipo) {
      if (!output) return;
      output.innerHTML = '';

      if (tipo === 'html') {
        var iframe = document.createElement('iframe');
        iframe.style.cssText = 'width:100%;height:350px;border:1px solid var(--border);border-radius:8px;background:#fff;';
        iframe.sandbox = 'allow-same-origin';
        output.appendChild(iframe);
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(code);
        doc.close();

      } else if (tipo === 'css') {
        // Prévia de CSS aplicado a um div demo
        var iframe2 = document.createElement('iframe');
        iframe2.style.cssText = 'width:100%;height:350px;border:1px solid var(--border);border-radius:8px;background:#fff;';
        iframe2.sandbox = 'allow-same-origin';
        output.appendChild(iframe2);
        var doc2 = iframe2.contentDocument || iframe2.contentWindow.document;
        doc2.open();
        doc2.write('<style>' + code + '</style><div class="container"><div class="caixa"></div></div>');
        doc2.close();

      } else {
        showOutput('Prévia disponível apenas para HTML e CSS.', 'info');
      }
    }

    function showOutput(msg, tipo) {
      if (!output) return;
      var cls = {
        success: 'alert-success',
        error:   'alert-error',
        info:    'alert-info',
        warning: 'alert-warning',
      }[tipo] || 'alert-info';
      output.innerHTML = '<div class="alert ' + cls + '">' + msg + '</div>';
    }

    function clearOutput() {
      if (output) output.innerHTML = '';
    }

    // ── Contador de chars ───────────────────────────────────
    var counter = document.getElementById('charCounter');
    if (counter && editor) {
      function updateCounter() {
        counter.textContent = editor.value.length + ' caracteres';
      }
      editor.addEventListener('input', updateCounter);
      updateCounter();
    }

  });

}());
