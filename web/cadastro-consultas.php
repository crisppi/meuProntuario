<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cadastro de Consultas – Prontuário</title>
  <link rel="stylesheet" href="assets/styles/web.css" />
  <link rel="stylesheet" href="assets/styles/app-overrides.css" />
</head>
<body class="web-mode">
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
