<?php
namespace App\Services;

class FeedbackService {
    private $repository;

    public function __construct($repository) {
        $this->repository = $repository;
    }

    public function getFeedback($studentId, $courseId) {
        return $this->repository->getFeedback($studentId, $courseId);
    }

    public function setFeedback($studentId, $courseId, $feedback) {
        return $this->repository->setFeedback($studentId, $courseId, $feedback);
    }
}
