<?php
namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function getAllStudents()
    {
        // you can add any business logic here if needed
        // echo "In Service...";
        return $this->userRepository->getAll();
    }

    public function authenticateUser(string $email, string $password): ?array
    {
        $user = $this->userRepository->getByEmail($email);

        if (!$user) {
            return null;
        }
       
    // Debugging (optional)
    error_log("Input password: " . $password);
    error_log("Stored hashed password: " . $user['password']);

    // // Use password_verify to check plain-text password against hash
    if (!password_verify($password, $user['password'])) {
        error_log("Password verification failed for user: " . $email);
        return null;
    }else{
        error_log("Password verified successfully.");
    }

    // if ($password !== $user['password']) {
    //     error_log("Password verification failed for user: " . $email);
    //     return null;
    // }else{
    //     error_log("Password verified successfully.");

    // }

    error_log("User authenticated: " . json_encode($user));
        return $user;
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