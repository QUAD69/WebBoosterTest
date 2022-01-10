<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    protected array $routes = [];

    protected array $notFound = ['Index', 'notFound'];

    protected array $notSupport = ['Index', 'notSupport'];

    protected string $controllerName = '';

    protected string $actionName = '';

    protected array $parameters = [];


    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function setNotFoundRoute(string $controller, string $action): void
    {
        $this->notFound = [$controller, $action];
    }

    public function setNotSupportRoute(string $controller, string $action): void
    {
        $this->notSupport = [$controller, $action];
    }

    public function direct(string $uri, string $method): void
    {
        $urlPath = strstr("{$uri}?", '?', true);

        foreach ($this->routes as $route => $target) {
            if (!preg_match("#^{$route}$#", $urlPath, $matches)) continue;

            if (isset($matches[2]) && !in_array($method, $target[2])) {

                $this->controllerName = $this->notSupport[0];
                $this->actionName = $this->notSupport[1];
                return;
            }

            array_shift($matches);

            $this->controllerName = $target[0];
            $this->actionName = $target[1];
            $this->parameters = $matches;
            return;
        }

        $this->controllerName = $this->notFound[0];
        $this->actionName = $this->notFound[1];
    }

    public function getControllerName(): string
    {
        return $this->controllerName;
    }

    public function getActionName(): string
    {
        return $this->actionName;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}