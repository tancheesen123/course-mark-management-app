<?php

namespace App\Repositories;

use PDO;
use PDOException;

class StudentRepository
{
    public function getAllStudents(): array
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->query("SELECT * FROM students");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all students: " . $e->getMessage());
            throw $e;
        }
    }

    public function findStudentsByCourseAndAssessment(int $courseId, int $assessmentId): array
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("
                SELECT
                    s.id AS student_id,
                    s.name,
                    s.matric_number,
                    sa.mark
                FROM
                    students s
                JOIN
                    enrollments e ON s.id = e.student_id
                JOIN
                    student_assessments sa ON s.id = sa.student_id
                WHERE
                    e.course_id = ?
                    AND sa.assessment_id = ?
                ORDER BY
                    s.name
            ");
            $stmt->execute([$courseId, $assessmentId]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $students;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function getStudentByMatricNumber(string $matricNumber): ?array
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id, name, matric_number FROM students WHERE matric_number = ?");
            $stmt->execute([$matricNumber]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching student by matric number: " . $e->getMessage());
            throw $e;
        }
    }

    public function createStudent(string $name, string $matricNumber): int
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("INSERT INTO students (name, matric_number) VALUES (?, ?)");
            $stmt->execute([$name, $matricNumber]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating student: " . $e->getMessage());
            throw $e;
        }
    }

    public function enrollStudentInCourse(int $studentId, int $courseId): bool
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
            return $stmt->execute([$studentId, $courseId]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                error_log("Student $studentId already enrolled in course $courseId");
                return false;
            }
            error_log("Error enrolling student in course: " . $e->getMessage());
            throw $e;
        }
    }

    public function isStudentEnrolledInCourse(int $studentId, int $courseId): bool
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$studentId, $courseId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking student enrollment: " . $e->getMessage());
            throw $e;
        }
    }

    public function initializeStudentAssessment(int $studentId, int $assessmentId, int $mark = 0): bool
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("INSERT INTO student_assessments (student_id, assessment_id, mark) VALUES (?, ?, ?)");
            return $stmt->execute([$studentId, $assessmentId, $mark]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                error_log("Student $studentId already has assessment $assessmentId initialized.");
                return false;
            }
            error_log("Error initializing student assessment: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateStudentMark(int $studentId, int $assessmentId, int $mark): bool
{
    try{
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE student_assessments SET mark = ? WHERE student_id = ? AND assessment_id = ?");
    $executed = $stmt->execute([$mark, $studentId, $assessmentId]);

    if ($executed) {
        $rowCount = $stmt->rowCount();
        error_log("UPDATE success: mark=$mark for student_id=$studentId, assessment_id=$assessmentId, affected=$rowCount");
        return $rowCount > 0;
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("UPDATE failed: " . implode(" | ", $errorInfo));
        return false;
    }
    } catch (PDOException $e) {
        error_log("Error updating student mark: " . $e->getMessage());
        throw $e;
    }
}



    public function deleteStudentAssessmentsByAssessmentId(int $assessmentId): bool
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("DELETE FROM student_assessments WHERE assessment_id = ?");
            return $stmt->execute([$assessmentId]);
        } catch (PDOException $e) {
            error_log("Error deleting student assessments by assessment ID: " . $e->getMessage());
            throw $e;
        }
    }

    public function hasStudentAssessmentRecord(int $studentId, int $courseId, string $assessmentName): bool
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("
                SELECT COUNT(sa.id)
                FROM student_assessments sa
                JOIN assessment_component ac ON sa.assessment_id = ac.id
                WHERE sa.student_id = ? AND ac.course_id = ? AND ac.name = ?
            ");
            $stmt->execute([$studentId, $courseId, $assessmentName]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking for existing student assessment record: " . $e->getMessage());
            throw $e;
        }
    }

    public function getStudentsEnrolledInCourse(int $courseId): array
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("
                SELECT s.id
                FROM students s
                JOIN enrollments e ON s.id = e.student_id
                WHERE e.course_id = ?
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting students enrolled in course: " . $e->getMessage());
            throw $e;
        }
    }

    public function getStudentsNotInCourse(int $courseId): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.matric_number
        FROM students s
        JOIN user u ON s.user_id = u.user_id
        WHERE u.role = 2
          AND s.id NOT IN (
              SELECT e.student_id
              FROM enrollments e
              WHERE e.course_id = ?
          )
        ORDER BY s.name
    ");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
