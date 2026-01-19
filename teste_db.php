<?php
require __DIR__ . '/vendor/autoload.php';

use Prontuario\Database\Connection;

try {
    $pdo = Connection::open();
    echo "Conectou. Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (Throwable $e) {
    echo nl2br(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
