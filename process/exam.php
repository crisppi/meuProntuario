<?php
declare(strict_types=1);

use Prontuario\DAO\ExamDAO;
use Prontuario\DAO\PatientDAO;
use Prontuario\DAO\LaboratoryExamDAO;
use Prontuario\Model\Exam;
use Prontuario\Model\LaboratoryExam;

require_once __DIR__ . '/bootstrap.php';

try {
    $exam = Exam::fromArray($_POST);
    if ($exam->nome === '') {
        throw new RuntimeException('Nome do exame é obrigatório.');
    }

    $examId = (new ExamDAO())->upsertDefinition($exam);

    if ($exam->resultadoValor !== null) {
        $patientRow = (new PatientDAO())->loadLatest();
        $patientId = $patientRow['paciente_id'] ?? null;
        if ($patientId !== null) {
            $laboratoryExam = LaboratoryExam::fromArray([
                'exame_id' => $examId,
                'paciente_id' => $patientId,
                'data_coleta' => $exam->resultadoData ?? $exam->dataRealizacao,
                'valor' => $exam->resultadoValor,
                'unidade' => $exam->resultadoUnidade ?? $exam->unidade,
                'laboratorio' => $exam->resultadoLaboratorio ?? $exam->laboratorio,
                'referencia_min' => $exam->referenciaMin,
                'referencia_max' => $exam->referenciaMax,
                'observacoes' => $exam->resultadoObservacoes,
            ]);
            (new LaboratoryExamDAO())->persist($laboratoryExam);
        }
    }

    respondSuccess([
        'message' => 'Exame registrado.',
        'exame_id' => $examId,
    ]);
} catch (\Throwable $throwable) {
    respondError($throwable->getMessage());
}
