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

    public function index(Request $request, Response $response, array $args): Response
    {
        // Example: Get all students
        try {
            $students = $this->studentService->getAllStudents(); // You'd need this method in StudentService
            $response->getBody()->write(json_encode(['students' => $students]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to fetch students', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function findById(Request $request, Response $response, array $args): Response
    {
        // Example: Find student by ID
        $studentId = (int)$request->getQueryParams()['id']; // Assuming ID comes from query param
        // Implement logic to get student by ID from service/repository
        $response->getBody()->write(json_encode(['message' => "Find student by ID: {$studentId}"]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function findEnrollmentById(Request $request, Response $response, array $args): Response
    {
        // Example: Find student enrollment by ID
        $studentId = (int)$request->getQueryParams()['id']; // Assuming ID comes from query param
        // Implement logic to get student enrollment by ID from service/repository
        $response->getBody()->write(json_encode(['message' => "Find student enrollment by ID: {$studentId}"]));
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