<?php
namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class JwtMiddleware
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Unauthorized: Missing or malformed Authorization header']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $jwt = $matches[1];

        $jwtSecret = $_ENV['JWT_SECRET'] ?? null;
        if (!$jwtSecret) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Server configuration error: JWT secret not set']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        try {
            $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
            // Pass the decoded token data to the request attributes for downstream usage
            $request = $request->withAttribute('jwt', $decoded);
            return $handler->handle($request);
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Invalid token', 'message' => $e->getMessage()]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
