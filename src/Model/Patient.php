<?php
declare(strict_types=1);

namespace Prontuario\Model;

final class Patient
{
    public string $nome;
    public ?string $cpf = null;
    public ?string $email = null;
    public ?string $telefone = null;
    public ?string $dataNascimento = null;
    public ?string $logradouro = null;
    public ?string $numero = null;
    public ?string $complemento = null;
    public ?string $bairro = null;
    public ?string $cidade = null;
    public ?string $estado = null;
    public ?string $cep = null;
    public ?string $pais = null;
    public ?float $peso = null;
    public ?float $altura = null;
    public ?string $tipoSanguineo = null;
    public ?string $alergias = null;
    public ?string $condicoesCronicas = null;
    public ?string $observacoes = null;

    public static function fromArray(array $payload): self
    {
        $entity = new self();
        $entity->nome = trim((string) ($payload['nome'] ?? ''));
        $cpfRaw = preg_replace('/\D/', '', (string) ($payload['cpf'] ?? ''));
        $entity->cpf = $cpfRaw !== '' ? $cpfRaw : null;
        $entity->email = isset($payload['email']) ? trim((string) $payload['email']) : null;
        $entity->telefone = isset($payload['telefone']) ? trim((string) $payload['telefone']) : null;
        $entity->dataNascimento = self::normalizeDate($payload['data_nascimento'] ?? $payload['nascimento'] ?? null);
        $entity->logradouro = $payload['logradouro'] ?? null;
        $entity->numero = $payload['numero'] ?? null;
        $entity->complemento = $payload['complemento'] ?? null;
        $entity->bairro = $payload['bairro'] ?? null;
        $entity->cidade = $payload['cidade'] ?? null;
        $entity->estado = $payload['estado'] ?? null;
        $entity->cep = $payload['cep'] ?? null;
        $entity->pais = $payload['pais'] ?? null;
        $entity->peso = self::normalizeFloat($payload['peso'] ?? null);
        $entity->altura = self::normalizeFloat($payload['altura'] ?? null);
        $entity->tipoSanguineo = $payload['tipo_sanguineo'] ?? null;
        $entity->alergias = $payload['alergias'] ?? null;
        $entity->condicoesCronicas = $payload['condicoes_cronicas'] ?? null;
        $entity->observacoes = $payload['observacoes'] ?? null;
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

    private static function normalizeFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        return (float) $str;
    }
}
