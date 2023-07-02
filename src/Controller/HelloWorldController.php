<?php
namespace YourProject\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HelloWorldController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute('parameters');
        $name = $parameters['name'] ?? 'World';

        $response = new \Laminas\Diactoros\Response();
        $response->getBody()->write('Hello, ' . $name . '!');
        return $response;
    }
}
