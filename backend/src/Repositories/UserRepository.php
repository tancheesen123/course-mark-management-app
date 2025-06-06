<?php
namespace App\Repositories;

class UserRepository
{

    public function getAll()
    {
        // echo "In repository";
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT * FROM user");
        return $stmt->fetchAll();
    }

    public function getByEmail(string $email): ?array
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    // public function getById($id)
    // {
    //     $pdo = getPDO();
    //     $stmt = $pdo->prepare("SELECT * FROM test WHERE id = ?");
    //     $stmt->execute([$id]);
    //     return $stmt->fetch();
    // }
}