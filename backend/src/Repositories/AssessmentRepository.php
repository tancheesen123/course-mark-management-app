<?php

namespace App\Repositories;

use PDO;
use PDOException;

class AssessmentRepository
{
    public function getAssessments(int $courseId = null): array
    {
        try {
            $pdo = getPDO();
            if ($courseId) {
                $stmt = $pdo->prepare("SELECT * FROM assessment_component WHERE course_id = ?");
                $stmt->execute([$courseId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM assessment_component");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching assessments: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAssessmentsMark($courseId, $student_id): array
    {
        try {
            $pdo = getPDO();
            if ($courseId) {
                $stmt = $pdo->prepare("
                SELECT 
                    s.id AS assessment_id,
                    s.course_id,
                    s.name,
                    s.weight,
                    s.type,
                    e.mark
                FROM 
                    assessment_component s
                JOIN 
                    student_assessments e ON s.id = e.assessment_id
                WHERE 
                    course_id = ?
                    AND e.student_id = ?

                ");
                $stmt->execute([$courseId, $student_id]);
            } else {
                $stmt = $pdo->query("SELECT * FROM assessment_component");
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching assessments: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAssessmentById(int $id): ?array
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT * FROM assessment_component WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching assessment by ID: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAssessmentByStudentId(int $id): ?array
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT * FROM assessment_component WHERE student_id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching assessment by ID: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAssessmentByCourseIdAndName(int $courseId, string $assessmentName): ?array
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id, weight FROM assessment_component WHERE course_id = ? AND name = ?");
            $stmt->execute([$courseId, $assessmentName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
            return $result;
        } else {
            return null; // Returns null if no row is found
        }


        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function createAssessment(int $courseId, string $name, int $weight, string $type): int
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("INSERT INTO assessment_component (course_id, name, weight, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$courseId, $name, $weight, $type]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating assessment: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateAssessment(int $id, string $name, int $weight, string $type): bool
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("UPDATE assessment_component SET name = ?, weight = ?, type = ? WHERE id = ?");
            return $stmt->execute([$name, $weight, $type, $id]);
        } catch (PDOException $e) {
            error_log("Error updating assessment: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteAssessment(int $id): bool
    {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("DELETE FROM assessment_component WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting assessment: " . $e->getMessage());
            throw $e;
        }
    }

   public function getTotalWeight(int $courseId, ?string $type = null, ?int $excludeAssessmentId = null): int {
    try {
        $pdo = getPDO();
        $params = [$courseId];
        $sql = "SELECT SUM(weight) FROM assessment_component WHERE course_id = ?";

        if ($type === 'final') {
            $sql .= " AND type = 'final'";
        } else {
            $sql .= " AND type != 'final'";
        }

        if ($excludeAssessmentId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeAssessmentId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch(PDOException $e) {
        error_log("Error fetching total weight: " . $e->getMessage());
        throw $e;
    }
}

}
