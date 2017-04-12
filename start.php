<?php
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use MongoDB\Client;

use \Terekhov\Application;

require 'vendor/autoload.php';

$log = new Logger('slack_history_bot');

$environment = new Dotenv(__DIR__);
$environment->load();
$log->pushHandler(new StreamHandler(\getenv('APPLICATION_LOG_FILE'), Logger::WARNING));

try {
    $client = new MongoDB\Client(getenv("MONGO_DSN"));

    $application = new Application($log, $client);
    $application->run();
} catch (\Exception $e) {
    $log->error("Some error happens {$e->getMessage()}");
}
