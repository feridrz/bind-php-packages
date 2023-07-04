<?php


namespace YourProject\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use DI\Container;

class RequestHandler implements RequestHandlerInterface
{
    private $middlewareQueue;
    private $container;
    private $handler;
    private $vars;

    public function __construct($middlewareQueue, Container $container, $handler, $vars)
    {
        $this->middlewareQueue = $middlewareQueue;
        $this->container = $container;
        $this->handler = $handler;
        $this->vars = $vars;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->middlewareQueue->isEmpty()) {
            list($controller, $method) = $this->handler;
            $controllerInstance = $this->container->get($controller);
            return $controllerInstance->$method($request, $this->vars);
        }

        $middleware = $this->middlewareQueue->dequeue();
        return $middleware->process($request, $this);
    }
}
