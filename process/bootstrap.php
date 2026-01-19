<?php
declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json; charset=utf-8');

function respondSuccess(array $payload = []): void
{
    http_response_code(200);
    echo json_encode(['success' => true] + $payload);
    exit;
}

function respondError(string $message, int $status = 500): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function respondJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
