<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lançamento de Exames Laboratoriais – Prontuário</title>
  <link rel="stylesheet" href="assets/styles/web.css" />
  <link rel="stylesheet" href="assets/styles/app-overrides.css?v=20260510-nav3" />
</head>
  <body class="web-mode">
    <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
    <div class="card">
      <h1>Lançamento de exames laboratoriais</h1>
      <p>Registre um novo resultado para manter o histórico atualizado.</p>
      <form id="lab-exam-form" class="grid-form">
        <label>
          Exame
          <select name="exame_id" required>
            <option value="">Selecione um exame cadastrado</option>
          </select>
        </label>
        <label>
          Data da coleta
          <input type="date" name="data_coleta" required />
        </label>
        <label>
          Valor do resultado
          <input type="number" step="0.01" name="valor" required />
        </label>
        <label>
          Laboratório do resultado
          <input type="text" name="laboratorio" />
        </label>
        <label class="ref-field">
          Referência mínima
          <input type="number" step="0.01" name="referencia_min" />
        </label>
        <label class="ref-field">
          Referência máxima
          <input type="number" step="0.01" name="referencia_max" />
        </label>
        <label class="attachment-field" style="display:none">
          Anexos para imagem
          <input type="file" name="anexos" accept=".pdf,image/png,image/jpeg" multiple />
          <ul class="file-list" id="attachment-list"></ul>
        </label>
        <label>
          Observações
          <textarea name="observacoes"></textarea>
        </label>
        <input type="hidden" name="resultado_id" value="" />
      </form>
      <div class="actions">
        <button class="secondary" type="button" onclick="window.location.href='navegacao.html'">Voltar</button>
        <button class="primary" id="lab-submit" type="button">Salvar resultado</button>
      </div>
      <div id="lab-feedback" class="form-feedback" hidden></div>
    </div>
  </div>

  <section class="lab-table-card">
      <h2>Laboratórios disponíveis</h2>
      <div class="lab-table-wrapper">
        <table class="lab-table">
          <thead>
            <tr>
              <th>Laboratório</th>
              <th>Especialidades</th>
              <th>Contato</th>
              <th>Ação</th>
            </tr>
          </thead>
          <tbody id="lab-table-body">
            <tr class="lab-table-row">
              <td>Laboratório Fleury</td>
              <td>Bioquímica, Hematologia</td>
              <td>(11) 4002-8922</td>
              <td><button type="button" data-lab="Fleury">Selecionar</button></td>
            </tr>
            <tr class="lab-table-row">
              <td>Laboratório Delboni</td>
              <td>Microbiologia, Parasitologia</td>
              <td>(11) 3003-3939</td>
              <td><button type="button" data-lab="Delboni">Selecionar</button></td>
            </tr>
            <tr class="lab-table-row">
              <td>Laboratório ABC</td>
              <td>Imagem, Citopatologia</td>
              <td>(21) 4004-0505</td>
              <td><button type="button" data-lab="ABC">Selecionar</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <script>
    const form = document.getElementById('lab-exam-form');
    const examSelect = form?.querySelector('[name="exame_id"]');
    const submitBtn = document.getElementById('lab-submit');
    const feedback = document.getElementById('lab-feedback');
    const attachmentField = document.querySelector('.attachment-field');
    const referenceFields = document.querySelectorAll('.ref-field');
    const attachmentInput = form?.querySelector('[name="anexos"]');
    const attachmentList = document.getElementById('attachment-list');
    const endpoint = new URL('../process/lab_exam.php', window.location.href).href;
    const definitionsEndpoint = new URL('../process/exam-definitions.php', window.location.href).href;
    const params = new URLSearchParams(window.location.search);
    const initialExamId = params.get('exame_id');
    const initialResultadoId = params.get('resultado_id');

    const definitionsById = new Map();

    const showFeedback = (message, type = 'success') => {
      if (!feedback) return;
      feedback.textContent = message;
      feedback.classList.remove('success', 'error');
      feedback.classList.add(type);
      feedback.hidden = false;
    };

    const hideFeedback = () => {
      if (feedback) {
        feedback.hidden = true;
      }
    };

    const toggleFieldsByType = (tipo) => {
      const isLab = tipo === 'laboratorial';
      referenceFields.forEach((field) => {
        field.style.display = isLab ? 'block' : 'none';
      });
      if (attachmentField) {
        attachmentField.style.display = isLab ? 'none' : 'block';
      }
      if (!isLab) {
        attachmentField?.querySelector('input')?.focus();
      }
      if (isLab) {
        if (attachmentList) {
          attachmentList.innerHTML = '';
        }
        if (attachmentInput) {
          attachmentInput.value = '';
        }
      }
    };

    const showAttachmentList = (files) => {
      if (!attachmentList) return;
      attachmentList.innerHTML = '';
      Array.from(files).forEach((file, index) => {
        const item = document.createElement('li');
        item.className = 'file-item';
        item.innerHTML = `
          <span>${file.name}</span>
          <button type="button" data-remove="${index}">Remover</button>
        `;
        attachmentList.appendChild(item);
      });
    };

    const handleSelectChange = () => {
      const definition = definitionsById.get(examSelect?.value ?? '');
      const tipo = definition?.tipo ?? 'laboratorial';
      toggleFieldsByType(tipo);
    };

    const setValue = (name, value) => {
      const field = form?.querySelector(`[name="${name}"]`);
      if (field) {
        field.value = value ?? '';
      }
    };

    const loadDefinitions = async () => {
      if (!examSelect) return;
      try {
        const response = await fetch(definitionsEndpoint, {
          headers: { Accept: 'application/json' },
        });
        const text = await response.text();
        if (!response.ok) {
          throw new Error(text || 'Falha ao carregar exames cadastrados.');
        }
        const payload = JSON.parse(text);
        const definitions = (Array.isArray(payload.data) ? payload.data : []).filter(
          (definition) => definition.tipo === 'laboratorial'
        );
        examSelect.innerHTML = '<option value="">Selecione um exame cadastrado</option>';
        definitions.forEach((definition) => {
          definitionsById.set(String(definition.exame_id), definition);
          const option = document.createElement('option');
          option.value = String(definition.exame_id);
          option.textContent = definition.nome ?? 'Exame';
          examSelect.appendChild(option);
        });
        if (initialExamId) {
          examSelect.value = initialExamId;
        }
        handleSelectChange();
      } catch (error) {
        showFeedback(error instanceof Error ? error.message : 'Erro ao carregar exames.', 'error');
      }
    };

    const fetchExamDetail = async () => {
      if (!initialExamId) return;
      const query = new URLSearchParams({ exame_id: initialExamId });
      if (initialResultadoId) {
        query.set('resultado_id', initialResultadoId);
      }
      try {
        const response = await fetch(`../process/exam-detail.php?${query.toString()}`, {
          headers: { Accept: 'application/json' },
        });
        const text = await response.text();
        if (!response.ok) {
          throw new Error(text || 'Erro ao carregar dados do exame.');
        }
        const payload = JSON.parse(text);
        if (!payload.success) {
          throw new Error(payload.message ?? 'Falha ao carregar exame.');
        }

        const data = payload.data ?? {};
        if (data.exame_id) {
          examSelect.value = String(data.exame_id);
        }
        setValue('data_coleta', data.data_coleta ?? data.data_realizacao ?? '');
        setValue('valor', data.valor ?? '');
        setValue('laboratorio', data.resultado_laboratorio ?? data.exame_laboratorio ?? '');
        setValue('observacoes', data.resultado_observacoes ?? data.exame_observacoes ?? '');
        setValue('resultado_id', data.resultado_id ?? '');
      } catch (error) {
        console.error('Falha ao carregar exame', error);
      }
    };

    examSelect?.addEventListener('change', handleSelectChange);

    attachmentInput?.addEventListener('change', () => {
      showAttachmentList(attachmentInput.files);
    });

    attachmentList?.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-remove]');
      if (!button || !attachmentInput) return;
      const removeIndex = Number(button.dataset.remove);
      const dt = new DataTransfer();
      Array.from(attachmentInput.files).forEach((file, idx) => {
        if (idx !== removeIndex) dt.items.add(file);
      });
      attachmentInput.files = dt.files;
      showAttachmentList(attachmentInput.files);
    });

    submitBtn?.addEventListener('click', async () => {
      if (!form?.checkValidity()) {
        form?.reportValidity();
        return;
      }
      hideFeedback();
      submitBtn.disabled = true;

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          body: new FormData(form),
          headers: {
            Accept: 'application/json',
          },
        });
        const text = await response.text();
        const payload = text ? JSON.parse(text) : {};
        if (!response.ok || payload.success === false) {
          throw new Error(payload.message ?? 'Erro ao salvar resultado.');
        }
        showFeedback(payload.message ?? 'Resultado cadastrado com sucesso.', 'success');
        if (!initialResultadoId) {
          form?.reset();
          examSelect.value = '';
        }
      } catch (error) {
        showFeedback(error instanceof Error ? error.message : 'Erro inesperado.', 'error');
      } finally {
        submitBtn.disabled = false;
      }
    });

    loadDefinitions().then(fetchExamDetail);

    document.querySelectorAll('.lab-table button').forEach((button) => {
      button.addEventListener('click', () => {
        const labName = button.dataset.lab;
        const laboratorioField = form?.querySelector('[name="laboratorio"]');
        if (laboratorioField) {
          laboratorioField.value = labName;
        }
        showFeedback(`Laboratório ${labName} copiado para o campo de resultado.`, 'success');
      });
    });
  </script>
</body>
</html>
