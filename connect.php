<?php
declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use Prontuario\Database\Connection;

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = Connection::open();
    $stmt = $pdo->query('SELECT NOW()');
    $now = $stmt->fetchColumn();
    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Conexão com o Banco</title>
    <style>
        body { font-family: system-ui,-apple-system,sans-serif; text-align:center; padding:3rem; background:#f8fafc; color:#0f172a; }
        .status { display:inline-block; padding:1rem 1.5rem; border-radius:0.85rem; background:#dcfce7; color:#047857; box-shadow:0 10px 25px rgba(15,23,42,.1); }
    </style>
</head>
<body>
    <h1>Banco conectado</h1>
    <p class="status">Conectado como <strong>{$pdo->query('SELECT USER()')->fetchColumn()}</strong> em <strong>{$pdo->getAttribute(PDO::ATTR_SERVER_VERSION)}</strong><br>Horário servidor: {$now}</p>
    <p>Veja <code>logs/db.log</code> para detalhes.</p>
</body>
</html>
HTML;
} catch (Throwable $error) {
    http_response_code(500);
    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Erro de Conexão</title>
    <style>
        body { font-family: system-ui,-apple-system,sans-serif; text-align:center; padding:3rem; background:#fef2f2; color:#991b1b; }
        .status { display:inline-block; padding:1rem 1.5rem; border-radius:0.85rem; background:#fee2e2; color:#b91c1c; box-shadow:0 10px 25px rgba(15,23,42,.1); }
    </style>
</head>
<body>
    <h1>Falha ao conectar</h1>
    <p class="status">{$error->getMessage()}</p>
</body>
</html>
HTML;
}
