<?php
namespace Src\Controllers;

use Src\Services\CourseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CourseController {
    private $courseService;

    public function construct() {
        $this->courseService = new CourseService();
    }

     public function getCoursesByLecturer(Request $request, Response $response, $args): Response {
        $queryParams = $request->getQueryParams();

        if (!isset($queryParams['lecturer_id'])) {
            $response->getBody()->write(json_encode(["error" => "Missing lecturer_id"]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $courses = $this->courseService->getCoursesByLecturerId($queryParams['lecturer_id']);
        $response->getBody()->write(json_encode($courses));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

?>