const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const {
  demoState,
  legacyStorageKey,
  session,
  sessionKey,
} = require('./generate_app_store_screenshots');

const root = path.resolve(__dirname, '..');
const appBundle = '/private/tmp/meuprontuario-ios-screenshots/Build/Products/Debug-iphonesimulator/App.app';
const indexPath = path.join(appBundle, 'public', 'index.html');
const outRoot = path.join(root, 'build', 'app-store-screenshots');
const bundleId = 'br.com.accertconsult.organizadorsaude';

const devices = [
  {
    name: 'AppStore-iPhone-6-5',
    folder: 'iphone-6.5',
  },
  {
    name: 'AppStore-iPhone-6-7',
    folder: 'iphone-6.7',
  },
  {
    name: 'AppStore-iPad-13',
    folder: 'ipad-13',
  },
];

const captures = [
  {
    id: 'painel',
    screen: 'dashboard',
  },
  {
    id: 'exames-evolucao',
    screen: 'exams',
    panel: 'evolution',
  },
  {
    id: 'pre-consulta',
    screen: 'pre-consultation',
  },
];

const ensureDir = (dir) => fs.mkdirSync(dir, { recursive: true });

const run = (cmd, args, options = {}) => {
  const result = spawnSync(cmd, args, {
    stdio: options.stdio || 'pipe',
    encoding: 'utf8',
  });
  if (result.status !== 0 && !options.ignoreFailure) {
    const output = `${result.stdout || ''}${result.stderr || ''}`.trim();
    throw new Error(`${cmd} ${args.join(' ')} failed${output ? `:\n${output}` : ''}`);
  }
  return result;
};

const deviceIdByName = (name) => {
  const result = run('xcrun', ['simctl', 'list', 'devices', '-j']);
  const data = JSON.parse(result.stdout);
  const devicesByRuntime = Object.values(data.devices || {}).flat();
  const match = devicesByRuntime.find((device) => device.name === name && device.isAvailable);
  if (!match) {
    throw new Error(`Simulator not found: ${name}`);
  }
  return match.udid;
};

const bootDevice = (udid) => {
  run('xcrun', ['simctl', 'boot', udid], { ignoreFailure: true });
  run('xcrun', ['simctl', 'bootstatus', udid, '-b'], { stdio: 'inherit' });
};

const seedScript = () => `
  <script>
    localStorage.setItem(${JSON.stringify(legacyStorageKey)}, ${JSON.stringify(JSON.stringify(demoState))});
    localStorage.setItem(${JSON.stringify(sessionKey)}, ${JSON.stringify(JSON.stringify(session))});
  </script>
`;

const navigationScript = (capture) => `
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const screen = ${JSON.stringify(capture.screen)};
      const panel = ${JSON.stringify(capture.panel || '')};
      const settle = () => {
        document.querySelector('[data-screen-target="' + screen + '"]')?.click();
        if (panel) {
          document.querySelector('[data-exam-panel-target="' + panel + '"]')?.click();
        }
        document.querySelector('#app-nav')?.setAttribute('hidden', '');
        document.body.classList.remove('menu-is-open');
        window.scrollTo(0, 0);
      };
      setTimeout(settle, 250);
      setTimeout(settle, 700);
      setTimeout(settle, 1200);
    });
  </script>
`;

const patchAppIndex = (originalIndex, capture) => {
  const patched = originalIndex
    .replace('<script src="assets/js/local-health-app.js"></script>', `${seedScript()}<script src="assets/js/local-health-app.js"></script>`)
    .replace('</body>', `${navigationScript(capture)}</body>`);
  fs.writeFileSync(indexPath, patched);
  run('/usr/bin/codesign', ['--force', '--sign', '-', '--timestamp=none', appBundle]);
};

const installLaunchAndCapture = (udid, outputPath) => {
  run('xcrun', ['simctl', 'uninstall', udid, bundleId], { ignoreFailure: true });
  run('xcrun', ['simctl', 'install', udid, appBundle], { stdio: 'inherit' });
  run('xcrun', ['simctl', 'launch', udid, bundleId], { stdio: 'inherit' });
  run('/bin/sleep', ['8']);
  run('xcrun', ['simctl', 'io', udid, 'screenshot', outputPath], { stdio: 'inherit' });
};

const main = () => {
  if (!fs.existsSync(indexPath)) {
    throw new Error(`Built simulator app not found. Run xcodebuild first: ${appBundle}`);
  }

  const originalIndex = fs.readFileSync(indexPath, 'utf8');

  devices.forEach((device) => {
    const udid = deviceIdByName(device.name);
    const folder = path.join(outRoot, device.folder);
    fs.rmSync(folder, { recursive: true, force: true });
    ensureDir(folder);
    bootDevice(udid);

    captures.forEach((capture, index) => {
      const outputPath = path.join(
        folder,
        `${String(index + 1).padStart(2, '0')}-${capture.id}.png`,
      );
      patchAppIndex(originalIndex, capture);
      installLaunchAndCapture(udid, outputPath);
      console.log(outputPath);
    });
  });

  fs.writeFileSync(indexPath, originalIndex);
  run('/usr/bin/codesign', ['--force', '--sign', '-', '--timestamp=none', appBundle]);
};

main();
