<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');

require BASE_PATH . '/bootstrap/autoload.php';
require APP_PATH . '/Helpers/functions.php';

\App\Utilities\Env::load(BASE_PATH . '/.env');

$database = \App\Core\Database::getInstance();
$sql = file_get_contents(__DIR__ . '/activity_log_migration.sql');
if ($sql === false) {
    throw new RuntimeException('Unable to read the Activity Log migration.');
}

$statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: []));
foreach ($statements as $statement) {
    $database->execute($statement);
}

echo "Activity Log migration completed.\n";
