<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

header('X-Fixture-Server: php-simple-curl');

switch($path){
    case '/health':
        header('Content-Type: text/plain');
        echo 'ok';
        break;

    case '/json':
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => true,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'accept' => $_SERVER['HTTP_ACCEPT'] ?? null,
        ], JSON_THROW_ON_ERROR);
        break;

    case '/text':
        header('Content-Type: text/plain');
        echo 'hello from fixture';
        break;

    case '/redirect':
        http_response_code(302);
        header('Location: /json');
        echo 'redirecting';
        break;

    case '/headers':
        header('Content-Type: text/plain');
        header('Set-Cookie: a=1', false);
        header('Set-Cookie: b=2', false);
        echo 'headers';
        break;

    case '/status/404':
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not_found'], JSON_THROW_ON_ERROR);
        break;

    default:
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'not found';
        break;
}
