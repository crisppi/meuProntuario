<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use Prontuario\Database\Connection;
use Throwable;

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = Connection::open();
    $stmt = $pdo->query('SELECT NOW()');
    $serverTime = $stmt->fetchColumn();
    $user = $pdo->query('SELECT USER()')->fetchColumn();
    $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Conexão ativa</title>
  <style>
    body { font-family: "Inter", system-ui, sans-serif; background:#0f172a; color:#fff; margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; }
    .card { background:#fff; color:#0f172a; border-radius:1rem; padding:2rem 3rem; box-shadow:0 30px 70px rgba(15,23,42,.4); text-align:center; }
    .status { font-size:1rem; margin-top:1rem; }
    strong { color:#2563eb; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Banco conectado</h1>
    <p class="status">Usuário: <strong>{$user}</strong></p>
    <p class="status">Servidor: <strong>{$version}</strong></p>
    <p class="status">Horário atual: <strong>{$serverTime}</strong></p>
  </div>
</body>
</html>
HTML;
} catch (Throwable $error) {
    http_response_code(500);
    echo "<h1>Falha ao conectar</h1><p>{$error->getMessage()}</p>";
}
