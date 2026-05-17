<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lançamento de exames de imagem – Prontuário</title>
  <link rel="stylesheet" href="assets/styles/web.css" />
  <link rel="stylesheet" href="assets/styles/app-overrides.css?v=20260510-nav3" />
</head>
  <body class="web-mode">
  <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
    <div class="card">
      <h1>Lançamento de exames de imagem</h1>
      <p>Associe o resultado à imagem obtida e carregue o relatório em PDF ou JPG.</p>
      <form id="image-exam-form" class="grid-form">
        <label>
          Exame
          <select name="exame_id" required>
            <option value="">Selecione um exame cadastrado</option>
          </select>
        </label>
        <input type="hidden" name="nome_exame" value="" />
        <label>
          Data da coleta
          <input type="date" name="data_coleta" required />
        </label>
        <label>
          Serviço/Unidade responsável
          <input type="text" name="laboratorio" />
        </label>
        <label class="full-width">
          Observações
          <textarea name="observacoes"></textarea>
        </label>
        <label class="full-width">
          Anexos (PDF, PNG, JPG)
          <input type="file" name="anexos" accept=".pdf,image/png,image/jpeg" multiple />
          <span class="info">Até 5 arquivos, máximo de 5 MB cada.</span>
          <ul class="file-list" id="file-list" aria-live="polite"></ul>
        </label>
        <input type="hidden" name="resultado_id" value="" />
      </form>
      <div class="actions">
        <button type="button" class="button secondary" onclick="window.location.href='navegacao.html'">Voltar</button>
        <button type="button" id="image-submit" class="button primary">Salvar resultado de imagem</button>
      </div>
      <div id="image-feedback" class="info" aria-live="polite"></div>
    </div>
    </div>
  </main>
  <script>
    const form = document.getElementById('image-exam-form');
    const examSelect = form?.querySelector('[name="exame_id"]');
    const fileInput = form?.querySelector('[name="anexos"]');
    const fileList = document.getElementById('file-list');
    const submitBtn = document.getElementById('image-submit');
    const feedback = document.getElementById('image-feedback');
    const endpoint = new URL('../process/exam.php', window.location.href).href;
    const definitionsEndpoint = new URL('../process/exam-definitions.php', window.location.href).href;

    const examNameField = form?.querySelector('[name="nome_exame"]');

    const handleExamChange = () => {
      const selected = examSelect?.selectedOptions?.[0];
      if (!examNameField) return;
      examNameField.value = selected?.dataset.nome ?? '';
    };

    const loadDefinitions = async () => {
      if (!examSelect) return;
      const response = await fetch(definitionsEndpoint, { headers: { Accept: 'application/json' } });
      const payload = await response.json();
      const definitions = (Array.isArray(payload.data) ? payload.data : []).filter(
        (definition) => definition.tipo === 'imagem'
      );
      examSelect.innerHTML = '<option value="">Selecione um exame cadastrado</option>';
      definitions.forEach((definition) => {
        const option = document.createElement('option');
        option.value = String(definition.exame_id);
        option.textContent = definition.nome ?? 'Exame';
        option.dataset.nome = definition.nome ?? '';
        examSelect.appendChild(option);
      });
      handleExamChange();
    };

    const showFiles = (files) => {
      if (!fileList) return;
      fileList.innerHTML = '';
      Array.from(files).forEach((file, index) => {
        const item = document.createElement('li');
        item.className = 'file-item';
        item.innerHTML = `
          <span>${file.name}</span>
          <button type="button" data-remove="${index}">Remover</button>
        `;
        fileList.appendChild(item);
      });
    };

    examSelect?.addEventListener('change', handleExamChange);

    fileInput?.addEventListener('change', () => {
      showFiles(fileInput.files);
    });

    fileList?.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-remove]');
      if (!button || !fileInput) return;
      const removeIndex = Number(button.dataset.remove);
      const dt = new DataTransfer();
      Array.from(fileInput.files).forEach((file, idx) => {
        if (idx !== removeIndex) {
          dt.items.add(file);
        }
      });
      fileInput.files = dt.files;
      showFiles(fileInput.files);
    });

    const setFeedback = (message, isError = false) => {
      if (!feedback) return;
      feedback.textContent = message;
      feedback.style.color = isError ? '#dc2626' : '#047857';
    };

    submitBtn?.addEventListener('click', async () => {
      if (!form?.checkValidity()) {
        form?.reportValidity();
        return;
      }
      submitBtn.disabled = true;
      setFeedback('Enviando resultado...');
      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json' },
        });
        const text = await response.text();
        const payload = text ? JSON.parse(text) : {};
        if (!response.ok || payload.success === false) {
          throw new Error(payload.message ?? 'Falha ao salvar resultado.');
        }
        setFeedback('Resultado de imagem cadastrado com sucesso.');
        form.reset();
        fileList.innerHTML = '';
      } catch (error) {
        setFeedback(error instanceof Error ? error.message : 'Erro inesperado.', true);
      } finally {
        submitBtn.disabled = false;
      }
    });

    loadDefinitions();
  </script>
</body>
</html>
