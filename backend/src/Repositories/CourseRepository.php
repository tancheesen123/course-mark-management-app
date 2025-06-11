<?php

namespace Src\Repositories;

class CourseRepository {
    private $db;

    public function __construct() {
        $this->db = getPDO();
    }

    public function findByLecturerId($lecturerId) {
        $stmt = $this->db->prepare("SELECT * FROM course WHERE lecturer_id = ?");
        $stmt->execute([$lecturerId]);
        return $stmt->fetchAll();
    }
}





?>