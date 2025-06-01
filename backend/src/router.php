<?php

use Slim\App;
use App\Controllers\StudentController;

return function (App $app) {
    $app->get('/students', [StudentController::class, 'index']);
    // $app->get('/students/{id}', [StudentController::class, 'show']);
};