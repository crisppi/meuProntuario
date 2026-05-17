<?php
declare(strict_types=1);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$currentPage = basename($currentPath) ?: 'index.php';
$navItems = [
  ['href' => 'index.php', 'label' => 'Início', 'short' => 'Início'],
  ['href' => 'dados-paciente.php', 'label' => 'Dados do paciente', 'short' => 'Paciente'],
  ['href' => 'cadastro-exames.php', 'label' => 'Cadastro exames', 'short' => 'Exames'],
  ['href' => 'cadastro-consultas.php', 'label' => 'Cadastro consultas', 'short' => 'Consultas'],
  ['href' => 'cadastro-exames-laboratoriais.php', 'label' => 'Lançamento exames laboratoriais', 'short' => 'Lab'],
  ['href' => 'cadastro-exames-imagem.php', 'label' => 'Lançamento exames de imagem', 'short' => 'Imagem'],
  ['href' => 'listagem-exames-cadastrados.php', 'label' => 'Lista de exames cadastrados', 'short' => 'Cadastrados'],
  ['href' => 'listagem-exames.php', 'label' => 'Lista resultados laboratoriais', 'short' => 'Resultados'],
];
?>
<script>
  (function () {
    const ua = navigator.userAgent || '';
    const isCapacitor = typeof window !== 'undefined' && (window.Capacitor || /Capacitor/i.test(ua));
    if (isCapacitor) {
      document.body.classList.add('app-mode');
      document.body.classList.remove('web-mode');
    }
  })();
</script>
<header class="site-header">
  <div class="site-logo">
    <img src="../logo.png" alt="Logo Meu Prontuário" />
    <span>Meu Prontuário Médico</span>
  </div>
  <button class="nav-toggle" type="button" aria-label="Abrir navegação" aria-expanded="false" aria-controls="site-nav">
    <span aria-hidden="true"></span>
    <span aria-hidden="true"></span>
    <span aria-hidden="true"></span>
  </button>
  <nav id="site-nav" class="site-nav" aria-label="Navegação principal">
    <?php foreach ($navItems as $item): ?>
      <?php
        $itemPage = basename($item['href']);
        $isActive = $currentPage === $itemPage;
      ?>
      <a
        href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
        class="<?= $isActive ? 'active' : '' ?>"
        data-short="<?= htmlspecialchars($item['short'], ENT_QUOTES, 'UTF-8') ?>"
        <?= $isActive ? 'aria-current="page"' : '' ?>
      >
        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</header>
<script>
  (function () {
    const header = document.querySelector('.site-header');
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.getElementById('site-nav');
    if (!header || !toggle || !nav) return;

    const setOpen = (isOpen) => {
      header.classList.toggle('nav-open', isOpen);
      toggle.setAttribute('aria-expanded', String(isOpen));
      toggle.setAttribute('aria-label', isOpen ? 'Fechar navegação' : 'Abrir navegação');
    };

    toggle.addEventListener('click', () => {
      setOpen(!header.classList.contains('nav-open'));
    });

    nav.addEventListener('click', (event) => {
      if (event.target.closest('a')) {
        setOpen(false);
      }
    });

    document.addEventListener('click', (event) => {
      if (!header.contains(event.target)) {
        setOpen(false);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    });
  })();
</script>
