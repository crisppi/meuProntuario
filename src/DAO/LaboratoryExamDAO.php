<?php
declare(strict_types=1);

namespace Prontuario\DAO;

use PDO;
use Prontuario\Model\LaboratoryExam;

final class LaboratoryExamDAO extends BaseDAO
{
    public function save(LaboratoryExam $exam): int
    {
        return $this->insert($exam);
    }

    public function persist(LaboratoryExam $exam): int
    {
        if ($exam->resultadoId !== null && $exam->resultadoId > 0) {
            $this->update($exam);
            return $exam->resultadoId;
        }
        return $this->insert($exam);
    }

    private function insert(LaboratoryExam $exam): int
    {
        $pdo = $this->getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO resultado_exame (
                    exame_id, paciente_id, data_coleta, valor, unidade, referencia_min, referencia_max, laboratorio, observacoes, criado_em
                 ) VALUES (
                    :exame_id, :paciente_id, :data_coleta, :valor, :unidade, :referencia_min, :referencia_max, :laboratorio, :observacoes, NOW()
                 )'
            );
            $stmt->execute([
                ':exame_id' => $exam->exameId,
                ':paciente_id' => $exam->pacienteId,
                ':data_coleta' => $exam->dataColeta,
                ':valor' => $exam->valor,
                ':unidade' => $exam->unidade,
                ':referencia_min' => $exam->referenciaMin,
                ':referencia_max' => $exam->referenciaMax,
                ':laboratorio' => $exam->laboratorio,
                ':observacoes' => $exam->observacoes,
            ]);

            $resultadoId = (int) $pdo->lastInsertId();
            $pdo->commit();
            return $resultadoId;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function update(LaboratoryExam $exam): void
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare(
            'UPDATE resultado_exame SET
                data_coleta = :data_coleta,
                valor = :valor,
                unidade = :unidade,
                referencia_min = :referencia_min,
                referencia_max = :referencia_max,
                laboratorio = :laboratorio,
                observacoes = :observacoes,
                paciente_id = :paciente_id
             WHERE resultado_id = :resultado_id AND exame_id = :exame_id'
        );
        $stmt->execute([
            ':data_coleta' => $exam->dataColeta,
            ':valor' => $exam->valor,
            ':unidade' => $exam->unidade,
            ':referencia_min' => $exam->referenciaMin,
            ':referencia_max' => $exam->referenciaMax,
            ':laboratorio' => $exam->laboratorio,
            ':observacoes' => $exam->observacoes,
            ':paciente_id' => $exam->pacienteId,
            ':resultado_id' => $exam->resultadoId,
            ':exame_id' => $exam->exameId,
        ]);
    }
}
