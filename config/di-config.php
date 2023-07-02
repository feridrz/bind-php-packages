<?php
use YourProject\Controller\HelloWorldController;
use YourProject\Service\GreetingService;

return [
    HelloWorldController::class => DI\autowire()->constructor(DI\get(GreetingService::class))
];
