<?php

// PHP
declare(strict_types=1);

// Init environment
if (empty($_SERVER['PHP_ENV']))
{
  $_SERVER['PHP_ENV'] = 'default';
}

// Generate configuration file if not exists
if (!file_exists(sprintf('%s/env.%s.php', __DIR__, $_SERVER['PHP_ENV'])))
{
  copy(
    __DIR__ . '/env.example.php',
    sprintf('%s/env.%s.php', __DIR__, $_SERVER['PHP_ENV'])
  );
}

// Load environment configuration
require_once sprintf('%s/env.%s.php', __DIR__, $_SERVER['PHP_ENV']);

// Local internal dependencies
require_once __DIR__ . '/../library/database.php';
require_once __DIR__ . '/../library/sphinx.php';
require_once __DIR__ . '/../library/scrapeer.php';
require_once __DIR__ . '/../library/time.php';

// Vendors autoload
require_once __DIR__ . '/../../vendor/autoload.php';