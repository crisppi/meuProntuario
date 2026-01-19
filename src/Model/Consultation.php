<?php
declare(strict_types=1);

namespace Prontuario\Model;

final class Consultation
{
    public ?int $id = null;
    public ?int $pacienteId = null;
    public ?int $medicoId = null;
    public ?string $dataConsulta = null;
    public ?string $horaInicio = null;
    public ?string $horaFim = null;
    public ?string $tipoConsulta = null;
    public ?string $motivo = null;
    public ?string $diagnostico = null;
    public ?string $status = null;

    public static function fromArray(array $payload): self
    {
        $entity = new self();
        $entity->pacienteId = isset($payload['paciente_id']) ? (int) $payload['paciente_id'] : null;
        $entity->medicoId = isset($payload['medico_id']) ? (int) $payload['medico_id'] : null;
        $entity->dataConsulta = $payload['data_consulta'] ?? null;
        $entity->horaInicio = $payload['hora_inicio'] ?? null;
        $entity->horaFim = $payload['hora_fim'] ?? null;
        $entity->tipoConsulta = $payload['tipo_consulta'] ?? null;
        $entity->motivo = $payload['motivo'] ?? null;
        $entity->diagnostico = $payload['diagnostico'] ?? null;
        $entity->status = $payload['status'] ?? $payload['status_consulta'] ?? 'agendada';
        return $entity;
    }
}
