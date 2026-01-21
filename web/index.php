<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Prontuário Médico - Pessoa Física</title>
  <link rel="stylesheet" href="assets/styles/web.css" />
  <link rel="stylesheet" href="assets/styles/app-overrides.css" />
</head>
  <body class="web-mode">
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
