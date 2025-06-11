<?php

namespace Src\Services;

use Src\repositories\CourseRepository;

class CourseService {
    private $courseRepository;

    public function __construct() {
        $this->courseRepository = new CourseRepository();
    }

    public function getCoursesByLecturerId($lecturerId) {
        return $this->courseRepository->findByLecturerId($lecturerId);
    }
}


?>