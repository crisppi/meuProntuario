<?php
declare(strict_types=1);

namespace Prontuario\DAO;

use PDO;
use Prontuario\Model\Consultation;

final class ConsultationDAO extends BaseDAO
{
    public function save(Consultation $consultation): int
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO consulta (
                paciente_id, medico_id, data_consulta, hora_inicio, hora_fim, tipo_consulta, motivo, diagnostico, status
             ) VALUES (
                :paciente_id, :medico_id, :data_consulta, :hora_inicio, :hora_fim, :tipo_consulta, :motivo, :diagnostico, :status
             )'
        );
        $stmt->execute([
            ':paciente_id' => $consultation->pacienteId,
            ':medico_id' => $consultation->medicoId,
            ':data_consulta' => $consultation->dataConsulta,
            ':hora_inicio' => $consultation->horaInicio,
            ':hora_fim' => $consultation->horaFim,
            ':tipo_consulta' => $consultation->tipoConsulta,
            ':motivo' => $consultation->motivo,
            ':diagnostico' => $consultation->diagnostico,
            ':status' => $consultation->status,
        ]);

        return (int) $pdo->lastInsertId();
    }
}
