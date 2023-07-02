<?php
use DI\ContainerBuilder;
use FastRoute\RouteCollector;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use YourProject\Controller\HelloWorldController;
use YourProject\Middleware\ExampleMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/di-config.php');
$container = $containerBuilder->build();

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/hello/{name}', [
        'handler' => HelloWorldController::class
    ]);
});

$request = ServerRequestFactory::fromGlobals();

$httpMethod = $request->getMethod();
$uri = $request->getUri()->getPath();

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::FOUND:
        $handler = $container->get($routeInfo[1]['handler']);
        $middlewares = $routeInfo[1]['middleware'] ?? [];
        $parameters = $routeInfo[2] ?? [];

        $request = $request->withAttribute('parameters', $parameters);

        $middlewarePipe = new class($handler) implements Psr\Http\Server\RequestHandlerInterface {
            private $handler;
            public function __construct($handler) {
                $this->handler = $handler;
            }
            public function handle(Psr\Http\Message\ServerRequestInterface $request): Psr\Http\Message\ResponseInterface {
                return ($this->handler)($request);
            }
        };

        foreach (array_reverse($middlewares) as $middlewareClass) {
            $middleware = $container->get($middlewareClass);
            $middlewarePipe = new class($middleware, $middlewarePipe) implements Psr\Http\Server\RequestHandlerInterface {
                private $middleware;
                private $next;
                public function __construct($middleware, $next) {
                    $this->middleware = $middleware;
                    $this->next = $next;
                }
                public function handle(Psr\Http\Message\ServerRequestInterface $request): Psr\Http\Message\ResponseInterface {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        $response = $middlewarePipe->handle($request);
        break;

    default:
        $response = (new ResponseFactory)->createResponse(404);
        $response->getBody()->write('Not found');
        break;
}

$emitter = new SapiEmitter();
$emitter->emit($response);
