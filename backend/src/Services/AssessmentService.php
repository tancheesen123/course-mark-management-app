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

        try {
            // Start a transaction if multiple operations need to be atomic
            $this->pdo->beginTransaction();

            $assessmentId = $this->assessmentRepository->createAssessment(
                $data['course_id'],
                $data['name'],
                $data['weight'],
                $data['type']
            );

            // Now, insert the record into the student_assessments table for each student in the course
            $students = $this->studentRepository->getStudentsEnrolledInCourse($data['course_id']);

            if (empty($students)) {
                error_log("No students found for course_id: " . $data['course_id'] . " when creating assessment.");
            }

            foreach ($students as $student) {
                $this->studentRepository->initializeStudentAssessment($student['id'], $assessmentId, 0);
            }

            $this->pdo->commit();

            return ['message' => 'Assessment and student records created successfully.'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Service Error: Failed to create assessment - " . $e->getMessage());
            throw new \Exception("Failed to create assessment. " . $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->pdo->rollBack();
            throw $e;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Service Error: " . $e->getMessage());
            throw new \Exception("An unexpected error occurred: " . $e->getMessage());
        }
    }

    public function updateAssessment(int $id, array $data): bool
    {
        if (empty($data['name']) || empty($data['weight']) || empty($data['type'])) {
            throw new \InvalidArgumentException('Missing required fields for assessment update.');
        }

        try {
            return $this->assessmentRepository->updateAssessment(
                $id,
                $data['name'],
                $data['weight'],
                $data['type']
            );
        } catch (PDOException $e) {
            error_log("Service Error: Failed to update assessment - " . $e->getMessage());
            throw new \Exception("Failed to update assessment. Please try again later.");
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
}