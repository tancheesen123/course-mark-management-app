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

    //     return $this->studentRepository->getById($id);
    // }
}