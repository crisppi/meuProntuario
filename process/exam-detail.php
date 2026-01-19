<?php
declare(strict_types=1);

use Prontuario\Database\Connection;

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $exameId = filter_input(INPUT_GET, 'exame_id', FILTER_VALIDATE_INT);
    if ($exameId === false || $exameId === null) {
        throw new RuntimeException('Parâmetro exame_id é obrigatório.');
    }

    $resultadoId = filter_input(INPUT_GET, 'resultado_id', FILTER_VALIDATE_INT);

    $pdo = Connection::open();
    if ($resultadoId !== false && $resultadoId !== null) {
        $stmt = $pdo->prepare(
            'SELECT
                e.exame_id,
                e.nome,
                e.tipo,
                e.unidade,
                e.referencia_min,
                e.referencia_max,
                e.frequencia,
                e.observacoes AS exame_observacoes,
                e.data_realizacao,
                r.resultado_id,
                r.data_coleta,
                r.valor,
                r.unidade AS resultado_unidade,
                r.laboratorio AS resultado_laboratorio,
                r.observacoes AS resultado_observacoes
             FROM exame e
             LEFT JOIN resultado_exame r ON r.exame_id = e.exame_id AND r.resultado_id = :resultado_id
             WHERE e.exame_id = :exame_id
             LIMIT 1'
        );
        $stmt->execute([
            ':exame_id' => $exameId,
            ':resultado_id' => $resultadoId,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                e.exame_id,
                e.nome,
                e.tipo,
                e.unidade,
                e.referencia_min,
                e.referencia_max,
                e.frequencia,
                e.observacoes AS exame_observacoes,
                e.data_realizacao,
                r.resultado_id,
                r.data_coleta,
                r.valor,
                r.unidade AS resultado_unidade,
                r.laboratorio AS resultado_laboratorio,
                r.observacoes AS resultado_observacoes
             FROM exame e
             LEFT JOIN resultado_exame r ON r.exame_id = e.exame_id
             WHERE e.exame_id = :exame_id
             ORDER BY r.data_coleta DESC, r.resultado_id DESC
             LIMIT 1'
        );
        $stmt->execute([':exame_id' => $exameId]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Exame não encontrado.');
    }

    echo json_encode([
        'success' => true,
        'data' => $row,
    ]);
} catch (\Throwable $throwable) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $throwable->getMessage(),
    ]);
}
