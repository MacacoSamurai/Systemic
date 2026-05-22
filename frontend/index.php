<?php
require_once "./libs/router.php";

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$router   = new Router(__DIR__, $basePath);

$router->get("/", function() use ($basePath) {
    http_response_code(200);
    header("Content-Type: text/html; charset=UTF-8");

    echo '<base href="' . $basePath . '/pages/homepage/">';
    include(__DIR__ . '/pages/homepage/index.html');
});

try {
    $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}