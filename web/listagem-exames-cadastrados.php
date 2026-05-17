<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lista de exames cadastrados – Prontuário</title>
  <link rel="stylesheet" href="assets/styles/web.css" />
  <link rel="stylesheet" href="assets/styles/app-overrides.css?v=20260510-nav3" />
</head>

<body class="web-mode">
  <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
      <div class="card">
        <h1>Lista de exames cadastrados</h1>
        <div class="table-container">
          <table aria-live="polite">
            <thead>
            <tr>
              <th scope="col">Exame</th>
              <th scope="col">Tipo</th>
              <th scope="col">Unidade</th>
              <th scope="col">Referência</th>
              <th scope="col" class="icon-column" aria-label="Editar"></th>
            </tr>
            </thead>
            <tbody id="definition-list"></tbody>
          </table>
        </div>

        <div id="definition-loading" class="loading">Carregando exames cadastrados...</div>
        <div id="definition-empty" class="loading" hidden>Nenhum exame cadastrado ainda.</div>
        <div id="definition-feedback" class="form-feedback error" hidden></div>

      </div>
    </div>
  </main>

  <script>
    const definitionTableBody = document.getElementById('definition-list');
    const definitionLoading = document.getElementById('definition-loading');
    const definitionEmpty = document.getElementById('definition-empty');
    const definitionFeedback = document.getElementById('definition-feedback');
    const definitionsEndpoint = new URL('../process/exam-definitions.php', window.location.href).href;

    const formatReference = (min, max) => {
      const parts = [];
      if (min !== null && min !== undefined && String(min).trim() !== '') {
        parts.push(min);
      }
      if (max !== null && max !== undefined && String(max).trim() !== '') {
        parts.push(max);
      }
      if (parts.length === 0) {
        return '—';
      }
      return parts.join(' – ');
    };

    const formatDate = (value) => {
      if (!value) {
        return '—';
      }
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return value;
      }
      return date.toLocaleDateString('pt-BR');
    };

    const showDefinitionFeedback = (message) => {
      if (!definitionFeedback) return;
      definitionFeedback.textContent = message;
      definitionFeedback.hidden = false;
    };

    const buildEditUrl = (row) => {
      const url = new URL('./editar-exame.html', window.location.href);
      if (row.slug) {
        url.searchParams.set('exame', row.slug);
      }
      if (row.exame_id) {
        url.searchParams.set('exame_id', row.exame_id);
      } else if (!row.slug && row.nome) {
        url.searchParams.set('exame', row.nome);
      }
      return url.href;
    };



    const loadDefinitions = async () => {
      definitionLoading.hidden = false;
      definitionEmpty.hidden = true;
      definitionFeedback.hidden = true;

      try {
        const response = await fetch(definitionsEndpoint, {
          headers: {
            Accept: 'application/json',
          },
        });
        const text = await response.text();
        const payload = JSON.parse(text);

        if (!response.ok || payload.success === false) {
          throw new Error(payload.message ?? 'Falha ao buscar exames cadastrados.');
        }

        const rows = Array.isArray(payload.data) ? payload.data : [];
        if (rows.length === 0) {
          definitionEmpty.hidden = false;
          return;
        }

        renderDefinitionRows(rows);
      } catch (error) {
        showDefinitionFeedback(error instanceof Error ? error.message : 'Erro inesperado.');
      } finally {
        definitionLoading.hidden = true;
      }
    };

    loadDefinitions();


    const renderDefinitionRows = (rows) => {
      definitionTableBody.innerHTML = '';
      rows.forEach((row) => {
        const editUrl = buildEditUrl(row);
        const tr = document.createElement('tr');

        // Adicionamos data-label em cada TD para o CSS mobile usar
        tr.innerHTML = `
      <td data-label="Exame">
        <strong>${row.nome ?? '—'}</strong>
      </td>
      <td data-label="Tipo">${row.tipo ?? '—'}</td>
      <td data-label="Unidade">${row.unidade ?? '—'}</td>
      <td data-label="Referência">${formatReference(row.referencia_min, row.referencia_max)}</td>
      <td data-label="Editar" class="action-cell">
        <a href="${editUrl}" class="icon-button" aria-label="Editar ${row.nome ?? 'exame'}">✏️</a>
      </td>
    `;
        definitionTableBody.appendChild(tr);
      });
    };
  </script>

</body>

</html>
