<?php
declare(strict_types=1);
?>
<style>
  :root {
    font-family: "Inter", system-ui, sans-serif;
    background: #eef2ff;
  }

  body {
    margin: 0;
    min-height: 100vh;
    background: linear-gradient(135deg, #eef2ff, #e0f2fe);
    display: flex;
    flex-direction: column;
    align-items: stretch;
  }

  .site-header {
    width: 100%;
    min-height: 72px;
    padding: 0.75rem 1.25rem;
    border-radius: 0 0 1.25rem 1.25rem;
    background: #fff;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: nowrap;
  }

  .site-logo {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    font-weight: 700;
    color: #0f172a;
    font-size: 1.8rem;
  }

  .site-logo img {
    width: 110px;
    height: 110px;
    border-radius: 0.9rem;
    border: 2px solid rgba(37, 99, 235, 0.4);
    background: #fff;
    padding: 0.3rem;
  }

  .site-nav {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.7rem;
    margin-left: auto;
    overflow-x: auto;
    scrollbar-width: none;
  }

  .site-nav a {
    padding: 0.4rem 0.85rem;
    border-radius: 0.65rem;
    background: #e0e7ff;
    color: #1e1b4b;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: none;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.2s ease;
  }

  .site-nav a:hover {
    background: #c7d7ff;
    transform: translateY(-1px);
  }

  .site-nav a.active {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: #fff;
  }

  .site-shell {
    width: 100%;
    padding: 0.65rem clamp(0.5rem, 2vw, 1.5rem) 2rem;
    margin: 0.75rem auto 0;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
    box-sizing: border-box;
  }

  .site-shell .panel,
  .site-shell .card {
    width: 100% !important;
    max-width: none !important;
  }

  .site-shell .card {
    border-radius: 0.9rem !important;
    border: 0.7px solid rgba(148, 163, 184, 0.25);
    box-shadow: 0 25px 45px rgba(15, 23, 42, 0.08);
    background: #ffffff;
  }

  .site-shell > .panel,
  .site-shell > .card,
  .site-shell > section {
    width: min(1200px, 100%);
    margin: 0 auto;
  }

  .site-shell section + section {
    margin-top: 1.5rem;
  }
</style>

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
