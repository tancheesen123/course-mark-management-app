<?php

use Slim\App;
use App\Controllers\StudentController;
use App\Controllers\UserController;
use App\Middleware\JwtMiddleware;
use App\Controllers\CourseController;
use App\Controllers\AssessmentController;
use App\Controllers\StudentRecordController;
use App\Middleware\CorsMiddleware;

// Advisor Part
use App\Controllers\AdvisorStudentController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

return function (App $app) {
    // Public route (no JWT required)    $app->add(new CorsMiddleware());
    
    $app->post('/api/login', [UserController::class, 'login']);
    // Protected routes
    $app->group('/api', function ($group) {
        $group->get('/users', [UserController::class, 'index']);
        $group->get('/students', [StudentController::class, 'index']); // if you meant StudentController
        $group->get('/studentsById', [StudentController::class, 'findById']);
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
        $group->get('/available-students', [StudentController::class, 'getAvailableStudents']);

    })->add(new JwtMiddleware());


    // Advisor Part
    $app->group('/api', function ($group) {
    // Get advisees of a specific advisor along with total and at-risk advisee statistics
    $group->get('/advisees/{advisor_id}', function (Request $request, Response $response, $args) {
        $advisor_id = $args['advisor_id'];

        // Database connection configuration
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=course_mark_management', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) AS total_advisees, 
                    SUM(CASE WHEN as_rel.risk = 'HIGH' THEN 1 ELSE 0 END) AS at_risk_advisees
                FROM advisor_student as_rel
                WHERE as_rel.advisor_id = :advisor_id
            ");
            $stmt->bindParam(':advisor_id', $advisor_id, PDO::PARAM_INT);
            $stmt->execute();
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($counts) {
                $stmt = $pdo->prepare("
                    SELECT u.username AS student_name, as_rel.matric_number, as_rel.gpa, as_rel.semester, as_rel.year, as_rel.risk
                    FROM advisor_student as_rel
                    JOIN user u ON as_rel.student_id = u.user_id
                    WHERE as_rel.advisor_id = :advisor_id
                ");
                $stmt->bindParam(':advisor_id', $advisor_id, PDO::PARAM_INT);
                $stmt->execute();
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $responseData = [
                    'total_advisees' => $counts['total_advisees'],
                    'at_risk_advisees' => $counts['at_risk_advisees'],
                    'students' => $students
                ];

                $response->getBody()->write(json_encode($responseData));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode(['message' => 'No advisees found for this advisor']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'SQL error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
});

};
   