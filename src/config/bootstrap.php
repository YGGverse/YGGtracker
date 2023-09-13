<?php

// PHP
declare(strict_types=1);

// Init environment
if (empty($_SERVER['PHP_ENV']))
{
  $_SERVER['PHP_ENV'] = 'default';
}

// Validate environment whitelist
if (!in_array($_SERVER['PHP_ENV'], ['default', 'mirror', 'dev', 'test', 'prod']))
{
  exit (_('Environment not supported! Check /src/config/bootstrap.php to add exception.'));
}

// Generate configuration file if not exists
if (!file_exists(__DIR__ . '/env.' . $_SERVER['PHP_ENV'] . '.php') && file_exists(__DIR__ . '/../../example/environment/env.example.php'))
{
  if (copy(__DIR__ . '/../../example/environment/env.example.php', __DIR__ . '/env.' . $_SERVER['PHP_ENV'] . '.php'))
  {
    chmod(__DIR__ . '/env.' . $_SERVER['PHP_ENV'] . '.php', 0770);
  }

  else exit (_('Could not init configuration file. Please check permissions.'));
}

// Load environment configuration
require_once __DIR__ . '/env.' . $_SERVER['PHP_ENV'] . '.php';

// Local internal dependencies
require_once __DIR__ . '/../library/database.php';
require_once __DIR__ . '/../library/sphinx.php';
require_once __DIR__ . '/../library/scrapeer.php';
require_once __DIR__ . '/../library/time.php';

// Vendors autoload
require_once __DIR__ . '/../../vendor/autoload.php';