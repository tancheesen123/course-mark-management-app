<?php

namespace App\Services;

use App\Repositories\AssessmentRepository;
use App\Repositories\StudentRepository;
use PDO;
use PDOException;

class AssessmentService
{
    private AssessmentRepository $assessmentRepository;
    private StudentRepository $studentRepository;
    private PDO $pdo;

    public function __construct(AssessmentRepository $assessmentRepository, StudentRepository $studentRepository, PDO $pdo)
    {
        $this->assessmentRepository = $assessmentRepository;
        $this->studentRepository = $studentRepository;
        $this->pdo = $pdo;
    }

    public function getAssessments(int $courseId = null): array
    {
        try {
            return $this->assessmentRepository->getAssessments($courseId);
        } catch (PDOException $e) {
            error_log("Error: Failed to retrieve assessments - " . $e->getMessage());
            throw new \Exception("Failed to retrieve assessments. Please try again later.");
        }
    }

    public function getAssessmentsAndMark($courseId,$student_id): array
    {
        try {

            $assessments = $this->assessmentRepository->getAssessmentsMark($courseId, $student_id);
            return $assessments;
        } catch (PDOException $e) {
            error_log("Error: Failed to retrieve assessments - " . $e->getMessage());
            throw new \Exception("Failed to retrieve assessments. Please try again later.");
        }
    }

    public function createAssessment(array $data): array
    {
        if (empty($data['course_id']) || empty($data['name']) || empty($data['type']) || empty($data['weight'])) {
            throw new \InvalidArgumentException('Missing required fields for assessment creation.');
        }

        $courseId = (int)$data['course_id'];
        $name = $data['name'];
        $weight = (int)$data['weight'];
        $type = $data['type'];

        try {
            // Check for existing assessment with the same name for the course
            $existingAssessment = $this->assessmentRepository->getAssessmentByCourseIdAndName($courseId, $name);
            if ($existingAssessment) {
                throw new \InvalidArgumentException('An assessment with this name already exists for this course.', 409); // 409 Conflict
            }

            // Validate weight before creating the assessment
            $this->validateAssessmentWeight($courseId, $type, $weight);

            // Start a transaction if multiple operations need to be atomic
            $this->pdo->beginTransaction();

            $assessmentId = $this->assessmentRepository->createAssessment($courseId, $name, $weight, $type);

            // Fetch all students enrolled in the course and initialize their marks for this new assessment
            $studentsInCourse = $this->studentRepository->getStudentsEnrolledInCourse($courseId);
            foreach ($studentsInCourse as $student) {
                $this->studentRepository->initializeStudentAssessment($student['id'], $assessmentId, 0); // Initialize with 0 marks
            }

            $this->pdo->commit();

            return ['id' => $assessmentId, 'message' => 'Assessment created successfully and student records initialized.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Service Error: Failed to create assessment - " . $e->getMessage());
            throw new Exception("Failed to create assessment. " . $e->getMessage());
        }
    }

    public function updateAssessment(int $id, array $data): bool
    {
        if (empty($data['name']) || empty($data['weight']) || empty($data['type'])) {
            throw new \InvalidArgumentException('Missing required fields for assessment update.');
        }

        $name = $data['name'];
        $weight = (int)$data['weight'];
        $type = $data['type'];

        try {
            // Get current assessment details to use its course_id for weight validation
            $currentAssessment = $this->assessmentRepository->getAssessmentById($id);
            if (!$currentAssessment) {
                throw new \InvalidArgumentException("Assessment with ID {$id} not found.", 404);
            }
            $courseId = (int)$currentAssessment['course_id'];

            // Check for existing assessment with the same name for the course, excluding the current one
            $existingAssessmentByName = $this->assessmentRepository->getAssessmentByCourseIdAndName($courseId, $name);
            if ($existingAssessmentByName && $existingAssessmentByName['id'] != $id) {
                throw new \InvalidArgumentException('An assessment with this name already exists for this course.', 409); // 409 Conflict
            }

            // Validate weight before updating the assessment
            $this->validateAssessmentWeight($courseId, $type, $weight, $id);


            return $this->assessmentRepository->updateAssessment(
                $id,
                $name,
                $weight,
                $type
            );
        } catch (PDOException $e) {
            error_log("Service Error: Failed to update assessment - " . $e->getMessage());
            throw new Exception("Failed to update assessment. Please try again later.");
        }
    }



    public function deleteAssessment(int $id): bool
    {
        try {
            // Start a transaction to ensure both deletes are atomic
            $this->pdo->beginTransaction();

            // First, delete related student_assessments
            $this->studentRepository->deleteStudentAssessmentsByAssessmentId($id);

            // Then, delete the assessment itself
            $deleted = $this->assessmentRepository->deleteAssessment($id);

            $this->pdo->commit();

            return $deleted;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Service Error: Failed to delete assessment - " . $e->getMessage());
            throw new \Exception("Failed to delete assessment. " . $e->getMessage());
        }
    }

    public function getTotalWeight(int $courseId, string $type, ?int $excludeAssessmentId = null): int
    {
        try {
            return $this->assessmentRepository->getTotalWeight($courseId, $type, $excludeAssessmentId);
        } catch (PDOException $e) {
            error_log("Service Error: Failed to retrieve total assessment weight - " . $e->getMessage());
            throw new \Exception("Failed to retrieve total assessment weight. Please try again later.");
        }
    }

    private function validateAssessmentWeight(int $courseId, string $type, int $newWeight, ?int $excludeAssessmentId = null): void
{
    $isFinal = strtolower($type) === 'final';

    $currentTotalWeight = $this->assessmentRepository->getTotalWeight($courseId, $type, $excludeAssessmentId);
    $newOverallTotalWeight = $currentTotalWeight + $newWeight;

    $maxAllowed = $isFinal ? 30 : 70;

    if ($newOverallTotalWeight > $maxAllowed) {
        $label = $isFinal ? "Final Exam" : "Coursework";
        throw new \InvalidArgumentException("{$label} weight cannot exceed {$maxAllowed}%.");
    }
}

    public function generateCsv(array $course): string
    {
        $output = fopen('php://temp', 'r+');

        // Add course info
        fputcsv($output, ['Course Code:', $course['course_code']]);
        fputcsv($output, ['Course Name:', $course['course_name']]);
        fputcsv($output, []); // blank line

        // Header
        $headers = ['Name', 'Matric Number'];
        foreach ($course['assessments'] as $a) {
            $headers[] = "{$a['name']} ({$a['weight']}%)";
        }
        $headers[] = "Total";
        $headers[] = "Grade";
        fputcsv($output, $headers);

        // Data rows
        foreach ($course['students'] as $student) {
            $row = [$student['name'], $student['matric_number']];
            $total = 0;
            foreach ($course['assessments'] as $a) {
                $mark = $student['marks'][$a['id']] ?? 0;
                $total += $mark;
                $row[] = $mark;
            }
            $row[] = number_format($total, 1);
            $row[] = $this->calculateGrade($total);
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function calculateGrade($total): string
    {
        if ($total >= 90) return "A+";
        if ($total >= 80) return "A";
        if ($total >= 75) return "A-";
        if ($total >= 70) return "B+";
        if ($total >= 65) return "B";
        if ($total >= 60) return "B-";
        if ($total >= 55) return "C+";
        if ($total >= 50) return "C";
        if ($total >= 45) return "C-";
        if ($total >= 40) return "D+";
        if ($total >= 35) return "D";
        if ($total >= 30) return "D-";
        return "E";
    }

}