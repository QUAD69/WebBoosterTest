<?php
declare(strict_types=1);

require('../bootstrap.php');

try {
    /** @var App\Core\Application $application */
    $application->run();
} catch (\Throwable $e) {
    header('Content-Type: text/plain; charset=UTF-8', true, 500);
    echo $e->getFile(), ':', $e->getLine(), ' - ', $e->getMessage(), PHP_EOL, $e->getTraceAsString();
}