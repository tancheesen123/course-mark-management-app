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

    public function getLecturerCourses(int $lecturerId): array
    {
        try {
            return $this->courseRepository->getCoursesByLecturerId($lecturerId);
        } catch (Exception $e) {
            error_log("Error in CourseService getting lecturer courses: " . $e->getMessage());
            throw $e; // Re-throw to be caught by the controller
        }
    }

    public function getCostViaStudent($studentId): array
    {
        try {
            return $this->courseRepository->getCostViaStudentId($studentId);
        } catch (Exception $e) {
            error_log("Error in CourseService getting lecturer courses: " . $e->getMessage());
            throw $e; // Re-throw to be caught by the controller
        }
    }
}