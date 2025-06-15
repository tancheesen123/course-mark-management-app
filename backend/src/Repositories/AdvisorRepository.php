<?php
namespace App\Repositories;

require_once __DIR__ . '/../../db.php';

class AdvisorRepository {
    public function getAdvisees($advisorUserId, $courseId) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT s.id AS student_id, s.name, s.matric_number
            FROM students s
            INNER JOIN advisor_student a ON a.student_id = s.id
            WHERE a.advisor_user_id = ? AND a.course_id = ?
        ");
        $stmt->execute([$advisorUserId, $courseId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getStudentMarks($studentId, $courseId) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT sa.mark, ac.weight, ac.type
            FROM student_assessments sa
            JOIN assessment_component ac ON sa.assessment_id = ac.id
            WHERE sa.student_id = ? AND ac.course_id = ?
        ");

        $stmt->execute([$studentId, $courseId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAdvisedCourses($advisorUserId) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.*
            FROM advisor_student a
            JOIN course c ON a.course_id = c.course_id
            WHERE a.advisor_user_id = ?
        ");
        $stmt->execute([$advisorUserId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getStudentComponentMarks($courseId, $studentId) {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT
                s.name AS student_name,
                s.matric_number,
                u.email AS student_email,
                c.course_name,
                ac.name AS component_name,
                ac.weight AS component_max_mark,
                ac.type AS component_type,
                sa.mark AS student_mark,
                sa.feedback
            FROM
                students AS s
            JOIN
                user AS u ON s.user_id = u.user_id
            JOIN
                enrollments AS e ON s.id = e.student_id AND e.course_id = :course_id
            JOIN
                course AS c ON e.course_id = c.course_id
            LEFT JOIN
                assessment_component AS ac ON c.course_id = ac.course_id
            LEFT JOIN
                student_assessments AS sa ON s.id = sa.student_id AND ac.id = sa.assessment_id
            WHERE
                s.id = :student_id AND c.course_id = :course_id
            ORDER BY
                ac.type, ac.name
        ");

        $stmt->execute(['course_id' => $courseId, 'student_id' => $studentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getStudentDetail($studentId, $courseId) {
        $pdo = getPDO();

        $stmt = $pdo->prepare("
            SELECT
                s.name AS student_name,
                s.matric_number,
                u.email AS student_email,
                c.course_name,
                ac.name AS component_name,
                ac.weight AS component_max_mark,
                ac.type AS component_type,
                sa.mark AS student_mark,
                sa.feedback
            FROM
                students AS s
            JOIN
                user AS u ON s.user_id = u.user_id
            JOIN
                enrollments AS e ON s.id = e.student_id AND e.course_id = :course_id
            JOIN
                course AS c ON e.course_id = c.course_id
            LEFT JOIN
                assessment_component AS ac ON c.course_id = ac.course_id
            LEFT JOIN
                student_assessments AS sa ON s.id = sa.student_id AND ac.id = sa.assessment_id
            WHERE
                s.id = :student_id AND c.course_id = :course_id
            ORDER BY
                ac.type, ac.name
        ");
        $stmt->execute(['student_id' => $studentId, 'course_id' => $courseId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAdviseesRaw($advisorUserId): array {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT s.id AS student_id, a.course_id
            FROM advisor_student a
            JOIN students s ON a.student_id = s.id
            WHERE a.advisor_user_id = ?
        ");
        $stmt->execute([$advisorUserId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCourseRiskStats($advisorUserId): array {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT
                c.course_name,
                COUNT(DISTINCT a.student_id) AS total,
                SUM(CASE
                    WHEN r.risk = 'High' THEN 1
                    ELSE 0
                END) AS at_risk
            FROM advisor_student a
            JOIN course c ON a.course_id = c.course_id
            JOIN (
                SELECT
                    s.id AS student_id,
                    ac.course_id,
                    CASE
                        WHEN (
                            SUM(CASE
                                WHEN ac.type = 'final' THEN IFNULL(sa.mark, 0) / ac.weight * 30
                                ELSE IFNULL(sa.mark, 0) / ac.weight * 70
                            END)
                        ) < 50 THEN 'High'
                        ELSE 'Low'
                    END AS risk
                FROM students s
                JOIN advisor_student astu ON astu.student_id = s.id
                JOIN assessment_component ac ON astu.course_id = ac.course_id
                LEFT JOIN student_assessments sa ON sa.assessment_id = ac.id AND sa.student_id = s.id
                WHERE astu.advisor_user_id = ?
                GROUP BY s.id, ac.course_id
            ) r ON r.student_id = a.student_id AND r.course_id = a.course_id
            WHERE a.advisor_user_id = ?
            GROUP BY c.course_name
        ");
        $stmt->execute([$advisorUserId, $advisorUserId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAdviseesWithCourse($advisorUserId) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT s.id AS student_id, a.course_id, c.course_name
            FROM advisor_student a
            JOIN students s ON a.student_id = s.id
            JOIN course c ON a.course_id = c.course_id
            WHERE a.advisor_user_id = ?
        ");
        $stmt->execute([$advisorUserId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
