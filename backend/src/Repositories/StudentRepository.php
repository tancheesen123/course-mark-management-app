<?php
namespace App\Repositories;

class StudentRepository
{

    public function getAll()
    {
        // echo "In repository";
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM Students");
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM Students WHERE user_id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch();

        return $student ?: null;
    }

    public function getEnrollmentById($id)
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE id = ?");
        $stmt->execute([$id]);
        $enrollment = $stmt->fetchAll();

        return $enrollment ?: [];
    }
    // public function getById($id)
    // {
    //     $pdo = getPDO();
    //     $stmt = $pdo->prepare("SELECT * FROM test WHERE id = ?");
    //     $stmt->execute([$id]);
    //     return $stmt->fetch();
    // }
}