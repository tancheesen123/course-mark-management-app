<?php

namespace App\Controllers;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\Services\CourseService;
use Exception;

class CourseController
{
    private CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function getAllCourses(Request $request, Response $response, array $args): Response
    {
        try {
            $courses = $this->courseService->getAllCourses();
            $response->getBody()->write(json_encode(["courses" => $courses]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to fetch courses",
                "details" => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getCoursesByLecturer(Request $request, Response $response, array $args): Response
    {
        try {
            $courses = $this->courseService->getAllCourses(); // Or implement lecturer-specific logic
            $response->getBody()->write(json_encode(["courses" => $courses]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Failed to fetch courses for lecturer",
                "details" => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}