<?php

use Slim\App;
use App\Controllers\StudentController;
use App\Controllers\UserController;
use App\Middleware\JwtMiddleware;
use App\Controllers\CourseController;
use App\Controllers\AssessmentController;
use App\Controllers\StudentRecordController;
use App\Controllers\FeedbackController;
use App\Middleware\CorsMiddleware;
use App\Controllers\AdvisorController;

require_once __DIR__ . '/Repositories/FeedbackAssessmentRepository.php';
require_once __DIR__ . '/Services/FeedbackService.php';
require_once __DIR__ . '/Controllers/FeedbackController.php';

return function (App $app) {
    // Public route (no JWT required)    $app->add(new CorsMiddleware());
    
    $app->post('/api/login', [UserController::class, 'login']);
    // Protected routes
    $app->group('/api', function ($group) {
        $group->get('/users', [UserController::class, 'index']);
        $group->get('/students', [StudentController::class, 'index']); // if you meant StudentController
        $group->get('/studentsById/{id}', [StudentController::class, 'findById']);
        $group->get('/getStudentEnrollmentById', [StudentController::class, 'findEnrollmentById']);
        $group->get('/courses', [CourseController::class, 'getCoursesByLecturer']);
         $group->get('/getAllCourses', [CourseController::class, 'getAllCourses']);
        // OR: $group->get('/students', [UserController::class, 'index']); if intentional
        // Assessment Routes
        $group->get('/assessments', [AssessmentController::class, 'getAssessments']);
        $group->post('/assessments', [AssessmentController::class, 'createAssessment']);
        $group->put('/assessments/{id}', [AssessmentController::class, 'updateAssessment']);
        $group->patch('/assessments/{id}', [AssessmentController::class, 'updateAssessment']);
        $group->delete('/assessments/{id}', [AssessmentController::class, 'deleteAssessment']);
        $group->get('/assessments/total-weight/{course_id}', [AssessmentController::class, 'getTotalWeight']);

        // Student Record / Marks Routes
        $group->get('/student-records', [StudentRecordController::class, 'getStudentRecords']);
        $group->patch('/student-marks/batch-update', [StudentRecordController::class, 'batchUpdateStudentMarks']);
        $group->post('/student-records/add', [StudentRecordController::class, 'addStudentRecord']);
        $group->post('/studentCourseMark', [StudentRecordController::class, 'findStudentCourseMark']);
        $group->post('/findChart', [StudentRecordController::class, 'findChartData']);
        $group->post('/total-calculation', [StudentRecordController::class, 'getTotalCalculation']);
        $group->get('/lecturer-courses',[StudentRecordController::class, 'getLecturerCourses']);
        $group->post('/exportCSV/{row}',[StudentRecordController::class, 'exportToCsv']);
        
        // ADVISOR PART
        $group->group('/advisor', function ($advisorGroup) {
            $advisorGroup->get('/courses/{course_id}/students', [AdvisorController::class, 'getAdviseesByCourse']);
            $advisorGroup->get('/courses/{course_id}/students/{student_id}/details', [AdvisorController::class, 'getAdviseeDetails']);
        });
        
        $group->get('/available-students', [StudentController::class, 'getAvailableStudents']);

        // Feedback routes
        $group->get('/student/feedback', [FeedbackController::class, 'getFeedback']);
        $group->post('/advisor/feedback', [FeedbackController::class, 'setFeedback']);
    })->add(new JwtMiddleware());
};
   