<?php
declare(strict_types=1);

use Prontuario\Database\Connection;

require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Connection::open();
    $stmt = $pdo->prepare(
        'SELECT
            e.exame_id,
            e.nome AS exame,
            e.tipo,
            e.laboratorio AS exame_laboratorio,
            e.frequencia AS frequencia,
            e.observacoes AS exame_observacoes,
            (
                SELECT resultado_id
                FROM resultado_exame re_id
                WHERE re_id.exame_id = e.exame_id
                ORDER BY re_id.data_coleta DESC, re_id.resultado_id DESC
                LIMIT 1
            ) AS resultado_id,
            (
                SELECT re_data.data_coleta
                FROM resultado_exame re_data
                WHERE re_data.exame_id = e.exame_id
                ORDER BY re_data.data_coleta DESC, re_data.resultado_id DESC
                LIMIT 1
            ) AS latest_data_coleta,
            (
                SELECT re_val.valor
                FROM resultado_exame re_val
                WHERE re_val.exame_id = e.exame_id
                ORDER BY re_val.data_coleta DESC, re_val.resultado_id DESC
                LIMIT 1
            ) AS valor,
            (
                SELECT re_unidade.unidade
                FROM resultado_exame re_unidade
                WHERE re_unidade.exame_id = e.exame_id
                ORDER BY re_unidade.data_coleta DESC, re_unidade.resultado_id DESC
                LIMIT 1
            ) AS unidade,
            (
                SELECT re_lab.laboratorio
                FROM resultado_exame re_lab
                WHERE re_lab.exame_id = e.exame_id
                ORDER BY re_lab.data_coleta DESC, re_lab.resultado_id DESC
                LIMIT 1
            ) AS resultado_laboratorio,
            (
                SELECT re_obs.observacoes
                FROM resultado_exame re_obs
                WHERE re_obs.exame_id = e.exame_id
                ORDER BY re_obs.data_coleta DESC, re_obs.resultado_id DESC
                LIMIT 1
            ) AS resultado_observacoes,
            COALESCE(
                (
                    SELECT re_data.data_coleta
                    FROM resultado_exame re_data
                    WHERE re_data.exame_id = e.exame_id
                    ORDER BY re_data.data_coleta DESC, re_data.resultado_id DESC
                    LIMIT 1
                ),
                e.data_realizacao,
                e.criado_em
            ) AS data_realizacao,
            e.referencia_min,
            e.referencia_max,
            COALESCE(max_values.max_valor,
                (
                    SELECT re_val.valor
                    FROM resultado_exame re_val
                    WHERE re_val.exame_id = e.exame_id
                    ORDER BY re_val.data_coleta DESC, re_val.resultado_id DESC
                    LIMIT 1
                )
            ) AS maior_valor
         FROM exame e
         LEFT JOIN (
             SELECT exame_id, MAX(valor) AS max_valor
             FROM resultado_exame
             GROUP BY exame_id
         ) max_values ON max_values.exame_id = e.exame_id
         ORDER BY data_realizacao DESC, e.criado_em DESC
         LIMIT 40'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'data' => $rows,
    ]);
} catch (\Throwable $throwable) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $throwable->getMessage(),
    ]);
}
