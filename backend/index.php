<?php
require_once __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/router.php';

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use App\Middleware\CorsMiddleware;

use App\Controllers\CourseController;
use App\Controllers\StudentController;
use App\Controllers\AssessmentController;
use App\Controllers\StudentRecordController;

use App\Services\CourseService;
use App\Services\StudentService;
use App\Services\AssessmentService;
use App\Services\AdvisorService;

use App\Repositories\CourseRepository;
use App\Repositories\StudentRepository;
use App\Repositories\AssessmentRepository;
use App\Repositories\AdvisorRepository;


$dotenv = Dotenv::createImmutable(__DIR__ . '/');
$dotenv->load();


$container = new Container();

// Repositories
$container->set(CourseRepository::class, fn() => new CourseRepository());
$container->set(StudentRepository::class, fn() => new StudentRepository());
$container->set(AssessmentRepository::class, fn() => new AssessmentRepository());

// Services
$container->set(CourseService::class, fn($c) => new CourseService($c->get(CourseRepository::class)));
$container->set(StudentService::class, fn($c) => new StudentService($c->get(StudentRepository::class),$c->get(CourseRepository::class),$c->get(AssessmentRepository::class), getPDO()));
$container->set(AssessmentService::class, fn($c) =>
    new AssessmentService(
        $c->get(AssessmentRepository::class),
        $c->get(StudentRepository::class),
        getPDO()
    )
);


// Controllers
$container->set(CourseController::class, fn($c) => new CourseController($c->get(CourseService::class)));
$container->set(StudentController::class, fn($c) => new StudentController($c->get(StudentService::class)));
$container->set(AssessmentController::class, fn($c) => new AssessmentController($c->get(AssessmentService::class)));
$container->set(StudentRecordController::class, fn($c) => new StudentRecordController($c->get(StudentService::class)));


AppFactory::setContainer($container);
$app = AppFactory::create();


$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
$app->add(new CorsMiddleware());

$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

$routes = require __DIR__ . '/src/router.php';
$routes($app);

// ADVISOR PART
$app->get('/api/advisor/courses', function ($request, $response) {
    $queryParams = $request->getQueryParams();
    $advisorUserId = $queryParams['advisor_user_id'] ?? null;

    if (!$advisorUserId) {
        $response->getBody()->write(json_encode([
            "error" => "Missing advisor_user_id in query parameters."
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $pdo = getPDO();

        $stmt = $pdo->prepare("SELECT DISTINCT course_id FROM advisor_student WHERE advisor_user_id = ?");
        $stmt->execute([$advisorUserId]);
        $courseIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($courseIds)) {
            $response->getBody()->write(json_encode([
                "courses" => [],
                "message" => "No courses found for this advisor."
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = $pdo->prepare("SELECT * FROM course WHERE course_id IN ($placeholders)");
        $stmt->execute($courseIds);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode([
            "courses" => $courses
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "error" => "Database error",
            "details" => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});



$app->get('/api/public/advisor/courses/{course_id}/students', function ($request, $response, $args) {
    $advisorId = $request->getQueryParams()['advisor_user_id'] ?? null;
    $courseId = $args['course_id'] ?? null;

    if (!$advisorId || !$courseId) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Missing advisor_user_id or course_id']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $service = new AdvisorService();
        $students = $service->getAdviseesByCourse($advisorId, $courseId);

        $response->getBody()->write(json_encode([
            'success' => true,
            'advisees' => $students
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/api/public/advisor/courses/{course_id}/students/{student_id}/details', function ($request, $response, $args) {
    $courseId = $args['course_id'] ?? null;
    $studentId = $args['student_id'] ?? null;

    if (!$courseId || !$studentId) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Missing course_id or student_id'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    try {
        $service = new \App\Services\AdvisorService();
        $detail = $service->getStudentDetail($studentId, $courseId);

        $response->getBody()->write(json_encode([
            'success' => true,
            'details' => $detail
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});


$app->get('/api/advisees/{advisor_id}', function ($request, $response, $args) {
    $advisorId = $args['advisor_id'];
    $service = new AdvisorService();
    $stats = $service->getAdviseeStats($advisorId);

    $response->getBody()->write(json_encode($stats));
    return $response->withHeader('Content-Type', 'application/json');
});


$app->get('/api/advisee-performance/{advisor_id}', function ($request, $response, $args) {
    $advisorId = $args['advisor_id'];
    $service = new AdvisorService();
    $data = $service->getAdvisorPerformance($advisorId);

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/advisor/course-wise-stats/{advisor_id}', function ($request, $response, $args) {
    $advisorId = $args['advisor_id'];
    $service = new AdvisorService();
    $data = $service->getCourseWiseStats($advisorId);
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
