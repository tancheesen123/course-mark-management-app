<?php

namespace App\Controllers;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\Services\StudentService;
use App\Services\AssessmentService;
use Exception;

class StudentRecordController
{
    private StudentService $studentService;
    private AssessmentService $assessmentService;

    public function __construct(StudentService $studentService, AssessmentService $assessmentService)
    {
        $this->studentService = $studentService;
        $this->assessmentService = $assessmentService;
    }

    public function findStudentCourseMark(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $courseId = $data['course_id'] ?? null;
        $student_id = $data['student_id'] ?? null;

        if (!$courseId || !$student_id) {
            $response->getBody()->write(json_encode(['error' => 'Missing course_id or student_id.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $totalMarks = 0;
        $finalExamMarks = 0;
        $totalAssessment = 0;
        $totalAssessmentWeight = 0;
        $totalWeight = 0;
        $finalExamAssessments = [];
        $filteredAssessments = [];
        try {
            $assessments = $this->assessmentService->getAssessmentsAndMark($courseId,$student_id);
            foreach ($assessments as $assessment) {
            $totalMarks += $assessment['mark'];
            $totalWeight += $assessment['weight'];
        if (strtolower($assessment['type']) !== 'final') {
            $totalAssessment += $assessment['mark'];
            $totalAssessmentWeight += $assessment['weight'];
            $filteredAssessments[] = $assessment; // Keep non-final assessments
        } else {
            $finalExamMarks = $assessment['mark'];
            $finalExamAssessments = $assessment;
        }
        // $totalMark += $assessment['mark'];
    }
            
            error_log("totalMarks". $totalMarks); // Log course ID for debugging
            error_log("finalExamMarks". $finalExamMarks); // Log course ID for debugging
            error_log("Assessments: " . json_encode($assessments)); // Log assessments for debugging
            // $students = $this->studentService->getStudentRecords((int)$courseId, $assessmentName);
            $response->getBody()->write(json_encode(
                ['assessments' => $filteredAssessments,
                'finalExamAssessments' => $finalExamAssessments,
                'total_weight' => $totalWeight,
                'total_marks' => $totalMarks,
                'total_assessment_marks' => $totalAssessment,
                'total_assessment_weight' => $totalAssessmentWeight,
                'final_exam_marks' => $finalExamMarks,
            ]));


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