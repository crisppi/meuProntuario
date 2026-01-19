<?php
declare(strict_types=1);

namespace Prontuario\Model;

final class LaboratoryExam
{
    public ?int $resultadoId = null;
    public ?int $exameId = null;
    public ?int $pacienteId = null;
    public ?string $dataColeta = null;
    public ?float $valor = null;
    public ?string $unidade = null;
    public ?float $referenciaMin = null;
    public ?float $referenciaMax = null;
    public ?string $laboratorio = null;
    public ?string $observacoes = null;
    public ?string $arquivo = null;

    public static function fromArray(array $payload): self
    {
        $entity = new self();
        $entity->exameId = isset($payload['exame_id']) ? (int) $payload['exame_id'] : null;
        $entity->pacienteId = isset($payload['paciente_id']) ? (int) $payload['paciente_id'] : null;
        $entity->dataColeta = $payload['data_coleta'] ?? null;
        $entity->valor = isset($payload['valor']) ? (float) $payload['valor'] : null;
        $entity->unidade = $payload['unidade'] ?? null;
        $entity->referenciaMin = isset($payload['referencia_min']) ? (float) $payload['referencia_min'] : null;
        $entity->referenciaMax = isset($payload['referencia_max']) ? (float) $payload['referencia_max'] : null;
        $entity->laboratorio = $payload['laboratorio'] ?? null;
        $entity->observacoes = $payload['observacoes'] ?? null;
        $entity->arquivo = $payload['arquivo'] ?? null;
        $entity->resultadoId = isset($payload['resultado_id']) ? (int) $payload['resultado_id'] : null;
        return $entity;
    }
}
