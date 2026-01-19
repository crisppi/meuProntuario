<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastro de Exames – Prontuário</title>
  <style>
    :root {
      font-family: "Inter", system-ui, sans-serif;
      color: #0f172a;
      background-color: #eef2ff;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      background: transparent;
    }

    .panel {
      width: min(1100px, 100%);
    }

    .card {
      background: #fff;
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 30px 50px rgba(15, 23, 42, 0.15);
    }

    .card h1 {
      margin-top: 0;
      font-size: 1.75rem;
      color: #111827;
    }

    .grid-form {
      margin-top: 1.2rem;
      display: grid;
      gap: 0.8rem;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    label {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
      font-size: 0.95rem;
      color: #475569;
    }

    input,
    select,
    textarea {
      padding: 0.7rem;
      border-radius: 0.6rem;
      border: 1px solid #cbd5f5;
      font: inherit;
      background: #f8fafc;
    }

    textarea {
      grid-column: 1/-1;
      min-height: 5rem;
      resize: vertical;
    }
    .result-section {
      grid-column: 1/-1;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 0.8rem;
      padding: 1.25rem;
      border-radius: 1rem;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
    }

    .result-section h3 {
      margin: 0;
      grid-column: 1/-1;
      font-size: 1rem;
      font-weight: 600;
      color: #1e293b;
    }

    .actions {
      margin-top: 1.2rem;
      display: flex;
      justify-content: flex-end;
      gap: 0.6rem;
      flex-wrap: wrap;
    }

    button {
      padding: 0.9rem 1.5rem;
      border: none;
      border-radius: 0.75rem;
      background: linear-gradient(135deg, #2563eb, #3b82f6);
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }

    .secondary {
      background: transparent;
      color: #2563eb;
      border: 1px solid rgba(37, 99, 235, 0.5);
    }

    .form-feedback {
      margin-top: 1rem;
      padding: 0.85rem 1rem;
      border-radius: 0.65rem;
      border: 1px solid transparent;
      font-size: 0.95rem;
      transition: opacity 0.2s;
    }

    .form-feedback.success {
      background: rgba(16, 185, 129, 0.15);
      border-color: rgba(16, 185, 129, 0.5);
      color: #047857;
    }

    .form-feedback.error {
      background: rgba(248, 113, 113, 0.15);
      border-color: rgba(248, 113, 113, 0.5);
      color: #b91c1c;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
      <div class="card">
      <h1>Cadastro de exames</h1>
      <p>Adicione um novo exame referente ao paciente, com unidade e referência.</p>
      <form id="exam-form" class="grid-form">
        <label>
          Nome do exame
          <input type="text" name="nome_exame" placeholder="Hemograma completo" />
        </label>
        <label>
          Tipo
          <select name="tipo_exame">
            <option value="">Selecione</option>
            <option value="laboratorial">Laboratorial</option>
            <option value="imagem">Imagem</option>
            <option value="clinico">Clínico</option>
          </select>
        </label>
        <label>
          Unidade (referência)
          <input type="text" name="unidade" placeholder="g/dL, mg/dL" />
        </label>
        <label>
          Referência mínima
          <input type="number" step="0.01" name="referencia_min" />
        </label>
        <label>
          Referência máxima
          <input type="number" step="0.01" name="referencia_max" />
        </label>
        <label>
          Frequência recomendada
          <input type="text" name="frequencia" placeholder="A cada 6 meses" />
        </label>
        <label>
          Observações
          <textarea name="observacoes"></textarea>
        </label>
      </form>
      <div class="actions">
        <button class="secondary" type="button" onclick="window.location.href='navegacao.html'">Voltar</button>
        <button type="button" id="exam-submit">Salvar exame</button>
      </div>
      <div id="exam-feedback" class="form-feedback" aria-live="polite" hidden></div>
    </div>
  </div>
  </main>

  <script>
    const examForm = document.getElementById('exam-form');
    const submitBtn = document.getElementById('exam-submit');
    const feedback = document.getElementById('exam-feedback');
    const apiUrl = new URL('./process/exam.php', `${window.location.origin}${window.location.pathname}`).href;

    const showMessage = (message, type = 'success') => {
      if (!feedback) {
        return;
      }
      feedback.textContent = message;
      feedback.classList.remove('success', 'error');
      feedback.classList.add(type);
      feedback.hidden = false;
    };

    submitBtn?.addEventListener('click', async () => {
      if (!examForm?.checkValidity()) {
        examForm?.reportValidity();
        return;
      }

      submitBtn.disabled = true;
      try {
        const response = await fetch(apiUrl, {
          method: 'POST',
          body: new FormData(examForm),
          headers: {
            Accept: 'application/json',
          },
        });

        const contentType = response.headers.get('content-type') ?? '';
        const text = await response.text();

        if (!contentType.includes('application/json')) {
          throw new Error('Resposta inesperada do servidor. Verifique o endpoint em ./process/exam.php.');
        }

        const payload = JSON.parse(text);

        if (!response.ok || payload.success === false) {
          throw new Error(payload.message ?? 'Erro ao salvar exame.');
        }

        showMessage(payload.message ?? 'Exame salvo com sucesso.', 'success');
        examForm.reset();
      } catch (error) {
        showMessage(error instanceof Error ? error.message : 'Erro inesperado.', 'error');
      } finally {
        submitBtn.disabled = false;
      }
    });
  </script>
</body>
</html>
