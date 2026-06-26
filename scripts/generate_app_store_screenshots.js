const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

const root = path.resolve(__dirname, '..');
const appRoot = path.join(root, 'app');
const outRoot = path.join(root, 'build', 'app-store-screenshots');
const sourceDir = path.join(outRoot, 'source');
const indexPath = path.join(appRoot, 'index.html');
const chromePath = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const legacyStorageKey = 'organizador_saude_local_v1';
const sessionKey = 'organizador_saude_session_v1';

const specs = [
  {
    folder: 'iphone-6.5',
    cssWidth: 414,
    cssHeight: 896,
    scale: 3,
  },
  {
    folder: 'iphone-6.7',
    cssWidth: 430,
    cssHeight: 932,
    scale: 3,
  },
  {
    folder: 'ipad-13',
    cssWidth: 1024,
    cssHeight: 1366,
    scale: 2,
  },
];

const captures = [
  {
    id: 'painel',
    screen: 'dashboard',
    title: 'Painel',
  },
  {
    id: 'exames-evolucao',
    screen: 'exams',
    panel: 'evolution',
    title: 'Exames',
  },
  {
    id: 'pre-consulta',
    screen: 'pre-consultation',
    title: 'Pre consulta',
  },
];

const ensureDir = (dir) => fs.mkdirSync(dir, { recursive: true });

const fileUri = (filePath) => `file://${filePath}`;

const cleanOutput = () => {
  [
    sourceDir,
    path.join(outRoot, 'iphone-6.5'),
    path.join(outRoot, 'iphone-6.7'),
    path.join(outRoot, 'ipad-13'),
    path.join(outRoot, 'iphone-6.9'),
    path.join(outRoot, 'ipad'),
  ].forEach((dir) => {
    fs.rmSync(dir, { recursive: true, force: true });
  });
};

const now = '2026-06-15T12:00:00.000Z';

const demoState = {
  version: 1,
  personal: {
    nome: 'Roberto Crisppi',
    email: 'roberto@example.com',
    telefone: '(11) 99999-0000',
    data_nascimento: '1982-04-18',
    updated_at: now,
  },
  profile: {
    peso: '82',
    altura: '1.78',
    tipo_sanguineo: 'O+',
    alergias: 'Sem alergias cadastradas.',
    condicoes_cronicas: 'Hipertensao em acompanhamento.',
    observacoes: 'Rotina de exames semestrais.',
    updated_at: now,
  },
  preConsultation: {
    observacoes: 'Levar exames recentes e revisar valores alterados.',
    perguntas: 'Preciso repetir algum exame? Ajustar medicamentos em uso?',
    updated_at: now,
  },
  exams: [
    {
      id: 'exam-glicose',
      nome: 'Glicose',
      tipo: 'laboratorial',
      unidade: 'mg/dL',
      referencia_min: '70',
      referencia_max: '99',
      frequencia: 'Semestral',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Controle de rotina.',
      data_realizacao: '',
      slug: 'glicose',
      created_at: '2026-01-10T12:00:00.000Z',
      updated_at: now,
    },
    {
      id: 'exam-hba1c',
      nome: 'Hemoglobina glicada',
      tipo: 'laboratorial',
      unidade: '%',
      referencia_min: '4',
      referencia_max: '5.6',
      frequencia: 'Semestral',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Acompanhar tendencia.',
      data_realizacao: '',
      slug: 'hemoglobina-glicada',
      created_at: '2026-01-10T12:00:00.000Z',
      updated_at: now,
    },
    {
      id: 'exam-ldl',
      nome: 'Colesterol LDL',
      tipo: 'laboratorial',
      unidade: 'mg/dL',
      referencia_min: '0',
      referencia_max: '130',
      frequencia: 'Anual',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Perfil lipidico.',
      data_realizacao: '',
      slug: 'colesterol-ldl',
      created_at: '2026-01-10T12:00:00.000Z',
      updated_at: now,
    },
    {
      id: 'exam-vitd',
      nome: 'Vitamina D',
      tipo: 'laboratorial',
      unidade: 'ng/mL',
      referencia_min: '30',
      referencia_max: '100',
      frequencia: 'Anual',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Reposicao conforme orientacao.',
      data_realizacao: '',
      slug: 'vitamina-d',
      created_at: '2026-01-10T12:00:00.000Z',
      updated_at: now,
    },
  ],
  examResults: [
    {
      id: 'result-glicose-1',
      exame_id: 'exam-glicose',
      data_coleta: '2026-01-12',
      valor: '92',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Jejum de 8 horas.',
      created_at: '2026-01-12T12:00:00.000Z',
      updated_at: '2026-01-12T12:00:00.000Z',
    },
    {
      id: 'result-glicose-2',
      exame_id: 'exam-glicose',
      data_coleta: '2026-04-20',
      valor: '98',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Controle de rotina.',
      created_at: '2026-04-20T12:00:00.000Z',
      updated_at: '2026-04-20T12:00:00.000Z',
    },
    {
      id: 'result-glicose-3',
      exame_id: 'exam-glicose',
      data_coleta: '2026-06-12',
      valor: '104',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Resultado para revisar.',
      created_at: '2026-06-12T12:00:00.000Z',
      updated_at: '2026-06-12T12:00:00.000Z',
    },
    {
      id: 'result-hba1c-1',
      exame_id: 'exam-hba1c',
      data_coleta: '2026-01-12',
      valor: '5.4',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Dentro da referencia.',
      created_at: '2026-01-12T12:00:00.000Z',
      updated_at: '2026-01-12T12:00:00.000Z',
    },
    {
      id: 'result-hba1c-2',
      exame_id: 'exam-hba1c',
      data_coleta: '2026-06-12',
      valor: '7.2',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Acima da referencia informada.',
      created_at: '2026-06-12T12:00:00.000Z',
      updated_at: '2026-06-12T12:00:00.000Z',
    },
    {
      id: 'result-ldl-1',
      exame_id: 'exam-ldl',
      data_coleta: '2026-06-10',
      valor: '158',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Fora da faixa cadastrada.',
      created_at: '2026-06-10T12:00:00.000Z',
      updated_at: '2026-06-10T12:00:00.000Z',
    },
    {
      id: 'result-vitd-1',
      exame_id: 'exam-vitd',
      data_coleta: '2026-06-10',
      valor: '36',
      laboratorio: 'Laboratorio Central',
      observacoes: 'Dentro da referencia.',
      created_at: '2026-06-10T12:00:00.000Z',
      updated_at: '2026-06-10T12:00:00.000Z',
    },
  ],
  attachments: [
    {
      id: 'attachment-hemograma',
      exame_id: 'exam-hba1c',
      resultado_id: 'result-hba1c-2',
      nome_original: 'resultado-junho.pdf',
      mime_type: 'application/pdf',
      tamanho: 248000,
      storage: 'data_url',
      path: '',
      uri: '',
      data_url: '',
      created_at: '2026-06-12T12:00:00.000Z',
    },
    {
      id: 'attachment-ldl',
      exame_id: 'exam-ldl',
      resultado_id: 'result-ldl-1',
      nome_original: 'perfil-lipidico.pdf',
      mime_type: 'application/pdf',
      tamanho: 219000,
      storage: 'data_url',
      path: '',
      uri: '',
      data_url: '',
      created_at: '2026-06-10T12:00:00.000Z',
    },
  ],
  consultations: [
    {
      id: 'consult-1',
      data_consulta: '2026-06-22',
      hora_inicio: '09:30',
      hora_fim: '10:00',
      medico: 'Clinico geral',
      motivo: 'Revisao de exames',
      diagnostico: 'Levar resultados recentes e lista de medicamentos.',
      status: 'agendada',
      created_at: '2026-06-10T12:00:00.000Z',
      updated_at: now,
    },
    {
      id: 'consult-2',
      data_consulta: '2026-05-14',
      hora_inicio: '15:00',
      hora_fim: '15:40',
      medico: 'Cardiologista',
      motivo: 'Acompanhamento de rotina',
      diagnostico: 'Manter controle pressorico e retorno com exames.',
      status: 'realizada',
      created_at: '2026-05-14T12:00:00.000Z',
      updated_at: '2026-05-14T12:00:00.000Z',
    },
  ],
  medications: [
    {
      id: 'med-1',
      nome: 'Losartana',
      laboratorio: 'Uso continuo',
      dosagem: '50 mg',
      intervalo: '1x ao dia',
      status: 'Em Uso',
      created_at: '2026-01-10T12:00:00.000Z',
      updated_at: now,
    },
    {
      id: 'med-2',
      nome: 'Vitamina D',
      laboratorio: 'Conforme orientacao',
      dosagem: '2000 UI',
      intervalo: 'Manha',
      status: 'Em Uso',
      created_at: '2026-01-10T12:00:00.000Z',
      updated_at: now,
    },
  ],
  security: {
    localOnly: true,
    cloudSync: false,
    privacyAcceptedAt: '2026-06-15T12:00:00.000Z',
    lastLoginAt: now,
  },
  updatedAt: now,
};

const session = {
  token: 'local-app-store-screenshots',
  refresh_token: '',
  user: {
    id: 'local-device',
    name: 'Roberto Crisppi',
    email: '',
  },
  logged_at: now,
};

const seedScript = () => `
  <script>
    localStorage.setItem(${JSON.stringify(legacyStorageKey)}, ${JSON.stringify(JSON.stringify(demoState))});
    localStorage.setItem(${JSON.stringify(sessionKey)}, ${JSON.stringify(JSON.stringify(session))});
  </script>
`;

const postRenderScript = (capture) => `
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const targetPanel = ${JSON.stringify(capture.panel || '')};
      const settle = () => {
        if (targetPanel) {
          document.querySelector('[data-exam-panel-target="' + targetPanel + '"]')?.click();
        }
        document.querySelector('#app-nav')?.setAttribute('hidden', '');
        document.body.classList.remove('menu-is-open');
        window.scrollTo(0, 0);
      };
      setTimeout(settle, 120);
      setTimeout(settle, 420);
    });
  </script>
`;

const captureStyles = (spec) => `
  <style>
    html, body {
      width: ${spec.cssWidth}px;
      min-height: ${spec.cssHeight}px;
      margin: 0;
      overflow: hidden;
      background: #f8fafc;
    }

    body.app-local-shell {
      min-height: ${spec.cssHeight}px;
    }

    .app-local-main {
      padding-bottom: 0.9rem;
    }

    @media (min-width: 769px) {
      .app-local-main {
        padding-left: 1.25rem;
        padding-right: 1.25rem;
      }

      .dashboard-hero {
        grid-template-columns: minmax(0, 1fr);
      }

      .app-local-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }

      .dashboard-info-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .doctor-summary,
      .screen-block[data-screen="pre-consultation"] .local-stack {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
      }

      .screen-block[data-screen="pre-consultation"] .local-stack .local-card:first-child,
      .screen-block[data-screen="pre-consultation"] .local-stack .local-card:last-child {
        grid-column: 1 / -1;
      }
    }
  </style>
`;

const rewriteAssetPaths = (html) => html
  .replace(/href="assets\/styles\/app\.css([^"]*)"/, `href="${fileUri(path.join(appRoot, 'assets', 'styles', 'app.css'))}$1"`)
  .replace(/src="assets\/images\/logo\.png"/g, `src="${fileUri(path.join(appRoot, 'assets', 'images', 'logo.png'))}"`)
  .replace(/src="assets\/js\/local-health-app\.js"/, `src="${fileUri(path.join(appRoot, 'assets', 'js', 'local-health-app.js'))}"`)
  .replace(/src="assets\/js\/app-page\.js"/, `src="${fileUri(path.join(appRoot, 'assets', 'js', 'app-page.js'))}"`);

const htmlFor = (capture, spec) => {
  const index = fs.readFileSync(indexPath, 'utf8');
  return rewriteAssetPaths(index)
    .replace('</head>', `${captureStyles(spec)}</head>`)
    .replace('<script src="', `${seedScript()}<script src="`)
    .replace('</body>', `${postRenderScript(capture)}</body>`);
};

const chromeArgs = (htmlPath, pngPath, spec, capture) => [
  '--headless=new',
  '--disable-gpu',
  '--hide-scrollbars',
  '--no-first-run',
  '--no-default-browser-check',
  '--disable-dev-shm-usage',
  '--allow-file-access-from-files',
  '--timeout=1400',
  `--force-device-scale-factor=${spec.scale}`,
  `--window-size=${spec.cssWidth},${spec.cssHeight}`,
  `--screenshot=${pngPath}`,
  `${fileUri(htmlPath)}?screen=${encodeURIComponent(capture.screen)}`,
];

const main = () => {
  if (!fs.existsSync(chromePath)) {
    throw new Error(`Google Chrome not found at ${chromePath}`);
  }

  cleanOutput();
  ensureDir(sourceDir);

  specs.forEach((spec) => {
    const folder = path.join(outRoot, spec.folder);
    ensureDir(folder);
    captures.forEach((capture, index) => {
      const htmlPath = path.join(sourceDir, `${spec.folder}-${capture.id}.html`);
      const pngPath = path.join(
        folder,
        `${String(index + 1).padStart(2, '0')}-${capture.id}-${spec.cssWidth * spec.scale}x${spec.cssHeight * spec.scale}.png`,
      );

      fs.writeFileSync(htmlPath, htmlFor(capture, spec));

      const result = spawnSync(chromePath, chromeArgs(htmlPath, pngPath, spec, capture), {
        stdio: 'inherit',
      });
      if (result.status !== 0) {
        throw new Error(`Chrome failed while generating ${pngPath}`);
      }
      console.log(pngPath);
    });
  });
};

module.exports = {
  demoState,
  session,
  legacyStorageKey,
  sessionKey,
};

if (require.main === module) {
  main();
}
