<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Mvc\View;

class Application extends DI
{
    public static self $instance;

    protected array $services = [];

    public function __construct()
    {
        self::$instance = $this;

        $this->setService('request', new Request(true));
        $this->setService('response', new Response());
    }

    public function setService(string $name, callable|object $service, bool $lazy = true): void
    {
        if (!$lazy && is_callable($service)) {
            $service = call_user_func($service);
        }

        $this->services[$name] = $service;
    }

    public function getService(string $name): ?object
    {
        if (isset($this->services[$name])) {

            if (is_callable($this->services[$name])) {

                $this->services[$name] = call_user_func($this->services[$name]);

                if (gettype($this->services[$name]) !== 'object') {

                    trigger_error("Can't initialize service '{$name}'!");
                    return null;
                }
            }

            return $this->services[$name];
        }

        return null;
    }

    public function hasService(string $name): bool
    {
        return isset($this->services[$name]);
    }

    public function setRoutes(array $routes): void
    {
        $this->setService('router', new Router($routes));
    }

    public function setConfig(array $config): void
    {
        $this->setService('config', new Config($config));
    }

    public function setViewsDir(string $path): void
    {
        $this->setService('view', new View($path));
    }

    public function run(): void
    {
        $this->router->direct(
            $this->request->getUri(),
            $this->request->getMethod()
        );

        $controllerName = $this->router->getControllerName() . 'Controller';
        $actionName = $this->router->getActionName() . 'Action';
        $parameters = $this->router->getParameters();

        $class = '\App\Controllers\\' . $controllerName;

        $controller = new $class();

        ob_start();
        ob_implicit_flush(false);

        if ($controller->initialize() === null) {
            $controller->{$actionName}(...$parameters);
            $controller->finalize();
        }

        $this->response->setContent(ob_get_clean());
        $this->response->send();
    }
}