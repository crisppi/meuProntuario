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

  const setMenuOpen = (isOpen) => {
    if (!appNav || !menuToggle) return;
    appNav.hidden = !isOpen;
    appNav.classList.toggle('is-open', isOpen);
    menuToggle.classList.toggle('is-open', isOpen);
    menuToggle.setAttribute('aria-expanded', String(isOpen));
  };

  const activateScreen = (name) => {
    screens.forEach((screen) => {
      screen.hidden = screen.dataset.screen !== name;
    });
    screenButtons.forEach((button) => {
      button.classList.toggle('is-active', button.dataset.screenTarget === name);
    });
    setMenuOpen(false);
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
  };

  screenButtons.forEach((button) => {
    button.addEventListener('click', () => activateScreen(button.dataset.screenTarget));
  });

  menuToggle?.addEventListener('click', () => {
    setMenuOpen(appNav?.hidden !== false);
  });

  document.addEventListener('click', (event) => {
    if (!appNav || appNav.hidden) return;
    if (appNav.contains(event.target) || menuToggle?.contains(event.target)) return;
    setMenuOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setMenuOpen(false);
    }
  });

  examPanelButtons.forEach((button) => {
    button.addEventListener('click', () => activateExamPanel(button.dataset.examPanelTarget));
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
  };

  const personalForm = $('#personal-form');
  const profileForm = $('#profile-form');
  await app.store.init();
  fillForm(personalForm, app.store.getPersonal());
  fillForm(profileForm, app.store.getProfile());

  $('#personal-save')?.addEventListener('click', async () => {
    if (!personalForm.checkValidity()) {
      personalForm.reportValidity();
      return;
    }
    await app.store.savePersonal(getFormData(personalForm));
    showMessage($('#personal-feedback'), 'Dados pessoais salvos no dispositivo.');
  });

  $('#profile-save')?.addEventListener('click', async () => {
    await app.store.saveProfile(getFormData(profileForm));
    showMessage($('#profile-feedback'), 'Perfil salvo no dispositivo.');
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
    showMessage($('#consultation-feedback'), 'Consulta salva localmente.');
    renderConsultations();
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
    showMessage($('#medication-feedback'), 'Medicamento salvo localmente.');
    renderMedications();
    renderDashboard();
  });

  $('#medication-search')?.addEventListener('input', renderMedications);

  const examDefinitionForm = $('#exam-definition-form');
  const examResultForm = $('#exam-result-form');
  const examSelect = $('#result-exam-id');
  const examValueField = $('#result-value-field');

  const refreshExamSelect = () => {
    const currentValue = examSelect.value;
    const rows = app.store.listExamDefinitions();
    examSelect.innerHTML = '<option value="">Selecione um exame</option>';
    rows.forEach((item) => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = `${item.nome} (${item.tipo || 'geral'})`;
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
        <td>${item.laboratorio || '—'}</td>
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
        const article = document.createElement('article');
        article.className = 'local-card';
        const attachmentHtml = item.attachments.length
          ? `<div class="attachment-inline-list">${item.attachments.map((attachment) => `
              <button type="button" class="button ghost" data-open-attachment="${attachment.id}">
                ${attachment.nome_original}
              </button>
            `).join('')}</div>`
          : '<p class="muted">Sem anexos.</p>';
        article.innerHTML = `
          <h3>${item.exame_nome}</h3>
          <p class="muted">${item.exame_tipo || 'geral'} • ${formatDate(item.data_coleta)}</p>
          <p><strong>Valor:</strong> ${item.valor || '—'} ${item.unidade || ''}</p>
          <p><strong>Laboratório:</strong> ${item.laboratorio || '—'}</p>
          <p><strong>Observações:</strong> ${item.observacoes || '—'}</p>
          ${attachmentHtml}
        `;
        container.appendChild(article);
      });
    }

    attachmentsContainer.innerHTML = '';
    if (!attachments.length) {
      attachmentsContainer.innerHTML = '<div class="empty-state">Nenhum anexo salvo no dispositivo.</div>';
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

  examSelect?.addEventListener('change', refreshExamSelect);

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
    showMessage($('#exam-feedback'), 'Exame salvo localmente.');
    renderExamDefinitions();
    renderExamResults();
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
      showMessage($('#exam-result-feedback'), 'Resultado e anexos salvos no dispositivo.');
      renderExamResults();
      renderDashboard();
      activateExamPanel('results-list');
    } catch (error) {
      showMessage($('#exam-result-feedback'), error instanceof Error ? error.message : 'Falha ao salvar resultado.', 'error');
    }
  });

  $('#exam-search')?.addEventListener('input', renderExamDefinitions);
  $('#result-search')?.addEventListener('input', renderExamResults);

  const initialScreen = new URLSearchParams(window.location.search).get('screen') || 'dashboard';

  renderDashboard();
  renderConsultations();
  renderMedications();
  renderExamDefinitions();
  renderExamResults();
  activateScreen(initialScreen);
  activateExamPanel(state.examPanel);
});
