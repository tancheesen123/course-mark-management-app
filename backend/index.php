<?php
require_once __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/router.php';
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
$app->add(new CorsMiddleware());
// $app->addErrorMiddleware(true, true, true);

$app->options('/api/assessments', function($request, $response){
    return $response;
});

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

$app->get('/api/getAllCourses', function($request, $response) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM course");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode([
            "courses" => $courses
        ]));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/api/assessments', function($request, $response, $args){
    // Get all query parameters
    $queryParams = $request->getQueryParams();
    
    // Extract course_id from query parameters
    $courseId = isset($queryParams['course_id']) ? $queryParams['course_id'] : null;
    
    $pdo = getPDO();
    
    // If course_id is provided, filter the results by course_id
    if ($courseId) {
        $stmt = $pdo->prepare("SELECT * FROM assessment_component WHERE course_id = ?");
        $stmt->execute([$courseId]);
    } else {
        // If no course_id is provided, fetch all assessments
        $stmt = $pdo->query("SELECT * FROM assessment_component");
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($data) {
        $response->getBody()->write(json_encode(['assessment_component' => $data]));
    } else {
        $response->getBody()->write(json_encode(['error' => 'Assessment not found'])); 
    }

    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/assessments', function($request, $response){
    $data = json_decode($request->getBody()->getContents(), true);
    if(empty($data['course_id']) || empty($data['name']) || empty($data['type']) || empty($data['weight'])){
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withStatus(400)->withHeader('Content-Type','application/json');
    }
    if(!isset($data['course_id']) || !is_numeric($data['course_id'])) {
        $response->getBody()->write(json_encode(['error' => 'Invalid course_id']));
        return $response->withStatus(400)->withHeader('Content-Type','application/json');
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO assessment_component (course_id, name, weight, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data['course_id'], $data['name'], $data['weight'], $data['type']]);
    $response->getBody()->write(json_encode(['message' => 'Assessment is successfully added.']));
    return $response->withHeader('content-type','application/json')->withStatus(200);
});

$app->put('/api/assessments/{id}', function($request, $response, $args){
    $data = json_decode($request->getBody()->getContents(), true);
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE assessment_component SET name = ?, weight = ?, type = ? WHERE id = ?");
    $stmt->execute([$data['name'], $data['weight'], $data['type'], $args['id']]);
    $response->getBody()->write(json_encode(['message' => 'Assessment successful updated']));
    return $response->withHeader('content-type', 'application/json')->withStatus(201);
});

$app->patch('/api/assessments/{id}', function ($request, $response, $args) {
    $data = json_decode($request->getBody()->getContents(), true);

    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE assessment_component SET name = ?, weight = ?, type = ? WHERE id = ?");
    $stmt->execute([$data['name'], $data['weight'], $data['type'], $args['id']]);

    $response->getBody()->write(json_encode(['message' => 'Assessment successfully updated']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->post('/api/student-assessments', function($request, $response) {
    $data = json_decode($request->getBody()->getContents(), true);

    if (empty($data['student_id']) || empty($data['assessment_id']) || empty($data['mark'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, assessment_id, mark) VALUES (?, ?, ?)");
    $stmt->execute([$data['student_id'], $data['assessment_id'], $data['mark']]);

    $response->getBody()->write(json_encode(['message' => 'Marks added successfully']));
    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
});

$app->delete('/api/assessments/{id}', function($request, $response, $args) {
    $assessmentId = $args['id'];
    
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM assessment_component WHERE id = ?");
    $stmt->execute([$assessmentId]);

    $response->getBody()->write(json_encode(['message' => 'Assessment deleted successfully']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});






$app ->run();