<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Prontuário Médico - Pessoa Física</title>
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
    
    .app-shell {
      max-width: 1100px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 1.5rem;
      box-shadow: 0 35px 60px rgba(15, 23, 42, 0.12);
      padding: 2rem;
    }

    .panel-header h1 {
      margin: 0;
      font-size: 2rem;
      color: #0f172a;
    }

    .panel-header p {
      margin: 0.35rem 0 1.5rem;
      color: #475569;
    }

    .table-wrapper {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
    }

    th,
    td {
      padding: 0.9rem 0.75rem;
      text-align: left;
      border-bottom: 1px solid #e2e8f0;
    }

    th {
      font-weight: 600;
      color: #1f2937;
      background: #f8fafc;
    }

    td {
      color: #1e1b4b;
    }

    td strong {
      display: block;
    }

    .action-cell {
      width: 60px;
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

    .content {
      display: flex;
      flex-direction: column;
    }
  </style>
</head>
  <body>
    <?php include __DIR__ . '/header.php'; ?>
    <main class="site-shell">
      <div class="app-shell">
        <div class="content">
          <section class="panel">
          <div class="panel-header">
            <h1>Lista de exames cadastrados</h1>
            <p>Esta tela mostra apenas exames lançados no prontuário que ainda não foram realizados.</p>
          </div>

          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Exame</th>
                  <th>Tipo</th>
                  <th>Unidade</th>
                  <th>Referência</th>
                  <th>Frequência</th>
                  <th>Laboratório</th>
                  <th>Ações</th>
                  <th>Data</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>HG</td>
                  <td>laboratorial</td>
                  <td>102</td>
                  <td>0.00 – 0.00</td>
                  <td>Mensal</td>
                  <td>Fleury</td>
                  <td class="action-cell">
                    <a href="editar-exame.html?exame=hg" class="icon-button" aria-label="Editar HG">✏️</a>
                  </td>
                  <td>17/01/2026</td>
                </tr>
                <tr>
                  <td>Potássio</td>
                  <td>laboratorial</td>
                  <td>4,3</td>
                  <td>3.80 – 4.50</td>
                  <td>Semanal</td>
                  <td>Fleury</td>
                  <td class="action-cell">
                    <a href="editar-exame.html?exame=potassio" class="icon-button" aria-label="Editar Potássio">✏️</a>
                  </td>
                  <td>01/01/2026</td>
                </tr>
                <tr>
                  <td>Sódio</td>
                  <td>laboratorial</td>
                  <td>143</td>
                  <td>130.00 – 145.00</td>
                  <td>Mensal</td>
                  <td>Laboratório XYZ</td>
                  <td class="action-cell">
                    <a href="editar-exame.html?exame=sodio" class="icon-button" aria-label="Editar Sódio">✏️</a>
                  </td>
                  <td>17/01/2026</td>
                </tr>
                <tr>
                  <td>Ureia</td>
                  <td>laboratorial</td>
                  <td>g</td>
                  <td>70.00 – 100.00</td>
                  <td>Trimestral</td>
                  <td>ABC</td>
                  <td class="action-cell">
                    <a href="editar-exame.html?exame=ureia" class="icon-button" aria-label="Editar Ureia">✏️</a>
                  </td>
                  <td>08/01/2026</td>
                </tr>
                <tr>
                  <td>Ureia</td>
                  <td>laboratorial</td>
                  <td>g</td>
                  <td>50.00 – 100.00</td>
                  <td>Semestral</td>
                  <td>Laboratório 123</td>
                  <td class="action-cell">
                    <a href="editar-exame.html?exame=ureia" class="icon-button" aria-label="Editar Ureia">✏️</a>
                  </td>
                  <td>31/12/2025</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </body>
</html>
