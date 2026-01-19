<?php
declare(strict_types=1);

namespace Prontuario\Model;

final class Exam
{
    public ?int $id = null;
    public string $nome;
    public ?string $descricao = null;
    public ?string $unidade = null;
    public ?float $referenciaMin = null;
    public ?float $referenciaMax = null;
    public ?string $tipo = null;
    public ?string $laboratorio = null;
    public ?string $frequencia = null;
    public ?string $observacoes = null;
    public ?string $dataRealizacao = null;
    public ?float $resultadoValor = null;
    public ?string $resultadoUnidade = null;
    public ?string $resultadoLaboratorio = null;
    public ?string $resultadoData = null;
    public ?string $resultadoObservacoes = null;
    public ?string $slug = null;

    public static function fromArray(array $payload): self
    {
        $entity = new self();
        $entity->id = isset($payload['exame_id']) ? (int) $payload['exame_id'] : null;
        $entity->nome = trim((string) ($payload['nome_exame'] ?? $payload['nome'] ?? ''));
        $entity->descricao = $payload['descricao'] ?? null;
        $entity->unidade = $payload['unidade'] ?? null;
        $entity->referenciaMin = isset($payload['referencia_min']) ? (float) $payload['referencia_min'] : null;
        $entity->referenciaMax = isset($payload['referencia_max']) ? (float) $payload['referencia_max'] : null;
        $entity->tipo = $payload['tipo_exame'] ?? null;
        $entity->laboratorio = $payload['laboratorio'] ?? null;
        $entity->frequencia = $payload['frequencia'] ?? null;
        $entity->observacoes = $payload['observacoes'] ?? null;
        $entity->dataRealizacao = self::normalizeDate($payload['data_realizacao'] ?? $payload['data_coleta'] ?? null);
        $entity->resultadoValor = isset($payload['resultado_valor']) ? (float) $payload['resultado_valor'] : null;
        $entity->resultadoUnidade = $payload['resultado_unidade'] ?? null;
        $entity->resultadoLaboratorio = $payload['resultado_laboratorio'] ?? null;
        $entity->resultadoData = self::normalizeDate($payload['resultado_data'] ?? $payload['data_realizacao'] ?? null);
        $entity->resultadoObservacoes = $payload['resultado_observacoes'] ?? null;
        $entity->slug = self::normalizeSlug($entity->nome);
        return $entity;
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);
        return $str === '' ? null : $str;
    }

    public static function normalizeSlug(string $value): string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }

        $normalized = $value;
        if (extension_loaded('intl')) {
            $normalized = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $normalized);
        } else {
            $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $normalized) ?: $normalized;
        }

        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized);
        $normalized = trim($normalized, '-');
        return $normalized === '' ? $value : $normalized;
    }
}
