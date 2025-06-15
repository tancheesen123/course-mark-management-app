<?php

namespace App\Controllers;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\Services\StudentService; // Assuming basic student operations are here

class StudentController
{
    private StudentService $studentService; // Inject a general StudentService

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    public function index(Request $request, Response $response, $args)
    {
        // echo "In controller...";
        $students = $this->studentService->getAllStudents();

        $response->getBody()->write(json_encode($students));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function findById(Request $request, Response $response, $args)
    {
        // $data = $request->getParsedBody();
        $id = $args['id'] ?? null;
        $students = $this->studentService->getStudentById($id);
        error_log("Student data: " . json_encode($students));
        $response->getBody()->write(json_encode([
            'student_id' => $students[0]['id'] ?? null,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function findEnrollmentById(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $id = $data['id'] ?? null;

        $students = $this->studentService->getStudentById( $id);
        //display student array

        error_log("Student Data: " . print_r($students, true)); // Log the student ID for debugging
        error_log("Student ID: " . $students[0]["id"]); // Log the student ID for debugging

        $studentsEnrollment = $this->studentService->getEnrollmentById($students[0]["id"]);


        $response->getBody()->write(json_encode($studentsEnrollment));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getAvailableStudents(Request $req, Response $res)
{
    $queryParams = $req->getQueryParams();
    $courseId = $queryParams['course_id'] ?? null;

    if (!$courseId) {
        return $res->withStatus(400)->withHeader('Content-Type','application/json')->write(json_encode(['error' => 'Missing course_id']));
    }

    try {
        $students = $this->studentService->getEligibleStudents((int)$courseId);
        $res->getBody()->write(json_encode(['students' => $students]));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (\Throwable $e) {
        $res->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}


}