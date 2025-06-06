<?php
require_once __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use App\Middleware\CorsMiddleware;

// $app = AppFactory::create();
// $app->get('/hello', function ($request, $response) {
//     $data = ['message' => 'Hello, World!'];
//     $response->getBody()->write(json_encode($data));
//     return $response->withHeader('Content-Type', 'application/json');
// });

// $app->get('/hello/{name}', function ($request, $response, $args) {
//     $name = $args['name'];
//     $response->getBody()->write("Hello, $name!");
//     return $response;
// });


// $app->post('/person', function ($request, $response) {
//     $data = $request->getParsedBody();
//     $person = json_decode($request->getbody()->getContents(), true);
//     $name = $data['name'] ?? 'Guest';
//     $response->getBody()->write("Hello, ". $person['name'] . "!". " You are " . $person['age'] . " years old.");
//     return $response;
// });


// $app->get('/db-test', function ($request, $response) {
//     $pdo = getPDO();
//     $stmt = $pdo->query("SELECT * FROM test LIMIT 10");
//     $students = $stmt->fetchAll();
//     $response->getBody()->write(json_encode($students));
//     return $response->withHeader('Content-Type', 'application/json');
// });

$dotenv = Dotenv::createImmutable(__DIR__ . '/' ); // adjust path to your project root
$dotenv->load();

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
// $app->add(new CorsMiddleware());
// $app->addErrorMiddleware(true, true, true);

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

$routes = require __DIR__ . '/src/router.php';
$routes($app); 

$app ->run();