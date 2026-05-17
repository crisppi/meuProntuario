# Organizador de Saude

Aplicativo mobile local empacotado pelo Capacitor.

## Arquitetura

- Entrada do app em `app/index.html`.
- `capacitor.config.json` usa `webDir: "app"` e nao usa `server.url`.
- Nao ha backend PHP, DAO, MySQL, SQL migrations ou pasta `web/`.
- Dados pessoais, perfil, consultas, medicamentos, exames, resultados e anexos ficam no dispositivo.
- Anexos usam a area privada do app quando `@capacitor/filesystem` estiver disponivel.

## Arquivos principais

- `app/index.html`: tela unica do app.
- `app/assets/js/local-health-app.js`: persistencia local, SQLite/armazenamento local e anexos.
- `app/assets/js/app-page.js`: comportamento da interface.
- `app/assets/styles/app.css`: estilos do app.

## Android / Google Play

O build Android deve empacotar apenas o conteudo de `app/` em `android/app/src/main/assets/public`.

```bash
npm run sync:android
./android/gradlew bundleRelease
```

O bundle Android fica em `android/app/build/outputs/bundle/release/app-release.aab`.
