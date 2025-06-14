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

use App\Repositories\CourseRepository;
use App\Repositories\StudentRepository;
use App\Repositories\AssessmentRepository;


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

$app->run();
