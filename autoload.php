<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Prontuario\\';
    $prefixLen = strlen($prefix);
    if (strncmp($class, $prefix, $prefixLen) !== 0) {
        return;
    }

    $relativeClass = substr($class, $prefixLen);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
