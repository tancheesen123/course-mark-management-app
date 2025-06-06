<?php

use Slim\App;
use App\Controllers\StudentController;
use App\Controllers\UserController;
use App\Middleware\JwtMiddleware;

return function (App $app) {
    // Public route (no JWT required)
    
    $app->post('/api/login', [UserController::class, 'login']);
    // Protected routes
    $app->group('/api', function ($group) {
        $group->get('/users', [UserController::class, 'index']);
        $group->get('/students', [StudentController::class, 'index']); // if you meant StudentController
        // OR: $group->get('/students', [UserController::class, 'index']); if intentional
    })->add(new JwtMiddleware());
};