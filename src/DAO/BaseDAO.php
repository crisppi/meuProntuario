<?php
declare(strict_types=1);

namespace Prontuario\DAO;

use PDO;
use Prontuario\Database\Connection;

abstract class BaseDAO
{
    private ?PDO $pdo = null;

    protected function getConnection(): PDO
    {
        return $this->pdo ??= Connection::open();
    }
}
