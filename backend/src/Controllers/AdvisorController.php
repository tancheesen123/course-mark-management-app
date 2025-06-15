<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AdvisorService;

class AdvisorController {
    private $advisorService;

    public function __construct() {
        $this->advisorService = new AdvisorService();
    }

    public function getAdviseesByCourse(Request $request, Response $response, $args): Response {
        $courseId = $args['course_id'];
        $jwtPayload = $request->getAttribute('jwt');
        
        if (!$jwtPayload || !isset($jwtPayload->sub)) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json')
                            ->withBody($response->getBody()->write(json_encode(['error' => 'Unauthorized: Missing JWT payload'])));
        }

        $advisorUserId = (int) $jwtPayload->sub;

        try {
            $data = $this->advisorService->getAdviseesByCourse($advisorUserId, $courseId);
            $response->getBody()->write(json_encode(['success' => true, 'advisees' => $data]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to retrieve advisees',
                'details' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getAdvisedCourses(Request $request, Response $response): Response {
        $jwtPayload = $request->getAttribute('jwt');
        if (!$jwtPayload || !isset($jwtPayload->sub)) {
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json')
                ->withBody($response->getBody()->write(json_encode(['error' => 'Unauthorized: Missing JWT payload'])));
        }

        $advisorUserId = (int) $jwtPayload->sub;

        try {
            $data = $this->advisorService->getAdvisedCourses($advisorUserId);
            $response->getBody()->write(json_encode(['success' => true, 'courses' => $data]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to retrieve courses',
                'details' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getAdviseeDetails(Request $request, Response $response, array $args): Response {
        $courseId = $args['course_id'];
        $studentId = $args['student_id'];

        try {
            $details = $this->advisorService->getStudentDetails($courseId, $studentId);
            if (!$details) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Student not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['success' => true, 'details' => $details]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getStudentDetail(Request $request, Response $response, $args): Response {
        $courseId = $args['course_id'] ?? null;
        $studentId = $args['student_id'] ?? null;

        try {
            $detail = $this->service->getStudentDetail($studentId, $courseId);
            $response->getBody()->write(json_encode([
                'success' => true,
                'details' => $detail
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getAdviseeStats(Request $request, Response $response, $args): Response {
        $advisorId = $args['advisor_id'] ?? null;

        if (!$advisorId) {
            $response->getBody()->write(json_encode(['error' => 'Missing advisor_id']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $stats = $this->advisorService->getAdviseeStats($advisorId);
            $response->getBody()->write(json_encode($stats));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to get stats', 'details' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
