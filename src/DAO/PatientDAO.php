<?php

declare(strict_types=1);

namespace Prontuario\DAO;

use PDO;
use Prontuario\Model\Patient;
use Throwable;

final class PatientDAO extends BaseDAO
{
    public function loadLatest(): ?array
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->query(
            'SELECT
                p.pessoa_id,
                p.nome,
                p.cpf,
                p.email,
                p.telefone,
                p.data_nascimento,
                p.logradouro,
                p.numero,
                p.complemento,
                p.bairro,
                p.cidade,
                p.estado,
                p.cep,
                p.pais,
                pa.paciente_id,
                pa.peso,
                pa.altura,
                pa.tipo_sanguineo,
                pa.alergias,
                pa.condicoes_cronicas,
                pa.observacoes
             FROM pessoa p
             LEFT JOIN paciente pa ON pa.paciente_id = p.pessoa_id
             ORDER BY p.atualizado_em DESC, p.criado_em DESC
             LIMIT 1'
        );

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function save(Patient $patient): int
    {
        $pdo = $this->getConnection();
        $pdo->beginTransaction();
        try {
            $latestId = $this->fetchLatestPessoaId($pdo);
            if ($latestId === null) {
                $pessoaId = $this->insertPessoa($pdo, $patient);
            } else {
                $this->updatePessoa($pdo, $latestId, $patient);
                $pessoaId = $latestId;
            }

            $this->upsertPaciente($pdo, $pessoaId, $patient);
            $pdo->commit();
            return $pessoaId;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function fetchLatestPessoaId(PDO $pdo): ?int
    {
        $stmt = $pdo->query('SELECT pessoa_id FROM pessoa ORDER BY atualizado_em DESC, criado_em DESC LIMIT 1');
        $value = $stmt->fetchColumn();
        if ($value === false) {
            return null;
        }

        return (int)$value;
    }

    private function insertPessoa(PDO $pdo, Patient $patient): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO pessoa (
                nome, cpf, email, telefone, data_nascimento, logradouro, numero, complemento,
                bairro, cidade, estado, cep, pais, criado_em, atualizado_em
             ) VALUES (
                :nome, :cpf, :email, :telefone, :data_nascimento, :logradouro, :numero, :complemento,
                :bairro, :cidade, :estado, :cep, :pais, NOW(), NOW()
             )'
        );

        $stmt->execute($this->pessoaParams($patient));
        return (int)$pdo->lastInsertId();
    }

    private function updatePessoa(PDO $pdo, int $pessoaId, Patient $patient): void
    {
        $stmt = $pdo->prepare(
            'UPDATE pessoa SET
                nome = :nome,
                cpf = :cpf,
                email = :email,
                telefone = :telefone,
                data_nascimento = :data_nascimento,
                logradouro = :logradouro,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade,
                estado = :estado,
                cep = :cep,
                pais = :pais,
                atualizado_em = NOW()
             WHERE pessoa_id = :pessoa_id'
        );

        $params = $this->pessoaParams($patient);
        $params[':pessoa_id'] = $pessoaId;
        $stmt->execute($params);
    }

    private function upsertPaciente(PDO $pdo, int $pessoaId, Patient $patient): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO paciente (
                paciente_id, peso, altura, tipo_sanguineo, alergias, condicoes_cronicas, observacoes
             ) VALUES (
                :paciente_id, :peso, :altura, :tipo_sanguineo, :alergias, :condicoes_cronicas, :observacoes
             )
             ON DUPLICATE KEY UPDATE
                peso = VALUES(peso),
                altura = VALUES(altura),
                tipo_sanguineo = VALUES(tipo_sanguineo),
                alergias = VALUES(alergias),
                condicoes_cronicas = VALUES(condicoes_cronicas),
                observacoes = VALUES(observacoes)'
        );

        $stmt->execute($this->pacienteParams($pessoaId, $patient));
    }

    private function pessoaParams(Patient $patient): array
    {
        return [
            ':nome' => $patient->nome,
            ':cpf' => $patient->cpf,
            ':email' => $patient->email,
            ':telefone' => $patient->telefone,
            ':data_nascimento' => $patient->dataNascimento,
            ':logradouro' => $patient->logradouro,
            ':numero' => $patient->numero,
            ':complemento' => $patient->complemento,
            ':bairro' => $patient->bairro,
            ':cidade' => $patient->cidade,
            ':estado' => $patient->estado,
            ':cep' => $patient->cep,
            ':pais' => $patient->pais ?? 'Brasil',
        ];
    }

    private function pacienteParams(int $pessoaId, Patient $patient): array
    {
        return [
            ':paciente_id' => $pessoaId,
            ':peso' => $patient->peso,
            ':altura' => $patient->altura,
            ':tipo_sanguineo' => $patient->tipoSanguineo,
            ':alergias' => $patient->alergias,
            ':condicoes_cronicas' => $patient->condicoesCronicas,
            ':observacoes' => $patient->observacoes,
        ];
    }
}
