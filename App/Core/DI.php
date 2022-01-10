<?php
declare(strict_types=1);

namespace App\Core;

/**
 * @property \PDO $db
 * @property Config $config
 * @property Router $router
 * @property \App\Core\Html\Assets $assets
 * @property \App\Core\Html\Breadcrumbs $breadcrumbs
 * @property \App\Core\Mvc\View $view
 * @property \App\Core\Http\Request $request
 * @property \App\Core\Http\Response $response
 */
abstract class DI
{
    public function __get(string $name): ?object
    {
        return Application::$instance->getService($name);
    }
}