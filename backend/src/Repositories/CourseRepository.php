<?php

namespace App\Repositories;

use PDO;
use PDOException;

class CourseRepository
{
    public function getAllCourses(): array
    {
        try {
            $pdo = getPDO();  
            $stmt = $pdo->query("SELECT * FROM course");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all courses: " . $e->getMessage());
            throw $e;
        }
    }

    public function getCourseById(int $courseId): ?array
    {
        try {
            $pdo = getPDO();  
            $stmt = $pdo->prepare("SELECT * FROM course WHERE course_id = ?");
            $stmt->execute([$courseId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching course by ID: " . $e->getMessage());
            throw $e;
        }
    }
}
