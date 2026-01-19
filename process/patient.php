<?php
declare(strict_types=1);

use Prontuario\DAO\PatientDAO;
use Prontuario\Model\Patient;

require_once __DIR__ . '/bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $patientData = (new PatientDAO())->loadLatest();
        if ($patientData === null) {
            respondJson(['success' => true, 'data' => null]);
        }

        respondJson(['success' => true, 'data' => $patientData]);
    }

    $patient = Patient::fromArray($_POST);
    if ($patient->nome === '') {
        throw new RuntimeException('Nome é obrigatório.');
    }

    $patientId = (new PatientDAO())->save($patient);
    respondSuccess([
        'message' => 'Dados salvos com sucesso.',
        'patient_id' => $patientId,
    ]);
} catch (Throwable $throwable) {
    respondError($throwable->getMessage());
}
