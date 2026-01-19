<?php
declare(strict_types=1);

use Prontuario\DAO\ExamDAO;

require_once __DIR__ . '/bootstrap.php';

try {
    $definitions = (new ExamDAO())->listDefinitions();
    respondSuccess([
        'data' => $definitions,
    ]);
} catch (\Throwable $throwable) {
    respondError($throwable->getMessage());
}
