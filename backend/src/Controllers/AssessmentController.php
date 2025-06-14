<?php

namespace App\Controllers;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\Services\AssessmentService;
use Exception;

class AssessmentController
{
    private AssessmentService $assessmentService;

    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    public function getAssessments(Request $request, Response $response, array $args): Response
    {
        $queryParams = $request->getQueryParams();
        $courseId = isset($queryParams['course_id']) ? (int)$queryParams['course_id'] : null;

        try {
            $assessments = $this->assessmentService->getAssessments($courseId);
            $response->getBody()->write(json_encode(['assessment_component' => $assessments]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to fetch assessments",
                "details" => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createAssessment(Request $request, Response $response, array $args): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        try {
            $result = $this->assessmentService->createAssessment($data);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to create assessment",
                "details" => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateAssessment(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        try {
            $this->assessmentService->updateAssessment($id, $data);
            $response->getBody()->write(json_encode(['message' => 'Assessment successfully updated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to update assessment",
                "details" => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function deleteAssessment(Request $request, Response $response, array $args): Response
    {
        $assessmentId = (int)$args['id'];

        try {
            $this->assessmentService->deleteAssessment($assessmentId);
            $response->getBody()->write(json_encode(['message' => 'Assessment deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to delete assessment",
                "details" => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}