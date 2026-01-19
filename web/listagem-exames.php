<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Listagem de Exames – Prontuário</title>
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

    .badge {
      padding: 0.35rem 0.9rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .badge.normal {
      background: rgba(16, 185, 129, 0.15);
      color: #047857;
    }

    .badge.alterado {
      background: rgba(249, 115, 22, 0.15);
      color: #c2410c;
    }

    .badge.critico {
      background: rgba(220, 38, 38, 0.15);
      color: #b91c1c;
    }

    .badge.sem-valor {
      background: rgba(59, 130, 246, 0.15);
      color: #2563eb;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .status-cell {
      display: flex;
      gap: 0.35rem;
      align-items: center;
      justify-content: flex-start;
    }

    .edit-btn {
      border: none;
      background: transparent;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #2563eb;
    }

    .edit-btn svg {
      width: 20px;
      height: 20px;
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
  </style>
</head>
<body>
  <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
    <div class="card">
      <h1>Lista de resultados laboratoriais</h1>
      <p>Acompanhe os registros recentes com status, valores e observações.
        <a href="listagem-exames-cadastrados.php">Consultar lista de exames cadastrados</a>
      </p>

      <div class="table-container">
        <table aria-live="polite">
          <thead>
            <tr>
              <th scope="col">Exame</th>
              <th scope="col">Data</th>
              <th scope="col">Valor</th>
              <th scope="col">Unidade</th>
              <th scope="col">Laboratório</th>
              <th scope="col">Maior valor</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody id="exam-list"></tbody>
        </table>
      </div>

      <div id="exam-loading" class="loading">Carregando exames...</div>
      <div id="exam-empty" class="loading" hidden>Não há resultados cadastrados ainda.</div>
      <div id="exam-feedback" class="form-feedback error" hidden></div>
    </div>
  </div>
  </main>

  <script>
    const examListBody = document.getElementById('exam-list');
    const loading = document.getElementById('exam-loading');
    const emptyState = document.getElementById('exam-empty');
    const feedback = document.getElementById('exam-feedback');
    const endpoint = new URL('./process/exams.php', `${window.location.origin}${window.location.pathname}`).href;

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

    const normalizeNumber = (value) => {
      if (value === null || value === undefined) {
        return NaN;
      }
      if (typeof value === 'number') {
        return value;
      }
      return Number(String(value).replace(',', '.').trim());
    };

    const formatNumber = (value) => {
      const numeric = normalizeNumber(value);
      if (Number.isNaN(numeric)) {
        return '—';
      }
      return numeric.toLocaleString('pt-BR', { maximumFractionDigits: 2 });
    };

    const computeStatus = (value, min, max) => {
      const numeric = normalizeNumber(value);
      const minValue = normalizeNumber(min);
      const maxValue = normalizeNumber(max);
      const hasRange = !Number.isNaN(minValue) && !Number.isNaN(maxValue);

      if (Number.isNaN(numeric) || !hasRange) {
        return { label: 'Sem valor', variant: 'sem-valor' };
      }

      if (numeric < minValue || numeric > maxValue) {
        return { label: 'Alterado', variant: 'alterado' };
      }

      return { label: 'Normal', variant: 'normal' };
    };

    function attachEditHandlers() {
      document.querySelectorAll('.edit-btn').forEach((btn, index) => {
        btn.addEventListener('click', () => {
          const examId = btn.dataset.examId;
          const resultadoId = btn.dataset.resultadoId;
          const params = new URLSearchParams({ exame_id: examId });
          if (resultadoId) {
            params.set('resultado_id', resultadoId);
          }
          window.location.href = `cadastro-exames-laboratoriais.php?${params.toString()}`;
        });
      });
    }

    const renderRows = (rows) => {
      examListBody.innerHTML = '';
      rows.forEach((row) => {
        const status = computeStatus(row.valor, row.referencia_min, row.referencia_max);
        const laboratorio = row.resultado_laboratorio || row.exame_laboratorio || '—';
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            <strong>${row.exame || 'Exame não identificado'}</strong>
            <div style="font-size:0.85rem; color:#6b7280;">${row.tipo || 'Tipo indefinido'}</div>
          </td>
          <td>${formatDate(row.data_realizacao)}</td>
          <td>${formatNumber(row.valor)}</td>
          <td>${row.unidade || '—'}</td>
          <td>${laboratorio}</td>
          <td>${formatNumber(row.maior_valor)}</td>
        <td class="status-cell">
            <span class="badge ${status.variant}">${status.label}</span>
            <button class="edit-btn" type="button" aria-label="Editar exames realizados" data-exam-id="${row.exame_id}" data-resultado-id="${row.resultado_id || ''}">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4 17.25V21h3.75L17.81 10.94l-3.75-3.75L4 17.25zm15.27-9.38a.996.996 0 000-1.41l-2.12-2.12a.996.996 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.61-1.61z"></path>
              </svg>
            </button>
          </td>
        `;
        examListBody.appendChild(tr);
      });
      attachEditHandlers();
    };

    const showFeedback = (message) => {
      if (!feedback) return;
      feedback.textContent = message;
      feedback.hidden = false;
    };

    const loadExams = async () => {
      loading.hidden = false;
      emptyState.hidden = true;
      feedback.hidden = true;

      try {
        const response = await fetch(endpoint, {
          headers: {
            Accept: 'application/json',
          },
        });

        const text = await response.text();
        const payload = JSON.parse(text);

        if (!response.ok || payload.success === false) {
          throw new Error(payload.message ?? 'Falha ao buscar exames.');
        }

        const launched = Array.isArray(payload.data)
          ? payload.data.filter((row) => row.resultado_id || row.valor !== null)
          : [];

        if (launched.length === 0) {
          emptyState.hidden = false;
          return;
        }

        renderRows(launched);
      } catch (error) {
        showFeedback(error instanceof Error ? error.message : 'Erro inesperado.');
      } finally {
        loading.hidden = true;
      }
    };

    loadExams();
  </script>
</body>
</html>
