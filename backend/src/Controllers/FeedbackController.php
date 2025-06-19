<?php
namespace App\Controllers;

class FeedbackController {
    private $service;

    public function __construct($service) {
        $this->service = $service;
    }

    // GET /api/student/feedback?student_id=...&course_id=...
    public function getFeedback($request, $response, $args) {
        $studentId = $request->getQueryParams()['student_id'] ?? null;
        $courseId = $request->getQueryParams()['course_id'] ?? null;

        if (!$studentId || !$courseId) {
            $response->getBody()->write(json_encode(['error' => 'Missing parameters']));
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        }

        $feedback = $this->service->getFeedback($studentId, $courseId);
        $response->getBody()->write(json_encode(['feedback' => $feedback]));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    // POST /api/advisor/feedback
    public function setFeedback($request, $response, $args) {
        $data = $request->getParsedBody();
        $studentId = $data['student_id'] ?? null;
        $courseId = $data['course_id'] ?? null;
        $feedback = $data['feedback'] ?? null;

        if (!$studentId || !$courseId || $feedback === null) {
            $response->getBody()->write(json_encode(['error' => 'Missing parameters']));
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        }

        $this->service->setFeedback($studentId, $courseId, $feedback);
        $response->getBody()->write(json_encode(['success' => true]));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }
}
