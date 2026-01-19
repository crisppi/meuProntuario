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

    .menu {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 0.6rem;
      margin-bottom: 1rem;
    }

    .menu-item {
      display: block;
      padding: 0.9rem 1.1rem;
      border-radius: 0.8rem;
      background: rgba(59, 130, 246, 0.1);
      color: #0f172a;
      text-decoration: none;
      font-weight: 600;
      text-align: center;
      border: 1px solid transparent;
      transition: transform .2s, border .2s;
    }

    .menu-item:hover {
      transform: translateY(-2px);
    }

    header {
      background: #fff;
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 35px 60px rgba(15, 23, 42, .15);
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .card {
      background: #fff;
      border-radius: 1rem;
      padding: 2rem;
      box-shadow: 0 30px 50px rgba(15, 23, 42, .15);
    }

    form {
      display: grid;
      gap: .9rem;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    label {
      display: flex;
      flex-direction: column;
      gap: .35rem;
      font-size: .95rem;
      color: #475569;
    }

    input,
    textarea {
      border-radius: .6rem;
      border: 1px solid #cbd5f5;
      padding: .75rem;
      font: inherit;
      background: #f8fafc;
    }

    textarea {
      grid-column: 1/-1;
      min-height: 5rem;
      resize: vertical;
    }

    .actions {
      margin-top: 1.5rem;
      display: flex;
      flex-wrap: wrap;
      gap: .6rem;
      justify-content: flex-end;
    }

    button {
      padding: .9rem 1.5rem;
      border: none;
      border-radius: .75rem;
      background: linear-gradient(135deg, #2563eb, #3b82f6);
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }

    .secondary {
      background: transparent;
      color: #2563eb;
      border: 1px solid rgba(37, 99, 235, .5);
    }

    .form-feedback {
      margin-top: 1rem;
      font-size: .95rem;
      color: #0f172a;
      padding: .75rem 1rem;
      border-radius: .65rem;
      border: 1px solid transparent;
      transition: opacity .2s;
      opacity: 0;
    }

    .form-feedback.success {
      background: rgba(16, 185, 129, .15);
      border-color: rgba(16, 185, 129, .5);
      color: #047857;
      opacity: 1;
    }

    .form-feedback.error {
      background: rgba(248, 113, 113, .15);
      border-color: rgba(248, 113, 113, .5);
      color: #b91c1c;
      opacity: 1;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/header.php'; ?>
  <main class="site-shell">
    <div class="panel">
    <header>
      <h1>Dados da pessoa física</h1>
      <p>Preencha o cadastro único do prontuário para manter histórico completo dos exames e consultas.</p>
    </header>

    <div class="card">
      <h2>Cadastro do paciente</h2>

      <form id="patient-form">
        <label>Nome completo
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

        <label>Alergias conhecidas
          <textarea name="alergias" rows="2"><?= patientValue('alergias'); ?></textarea>
        </label>

        <label>Condições crônicas
          <textarea name="condicoes_cronicas" rows="2"><?= patientValue('condicoes_cronicas'); ?></textarea>
        </label>

        <label>Observações gerais
          <textarea name="observacoes" rows="3"><?= patientValue('observacoes'); ?></textarea>
        </label>

        <label>Endereço completo
          <textarea name="logradouro" rows="3"><?= patientValue('logradouro'); ?></textarea>
        </label>

        <label>Número
          <input type="text" name="numero" value="<?= patientValue('numero'); ?>" />
        </label>

        <label>Complemento
          <input type="text" name="complemento" value="<?= patientValue('complemento'); ?>" />
        </label>

        <label>Bairro
          <input type="text" name="bairro" value="<?= patientValue('bairro'); ?>" />
        </label>

        <label>Cidade
          <input type="text" name="cidade" value="<?= patientValue('cidade'); ?>" />
        </label>

        <label>Estado
          <input type="text" name="estado" value="<?= patientValue('estado'); ?>" />
        </label>

        <label>CEP
          <input type="text" name="cep" value="<?= patientValue('cep'); ?>" />
        </label>

        <label>País
          <input type="text" name="pais" value="<?= patientValue('pais') ?: 'Brasil'; ?>" />
        </label>
      </form>

      <div class="actions">
        <button class="secondary" type="button" onclick="window.location.href='index.html'">Voltar</button>
        <button type="button" id="patient-submit">Salvar dados</button>
      </div>

      <?php if ($loadError): ?>
        <div class="form-feedback error" style="opacity:1;">
          Não foi possível carregar os dados diretamente do banco:<br>
          <?= htmlspecialchars($loadError, ENT_QUOTES) ?>
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
