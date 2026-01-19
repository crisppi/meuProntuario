<?php
declare(strict_types=1);

/**
 * Executa a migração de esquema que adiciona o slug (VARCHAR(191)) à tabela `exame`.
 * Execute com `php scripts/add_slug_column.php` a partir do diretório do projeto.
 */

require_once __DIR__ . '/../autoload.php';

use Prontuario\Database\Connection;

try {
    $pdo = Connection::open();
    $statement = <<<SQL
ALTER TABLE exame
  ADD COLUMN slug VARCHAR(191) UNIQUE;
SQL;
    $pdo->exec($statement);
    echo "COLUNA slug ADICIONADA!\n";
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf("Falha: %s\n", $exception->getMessage()));
    exit(1);
}
