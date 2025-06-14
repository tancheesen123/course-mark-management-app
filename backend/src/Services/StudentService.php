<?php

namespace App\Services;

use App\Repositories\StudentRepository;
use App\Repositories\CourseRepository;
use App\Repositories\AssessmentRepository;
use PDO;
use PDOException;

class StudentService
{
    private StudentRepository $studentRepository;
    private CourseRepository $courseRepository;
    private AssessmentRepository $assessmentRepository;
    private PDO $pdo;

    public function __construct(
        StudentRepository $studentRepository,
        CourseRepository $courseRepository,
        AssessmentRepository $assessmentRepository,
        PDO $pdo
    ) {
        $this->studentRepository = $studentRepository;
        $this->courseRepository = $courseRepository;
        $this->assessmentRepository = $assessmentRepository;
        $this->pdo = $pdo;
    }

    public function getStudentRecords(int $courseId, string $assessmentName): array
    {
<<<<<<< Updated upstream
        // you can add any business logic here if needed
        // echo "In Service...";
        return $this->studentRepository->getAll();
    }

    // public function getStudentById($id)
    // {
    //     // validation, filtering or other business logic
    //     if (!is_numeric($id)) {
    //         throw new \InvalidArgumentException("ID must be a number");
    //     }
=======
        try {
            $assessment = $this->assessmentRepository->getAssessmentByCourseIdAndName($courseId, $assessmentName);

            if (!$assessment) {
                throw new \RuntimeException('Assessment not found for the given course and name.', 404);
            }

            return $this->studentRepository->findStudentsByCourseAndAssessment($courseId, $assessment['id']);
        } catch (PDOException $e) {
            throw new \Exception("Failed to retrieve student records. Please try again later.");
        }
    }

    public function batchUpdateStudentMarks(int $courseId, string $assessmentName, array $marksToUpdate): string
    {
        if (!is_array($marksToUpdate)) {
            throw new \InvalidArgumentException('Invalid marks data provided.');
        }

        $pdo = getPDO(); // ✅ use global function from db.php

        try {
            $pdo->beginTransaction();

            $assessment = $this->assessmentRepository->getAssessmentByCourseIdAndName($courseId, $assessmentName);

            if (!$assessment) {
                throw new \RuntimeException('Assessment not found for the given course and name.', 404);
            }

            $assessmentId = $assessment['id'];
            $assessmentWeight = $assessment['weight'];

            foreach ($marksToUpdate as $markData) {
                $studentId = $markData['student_id']?? null;
                $mark = $markData['mark']?? null;

                if ($studentId === null || $mark === null || !is_numeric($mark) || $mark < 0 || $mark > $assessmentWeight) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw new \InvalidArgumentException(
                        'Invalid mark value for student_id: ' . $studentId . '. Mark must be non-negative and not exceed assessment weight (' . $assessmentWeight . ').',
                        400
                    );
                }

                $this->studentRepository->updateStudentMark($studentId, $assessmentId, $mark);
            }
            return 'Marks updated successfully.';
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Re-throw the specific exception if it's already a RuntimeException or InvalidArgumentException
            if ($e instanceof \RuntimeException || $e instanceof \InvalidArgumentException) {
                throw $e;
            }
            throw new \Exception("Failed to batch update student marks. " . $e->getMessage());
        }
    }

    public function addStudentRecord(array $data): string
    {
        $name = $data['name'] ?? null;
        $matricNumber = $data['matric_number'] ?? null;
        $courseId = $data['course_id'] ?? null;
        $assessmentName = $data['assessment_name'] ?? null;
        $mark = $data['mark'] ?? null;
>>>>>>> Stashed changes

        if (empty($name) || empty($matricNumber) || empty($courseId) || empty($assessmentName) || $mark === null || !is_numeric($mark)) {
            throw new \InvalidArgumentException('Missing or invalid required fields (name, matric_number, mark, course_id, assessment_name).', 400);
        }

        $pdo = getPDO(); // ✅ use global function from db.php

        try {
            $pdo->beginTransaction();

            $student = $this->studentRepository->getStudentByMatricNumber($matricNumber);
            $studentId = $student['id'] ?? $this->studentRepository->createStudent($name, $matricNumber);

            if ($student && $this->studentRepository->hasStudentAssessmentRecord($studentId, $courseId, $assessmentName)) {
                throw new \RuntimeException('A record for this student in this assessment already exists. Use the "Edit" function to change marks.', 409);
            }

            $currentAssessment = $this->assessmentRepository->getAssessmentByCourseIdAndName($courseId, $assessmentName);

            if (!$currentAssessment) {
                throw new \RuntimeException('Current assessment not found.', 404);
            }

            $currentAssessmentId = $currentAssessment['id'];
            $assessmentWeight = $currentAssessment['weight'];

            if ($mark < 0 || $mark > $assessmentWeight) {
                throw new \InvalidArgumentException('Invalid mark. Mark must be between 0 and ' . $assessmentWeight . '.', 400);
            }

            $allCourses = $this->courseRepository->getAllCourses();
            foreach ($allCourses as $c) {
                $cId = $c['course_id'];
                if (!$this->studentRepository->isStudentEnrolledInCourse($studentId, $cId)) {
                    $this->studentRepository->enrollStudentInCourse($studentId, $cId);
                }
                $courseAssessments = $this->assessmentRepository->getAssessments($cId);
                foreach ($courseAssessments as $a) {
                    $aId = $a['id'];
                    $this->studentRepository->initializeStudentAssessment($studentId, $aId, 0);
                }
            }

            $this->studentRepository->updateStudentMark($studentId, $currentAssessmentId, $mark);

            $pdo->commit();
            return 'Student record added/updated successfully. Student enrolled in all courses and relevant assessments initialized.';
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
