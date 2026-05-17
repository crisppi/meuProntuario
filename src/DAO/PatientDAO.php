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

        if (!$this->tableExists($pdo, 'pessoa')) {
            return $this->loadLatestUsuario($pdo);
        }

        $patientSelect = $this->tableExists($pdo, 'paciente')
            ? 'pa.paciente_id,
                pa.peso,
                pa.altura,
                pa.tipo_sanguineo,
                pa.alergias,
                pa.condicoes_cronicas,
                pa.observacoes'
            : 'p.pessoa_id AS paciente_id,
                NULL AS peso,
                NULL AS altura,
                NULL AS tipo_sanguineo,
                NULL AS alergias,
                NULL AS condicoes_cronicas,
                NULL AS observacoes';

        $patientJoin = $this->tableExists($pdo, 'paciente')
            ? 'LEFT JOIN paciente pa ON pa.paciente_id = p.pessoa_id'
            : '';

        $stmt = $pdo->query(
            "SELECT
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
                {$patientSelect}
             FROM pessoa p
             {$patientJoin}
             ORDER BY p.atualizado_em DESC, p.criado_em DESC
             LIMIT 1"
        );

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function save(Patient $patient): int
    {
        $pdo = $this->getConnection();
        $pdo->beginTransaction();
        try {
            if (!$this->tableExists($pdo, 'pessoa')) {
                $latestId = $this->fetchLatestUsuarioId($pdo);
                if ($latestId === null) {
                    $pessoaId = $this->insertUsuario($pdo, $patient);
                } else {
                    $this->updateUsuario($pdo, $latestId, $patient);
                    $pessoaId = $latestId;
                }

                if ($this->tableExists($pdo, 'paciente')) {
                    $this->upsertPaciente($pdo, $pessoaId, $patient);
                }

                $pdo->commit();
                return $pessoaId;
            }

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

    private function loadLatestUsuario(PDO $pdo): ?array
    {
        $patientSelect = $this->tableExists($pdo, 'paciente')
            ? 'pa.paciente_id,
                pa.peso,
                pa.altura,
                pa.tipo_sanguineo,
                pa.alergias,
                pa.condicoes_cronicas,
                pa.observacoes'
            : 'u.usuario_id AS paciente_id,
                NULL AS peso,
                NULL AS altura,
                NULL AS tipo_sanguineo,
                NULL AS alergias,
                NULL AS condicoes_cronicas,
                NULL AS observacoes';

        $patientJoin = $this->tableExists($pdo, 'paciente')
            ? 'LEFT JOIN paciente pa ON pa.paciente_id = u.usuario_id'
            : '';

        $stmt = $pdo->query(
            "SELECT
                u.usuario_id AS pessoa_id,
                u.nome,
                u.cpf,
                u.email,
                u.telefone,
                u.data_nascimento,
                u.logradouro,
                u.numero,
                u.complemento,
                u.bairro,
                u.cidade,
                u.estado,
                u.cep,
                u.pais,
                {$patientSelect}
             FROM tb_usuario u
             {$patientJoin}
             ORDER BY u.atualizado_em DESC, u.criado_em DESC
             LIMIT 1"
        );

        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name'
        );
        $stmt->execute([':table_name' => $tableName]);
        return (int)$stmt->fetchColumn() > 0;
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

    private function fetchLatestUsuarioId(PDO $pdo): ?int
    {
        $stmt = $pdo->query('SELECT usuario_id FROM tb_usuario ORDER BY atualizado_em DESC, criado_em DESC LIMIT 1');
        $value = $stmt->fetchColumn();
        if ($value === false) {
            return null;
        }

        return (int)$value;
    }

    private function insertUsuario(PDO $pdo, Patient $patient): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO tb_usuario (
                nome, cpf, email, telefone, data_nascimento, criado_em, atualizado_em
             ) VALUES (
                :nome, :cpf, :email, :telefone, :data_nascimento, NOW(), NOW()
             )'
        );

        $stmt->execute($this->usuarioParams($patient));
        return (int)$pdo->lastInsertId();
    }

    private function updateUsuario(PDO $pdo, int $usuarioId, Patient $patient): void
    {
        $stmt = $pdo->prepare(
            'UPDATE tb_usuario SET
                nome = :nome,
                cpf = :cpf,
                email = :email,
                telefone = :telefone,
                data_nascimento = :data_nascimento,
                atualizado_em = NOW()
             WHERE usuario_id = :usuario_id'
        );

        $params = $this->usuarioParams($patient);
        $params[':usuario_id'] = $usuarioId;
        $stmt->execute($params);
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

    private function usuarioParams(Patient $patient): array
    {
        return [
            ':nome' => $patient->nome,
            ':cpf' => $patient->cpf,
            ':email' => $patient->email,
            ':telefone' => $patient->telefone,
            ':data_nascimento' => $patient->dataNascimento,
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
