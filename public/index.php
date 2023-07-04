<?php
use DI\ContainerBuilder;
use FastRoute\RouteCollector;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use YourProject\Controller\HelloWorldController;
use YourProject\Middleware\ExampleMiddleware;
use YourProject\Middleware\CheckHeaderMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/di-config.php');
$container = $containerBuilder->build();

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/hello/{name}', [
        'handler' => [HelloWorldController::class, 'sayHello'],
    ]);
    $r->addRoute('GET', '/nia/{name}', [
        'handler' => [HelloWorldController::class, 'sayHello'],
        'middleware' => [
            CheckHeaderMiddleware::class,
            ExampleMiddleware::class
        ]
    ]);
    
});

$request = ServerRequestFactory::fromGlobals();

$httpMethod = $request->getMethod();
$uri = $request->getUri()->getPath();

// Trim query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// Create a ServerRequest object
$serverRequest = \Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // Handle 404
        $response = new \Laminas\Diactoros\Response\JsonResponse(['error' => 'Not Found'], 404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // Handle 405
        $response = new \Laminas\Diactoros\Response\JsonResponse(['error' => 'Method Not Allowed'], 405);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1]['handler'];
        $vars = $routeInfo[2];

        // Initialize the middleware queue
        $middlewareQueue = new \SplQueue();

        // Check if middleware is defined for the route
        if (isset($routeInfo[1]['middleware'])) {
            $middleware = $routeInfo[1]['middleware'];
            foreach ($middleware as $middlewareClass) {
                $middlewareQueue->enqueue(new $middlewareClass());
            }
        }

        // Create a request handler using the middleware queue
        $requestHandler = new \YourProject\Http\RequestHandler($middlewareQueue, $container, $handler, $vars);

        // Get the response from the request handler
        $response = $requestHandler->handle($serverRequest);
        break;
}

$emitter = new SapiEmitter();
$emitter->emit($response);
