<?php
declare(strict_types=1);

use Prontuario\DAO\ConsultationDAO;
use Prontuario\Model\Consultation;
use Throwable;

require_once __DIR__ . '/bootstrap.php';

try {
    $consultation = Consultation::fromArray($_POST);
    if ($consultation->pacienteId === null) {
        throw new RuntimeException('Paciente não informado.');
    }

    $consultationId = (new ConsultationDAO())->save($consultation);
    respondSuccess([
        'message' => 'Consulta agendada.',
        'consulta_id' => $consultationId,
    ]);
} catch (Throwable $throwable) {
    respondError($throwable->getMessage());
}
