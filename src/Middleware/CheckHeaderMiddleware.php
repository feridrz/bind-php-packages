<?php
namespace YourProject\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class CheckHeaderMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check if the header 'X-Specific-Header' exists in the request
        if (!$request->hasHeader('X-Specific-Header')) {
            // If the header is not present, return a 400 Bad Request response
            return new JsonResponse(['error' => 'X-Specific-Header is required'], 400);
        }

        // If the header is present, pass the request to the next middleware or handler
        return $handler->handle($request);
    }
}
