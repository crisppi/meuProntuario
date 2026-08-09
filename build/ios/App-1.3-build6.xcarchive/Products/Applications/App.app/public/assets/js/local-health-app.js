(function () {
  const LEGACY_STORAGE_KEY = 'organizador_saude_local_v1';
  const SESSION_KEY = 'organizador_saude_session_v1';
  const DB_NAME = 'saude_em_dia';
  const DB_VERSION = 1;

  const defaultState = () => ({
    version: DB_VERSION,
    personal: {},
    profile: {},
    preConsultation: {},
    exams: [],
    examResults: [],
    attachments: [],
    consultations: [],
    medications: [],
    security: {
      localOnly: true,
      cloudSync: false,
      privacyAcceptedAt: null,
      lastLoginAt: null,
    },
    updatedAt: new Date().toISOString(),
  });

  let state = defaultState();

  const clone = (value) => JSON.parse(JSON.stringify(value));

  const generateId = () => {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }
    return `id-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  };

  const slugify = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');

  const nowIso = () => new Date().toISOString();

  const loadLegacyState = () => {
    try {
      const raw = localStorage.getItem(LEGACY_STORAGE_KEY);
      if (!raw) {
        return defaultState();
      }
      const parsed = JSON.parse(raw);
      return {
        ...defaultState(),
        ...parsed,
        security: {
          ...defaultState().security,
          ...(parsed.security || {}),
        },
      };
    } catch (error) {
      return defaultState();
    }
  };

  const saveLegacyState = () => {
    state.updatedAt = nowIso();
    localStorage.setItem(LEGACY_STORAGE_KEY, JSON.stringify(state));
  };

  const setStateFromLegacyIfNeeded = () => {
    state = loadLegacyState();
  };

  const readFileAsDataUrl = (file) => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ''));
    reader.onerror = () => reject(new Error('Não foi possível ler o arquivo selecionado.'));
    reader.readAsDataURL(file);
  });

  const toBase64Payload = (dataUrl) => {
    const commaIndex = dataUrl.indexOf(',');
    return commaIndex >= 0 ? dataUrl.slice(commaIndex + 1) : dataUrl;
  };

  const extensionFromFile = (fileName, mimeType) => {
    const rawExt = String(fileName || '').split('.').pop() || '';
    const cleaned = rawExt.toLowerCase().replace(/[^a-z0-9]/g, '');
    if (cleaned) {
      return cleaned;
    }
    const map = {
      'image/jpeg': 'jpg',
      'image/png': 'png',
      'application/pdf': 'pdf',
    };
    return map[mimeType] || 'bin';
  };

  const getFilesystem = () => window.Capacitor?.Plugins?.Filesystem || null;

  const storeAttachmentFile = async (file) => {
    const dataUrl = await readFileAsDataUrl(file);
    const filesystem = getFilesystem();
    const mimeType = file.type || 'application/octet-stream';
    const fileId = generateId();
    const ext = extensionFromFile(file.name, mimeType);

    if (filesystem && typeof filesystem.writeFile === 'function') {
      const relativePath = `saude-em-dia/attachments/${fileId}.${ext}`;
      await filesystem.writeFile({
        path: relativePath,
        data: toBase64Payload(dataUrl),
        directory: 'DATA',
        recursive: true,
      });

      let uri = '';
      if (typeof filesystem.getUri === 'function') {
        const result = await filesystem.getUri({
          path: relativePath,
          directory: 'DATA',
        });
        uri = result?.uri || '';
      }

      return {
        storage: 'filesystem',
        path: relativePath,
        uri,
        data_url: '',
      };
    }

    return {
      storage: 'data_url',
      path: '',
      uri: '',
      data_url: dataUrl,
    };
  };

  const getAttachmentUrl = (attachment) => {
    if (!attachment) {
      return '';
    }
    if (attachment.storage === 'filesystem') {
      const uri = attachment.uri || attachment.path || '';
      if (!uri) {
        return '';
      }
      const convertFileSrc = window.Capacitor?.convertFileSrc;
      return typeof convertFileSrc === 'function' ? convertFileSrc(uri) : uri;
    }
    return attachment.data_url || '';
  };

  const removeAttachmentFile = async (attachment) => {
    if (!attachment || attachment.storage !== 'filesystem' || !attachment.path) {
      return;
    }
    const filesystem = getFilesystem();
    if (!filesystem || typeof filesystem.deleteFile !== 'function') {
      return;
    }
    try {
      await filesystem.deleteFile({
        path: attachment.path,
        directory: 'DATA',
      });
    } catch (error) {
      // O registro deve poder ser removido mesmo se o arquivo já não existir.
    }
  };

  const sortByNewest = (items, field = 'updated_at') => [...items].sort((a, b) => {
    const aTime = new Date(a[field] || a.created_at || 0).getTime();
    const bTime = new Date(b[field] || b.created_at || 0).getTime();
    return bTime - aTime;
  });

  const getSession = () => {
    try {
      const raw = localStorage.getItem(SESSION_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  };

  const setSession = (payload) => {
    const user = payload.user || {};
    const session = {
      token: payload.token || '',
      refresh_token: payload.refresh_token || '',
      user: {
        id: user.id || user.user_id || '',
        name: user.name || user.nome || '',
        email: user.email || '',
      },
      logged_at: nowIso(),
    };
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
    state.security.lastLoginAt = session.logged_at;
    if (!db.native) {
      saveLegacyState();
    }
  };

  const clearSession = () => {
    localStorage.removeItem(SESSION_KEY);
  };

  const auth = {
    ensureLocalSession() {
      if (!getSession()) {
        setSession({
          token: `local-${Date.now()}`,
          user: {
            id: 'local-device',
            name: 'Usuário local',
            email: '',
          },
        });
      }
      return this.owner();
    },

    isAuthenticated() {
      return !!getSession()?.token;
    },

    currentSession() {
      return getSession();
    },

    owner() {
      return getSession()?.user || null;
    },

    logout() {
      clearSession();
    },
  };

  const db = {
    native: false,
    sqlite: null,
    initialized: false,
    initPromise: null,

    schema() {
      return `
        CREATE TABLE IF NOT EXISTS app_meta (
          key TEXT PRIMARY KEY NOT NULL,
          value TEXT NOT NULL,
          updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS consultations (
          id TEXT PRIMARY KEY NOT NULL,
          data_consulta TEXT,
          hora_inicio TEXT,
          hora_fim TEXT,
          medico TEXT,
          motivo TEXT,
          diagnostico TEXT,
          status TEXT,
          created_at TEXT NOT NULL,
          updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS medications (
          id TEXT PRIMARY KEY NOT NULL,
          nome TEXT NOT NULL,
          laboratorio TEXT,
          dosagem TEXT,
          intervalo TEXT,
          status TEXT,
          created_at TEXT NOT NULL,
          updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS exams (
          id TEXT PRIMARY KEY NOT NULL,
          nome TEXT NOT NULL,
          tipo TEXT,
          unidade TEXT,
          referencia_min TEXT,
          referencia_max TEXT,
          frequencia TEXT,
          laboratorio TEXT,
          observacoes TEXT,
          data_realizacao TEXT,
          slug TEXT,
          created_at TEXT NOT NULL,
          updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS exam_results (
          id TEXT PRIMARY KEY NOT NULL,
          exame_id TEXT NOT NULL,
          data_coleta TEXT,
          valor TEXT,
          laboratorio TEXT,
          observacoes TEXT,
          created_at TEXT NOT NULL,
          updated_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS attachments (
          id TEXT PRIMARY KEY NOT NULL,
          exame_id TEXT NOT NULL,
          resultado_id TEXT NOT NULL,
          nome_original TEXT NOT NULL,
          mime_type TEXT,
          tamanho INTEGER,
          storage TEXT,
          path TEXT,
          uri TEXT,
          data_url TEXT,
          created_at TEXT NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_exam_results_exame_id ON exam_results(exame_id);
        CREATE INDEX IF NOT EXISTS idx_attachments_resultado_id ON attachments(resultado_id);
        CREATE INDEX IF NOT EXISTS idx_attachments_exame_id ON attachments(exame_id);
      `;
    },

    async init() {
      if (this.initialized) {
        return;
      }
      if (this.initPromise) {
        return this.initPromise;
      }

      this.initPromise = (async () => {
        const platform = window.Capacitor?.getPlatform?.() || 'web';
        const sqlite = platform !== 'web' && typeof window.Capacitor?.registerPlugin === 'function'
          ? window.Capacitor.registerPlugin('CapacitorSQLite')
          : null;
        this.sqlite = sqlite;
        this.native = !!sqlite;

        if (!sqlite) {
          setStateFromLegacyIfNeeded();
          this.initialized = true;
          return;
        }

        try {
          const consistency = await sqlite.checkConnectionsConsistency();
          if (consistency?.result === false) {
            try {
              await sqlite.closeConnection({ database: DB_NAME, readonly: false });
            } catch (error) {
              // ignore stale connection cleanup
            }
          }
        } catch (error) {
          // optional on some platforms
        }

        try {
          const isConn = await sqlite.isConnection({ database: DB_NAME, readonly: false });
          if (!isConn?.result) {
            await sqlite.createConnection({
              database: DB_NAME,
              encrypted: false,
              mode: 'no-encryption',
              version: DB_VERSION,
              readonly: false,
            });
          }
        } catch (error) {
          const message = String(error?.message || error || '');
          if (!message.toLowerCase().includes('already exists')) {
            throw error;
          }
        }

        try {
          await sqlite.open({ database: DB_NAME, readonly: false });
        } catch (error) {
          const message = String(error?.message || error || '');
          if (!message.toLowerCase().includes('already open')) {
            throw error;
          }
        }

        await sqlite.execute({
          database: DB_NAME,
          statements: this.schema(),
          transaction: true,
          readonly: false,
        });

        await this.migrateLegacyIfNeeded();
        await this.loadIntoMemory();
        this.initialized = true;
      })();

      return this.initPromise;
    },

    async migrateLegacyIfNeeded() {
      const legacy = loadLegacyState();
      const alreadyMigrated = await this.readMetaValue('migration_v1_done');
      const hasLegacyData = legacy.exams.length || legacy.examResults.length || legacy.attachments.length ||
        legacy.consultations.length || legacy.medications.length ||
        Object.keys(legacy.personal || {}).length || Object.keys(legacy.profile || {}).length ||
        Object.keys(legacy.preConsultation || {}).length;

      if (alreadyMigrated === '1' || !hasLegacyData) {
        await this.writeMetaValue('migration_v1_done', '1');
        return;
      }

      if (Object.keys(legacy.personal || {}).length) {
        await this.writeJsonMeta('personal', legacy.personal);
      }
      if (Object.keys(legacy.profile || {}).length) {
        await this.writeJsonMeta('profile', legacy.profile);
      }
      if (Object.keys(legacy.preConsultation || {}).length) {
        await this.writeJsonMeta('preConsultation', legacy.preConsultation);
      }
      if (legacy.security) {
        await this.writeJsonMeta('security', legacy.security);
      }

      for (const item of legacy.consultations || []) {
        await this.run(
          `INSERT INTO consultations (
            id, data_consulta, hora_inicio, hora_fim, medico, motivo, diagnostico, status, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            data_consulta=excluded.data_consulta,
            hora_inicio=excluded.hora_inicio,
            hora_fim=excluded.hora_fim,
            medico=excluded.medico,
            motivo=excluded.motivo,
            diagnostico=excluded.diagnostico,
            status=excluded.status,
            updated_at=excluded.updated_at`,
          [
            item.id,
            item.data_consulta || '',
            item.hora_inicio || '',
            item.hora_fim || '',
            item.medico || '',
            item.motivo || '',
            item.diagnostico || '',
            item.status || 'agendada',
            item.created_at || nowIso(),
            item.updated_at || nowIso(),
          ]
        );
      }

      for (const item of legacy.medications || []) {
        await this.run(
          `INSERT INTO medications (
            id, nome, laboratorio, dosagem, intervalo, status, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            nome=excluded.nome,
            laboratorio=excluded.laboratorio,
            dosagem=excluded.dosagem,
            intervalo=excluded.intervalo,
            status=excluded.status,
            updated_at=excluded.updated_at`,
          [
            item.id,
            item.nome || '',
            item.laboratorio || '',
            item.dosagem || '',
            item.intervalo || '',
            item.status || 'Em Uso',
            item.created_at || nowIso(),
            item.updated_at || nowIso(),
          ]
        );
      }

      for (const item of legacy.exams || []) {
        await this.run(
          `INSERT INTO exams (
            id, nome, tipo, unidade, referencia_min, referencia_max, frequencia, laboratorio, observacoes, data_realizacao, slug, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            nome=excluded.nome,
            tipo=excluded.tipo,
            unidade=excluded.unidade,
            referencia_min=excluded.referencia_min,
            referencia_max=excluded.referencia_max,
            frequencia=excluded.frequencia,
            laboratorio=excluded.laboratorio,
            observacoes=excluded.observacoes,
            data_realizacao=excluded.data_realizacao,
            slug=excluded.slug,
            updated_at=excluded.updated_at`,
          [
            item.id,
            item.nome || '',
            item.tipo || '',
            item.unidade || '',
            item.referencia_min || '',
            item.referencia_max || '',
            item.frequencia || '',
            item.laboratorio || '',
            item.observacoes || '',
            item.data_realizacao || '',
            item.slug || slugify(item.nome || ''),
            item.created_at || nowIso(),
            item.updated_at || nowIso(),
          ]
        );
      }

      for (const item of legacy.examResults || []) {
        await this.run(
          `INSERT INTO exam_results (
            id, exame_id, data_coleta, valor, laboratorio, observacoes, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            exame_id=excluded.exame_id,
            data_coleta=excluded.data_coleta,
            valor=excluded.valor,
            laboratorio=excluded.laboratorio,
            observacoes=excluded.observacoes,
            updated_at=excluded.updated_at`,
          [
            item.id,
            item.exame_id || '',
            item.data_coleta || '',
            item.valor || '',
            item.laboratorio || '',
            item.observacoes || '',
            item.created_at || nowIso(),
            item.updated_at || nowIso(),
          ]
        );
      }

      for (const item of legacy.attachments || []) {
        await this.run(
          `INSERT INTO attachments (
            id, exame_id, resultado_id, nome_original, mime_type, tamanho, storage, path, uri, data_url, created_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            exame_id=excluded.exame_id,
            resultado_id=excluded.resultado_id,
            nome_original=excluded.nome_original,
            mime_type=excluded.mime_type,
            tamanho=excluded.tamanho,
            storage=excluded.storage,
            path=excluded.path,
            uri=excluded.uri,
            data_url=excluded.data_url`,
          [
            item.id,
            item.exame_id || '',
            item.resultado_id || '',
            item.nome_original || '',
            item.mime_type || '',
            Number(item.tamanho || 0),
            item.storage || '',
            item.path || '',
            item.uri || '',
            item.data_url || '',
            item.created_at || nowIso(),
          ]
        );
      }

      await this.writeMetaValue('migration_v1_done', '1');
      localStorage.removeItem(LEGACY_STORAGE_KEY);
    },

    async readMetaValue(key) {
      const result = await this.query('SELECT value FROM app_meta WHERE key = ? LIMIT 1', [key]);
      return result[0]?.value || null;
    },

    async writeMetaValue(key, value) {
      await this.run(
        `INSERT INTO app_meta (key, value, updated_at)
         VALUES (?, ?, ?)
         ON CONFLICT(key) DO UPDATE SET
           value=excluded.value,
           updated_at=excluded.updated_at`,
        [key, String(value), nowIso()]
      );
    },

    async writeJsonMeta(key, payload) {
      await this.writeMetaValue(key, JSON.stringify(payload || {}));
    },

    async readJsonMeta(key) {
      const value = await this.readMetaValue(key);
      if (!value) {
        return {};
      }
      try {
        return JSON.parse(value);
      } catch (error) {
        return {};
      }
    },

    async loadIntoMemory() {
      state = {
        version: DB_VERSION,
        personal: await this.readJsonMeta('personal'),
        profile: await this.readJsonMeta('profile'),
        preConsultation: await this.readJsonMeta('preConsultation'),
        exams: sortByNewest(await this.query('SELECT * FROM exams ORDER BY nome COLLATE NOCASE ASC'), 'nome'),
        examResults: sortByNewest(await this.query('SELECT * FROM exam_results ORDER BY updated_at DESC')),
        attachments: sortByNewest(await this.query('SELECT * FROM attachments ORDER BY created_at DESC'), 'created_at'),
        consultations: sortByNewest(await this.query('SELECT * FROM consultations ORDER BY updated_at DESC')),
        medications: sortByNewest(await this.query('SELECT * FROM medications ORDER BY updated_at DESC')),
        security: {
          ...defaultState().security,
          ...(await this.readJsonMeta('security')),
        },
        updatedAt: nowIso(),
      };
    },

    async query(statement, values = []) {
      if (!this.native || !this.sqlite) {
        return [];
      }
      const result = await this.sqlite.query({
        database: DB_NAME,
        statement,
        values,
        readonly: false,
      });
      return Array.isArray(result?.values) ? result.values : [];
    },

    async run(statement, values = []) {
      if (!this.native || !this.sqlite) {
        return null;
      }
      return this.sqlite.run({
        database: DB_NAME,
        statement,
        values,
        transaction: true,
        readonly: false,
      });
    },
  };

  const store = {
    async init() {
      await db.init();
    },

    usingSQLite() {
      return db.native;
    },

    stats() {
      return {
        exams: state.exams.length,
        examResults: state.examResults.length,
        consultations: state.consultations.length,
        medications: state.medications.length,
        attachments: state.attachments.length,
      };
    },

    getPersonal() {
      return clone(state.personal || {});
    },

    async savePersonal(payload) {
      state.personal = {
        nome: payload.nome || '',
        email: payload.email || '',
        telefone: payload.telefone || '',
        data_nascimento: payload.data_nascimento || '',
        updated_at: nowIso(),
      };
      if (db.native) {
        await db.writeJsonMeta('personal', state.personal);
      } else {
        saveLegacyState();
      }
      return this.getPersonal();
    },

    getProfile() {
      return clone(state.profile || {});
    },

    async saveProfile(payload) {
      state.profile = {
        ...state.profile,
        ...payload,
        updated_at: nowIso(),
      };
      if (db.native) {
        await db.writeJsonMeta('profile', state.profile);
      } else {
        saveLegacyState();
      }
      return this.getProfile();
    },

    getPreConsultation() {
      return clone(state.preConsultation || {});
    },

    async savePreConsultation(payload) {
      state.preConsultation = {
        observacoes: payload.observacoes || '',
        perguntas: payload.perguntas || '',
        updated_at: nowIso(),
      };
      if (db.native) {
        await db.writeJsonMeta('preConsultation', state.preConsultation);
      } else {
        saveLegacyState();
      }
      return this.getPreConsultation();
    },

    listConsultations() {
      return sortByNewest(state.consultations);
    },

    async saveConsultation(payload) {
      const id = payload.id || generateId();
      const now = nowIso();
      const entry = {
        id,
        data_consulta: payload.data_consulta || '',
        hora_inicio: payload.hora_inicio || '',
        hora_fim: payload.hora_fim || '',
        medico: payload.medico || '',
        motivo: payload.motivo || '',
        diagnostico: payload.diagnostico || '',
        status: payload.status || 'agendada',
        created_at: payload.created_at || now,
        updated_at: now,
      };
      state.consultations = [...state.consultations.filter((item) => item.id !== id), entry];
      if (db.native) {
        await db.run(
          `INSERT INTO consultations (
            id, data_consulta, hora_inicio, hora_fim, medico, motivo, diagnostico, status, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            data_consulta=excluded.data_consulta,
            hora_inicio=excluded.hora_inicio,
            hora_fim=excluded.hora_fim,
            medico=excluded.medico,
            motivo=excluded.motivo,
            diagnostico=excluded.diagnostico,
            status=excluded.status,
            updated_at=excluded.updated_at`,
          [entry.id, entry.data_consulta, entry.hora_inicio, entry.hora_fim, entry.medico, entry.motivo, entry.diagnostico, entry.status, entry.created_at, entry.updated_at]
        );
      } else {
        saveLegacyState();
      }
      return clone(entry);
    },

    getConsultation(id) {
      return clone(state.consultations.find((item) => item.id === id) || null);
    },

    async deleteConsultation(id) {
      if (!state.consultations.some((item) => item.id === id)) return false;
      state.consultations = state.consultations.filter((item) => item.id !== id);
      if (db.native) await db.run('DELETE FROM consultations WHERE id = ?', [id]);
      else saveLegacyState();
      return true;
    },

    listMedications() {
      return sortByNewest(state.medications);
    },

    async saveMedication(payload) {
      const id = payload.id || generateId();
      const now = nowIso();
      const entry = {
        id,
        nome: payload.nome || '',
        laboratorio: payload.laboratorio || '',
        dosagem: payload.dosagem || '',
        intervalo: payload.intervalo || '',
        status: payload.status || 'Em Uso',
        created_at: payload.created_at || now,
        updated_at: now,
      };
      state.medications = [...state.medications.filter((item) => item.id !== id), entry];
      if (db.native) {
        await db.run(
          `INSERT INTO medications (
            id, nome, laboratorio, dosagem, intervalo, status, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            nome=excluded.nome,
            laboratorio=excluded.laboratorio,
            dosagem=excluded.dosagem,
            intervalo=excluded.intervalo,
            status=excluded.status,
            updated_at=excluded.updated_at`,
          [entry.id, entry.nome, entry.laboratorio, entry.dosagem, entry.intervalo, entry.status, entry.created_at, entry.updated_at]
        );
      } else {
        saveLegacyState();
      }
      return clone(entry);
    },

    getMedication(id) {
      return clone(state.medications.find((item) => item.id === id) || null);
    },

    async deleteMedication(id) {
      if (!state.medications.some((item) => item.id === id)) return false;
      state.medications = state.medications.filter((item) => item.id !== id);
      if (db.native) await db.run('DELETE FROM medications WHERE id = ?', [id]);
      else saveLegacyState();
      return true;
    },

    listExamDefinitions() {
      return [...state.exams].sort((a, b) => String(a.nome).localeCompare(String(b.nome), 'pt-BR'));
    },

    getExamDefinition(id) {
      return clone(state.exams.find((item) => item.id === id) || null);
    },

    getExamDeletionSummary(id) {
      return {
        results: state.examResults.filter((item) => item.exame_id === id).length,
        attachments: state.attachments.filter((item) => item.exame_id === id).length,
      };
    },

    async deleteExamDefinition(id) {
      if (!state.exams.some((item) => item.id === id)) return false;
      const attachments = state.attachments.filter((item) => item.exame_id === id);
      await Promise.all(attachments.map(removeAttachmentFile));
      state.attachments = state.attachments.filter((item) => item.exame_id !== id);
      state.examResults = state.examResults.filter((item) => item.exame_id !== id);
      state.exams = state.exams.filter((item) => item.id !== id);
      if (db.native) {
        await db.run('DELETE FROM attachments WHERE exame_id = ?', [id]);
        await db.run('DELETE FROM exam_results WHERE exame_id = ?', [id]);
        await db.run('DELETE FROM exams WHERE id = ?', [id]);
      } else saveLegacyState();
      return true;
    },

    async saveExamDefinition(payload) {
      const id = payload.id || generateId();
      const now = nowIso();
      const entry = {
        id,
        nome: payload.nome || payload.nome_exame || '',
        tipo: payload.tipo || payload.tipo_exame || 'laboratorial',
        unidade: payload.unidade || '',
        referencia_min: payload.referencia_min || '',
        referencia_max: payload.referencia_max || '',
        frequencia: payload.frequencia || '',
        laboratorio: payload.laboratorio || '',
        observacoes: payload.observacoes || '',
        data_realizacao: payload.data_realizacao || '',
        slug: slugify(payload.nome || payload.nome_exame || ''),
        created_at: payload.created_at || now,
        updated_at: now,
      };
      if (!entry.nome) {
        throw new Error('Nome do exame e obrigatorio.');
      }
      state.exams = [...state.exams.filter((item) => item.id !== id), entry];
      if (db.native) {
        await db.run(
          `INSERT INTO exams (
            id, nome, tipo, unidade, referencia_min, referencia_max, frequencia, laboratorio, observacoes, data_realizacao, slug, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            nome=excluded.nome,
            tipo=excluded.tipo,
            unidade=excluded.unidade,
            referencia_min=excluded.referencia_min,
            referencia_max=excluded.referencia_max,
            frequencia=excluded.frequencia,
            laboratorio=excluded.laboratorio,
            observacoes=excluded.observacoes,
            data_realizacao=excluded.data_realizacao,
            slug=excluded.slug,
            updated_at=excluded.updated_at`,
          [entry.id, entry.nome, entry.tipo, entry.unidade, entry.referencia_min, entry.referencia_max, entry.frequencia, entry.laboratorio, entry.observacoes, entry.data_realizacao, entry.slug, entry.created_at, entry.updated_at]
        );
      } else {
        saveLegacyState();
      }
      return clone(entry);
    },

    async saveExamResult(payload, files) {
      const examId = payload.exame_id || '';
      const exam = state.exams.find((item) => item.id === examId);
      if (!exam) {
        throw new Error('Selecione um exame ja cadastrado.');
      }
      if (exam.tipo === 'laboratorial' && String(payload.valor || '').trim() === '') {
        throw new Error('Informe o valor do resultado para exame laboratorial.');
      }

      const id = payload.id || generateId();
      const now = nowIso();
      const entry = {
        id,
        exame_id: exam.id,
        data_coleta: payload.data_coleta || '',
        valor: payload.valor || '',
        laboratorio: payload.laboratorio || '',
        observacoes: payload.observacoes || '',
        created_at: payload.created_at || now,
        updated_at: now,
      };

      state.examResults = [...state.examResults.filter((item) => item.id !== id), entry];

      if (db.native) {
        await db.run(
          `INSERT INTO exam_results (
            id, exame_id, data_coleta, valor, laboratorio, observacoes, created_at, updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          ON CONFLICT(id) DO UPDATE SET
            exame_id=excluded.exame_id,
            data_coleta=excluded.data_coleta,
            valor=excluded.valor,
            laboratorio=excluded.laboratorio,
            observacoes=excluded.observacoes,
            updated_at=excluded.updated_at`,
          [entry.id, entry.exame_id, entry.data_coleta, entry.valor, entry.laboratorio, entry.observacoes, entry.created_at, entry.updated_at]
        );
      }

      const selectedFiles = Array.from(files || []);
      for (const file of selectedFiles) {
        const stored = await storeAttachmentFile(file);
        const attachment = {
          id: generateId(),
          exame_id: exam.id,
          resultado_id: id,
          nome_original: file.name,
          mime_type: file.type || 'application/octet-stream',
          tamanho: file.size || 0,
          storage: stored.storage,
          path: stored.path,
          uri: stored.uri,
          data_url: stored.data_url,
          created_at: now,
        };
        state.attachments.push(attachment);

        if (db.native) {
          await db.run(
            `INSERT INTO attachments (
              id, exame_id, resultado_id, nome_original, mime_type, tamanho, storage, path, uri, data_url, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [
              attachment.id,
              attachment.exame_id,
              attachment.resultado_id,
              attachment.nome_original,
              attachment.mime_type,
              attachment.tamanho,
              attachment.storage,
              attachment.path,
              attachment.uri,
              attachment.data_url,
              attachment.created_at,
            ]
          );
        }
      }

      if (!db.native) {
        saveLegacyState();
      }
      return clone(entry);
    },

    listExamResults() {
      return sortByNewest(state.examResults).map((result) => {
        const exam = state.exams.find((item) => item.id === result.exame_id) || {};
        const attachments = state.attachments
          .filter((item) => item.resultado_id === result.id)
          .map((item) => ({
            ...clone(item),
            preview_url: getAttachmentUrl(item),
          }));

        return {
          ...clone(result),
          exame_nome: exam.nome || 'Exame',
          exame_tipo: exam.tipo || '',
          unidade: exam.unidade || '',
          referencia_min: exam.referencia_min || '',
          referencia_max: exam.referencia_max || '',
          attachments,
        };
      });
    },

    async deleteExamResult(id) {
      if (!state.examResults.some((item) => item.id === id)) return false;
      const attachments = state.attachments.filter((item) => item.resultado_id === id);
      await Promise.all(attachments.map(removeAttachmentFile));
      state.attachments = state.attachments.filter((item) => item.resultado_id !== id);
      state.examResults = state.examResults.filter((item) => item.id !== id);
      if (db.native) {
        await db.run('DELETE FROM attachments WHERE resultado_id = ?', [id]);
        await db.run('DELETE FROM exam_results WHERE id = ?', [id]);
      } else saveLegacyState();
      return true;
    },

    listExamEvolution() {
      return this.listExamDefinitions().map((exam) => {
        const results = state.examResults
          .filter((result) => result.exame_id === exam.id && String(result.valor || '').trim() !== '')
          .map((result) => ({
            ...clone(result),
            valor_numero: Number(String(result.valor).replace(',', '.')),
          }))
          .filter((result) => Number.isFinite(result.valor_numero))
          .sort((a, b) => String(a.data_coleta || '').localeCompare(String(b.data_coleta || '')));

        return {
          ...clone(exam),
          results,
          latest: results[results.length - 1] || null,
        };
      }).filter((exam) => exam.results.length);
    },

    listAttachments(filterExamId) {
      return sortByNewest(state.attachments, 'created_at').filter((item) => {
        if (!filterExamId) {
          return true;
        }
        return item.exame_id === filterExamId;
      }).map((item) => {
        const exam = state.exams.find((entry) => entry.id === item.exame_id) || {};
        const result = state.examResults.find((entry) => entry.id === item.resultado_id) || {};
        return {
          ...clone(item),
          exame_nome: exam.nome || 'Exame',
          exame_tipo: exam.tipo || '',
          data_coleta: result.data_coleta || '',
          resultado_observacoes: result.observacoes || '',
          preview_url: getAttachmentUrl(item),
        };
      });
    },

    getAttachment(id) {
      const found = state.attachments.find((item) => item.id === id);
      if (!found) {
        return null;
      }
      return {
        ...clone(found),
        preview_url: getAttachmentUrl(found),
      };
    },

    async deleteAttachment(id) {
      const attachment = state.attachments.find((item) => item.id === id);
      if (!attachment) return false;
      await removeAttachmentFile(attachment);
      state.attachments = state.attachments.filter((item) => item.id !== id);
      if (db.native) await db.run('DELETE FROM attachments WHERE id = ?', [id]);
      else saveLegacyState();
      return true;
    },
  };

  window.LocalHealthApp = {
    auth,
    store,
  };
})();
