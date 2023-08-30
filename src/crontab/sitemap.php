<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.sitemap'), 1);

if (false === sem_acquire($semaphore, true)) {

  exit (PHP_EOL . 'yggtracker.crontab.sitemap process locked by another thread.' . PHP_EOL);
}

// Load system dependencies
require_once(__DIR__ . '/../config/app.php');
require_once(__DIR__ . '/../library/database.php');

// Init Debug
$debug = [
  'time' => [
    'ISO8601' => date('c'),
    'total'   => microtime(true),
  ],
];

// Connect DB
try {

  $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USERNAME, DB_PASSWORD);

} catch(Exception $e) {

  var_dump($e);

  exit;
}

// Begin
try {

  // Delete cache
  @unlink(__DIR__ . '/../public/sitemap.xml');

  if ($handle = fopen(__DIR__ . '/../public/sitemap.xml', 'w+'))
  {

    fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>');
    fwrite($handle, '<urlset>');

    foreach ($db->getMagnets() as $magnet)
    {
      if ($magnet->public && $magnet->approved)
      {
        fwrite($handle, sprintf('<url><loc>%s/magnet.php?magnetId=%s</loc></url>', WEBSITE_URL, $magnet->magnetId));
      }
    }

    fwrite($handle, '</urlset>');
    fclose($handle);
  }

} catch (EXception $e) {

  var_dump($e);
}

// Debug output
$debug['time']['total'] = microtime(true) - $debug['time']['total'];

print_r(
  array_merge($debug, [
    'db' => [
      'total' => [
        'select' => $db->getDebug()->query->select->total,
        'insert' => $db->getDebug()->query->insert->total,
        'update' => $db->getDebug()->query->update->total,
        'delete' => $db->getDebug()->query->delete->total,
      ]
    ]
  ])
);