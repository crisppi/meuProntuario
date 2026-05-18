# Organizador de Saude

Aplicativo mobile local empacotado pelo Capacitor para organizar registros pessoais de saude.

## Arquitetura

- Entrada do app em `app/index.html`.
- `capacitor.config.json` usa `webDir: "app"` e nao usa `server.url`.
- Nao ha backend PHP, DAO, MySQL, SQL migrations ou pasta `web/`.
- Dados pessoais, perfil, consultas, medicamentos, exames, resultados e anexos ficam no dispositivo.
- Anexos usam a area privada do app quando `@capacitor/filesystem` estiver disponivel.
- O app nao declara permissao de internet no Android.
- Backup automatico do Android fica desativado para manter a proposta de dados locais.

## Arquivos principais

- `app/index.html`: tela unica do app.
- `app/assets/js/local-health-app.js`: persistencia local, SQLite/armazenamento local e anexos.
- `app/assets/js/app-page.js`: comportamento da interface.
- `app/assets/styles/app.css`: estilos do app.

## Android / Google Play

O build Android deve empacotar apenas o conteudo de `app/` em `android/app/src/main/assets/public`.

```bash
npm run sync
./android/gradlew bundleRelease
```

O bundle Android fica em `android/app/build/outputs/bundle/release/app-release.aab`.

## Privacidade

A politica de privacidade tambem esta disponivel em `PRIVACY_POLICY.md` para publicacao como URL externa na Play Console.

Mesmo sem coleta online, o app organiza informacoes de saude inseridas pelo usuario. Na Play Console, preencha a declaracao de apps de saude e a secao de seguranca dos dados de forma consistente com armazenamento local, sem coleta e sem compartilhamento.

Para a Play Console, use uma URL publica, sem login, sem bloqueio geografico e nao editavel. Evite usar link de edicao do Google Docs; prefira publicar a politica como pagina web ou usar GitHub Pages.
