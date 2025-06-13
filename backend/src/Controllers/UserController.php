<?php
namespace App\Controllers;

use App\Services\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index(Request $request, Response $response, $args)
    {
        // echo "In controller...";
        $students = $this->userService->getAllStudents();

        $response->getBody()->write(json_encode($students));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        error_log("Login data: " . json_encode($data)); // Log the request data for debugging
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            $response->getBody()->write(json_encode(['error' => 'Email and password required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $user = $this->userService->authenticateUser($email, $password);
        error_log("Authenticated user: " . json_encode($user)); // Log the authenticated user for debugging

         // If user is not found or password is incorrect
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $payload = [
            'sub' => $user['user_id'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + 3600 // 1 hour expiry
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $response->getBody()->write(json_encode([
            'token' => $jwt,
            'user' => [
            'id' => $user['user_id'],
            'email' => $user['email'],
            'name' => $user['username'] ?? null, // optional
            'role' => $user['role'] ?? null, // optional
            'advisor_id' => $user['user_id'] // Add advisor_id here, assuming advisor_id is user_id 
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // public function show(Request $request, Response $response, $args)
    // {
    //     $id = $args['id'] ?? null;

    //     try {
    //         $student = $this->studentService->getStudentById($id);
    //         if (!$student) {
    //             $response->getBody()->write(json_encode(['error' => 'Student not found']));
    //             return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    //         }

    //         $response->getBody()->write(json_encode($student));
    //         return $response->withHeader('Content-Type', 'application/json');
    //     } catch (\InvalidArgumentException $e) {
    //         $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
    //         return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    //     }
    // }
}
