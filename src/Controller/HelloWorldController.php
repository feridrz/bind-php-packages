<?php
namespace YourProject\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use YourProject\Service\GreetingService;

class HelloWorldController
{
    private $greetingService;

    public function __construct(GreetingService $greetingService)
    {
        $this->greetingService = $greetingService;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute('parameters');
        $name = $parameters['name'] ?? 'World';

        $greeting = $this->greetingService->greet($name);

        $response = new \Laminas\Diactoros\Response();
        $response->getBody()->write($greeting);
        return $response;
    }
}
