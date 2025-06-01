<?php
namespace App\Repositories;

class StudentRepository
{

    public function getAll()
    {
        // echo "In repository";
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM test");
        return $stmt->fetchAll();
    }

    // public function getById($id)
    // {
    //     $pdo = getPDO();
    //     $stmt = $pdo->prepare("SELECT * FROM test WHERE id = ?");
    //     $stmt->execute([$id]);
    //     return $stmt->fetch();
    // }
}