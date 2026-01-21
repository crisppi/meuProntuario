<?php
declare(strict_types=1);
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
  <nav class="site-nav">
    <a href="navegacao.html">Navegação</a>
    <a href="dados-paciente.php">Dados do paciente</a>
    <a href="cadastro-exames.php">Cadastro exames</a>
    <a href="cadastro-consultas.php">Cadastro consultas</a>
    <a href="cadastro-exames-laboratoriais.php">Lançamento exames laboratoriais</a>
    <a href="cadastro-exames-imagem.php">Lançamento exames de imagem</a>
    <a href="listagem-exames-cadastrados.php">Lista de exames cadastrados</a>
    <a href="listagem-exames.php">Lista resultados laboratoriais</a>
  </nav>
</header>
