document.addEventListener('DOMContentLoaded', async () => {
  const app = window.LocalHealthApp;
  if (!app) {
    return;
  }

  app.auth.ensureLocalSession?.();

  const owner = app.auth.owner() || { name: 'Usuário' };

  const state = {
    consultationEditId: '',
    medicationEditId: '',
    examEditId: '',
    examPanel: 'definition',
  };

  const $ = (selector) => document.querySelector(selector);
  const $$ = (selector) => Array.from(document.querySelectorAll(selector));
  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const appOwner = $('#app-owner');
  if (appOwner) {
    appOwner.textContent = owner.name || 'Usuário';
  }

  const screenButtons = $$('[data-screen-target]');
  const screens = $$('[data-screen]');
  const examPanelButtons = $$('[data-exam-panel-target]');
  const examPanels = $$('[data-exam-panel]');
  const menuToggle = $('#app-menu-toggle');
  const appNav = $('#app-nav');
  const examPanelToggle = $('#exam-panel-toggle');
  const examPanelMenu = $('#exam-panel-menu');
  const examPanelCurrent = $('#exam-panel-current');
  const examPanelLabels = {
    definition: 'Cadastrar exame',
    result: 'Lançar resultado',
    evolution: 'Evolução',
    'definitions-list': 'Exames cadastrados',
    'results-list': 'Resultados',
    attachments: 'Anexos',
  };

  const setMenuOpen = (isOpen) => {
    if (!appNav || !menuToggle) return;
    appNav.hidden = !isOpen;
    appNav.classList.toggle('is-open', isOpen);
    menuToggle.classList.toggle('is-open', isOpen);
    menuToggle.setAttribute('aria-expanded', String(isOpen));
    document.body.classList.toggle('menu-is-open', isOpen);
  };

  const setExamMenuOpen = (isOpen) => {
    if (!examPanelMenu || !examPanelToggle) return;
    examPanelMenu.hidden = !isOpen;
    examPanelMenu.classList.toggle('is-open', isOpen);
    examPanelToggle.classList.toggle('is-open', isOpen);
    examPanelToggle.setAttribute('aria-expanded', String(isOpen));
  };

  const activateScreen = (name) => {
    screens.forEach((screen) => {
      screen.hidden = screen.dataset.screen !== name;
    });
    screenButtons.forEach((button) => {
      button.classList.toggle('is-active', button.dataset.screenTarget === name);
    });
    setMenuOpen(false);
    if (name === 'alerts') {
      renderAlerts();
    }
    if (name === 'doctor-mode') {
      renderDoctorMode();
    }
    if (name === 'pre-consultation') {
      renderPreConsultation();
    }
    window.history.replaceState({}, '', `index.html?screen=${encodeURIComponent(name)}`);
  };

  const activateExamPanel = (name) => {
    state.examPanel = name;
    examPanels.forEach((panel) => {
      panel.hidden = panel.dataset.examPanel !== name;
    });
    examPanelButtons.forEach((button) => {
      button.classList.toggle('is-active', button.dataset.examPanelTarget === name);
    });
    if (examPanelCurrent) {
      examPanelCurrent.textContent = examPanelLabels[name] || 'Exames';
    }
    setExamMenuOpen(false);
    if (name === 'evolution') {
      renderExamEvolution();
    }
  };

  screenButtons.forEach((button) => {
    button.addEventListener('click', () => activateScreen(button.dataset.screenTarget));
  });

  menuToggle?.addEventListener('click', () => {
    setMenuOpen(appNav?.hidden !== false);
  });

  document.addEventListener('click', (event) => {
    if (appNav && !appNav.hidden && !appNav.contains(event.target) && !menuToggle?.contains(event.target)) {
      setMenuOpen(false);
    }
    if (examPanelMenu && !examPanelMenu.hidden && !examPanelMenu.contains(event.target) && !examPanelToggle?.contains(event.target)) {
      setExamMenuOpen(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setMenuOpen(false);
      setExamMenuOpen(false);
    }
  });

  examPanelButtons.forEach((button) => {
    button.addEventListener('click', () => activateExamPanel(button.dataset.examPanelTarget));
  });

  examPanelToggle?.addEventListener('click', () => {
    setExamMenuOpen(examPanelMenu?.hidden !== false);
  });

  $('#logout-btn')?.addEventListener('click', () => {
    app.auth.logout();
    app.auth.ensureLocalSession?.();
    window.location.href = 'index.html';
  });

  const showMessage = (element, message, type = 'success') => {
    if (!element) return;
    element.textContent = message;
    element.hidden = message === '';
    element.classList.remove('success', 'error');
    element.classList.add(type);
  };

  const fillForm = (form, data) => {
    if (!form || !data) return;
    Array.from(form.elements).forEach((field) => {
      if (!field.name) return;
      field.value = data[field.name] ?? '';
    });
  };

  const getFormData = (form) => Object.fromEntries(new FormData(form).entries());

  const toNumber = (value) => {
    const number = Number(String(value ?? '').replace(',', '.'));
    return Number.isFinite(number) ? number : null;
  };

  const getReferenceStatus = (item) => {
    const value = toNumber(item.valor ?? item.valor_numero);
    const min = toNumber(item.referencia_min);
    const max = toNumber(item.referencia_max);
    if (value === null || (min === null && max === null)) {
      return {
        key: 'none',
        label: 'Sem referência',
      };
    }
    if (min !== null && value < min) {
      return {
        key: 'low',
        label: 'Abaixo da referência',
      };
    }
    if (max !== null && value > max) {
      return {
        key: 'high',
        label: 'Acima da referência',
      };
    }
    return {
      key: 'ok',
      label: 'Dentro da referência',
    };
  };

  const referenceBadge = (status) => `<span class="reference-badge is-${status.key}">${status.label}</span>`;

  const getLatestExamAlerts = () => app.store.listExamEvolution().map((exam) => {
    const latest = exam.latest;
    const status = getReferenceStatus({
      ...exam,
      valor: latest?.valor,
      valor_numero: latest?.valor_numero,
    });
    return {
      ...exam,
      latest,
      status,
    };
  }).filter((exam) => exam.latest && ['low', 'high'].includes(exam.status.key));

  const formatDate = (value, includeTime = false) => {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return includeTime
      ? date.toLocaleString('pt-BR')
      : date.toLocaleDateString('pt-BR');
  };

  const stats = () => app.store.stats();

  const renderDashboard = () => {
    const data = stats();
    $('#stat-exams').textContent = String(data.exams);
    $('#stat-results').textContent = String(data.examResults);
    $('#stat-consultations').textContent = String(data.consultations);
    $('#stat-medications').textContent = String(data.medications);
    $('#stat-attachments').textContent = String(data.attachments);
    const alertStat = $('#stat-alerts');
    if (alertStat) {
      alertStat.textContent = String(getLatestExamAlerts().length);
    }
  };

  const personalForm = $('#personal-form');
  const profileForm = $('#profile-form');
  const preConsultationForm = $('#preconsultation-form');
  await app.store.init();
  fillForm(personalForm, app.store.getPersonal());
  fillForm(profileForm, app.store.getProfile());
  fillForm(preConsultationForm, app.store.getPreConsultation?.());

  $('#personal-save')?.addEventListener('click', async () => {
    if (!personalForm.checkValidity()) {
      personalForm.reportValidity();
      return;
    }
    await app.store.savePersonal(getFormData(personalForm));
    showMessage($('#personal-feedback'), 'Dados pessoais salvos.');
  });

  $('#profile-save')?.addEventListener('click', async () => {
    await app.store.saveProfile(getFormData(profileForm));
    showMessage($('#profile-feedback'), 'Perfil salvo.');
  });

  $('#preconsultation-save')?.addEventListener('click', async () => {
    await app.store.savePreConsultation(getFormData(preConsultationForm));
    showMessage($('#preconsultation-feedback'), 'Preparação salva.');
  });

  const consultationForm = $('#consultation-form');
  const renderConsultations = () => {
    const tbody = $('#consultation-list');
    const search = ($('#consultation-search')?.value || '').trim().toLowerCase();
    const rows = app.store.listConsultations().filter((item) => {
      const text = `${item.medico} ${item.motivo} ${item.diagnostico}`.toLowerCase();
      return !search || text.includes(search);
    });
    tbody.innerHTML = '';
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6">Nenhuma consulta registrada.</td></tr>';
      return;
    }
    rows.forEach((item) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${formatDate(item.data_consulta)}</td>
        <td>${item.medico || '—'}</td>
        <td>${item.motivo || '—'}</td>
        <td>${item.diagnostico || '—'}</td>
        <td>${item.status || 'agendada'}</td>
        <td><button type="button" class="button ghost" data-edit-consultation="${item.id}">Editar</button></td>
      `;
      tbody.appendChild(tr);
    });
    $$('[data-edit-consultation]').forEach((button) => {
      button.addEventListener('click', () => {
        const consultation = app.store.getConsultation(button.dataset.editConsultation);
        state.consultationEditId = consultation?.id || '';
        fillForm(consultationForm, consultation);
        activateScreen('consultations');
        consultationForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  };

  $('#consultation-save')?.addEventListener('click', async () => {
    if (!consultationForm.checkValidity()) {
      consultationForm.reportValidity();
      return;
    }
    const payload = getFormData(consultationForm);
    payload.id = state.consultationEditId;
    await app.store.saveConsultation(payload);
    state.consultationEditId = '';
    consultationForm.reset();
    showMessage($('#consultation-feedback'), 'Consulta salva.');
    renderConsultations();
    renderDoctorMode();
    renderDashboard();
  });

  $('#consultation-search')?.addEventListener('input', renderConsultations);

  const medicationForm = $('#medication-form');
  const renderMedications = () => {
    const tbody = $('#medication-list');
    const search = ($('#medication-search')?.value || '').trim().toLowerCase();
    const rows = app.store.listMedications().filter((item) => {
      const text = `${item.nome} ${item.laboratorio} ${item.dosagem} ${item.intervalo}`.toLowerCase();
      return !search || text.includes(search);
    });
    tbody.innerHTML = '';
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6">Nenhum medicamento cadastrado.</td></tr>';
      return;
    }
    rows.forEach((item) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${item.nome || '—'}</td>
        <td>${item.laboratorio || '—'}</td>
        <td>${item.dosagem || '—'}</td>
        <td>${item.intervalo || '—'}</td>
        <td>${item.status || 'Em Uso'}</td>
        <td><button type="button" class="button ghost" data-edit-medication="${item.id}">Editar</button></td>
      `;
      tbody.appendChild(tr);
    });
    $$('[data-edit-medication]').forEach((button) => {
      button.addEventListener('click', () => {
        const medication = app.store.getMedication(button.dataset.editMedication);
        state.medicationEditId = medication?.id || '';
        fillForm(medicationForm, medication);
        activateScreen('medications');
        medicationForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  };

  $('#medication-save')?.addEventListener('click', async () => {
    if (!medicationForm.checkValidity()) {
      medicationForm.reportValidity();
      return;
    }
    const payload = getFormData(medicationForm);
    payload.id = state.medicationEditId;
    await app.store.saveMedication(payload);
    state.medicationEditId = '';
    medicationForm.reset();
    medicationForm.querySelector('[name="status"]').value = 'Em Uso';
    showMessage($('#medication-feedback'), 'Medicamento salvo.');
    renderMedications();
    renderDoctorMode();
    renderPreConsultation();
    renderDashboard();
  });

  $('#medication-search')?.addEventListener('input', renderMedications);

  const examDefinitionForm = $('#exam-definition-form');
  const examResultForm = $('#exam-result-form');
  const examSelect = $('#result-exam-id');
  const examValueField = $('#result-value-field');
  const resultExamSearch = $('#result-exam-search');
  const evolutionSearch = $('#evolution-search');

  const refreshExamSelect = () => {
    const currentValue = examSelect.value;
    const search = (resultExamSearch?.value || '').trim().toLowerCase();
    const rows = app.store.listExamDefinitions().filter((item) => {
      const text = `${item.nome} ${item.unidade} ${item.frequencia}`.toLowerCase();
      return !search || text.includes(search);
    });
    examSelect.innerHTML = '<option value="">Selecione um exame</option>';
    rows.forEach((item) => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = `${item.nome}${item.unidade ? ` (${item.unidade})` : ''}`;
      option.dataset.tipo = item.tipo || '';
      examSelect.appendChild(option);
    });
    if (currentValue) {
      examSelect.value = currentValue;
    }
    const selected = examSelect.selectedOptions[0];
    const tipo = selected?.dataset?.tipo || '';
    examValueField.hidden = tipo !== 'laboratorial';
  };

  const renderExamDefinitions = () => {
    const tbody = $('#exam-definition-list');
    const search = ($('#exam-search')?.value || '').trim().toLowerCase();
    const rows = app.store.listExamDefinitions().filter((item) => {
      const text = `${item.nome} ${item.tipo} ${item.laboratorio}`.toLowerCase();
      return !search || text.includes(search);
    });
    tbody.innerHTML = '';
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6">Nenhum exame cadastrado.</td></tr>';
      return;
    }
    rows.forEach((item) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${item.nome || '—'}</td>
        <td>${item.tipo || '—'}</td>
        <td>${item.unidade || '—'}</td>
        <td>${item.referencia_min || '—'} / ${item.referencia_max || '—'}</td>
        <td>${item.frequencia || '—'}</td>
        <td><button type="button" class="button ghost" data-edit-exam="${item.id}">Editar</button></td>
      `;
      tbody.appendChild(tr);
    });
    $$('[data-edit-exam]').forEach((button) => {
      button.addEventListener('click', () => {
        const exam = app.store.getExamDefinition(button.dataset.editExam);
        state.examEditId = exam?.id || '';
        fillForm(examDefinitionForm, {
          ...exam,
          nome_exame: exam?.nome || '',
          tipo_exame: exam?.tipo || '',
        });
        activateScreen('exams');
        activateExamPanel('definition');
        examDefinitionForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
    refreshExamSelect();
  };

  const renderExamResults = () => {
    const container = $('#exam-results-list');
    const attachmentsContainer = $('#attachments-list');
    const query = ($('#result-search')?.value || '').trim().toLowerCase();
    const results = app.store.listExamResults().filter((item) => {
      const text = `${item.exame_nome} ${item.laboratorio} ${item.observacoes}`.toLowerCase();
      return !query || text.includes(query);
    });
    const attachments = app.store.listAttachments().filter((item) => {
      const text = `${item.exame_nome} ${item.nome_original} ${item.resultado_observacoes}`.toLowerCase();
      return !query || text.includes(query);
    });

    container.innerHTML = '';
    if (!results.length) {
      container.innerHTML = '<div class="empty-state">Nenhum resultado registrado.</div>';
    } else {
      results.forEach((item) => {
        const status = getReferenceStatus(item);
        const article = document.createElement('article');
        article.className = `local-card result-card is-${status.key}`;
        const attachmentHtml = item.attachments.length
          ? `<div class="attachment-inline-list">${item.attachments.map((attachment) => `
              <button type="button" class="button ghost" data-open-attachment="${attachment.id}">
                ${attachment.nome_original}
              </button>
            `).join('')}</div>`
          : '<p class="muted">Sem anexos.</p>';
        article.innerHTML = `
          <div class="result-card-header">
            <h3>${item.exame_nome}</h3>
            ${referenceBadge(status)}
          </div>
          <p class="muted">${item.exame_tipo || 'geral'} • ${formatDate(item.data_coleta)}</p>
          <p><strong>Valor:</strong> ${item.valor || '—'} ${item.unidade || ''}</p>
          <p><strong>Referência:</strong> ${item.referencia_min || '—'} / ${item.referencia_max || '—'} ${item.unidade || ''}</p>
          <p><strong>Laboratório:</strong> ${item.laboratorio || '—'}</p>
          <p><strong>Observações:</strong> ${item.observacoes || '—'}</p>
          ${attachmentHtml}
        `;
        container.appendChild(article);
      });
    }

    attachmentsContainer.innerHTML = '';
    if (!attachments.length) {
      attachmentsContainer.innerHTML = '<div class="empty-state">Nenhum anexo salvo.</div>';
    } else {
      attachments.forEach((item) => {
        const article = document.createElement('article');
        article.className = 'local-card attachment-card-local';
        const isImage = String(item.mime_type || '').startsWith('image/');
        const preview = isImage && item.preview_url
          ? `<img src="${item.preview_url}" alt="${item.nome_original}" class="attachment-preview-local" />`
          : '<div class="attachment-preview-fallback">Arquivo</div>';
        article.innerHTML = `
          ${preview}
          <h3>${item.exame_nome}</h3>
          <p class="muted">${formatDate(item.data_coleta)} • ${item.nome_original}</p>
          <p>${item.resultado_observacoes || 'Sem observações.'}</p>
          <button type="button" class="button secondary" data-open-attachment="${item.id}">Abrir anexo</button>
        `;
        attachmentsContainer.appendChild(article);
      });
    }

    $$('[data-open-attachment]').forEach((button) => {
      button.addEventListener('click', () => {
        const attachment = app.store.getAttachment(button.dataset.openAttachment);
        const url = attachment?.preview_url || '';
        if (!url) {
          showMessage($('#exam-result-feedback'), 'Não foi possível abrir este anexo neste ambiente.', 'error');
          return;
        }
        window.open(url, '_blank', 'noopener');
      });
    });
  };

  const buildSparkline = (exam) => {
    const width = 320;
    const height = 148;
    const padding = 22;
    const values = exam.results.map((result) => result.valor_numero);
    const refMin = Number(String(exam.referencia_min || '').replace(',', '.'));
    const refMax = Number(String(exam.referencia_max || '').replace(',', '.'));
    const domainValues = [...values];
    if (Number.isFinite(refMin)) domainValues.push(refMin);
    if (Number.isFinite(refMax)) domainValues.push(refMax);
    const minValue = Math.min(...domainValues);
    const maxValue = Math.max(...domainValues);
    const range = maxValue - minValue || 1;
    const xFor = (index) => {
      if (exam.results.length === 1) return width / 2;
      return padding + (index * (width - padding * 2)) / (exam.results.length - 1);
    };
    const yFor = (value) => height - padding - ((value - minValue) * (height - padding * 2)) / range;
    const points = values.map((value, index) => `${xFor(index)},${yFor(value)}`).join(' ');
    const refLines = [refMin, refMax]
      .filter((value) => Number.isFinite(value))
      .map((value) => {
        const y = yFor(value);
        return `<line x1="${padding}" y1="${y}" x2="${width - padding}" y2="${y}" class="chart-ref-line" />`;
      })
      .join('');
    const circles = values.map((value, index) => {
      const date = formatDate(exam.results[index].data_coleta);
      return `<circle cx="${xFor(index)}" cy="${yFor(value)}" r="3.5"><title>${date}: ${value} ${escapeHtml(exam.unidade)}</title></circle>`;
    }).join('');

    return `
      <svg class="evolution-chart" viewBox="0 0 ${width} ${height}" role="img" aria-label="Evolução de ${escapeHtml(exam.nome)}">
        <rect x="0" y="0" width="${width}" height="${height}" rx="8" />
        ${refLines}
        <polyline points="${points}" />
        ${circles}
      </svg>
    `;
  };

  const renderExamEvolution = () => {
    const container = $('#exam-evolution-list');
    if (!container) return;
    const search = (evolutionSearch?.value || '').trim().toLowerCase();
    const rows = app.store.listExamEvolution().filter((exam) => {
      const text = `${exam.nome} ${exam.unidade} ${exam.frequencia}`.toLowerCase();
      return !search || text.includes(search);
    });

    container.innerHTML = '';
    if (!rows.length) {
      container.innerHTML = '<div class="empty-state">Nenhuma evolução disponível. Cadastre um exame e lance pelo menos um resultado numérico.</div>';
      return;
    }

    rows.forEach((exam) => {
      const latest = exam.latest;
      const latestValue = latest ? `${latest.valor} ${exam.unidade || ''}`.trim() : '—';
      const latestDate = latest ? formatDate(latest.data_coleta) : '—';
      const status = getReferenceStatus({
        ...exam,
        valor: latest?.valor,
        valor_numero: latest?.valor_numero,
      });
      const article = document.createElement('article');
      article.className = `evolution-card is-${status.key}`;
      article.innerHTML = `
        <div class="evolution-card-header">
          <div>
            <h3>${escapeHtml(exam.nome)}</h3>
            <p class="muted">${exam.results.length} resultados • Último em ${latestDate}</p>
          </div>
          <div class="evolution-latest">
            ${referenceBadge(status)}
            <strong>${escapeHtml(latestValue)}</strong>
          </div>
        </div>
        ${buildSparkline(exam)}
        <div class="evolution-meta">
          <span>Referência: ${escapeHtml(exam.referencia_min || '—')} / ${escapeHtml(exam.referencia_max || '—')} ${escapeHtml(exam.unidade || '')}</span>
          <span>Menor: ${Math.min(...exam.results.map((result) => result.valor_numero))} ${escapeHtml(exam.unidade || '')}</span>
          <span>Maior: ${Math.max(...exam.results.map((result) => result.valor_numero))} ${escapeHtml(exam.unidade || '')}</span>
        </div>
      `;
      container.appendChild(article);
    });
  };

  const renderAlerts = () => {
    const container = $('#exam-alert-list');
    if (!container) return;
    const alerts = getLatestExamAlerts();

    container.innerHTML = '';
    if (!alerts.length) {
      container.innerHTML = '<div class="empty-state">Nenhum exame com resultado recente fora da referência.</div>';
      return;
    }

    alerts.forEach((exam) => {
      const latest = exam.latest;
      const value = `${latest.valor} ${exam.unidade || ''}`.trim();
      const article = document.createElement('article');
      article.className = `alert-card is-${exam.status.key}`;
      article.innerHTML = `
        <div class="alert-card-header">
          <div>
            <h3>${escapeHtml(exam.nome)}</h3>
            <p class="muted">Último resultado em ${formatDate(latest.data_coleta)}</p>
          </div>
          ${referenceBadge(exam.status)}
        </div>
        <p><strong>Resultado:</strong> ${escapeHtml(value || '—')}</p>
        <p><strong>Referência:</strong> ${escapeHtml(exam.referencia_min || '—')} / ${escapeHtml(exam.referencia_max || '—')} ${escapeHtml(exam.unidade || '')}</p>
        <p><strong>Local:</strong> ${escapeHtml(latest.laboratorio || '—')}</p>
        <p class="alert-advice">Tenha atenção a este resultado e considere repetir o exame em breve.</p>
      `;
      container.appendChild(article);
    });
  };

  const renderCompactAlerts = (container, alerts) => {
    if (!container) return;
    container.innerHTML = '';
    if (!alerts.length) {
      container.innerHTML = '<div class="empty-state">Nenhum exame alterado no último resultado.</div>';
      return;
    }

    alerts.forEach((exam) => {
      const latest = exam.latest;
      const value = `${latest.valor} ${exam.unidade || ''}`.trim();
      const article = document.createElement('article');
      article.className = `compact-item is-${exam.status.key}`;
      article.innerHTML = `
        <div>
          <h4>${escapeHtml(exam.nome)}</h4>
          <p>${formatDate(latest.data_coleta)} • ${escapeHtml(value || '—')}</p>
          <p>Referência: ${escapeHtml(exam.referencia_min || '—')} / ${escapeHtml(exam.referencia_max || '—')} ${escapeHtml(exam.unidade || '')}</p>
        </div>
        ${referenceBadge(exam.status)}
      `;
      container.appendChild(article);
    });
  };

  const renderMedicationCompactList = (container) => {
    if (!container) return;
    const rows = app.store.listMedications().filter((item) => item.status !== 'Suspenso');
    container.innerHTML = '';
    if (!rows.length) {
      container.innerHTML = '<div class="empty-state">Nenhum medicamento em uso cadastrado.</div>';
      return;
    }
    rows.forEach((item) => {
      const article = document.createElement('article');
      article.className = 'compact-item';
      article.innerHTML = `
        <div>
          <h4>${escapeHtml(item.nome || 'Medicamento')}</h4>
          <p>${escapeHtml(item.dosagem || '—')} • ${escapeHtml(item.intervalo || 'Sem intervalo')}</p>
          <p>${escapeHtml(item.laboratorio || '')}</p>
        </div>
      `;
      container.appendChild(article);
    });
  };

  const renderDoctorMode = () => {
    const alerts = getLatestExamAlerts();
    renderCompactAlerts($('#doctor-alerts'), alerts);
    renderMedicationCompactList($('#doctor-medications'));

    const consultationsContainer = $('#doctor-consultations');
    if (consultationsContainer) {
      const consultations = app.store.listConsultations().slice(0, 3);
      consultationsContainer.innerHTML = '';
      if (!consultations.length) {
        consultationsContainer.innerHTML = '<div class="empty-state">Nenhuma consulta recente cadastrada.</div>';
      } else {
        consultations.forEach((item) => {
          const article = document.createElement('article');
          article.className = 'compact-item';
          article.innerHTML = `
            <div>
              <h4>${escapeHtml(item.medico || 'Consulta')}</h4>
              <p>${formatDate(item.data_consulta)} • ${escapeHtml(item.status || '—')}</p>
              <p>${escapeHtml(item.motivo || item.diagnostico || 'Sem observações.')}</p>
            </div>
          `;
          consultationsContainer.appendChild(article);
        });
      }
    }

    const evolutionContainer = $('#doctor-evolution');
    if (evolutionContainer) {
      const alertIds = new Set(alerts.map((item) => item.id));
      const rows = [
        ...alerts,
        ...app.store.listExamEvolution().filter((item) => !alertIds.has(item.id)),
      ].slice(0, 3);
      evolutionContainer.innerHTML = '';
      if (!rows.length) {
        evolutionContainer.innerHTML = '<div class="empty-state">Nenhuma evolução disponível.</div>';
      } else {
        rows.forEach((exam) => {
          const latest = exam.latest;
          const status = getReferenceStatus({
            ...exam,
            valor: latest?.valor,
            valor_numero: latest?.valor_numero,
          });
          const article = document.createElement('article');
          article.className = `evolution-card is-${status.key}`;
          article.innerHTML = `
            <div class="evolution-card-header">
              <div>
                <h3>${escapeHtml(exam.nome)}</h3>
                <p class="muted">${exam.results.length} resultados • Último em ${formatDate(latest?.data_coleta)}</p>
              </div>
              <div class="evolution-latest">
                ${referenceBadge(status)}
                <strong>${escapeHtml(`${latest?.valor || '—'} ${exam.unidade || ''}`.trim())}</strong>
              </div>
            </div>
            ${buildSparkline(exam)}
          `;
          evolutionContainer.appendChild(article);
        });
      }
    }
  };

  const renderPreConsultation = () => {
    const alerts = getLatestExamAlerts();
    renderCompactAlerts($('#preconsult-alerts'), alerts);
    renderMedicationCompactList($('#preconsult-medications'));
    fillForm(preConsultationForm, app.store.getPreConsultation?.());
  };

  examSelect?.addEventListener('change', refreshExamSelect);
  resultExamSearch?.addEventListener('input', refreshExamSelect);

  $('#exam-definition-save')?.addEventListener('click', async () => {
    if (!examDefinitionForm.checkValidity()) {
      examDefinitionForm.reportValidity();
      return;
    }
    const payload = getFormData(examDefinitionForm);
    payload.id = state.examEditId;
    await app.store.saveExamDefinition(payload);
    state.examEditId = '';
    examDefinitionForm.reset();
    showMessage($('#exam-feedback'), 'Exame salvo.');
    renderExamDefinitions();
    renderExamResults();
    renderExamEvolution();
    renderAlerts();
    renderDoctorMode();
    renderPreConsultation();
    renderDashboard();
    activateExamPanel('definitions-list');
  });

  $('#exam-result-save')?.addEventListener('click', async () => {
    if (!examResultForm.checkValidity()) {
      examResultForm.reportValidity();
      return;
    }
    const payload = getFormData(examResultForm);
    const files = $('#result-attachments').files;
    try {
      await app.store.saveExamResult(payload, files);
      examResultForm.reset();
      refreshExamSelect();
      showMessage($('#exam-result-feedback'), 'Resultado e anexos salvos.');
      renderExamResults();
      renderExamEvolution();
      renderAlerts();
      renderDoctorMode();
      renderPreConsultation();
      renderDashboard();
      activateExamPanel('evolution');
    } catch (error) {
      showMessage($('#exam-result-feedback'), error instanceof Error ? error.message : 'Falha ao salvar resultado.', 'error');
    }
  });

  $('#exam-search')?.addEventListener('input', renderExamDefinitions);
  $('#result-search')?.addEventListener('input', renderExamResults);
  evolutionSearch?.addEventListener('input', renderExamEvolution);

  const initialScreen = new URLSearchParams(window.location.search).get('screen') || 'dashboard';

  renderDashboard();
  renderConsultations();
  renderMedications();
  renderExamDefinitions();
  renderExamResults();
  renderExamEvolution();
  renderAlerts();
  renderDoctorMode();
  renderPreConsultation();
  activateScreen(initialScreen);
  activateExamPanel(state.examPanel);
});
