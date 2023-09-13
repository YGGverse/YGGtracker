<?php

// PHP
declare(strict_types=1);

// Environment
if (!empty($_SERVER['PHP_ENV']) && file_exists(sprintf('%s/env.%s.php', __DIR__, $_SERVER['PHP_ENV'])))
{
  require_once sprintf('%s/env.%s.php', __DIR__, $_SERVER['PHP_ENV']);
}

else require_once __DIR__ . '/env.default.php';

// Autoload
require_once __DIR__ . '/../../vendor/autoload.php';

// Local
require_once __DIR__ . '/../library/database.php';
require_once __DIR__ . '/../library/sphinx.php';
require_once __DIR__ . '/../library/scrapeer.php';
require_once __DIR__ . '/../library/time.php';