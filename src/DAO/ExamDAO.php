<?php
declare(strict_types=1);

namespace Prontuario\DAO;

use PDO;
use Prontuario\Model\Exam;

final class ExamDAO extends BaseDAO
{
    public function save(Exam $exam): int
    {
        $params = $this->definitionParams($exam);
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO exame (
                nome, descricao, unidade, referencia_min, referencia_max, tipo,
                laboratorio, frequencia, observacoes, data_realizacao, slug, criado_em
             ) VALUES (
                :nome, :descricao, :unidade, :referencia_min, :referencia_max, :tipo,
                :laboratorio, :frequencia, :observacoes, :data_realizacao, :slug, NOW()
             )'
        );
        $stmt->execute($params);
        return (int) $pdo->lastInsertId();
    }

    public function listDefinitions(): array
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->query(
            'SELECT
                exame_id,
                nome,
                tipo,
                unidade,
                laboratorio,
                referencia_min,
                referencia_max,
                frequencia,
                observacoes,
                data_realizacao,
                criado_em,
                slug
             FROM exame
             ORDER BY nome ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySlug(string $slug): ?array
    {
        if ($slug === '') {
            return null;
        }
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare('SELECT * FROM exame WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function upsertDefinition(Exam $exam): int
    {
        if ($exam->id !== null) {
            $this->updateDefinition($exam, $exam->id);
            return $exam->id;
        }

        $slug = $exam->slug ?: Exam::normalizeSlug($exam->nome);
        if ($slug === '') {
            throw new \RuntimeException('Nome do exame é obrigatório.');
        }

        $existing = $this->findBySlug($slug);
        if ($existing !== null) {
            $this->updateDefinition($exam, (int)$existing['exame_id']);
            return (int)$existing['exame_id'];
        }

        $exam->slug = $slug;
        return $this->save($exam);
    }

    private function updateDefinition(Exam $exam, int $id): void
    {
        $params = $this->definitionParams($exam);
        $params[':exame_id'] = $id;

        $pdo = $this->getConnection();
        $stmt = $pdo->prepare(
            'UPDATE exame SET
                nome = :nome,
                descricao = :descricao,
                unidade = :unidade,
                referencia_min = :referencia_min,
                referencia_max = :referencia_max,
                tipo = :tipo,
                laboratorio = :laboratorio,
                frequencia = :frequencia,
                observacoes = :observacoes,
                data_realizacao = :data_realizacao,
                slug = :slug
             WHERE exame_id = :exame_id'
        );
        $stmt->execute($params);
    }

    private function definitionParams(Exam $exam): array
    {
        $slug = $exam->slug ?: Exam::normalizeSlug($exam->nome);
        return [
            ':nome' => $exam->nome,
            ':descricao' => $exam->descricao ?? $exam->laboratorio,
            ':unidade' => $exam->unidade,
            ':referencia_min' => $exam->referenciaMin,
            ':referencia_max' => $exam->referenciaMax,
            ':tipo' => $exam->tipo,
            ':laboratorio' => $exam->laboratorio,
            ':frequencia' => $exam->frequencia,
            ':observacoes' => $exam->observacoes,
            ':data_realizacao' => $exam->dataRealizacao,
            ':slug' => $slug,
        ];
    }
}
