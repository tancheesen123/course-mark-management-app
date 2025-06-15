<?php

use Slim\App;
use App\Controllers\StudentController;
use App\Controllers\UserController;
use App\Middleware\JwtMiddleware;
use Src\Controllers\CourseController;
use App\Controllers\AdvisorController;

return function (App $app) {
    // Public route (no JWT required)
    
    $app->post('/api/login', [UserController::class, 'login']);
    // Protected routes
    $app->group('/api', function ($group) {
        $group->get('/users', [UserController::class, 'index']);
        $group->get('/students', [StudentController::class, 'index']); // if you meant StudentController
        $group->get('/studentsById', [StudentController::class, 'findById']);
        $group->get('/getStudentEnrollmentById', [StudentController::class, 'findEnrollmentById']);
        $group->get('/courses', [CourseController::class, 'getCoursesByLecturer']);
        
        // OR: $group->get('/students', [UserController::class, 'index']); if intentional

        // ADVISOR PART
        $group->group('/advisor', function ($advisorGroup) {
            $advisorGroup->get('/courses/{course_id}/students', [AdvisorController::class, 'getAdviseesByCourse']);
            $advisorGroup->get('/courses/{course_id}/students/{student_id}/details', [AdvisorController::class, 'getAdviseeDetails']);
        });

    })->add(new JwtMiddleware());
};
   