<?php
namespace App\Services;

use App\Repositories\StudentRepository;

class StudentService
{
    private StudentRepository $studentRepository;

    public function __construct()
    {
        $this->studentRepository = new StudentRepository();
    }

    public function getAllStudents()
    {

        return $this->studentRepository->getAll();
    }

    public function getStudentById($id)
    {

        return $this->studentRepository->getById($id);
    }

    public function getEnrollmentById($id)
    {
        return $this->studentRepository->getEnrollmentById($id);
    }
    // public function getStudentById($id)
    // {
    //     // validation, filtering or other business logic
    //     if (!is_numeric($id)) {
    //         throw new \InvalidArgumentException("ID must be a number");
    //     }

    //     return $this->studentRepository->getById($id);
    // }
}