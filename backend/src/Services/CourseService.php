<?php

namespace App\Services;

use App\Repositories\CourseRepository;
use PDOException;

class CourseService
{
    private CourseRepository $courseRepository;

    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    public function getAllCourses(): array
    {
        try {
            return $this->courseRepository->getAllCourses();
        } catch (PDOException $e) {
            error_log("Failed to retrieve all courses - " . $e->getMessage());
            throw new \Exception("Failed to retrieve courses. Please try again later.");
        }
    }
}