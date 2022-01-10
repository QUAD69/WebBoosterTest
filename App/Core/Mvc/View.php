<?php
declare(strict_types=1);

namespace App\Core\Mvc;

class View extends \App\Core\DI
{
    public string $viewsDir;

    public function __construct(string $viewsDir)
    {
        $this->viewsDir = $viewsDir;
    }

    public function render(string $view, array $args = []): void
    {
        $viewPath = "{$this->viewsDir}{$view}.phtml";
        $viewPath = strtr($viewPath, '\\', '/');

        extract($args);
        require($viewPath);
    }
}