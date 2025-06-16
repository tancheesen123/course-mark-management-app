<?php
namespace App\Services;

use App\Repositories\AdvisorRepository;

class AdvisorService {
    private $repo;

    public function __construct() {
        $this->repo = new AdvisorRepository();
    }

    public function getAdviseesByCourse($advisorUserId, $courseId) {
        $advisees = $this->repo->getAdvisees($advisorUserId, $courseId);

        foreach ($advisees as &$student) {
            $marks = $this->repo->getStudentMarks($student['student_id'], $courseId);
            $student['total_mark'] = $this->calculateTotalMark($marks);
            $student['gpa'] = $this->calculateGPA($student['total_mark']);
            $student['risk'] = $this->determineRisk($student['gpa']);
        }
        return $advisees;
    }

    public function getAdvisedCourses($advisorUserId) {
        return $this->repo->getAdvisedCourses($advisorUserId);
    }

    private function calculateTotalMark(array $assessments): float {
    $total = 0;

    foreach ($assessments as $row) {
        $mark = (float) ($row['mark'] ?? 0);
        $total += $mark;
    }

    return round($total, 2);
}



    private function calculateGPA(float $totalMark): float {
        if ($totalMark >= 90) return 4.00;
        if ($totalMark >= 80) return 4.00;
        if ($totalMark >= 75) return 3.67;
        if ($totalMark >= 70) return 3.33;
        if ($totalMark >= 65) return 3.00;
        if ($totalMark >= 60) return 2.67;
        if ($totalMark >= 55) return 2.33;
        if ($totalMark >= 50) return 2.00;
        if ($totalMark >= 45) return 1.67;
        if ($totalMark >= 40) return 1.33;
        if ($totalMark >= 35) return 1.00;
        if ($totalMark >= 30) return 0.67;
        return 0.00;
    }

    private function determineRisk(float $gpa): string {
        return $gpa < 2.0 ? 'High' : 'Low';
    }

    public function getStudentDetail($studentId, $courseId) {
            $rows = $this->repo->getStudentDetail($studentId, $courseId);

            if (!$rows) {
                throw new \Exception("Student details not found");
            }

            $components = [];
            foreach ($rows as $row) {
                if ($row['component_name']) {
                    $components[] = [
                        'name' => $row['component_name'],
                        'type' => $row['component_type'],
                        'max_mark' => $row['component_max_mark'],
                        'mark' => $row['student_mark'],
                        'feedback' => $row['feedback'],
                    ];
                }
            }

            $totalMark = $this->calculateTotalMark($components);
            $gpa = $this->calculateGPA($totalMark);
            $risk = $this->determineRisk($gpa);

            return [
                'name' => $rows[0]['student_name'],
                'matric_number' => $rows[0]['matric_number'],
                'email' => $rows[0]['student_email'],
                'course_name' => $rows[0]['course_name'],
                'components' => $components,
                'total_mark' => round($totalMark, 2),
                'gpa' => round($gpa, 2),
                'risk' => $risk,
            ];
        }

        public function getAdviseeStats($advisorUserId): array {
        $studentsRaw = $this->repo->getAdviseesRaw($advisorUserId);

        $uniqueStudentIds = [];
        $atRiskCount = 0;

        foreach ($studentsRaw as $row) {
            $studentId = $row['student_id'];
            $courseId = $row['course_id'];

            if (!in_array($studentId, $uniqueStudentIds)) {
                $uniqueStudentIds[] = $studentId;

                $marks = $this->repo->getStudentMarks($studentId, $courseId);
                $total = $this->calculateTotalMark($marks);
                $gpa = $this->calculateGPA($total);

                if ($gpa < 2.0) {
                    $atRiskCount++;
                }
            }
        }

        return [
            'total_advisees' => count($uniqueStudentIds),
            'at_risk_advisees' => $atRiskCount
        ];
    }

    public function getAdvisorPerformanceByCourse($advisorUserId) {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT c.course_name, a.course_id, a.student_id
            FROM advisor_student a
            JOIN course c ON a.course_id = c.course_id
            WHERE a.advisor_user_id = ?
        ");
        $stmt->execute([$advisorUserId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $courseGroups = [];

        foreach ($rows as $row) {
            $cid = $row['course_id'];
            if (!isset($courseGroups[$cid])) {
                $courseGroups[$cid] = [
                    'course_name' => $row['course_name'],
                    'student_ids' => []
                ];
            }
            $courseGroups[$cid]['student_ids'][] = $row['student_id'];
        }

        $result = [];

        foreach ($courseGroups as $cid => $info) {
            $studentIds = array_unique($info['student_ids']);
            $riskCount = 0;

            foreach ($studentIds as $sid) {
                $marks = $this->repo->getStudentMarks($sid, $cid);
                $gpa = $this->calculateGPA($this->calculateTotalMark($marks));
                if ($gpa < 2.0) $riskCount++;
            }

            $result[] = [
                'course_name' => $info['course_name'],
                'total' => count($studentIds),
                'risk' => $riskCount
            ];
        }

        return $result;
    }

    public function getPerformanceStats($advisorId) {
        return $this->repo->getCourseRiskStats($advisorId);
    }

    public function getAdvisorPerformance($advisorUserId): array {
        $rawAdvisees = $this->repo->getAdviseesWithCourse($advisorUserId);
        $grouped = [];

        foreach ($rawAdvisees as $row) {
            $studentId = $row['student_id'];
            $courseId = $row['course_id'];
            $courseName = $row['course_name'];

            $marks = $this->repo->getStudentMarks($studentId, $courseId);
            $total = $this->calculateTotalMark($marks);
            $gpa = $this->calculateGPA($total);
            $risk = $this->determineRisk($gpa);

            if (!isset($grouped[$courseName])) {
                $grouped[$courseName] = ['total' => 0, 'at_risk' => 0];
            }

            $grouped[$courseName]['total'] += 1;
            if ($risk === 'High') {
                $grouped[$courseName]['at_risk'] += 1;
            }
        }

        $result = [];
        foreach ($grouped as $courseName => $data) {
            $result[] = [
                'course_name' => $courseName,
                'total' => $data['total'],
                'at_risk' => $data['at_risk']
            ];
        }

        return $result;
    }

    public function getCourseWiseStats($advisorUserId): array {
        $raw = $this->repo->getAdviseesWithCourse($advisorUserId);
        $courseStats = [];

        foreach ($raw as $row) {
            $studentId = $row['student_id'];
            $courseId = $row['course_id'];
            $courseName = $row['course_name'];

            $marks = $this->repo->getStudentMarks($studentId, $courseId);
            $total = $this->calculateTotalMark($marks);
            $gpa = $this->calculateGPA($total);
            $risk = $this->determineRisk($gpa);

            if (!isset($courseStats[$courseName])) {
                $courseStats[$courseName] = ['course_id' => $courseId, 'total' => 0, 'at_risk' => 0];
            }

            $courseStats[$courseName]['total'] += 1;
            if ($risk === 'High') {
                $courseStats[$courseName]['at_risk'] += 1;
            }
        }

        $result = [];
        foreach ($courseStats as $name => $data) {
            $result[] = [
                'course_name' => $name,
                'course_id' => $data['course_id'],
                'total_advisees' => $data['total'],
                'at_risk_advisees' => $data['at_risk']
            ];
        }

        return $result;
    }

    public function getAverageComponentStats($courseId): array {
        return $this->repo->getAverageComponentMarks($courseId);
    }

    public function getStudentRankingList($courseId): array {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT s.id AS student_id
            FROM students s
            JOIN enrollments e ON s.id = e.student_id
            WHERE e.course_id = ?
        ");
        $stmt->execute([$courseId]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $ranking = [];
        foreach ($students as $s) {
            $marks = $this->repo->getStudentMarks($s['student_id'], $courseId);
            $total = $this->calculateTotalMark($marks);
            $ranking[] = [
                'student_id' => $s['student_id'],
                'total_mark' => $total
            ];
        }

        return $ranking;
    }
}
