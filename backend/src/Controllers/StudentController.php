<?php
namespace App\Controllers;

use App\Services\StudentService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StudentController
{
    private StudentService $studentService;

    public function __construct()
    {
        $this->studentService = new StudentService();
    }

    public function index(Request $request, Response $response, $args)
    {
        // echo "In controller...";
        $students = $this->studentService->getAllStudents();

        $response->getBody()->write(json_encode($students));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // public function show(Request $request, Response $response, $args)
    // {
    //     $id = $args['id'] ?? null;

    //     try {
    //         $student = $this->studentService->getStudentById($id);
    //         if (!$student) {
    //             $response->getBody()->write(json_encode(['error' => 'Student not found']));
    //             return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    //         }

    //         $response->getBody()->write(json_encode($student));
    //         return $response->withHeader('Content-Type', 'application/json');
    //     } catch (\InvalidArgumentException $e) {
    //         $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
    //         return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    //     }
    // }
}
