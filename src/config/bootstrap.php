<?php

// PHP
declare(strict_types=1);

// Init environment
if (!file_exists(__DIR__ . '/.env'))
{
  if ($handle = fopen(__DIR__ . '/.env', 'w+'))
  {
    fwrite($handle, 'default');
    fclose($handle);

    chmod(__DIR__ . '/.env', 0770);
  }

  else exit (_('Could not init environment file. Please check permissions.'));
}

define('PHP_ENV', file_get_contents(__DIR__ . '/.env'));

// Init config
if (!file_exists(__DIR__ . '/env.' . PHP_ENV . '.php'))
{
  if (copy(__DIR__ . '/../../example/environment/env.example.php',
           __DIR__ . '/env.' . PHP_ENV . '.php'))
  {
     chmod(__DIR__ . '/env.' . PHP_ENV . '.php', 0770);
  }

  else exit (_('Could not init configuration file. Please check permissions.'));
}

// Load environment
require_once __DIR__ . '/env.' . PHP_ENV . '.php';

// Local internal dependencies
require_once __DIR__ . '/../library/database.php';
require_once __DIR__ . '/../library/sphinx.php';
require_once __DIR__ . '/../library/scrapeer.php';
require_once __DIR__ . '/../library/time.php';
require_once __DIR__ . '/../library/curl.php';
require_once __DIR__ . '/../library/valid.php';
require_once __DIR__ . '/../library/filter.php';

// Vendors autoload
require_once __DIR__ . '/../../vendor/autoload.php';

// Connect database
try {

  $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USERNAME, DB_PASSWORD);

} catch (Exception $e) {

  var_dump($e);

  exit;
}

// Connect Sphinx
try {

  $sphinx = new Sphinx(SPHINX_HOST, SPHINX_PORT);

} catch(Exception $e) {

  var_dump($e);

  exit;
}

// Connect memcached
try {

  $memory = new Yggverse\Cache\Memory(MEMCACHED_HOST, MEMCACHED_PORT, MEMCACHED_NAMESPACE, MEMCACHED_TIMEOUT + time());

} catch(Exception $e) {

  var_dump($e);

  exit;
}