<?php
declare(strict_types=1);

$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_NAME'] = 'oficina_db_test';
$_ENV['DB_USER'] = 'test';
$_ENV['DB_PASS'] = 'test';

// O autoloader do Composer carrega tudo via PSR-4 â€” nÃ£o hÃ¡ require_once manual.
require_once __DIR__ . '/../vendor/autoload.php';

