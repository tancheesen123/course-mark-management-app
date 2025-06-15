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

    public function getCoursesByLecturerId(int $lecturerId): array
    {
        try {
            $pdo = getPDO(); // Assuming getPDO() is globally accessible or passed via constructor
            $stmt = $pdo->prepare("SELECT * FROM course WHERE lecturer_id = ?");
            $stmt->execute([$lecturerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching courses by lecturer ID: " . $e->getMessage());
            throw new \RuntimeException("Failed to fetch courses: " . $e->getMessage(), 0, $e);
        }
    }

    public function getCostViaStudentId($student_id): array
    {
        try {
            $pdo = getPDO();
            if ($student_id) {
                $stmt = $pdo->prepare("
                SELECT 
                    c.course_id,
                    c.course_code,
                    c.course_name,
                    c.section,
                    c.semester,
                    c.year,
                    c.lecturer_id
                FROM 
                    enrollments e
                JOIN 
                    course c ON e.course_id = c.course_id
                WHERE 
                    e.student_id = ?
            ");
                $stmt->execute([$student_id]);
            } else {
                $stmt = $pdo->query("SELECT * FROM assessment_component");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching assessments: " . $e->getMessage());
            throw $e;
        }
    }
}
