<?php

namespace App\Controllers;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\Services\StudentService;
use Exception;

class StudentRecordController
{
    private StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    public function getStudentRecords(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $courseId = $queryParams['course_id'] ?? null;
        $assessmentName = $queryParams['assessment_name'] ?? null;

        if (!$courseId || !$assessmentName) {
            $response->getBody()->write(json_encode(['error' => 'Missing course_id or assessment_name.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $students = $this->studentService->getStudentRecords((int)$courseId, $assessmentName);
            $response->getBody()->write(json_encode(['students' => $students]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) { // Catch specific exceptions thrown by service
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus($e->getCode() ?: 404)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to fetch student records",
                "details" => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function batchUpdateStudentMarks(Request $request, Response $response, array $args): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $courseId = $data['course_id'] ?? null;
        $assessmentName = $data['assessment_name'] ?? null;
        $marksToUpdate = $data['marks'] ?? [];

        try {

            $message = $this->studentService->batchUpdateStudentMarks((int)$courseId, $assessmentName, $marksToUpdate);
            $response->getBody()->write(json_encode(['message' => $message]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus($e->getCode() ?: 400)->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus($e->getCode() ?: 404)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to batch update student marks",
                "details" => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function addStudentRecord(Request $request, Response $response, array $args): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        try {
            $message = $this->studentService->addStudentRecord($data);
            $response->getBody()->write(json_encode(['message' => $message]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus($e->getCode() ?: 400)->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus($e->getCode() ?: 500)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to add student record",
                "details" => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    
}