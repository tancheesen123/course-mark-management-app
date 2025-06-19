<?php
namespace App\Repositories;

class FeedbackAssessmentRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get feedback for a student and course
    public function getFeedback($studentId, $courseId) {
        $stmt = $this->db->prepare('SELECT feedback FROM course_feedback WHERE student_id = ? AND course_id = ? LIMIT 1');
        $stmt->execute([$studentId, $courseId]);
        $row = $stmt->fetch();
        return $row ? $row['feedback'] : null;
    }

    // Set feedback for a student and course
    public function setFeedback($studentId, $courseId, $feedback) {
        $stmt = $this->db->prepare('INSERT INTO course_feedback (student_id, course_id, feedback) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE feedback = VALUES(feedback)');
        return $stmt->execute([$studentId, $courseId, $feedback]);
    }
}
