<?php
declare(strict_types=1);

use Prontuario\DAO\LaboratoryExamDAO;
use Prontuario\DAO\PatientDAO;
use Prontuario\Model\LaboratoryExam;
use Throwable;

require_once __DIR__ . '/bootstrap.php';

try {
    $labExam = LaboratoryExam::fromArray($_POST);
    if ($labExam->exameId === null) {
        throw new RuntimeException('Exame é obrigatório.');
    }

    if ($labExam->pacienteId === null) {
        $patientRow = (new PatientDAO())->loadLatest();
        $labExam->pacienteId = $patientRow['paciente_id'] ?? null;
    }

    if ($labExam->pacienteId === null) {
        throw new RuntimeException('Paciente não encontrado. Cadastre um paciente antes de registrar resultados.');
    }

    $resultId = (new LaboratoryExamDAO())->persist($labExam);
    respondSuccess([
        'message' => 'Exame laboratorial registrado.',
        'resultado_id' => $resultId,
    ]);
} catch (Throwable $throwable) {
    respondError($throwable->getMessage());
}
