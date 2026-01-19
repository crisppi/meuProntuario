<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lista de exames cadastrados – Prontuário</title>
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

    .table-container {
      margin-top: 1.5rem;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
    }

    th,
    td {
      padding: 0.9rem;
      text-align: left;
      border-bottom: 1px solid #e2e8f0;
    }

    th {
      background: #f8fafc;
      font-weight: 600;
      color: #1f2937;
    }

    tr:last-child td {
      border-bottom: none;
    }

    .loading {
      margin-top: 1rem;
      color: #475569;
      font-size: 0.95rem;
    }

    .form-feedback {
      margin-top: 1rem;
      padding: 0.85rem 1rem;
      border-radius: 0.65rem;
      border: 1px solid transparent;
      font-size: 0.95rem;
    }

    .form-feedback.error {
      background: rgba(248, 113, 113, 0.15);
      border-color: rgba(248, 113, 113, 0.5);
      color: #b91c1c;
    }

    .action-cell {
      width: 40px;
      text-align: center;
    }

    .icon-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      border: 1px solid #cbd5f5;
      background: #fff;
      color: #2563eb;
      text-decoration: none;
      font-size: 1rem;
    }

    .icon-button:hover {
      background: #2563eb;
      color: #fff;
      border-color: #2563eb;
    }

  </style>
</head>
<body>
  <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
    <div class="card">
      <h1>Lista de exames cadastrados</h1>
      <p>Visualize todas as definições de exames com suas referências, unidades e observações.
        <a href="listagem-exames.php">Ver resultados laboratoriais lançados</a>
      </p>

      <div class="table-container">
        <table aria-live="polite">
          <thead>
            <tr>
              <th scope="col">Exame</th>
              <th scope="col">Tipo</th>
              <th scope="col">Unidade</th>
              <th scope="col">Referência</th>
              <th scope="col">Frequência</th>
              <th scope="col">Laboratório</th>
              <th scope="col">Ações</th>
              <th scope="col">Observações</th>
              <th scope="col">Data</th>
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

    const renderDefinitionRows = (rows) => {
      definitionTableBody.innerHTML = '';
      rows.forEach((row) => {
        const editUrl = buildEditUrl(row);
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            <strong>${row.nome ?? '—'}</strong>
            <div style="font-size:0.85rem; color:#6b7280;">${row.slug ?? ''}</div>
          </td>
          <td>${row.tipo ?? '—'}</td>
          <td>${row.unidade ?? '—'}</td>
          <td>${formatReference(row.referencia_min, row.referencia_max)}</td>
          <td>${row.frequencia ?? '—'}</td>
          <td>${row.laboratorio ?? '—'}</td>
          <td class="action-cell">
            <a href="${editUrl}" class="icon-button" aria-label="Editar ${row.nome ?? row.slug ?? 'exame'}">✏️</a>
          </td>
          <td>${row.observacoes ?? '—'}</td>
          <td>${formatDate(row.data_realizacao ?? row.criado_em)}</td>
        `;
        definitionTableBody.appendChild(tr);
      });
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
  </script>
</body>
</html>
