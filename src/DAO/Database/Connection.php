<?php

declare(strict_types=1);

namespace Prontuario\Database;

use DateTimeImmutable;
use PDO;
use PDOException;
use Throwable;

final class Connection
{
    private const APP_ENV = 'dev'; // 'dev' | 'prod'

    private const DB_HOST = 'bd-prontuario.mysql.uhserver.com';
    private const DB_PORT = 3306;
    private const DB_NAME = 'bd_prontuario';
    private const DB_USER = 'diretoria40';
    private const DB_PASS = 'SENHA_AQUI'; // TROQUE

    public static function open(): PDO
    {
        $logPath = self::logPath();
        $charset = 'utf8mb4';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            self::DB_HOST,
            self::DB_PORT,
            self::DB_NAME,
            $charset
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_TIMEOUT => 8,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
        ];

        try {
            $pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            $pdo->query('SELECT 1')->fetchColumn();

            self::setAppUserContext($pdo);

            self::log($logPath, sprintf(
                'OK: %s:%d/%s user=%s CURRENT_USER=%s',
                self::DB_HOST,
                self::DB_PORT,
                self::DB_NAME,
                self::DB_USER,
                (string)$pdo->query('SELECT CURRENT_USER()')->fetchColumn()
            ));

            return $pdo;
        } catch (PDOException $e) {
            self::log($logPath, sprintf(
                'FAIL: host=%s port=%d db=%s user=%s SQLSTATE=%s MSG=%s',
                self::DB_HOST,
                self::DB_PORT,
                self::DB_NAME,
                self::DB_USER,
                (string)$e->getCode(),
                $e->getMessage()
            ));

            if (self::APP_ENV === 'dev') {
                throw new PDOException(
                    "Falha ao conectar no banco.\nLog: {$logPath}\nSQLSTATE: {$e->getCode()}\nErro: {$e->getMessage()}",
                    0,
                    $e
                );
            }

            throw new PDOException('Falha ao conectar no banco.', 0, $e);
        }
    }

    private static function setAppUserContext(PDO $pdo): void
    {
        try {
            $userId = $_SESSION['id_usuario'] ?? null;
            $userName = $_SESSION['usuario_user'] ?? null;
            $userEmail = $_SESSION['email_user'] ?? null;
            $ipAddr = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $pdo->prepare(
                'SET @app_user_id = :uid,
                     @app_user_nome = :uname,
                     @app_user_email = :uemail,
                     @app_ip = :ip,
                     @app_user_agent = :ua'
            );

            if ($userId === null) {
                $stmt->bindValue(':uid', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':uid', (int)$userId, PDO::PARAM_INT);
            }

            $stmt->bindValue(':uname', $userName);
            $stmt->bindValue(':uemail', $userEmail);
            $stmt->bindValue(':ip', $ipAddr);
            $stmt->bindValue(':ua', $userAgent);
            $stmt->execute();
        } catch (Throwable $e) {
        }
    }

    private static function logPath(): string
    {
        $dir = rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'prontuario_logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . DIRECTORY_SEPARATOR . 'db.log';
    }

    private static function log(string $path, string $message): void
    {
        $entry = sprintf("[%s] %s\n", (new DateTimeImmutable())->format('Y-m-d H:i:s'), $message);
        @file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
    }
}
