<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastro de Consultas – Prontuário</title>
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
  </style>
</head>
<body>
  <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
    <div class="card">
      <h1>Cadastro de consultas</h1>
      <p>Inclua detalhes da consulta com médico, motivo e status.</p>
      <form class="grid-form">
        <label>
          Data
          <input type="date" name="data_consulta" />
        </label>
        <label>
          Hora início
          <input type="time" name="hora_inicio" />
        </label>
        <label>
          Hora fim
          <input type="time" name="hora_fim" />
        </label>
        <label>
          Médico responsável
          <input type="text" name="medico" placeholder="Dr. José Almeida" />
        </label>
        <label>
          Motivo
          <textarea name="motivo"></textarea>
        </label>
        <label>
          Diagnóstico preliminar
          <textarea name="diagnostico"></textarea>
        </label>
        <label>
          Status
          <select name="status">
            <option value="agendada">Agendada</option>
            <option value="realizada">Realizada</option>
            <option value="cancelada">Cancelada</option>
          </select>
        </label>
      </form>
      <div class="actions">
        <button class="secondary" type="button" onclick="window.location.href='navegacao.html'">Voltar</button>
        <button type="button">Salvar consulta</button>
      </div>
    </div>
    </div>
  </main>
</body>
</html>
