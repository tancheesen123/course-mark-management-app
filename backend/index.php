<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db_test.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->get('/hello', function ($request, $response) {
    $data = ['message' => 'Hello, World!'];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/hello/{name}', function ($request, $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name!");
    return $response;
});


$app->post('/person', function ($request, $response) {
    $data = $request->getParsedBody();
    $person = json_decode($request->getbody()->getContents(), true);
    $name = $data['name'] ?? 'Guest';
    $response->getBody()->write("Hello, ". $person['name'] . "!". " You are " . $person['age'] . " years old.");
    return $response;
});


$app->get('/db-test', function ($request, $response) {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT * FROM test LIMIT 10");
    $students = $stmt->fetchAll();
    $response->getBody()->write(json_encode($students));
    return $response->withHeader('Content-Type', 'application/json');
});

$app ->run();
// phpinfo();