<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use App\Core\Application;
use App\Core\Html\Assets;
use App\Core\Html\Breadcrumbs;

/**
 * Корневой каталог приложения
 */
const LOC_ROOT = __DIR__ . '/';
/**
 * Каталог конфигурационных файлов
 */
const LOC_CONFIG = LOC_ROOT . 'config/';
/**
 * Публичный каталог приложения.
 */
const LOC_PUBLIC = LOC_ROOT . 'public/';


$application = new Application();

$application->setConfig(require(LOC_CONFIG . 'config.php'));

$application->setRoutes(require(LOC_CONFIG . 'routes.php'));

$application->setViewsDir(LOC_ROOT . 'App/Views/');

$application->setService('db', fn() => new \PDO(
    $application->config->database->dsn,
    $application->config->database->username,
    $application->config->database->password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
    ]
));