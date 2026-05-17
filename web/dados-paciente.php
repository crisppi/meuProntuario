<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use Prontuario\DAO\PatientDAO;
use Throwable;

$patient = [];
$loadError = '';

try {
  $patient = (new PatientDAO())->loadLatest() ?? [];
} catch (Throwable $error) {
  $loadError = $error->getMessage();
}

function patientValue(string $key): string
{
  global $patient;
  if (empty($patient[$key])) return '';
  return htmlspecialchars((string)$patient[$key], ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dados do Paciente – Prontuário</title>
  <link rel="stylesheet" href="assets/styles/web.css" />
  <link rel="stylesheet" href="assets/styles/app-overrides.css?v=20260510-nav3" />
</head>
<body class="web-mode">
  <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
    <header class="page-intro">
      <h1>Dados da pessoa física</h1>
      <p>Preencha o cadastro único do prontuário para manter histórico completo dos exames e consultas.</p>
    </header>

    <div class="card">
      <h2>Cadastro do paciente</h2>

      <form id="patient-form" class="patient-form">
        <label class="wide">Nome completo
          <input type="text" name="nome" value="<?= patientValue('nome'); ?>" required />
        </label>

        <label>CPF
          <input type="text" name="cpf" value="<?= patientValue('cpf'); ?>" />
        </label>

        <label>E-mail
          <input type="email" name="email" value="<?= patientValue('email'); ?>" />
        </label>

        <label>Telefone
          <input type="tel" name="telefone" value="<?= patientValue('telefone'); ?>" />
        </label>

        <label>Data de nascimento
          <input type="date" name="data_nascimento" value="<?= patientValue('data_nascimento'); ?>" />
        </label>

        <label>Peso (kg)
          <input type="number" min="0" step="0.1" name="peso" value="<?= patientValue('peso'); ?>" />
        </label>

        <label>Altura (m)
          <input type="number" min="0" step="0.01" name="altura" value="<?= patientValue('altura'); ?>" />
        </label>

        <label>Tipo sanguíneo
          <input type="text" name="tipo_sanguineo" value="<?= patientValue('tipo_sanguineo'); ?>" placeholder="Ex: O+ ou A-" />
        </label>

        <label class="wide">Alergias conhecidas
          <textarea name="alergias" rows="2"><?= patientValue('alergias'); ?></textarea>
        </label>

        <label class="wide">Condições crônicas
          <textarea name="condicoes_cronicas" rows="2"><?= patientValue('condicoes_cronicas'); ?></textarea>
        </label>

        <label class="wide">Observações gerais
          <textarea name="observacoes" rows="3"><?= patientValue('observacoes'); ?></textarea>
        </label>
      </form>

      <div class="actions">
        <button class="secondary" type="button" onclick="window.location.href='navegacao.html'">Voltar</button>
        <button type="button" id="patient-submit">Salvar dados</button>
      </div>

      <?php if ($loadError): ?>
        <div class="form-feedback error load-warning" style="opacity:1;" title="<?= htmlspecialchars($loadError, ENT_QUOTES) ?>">
          Não foi possível carregar os dados salvos.
        </div>
      <?php endif; ?>

      <div id="patient-feedback" class="form-feedback" aria-live="polite"></div>
    </div>
  </div>
  </main>

  <script>
    const patientForm = document.getElementById('patient-form');
    const submitBtn = document.getElementById('patient-submit');
    const feedback = document.getElementById('patient-feedback');

    function showFeedback(message, type = 'success') {
      feedback.textContent = message;
      feedback.classList.remove('success', 'error');
      feedback.classList.add(type);
    }

    submitBtn.addEventListener('click', async () => {
      if (!patientForm.checkValidity()) {
        patientForm.reportValidity();
        return;
      }

      submitBtn.disabled = true;

      try {
        const response = await fetch('./process/patient.php', {
          method: 'POST',
          body: new FormData(patientForm),
          headers: {
            'Accept': 'application/json'
          }
        });

        const contentType = response.headers.get('content-type') || '';
        const text = await response.text();

        if (!contentType.includes('application/json')) {
          throw new Error('Endpoint respondeu algo que não é JSON. Verifique o caminho ./process/patient.php');
        }

        const payload = JSON.parse(text);

        if (!response.ok || payload.success === false) {
          throw new Error(payload.message ?? 'Erro ao salvar.');
        }

        showFeedback(payload.message ?? 'Dados salvos com sucesso.', 'success');
      } catch (e) {
        showFeedback(e.message || 'Erro inesperado.', 'error');
      } finally {
        submitBtn.disabled = false;
      }
    });
  </script>
</body>

</html>
