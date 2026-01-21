<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastro de Exames – Prontuário</title>
  <link rel="stylesheet" href="assets/styles/web.css" />
  <link rel="stylesheet" href="assets/styles/app-overrides.css" />
</head>
<body class="web-mode">
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
