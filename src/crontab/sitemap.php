<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.sitemap'), 1);

if (false === sem_acquire($semaphore, true)) {

  exit (PHP_EOL . 'yggtracker.crontab.sitemap process locked by another thread.' . PHP_EOL);
}

// Bootstrap
require_once __DIR__ . '/../config/bootstrap.php';

// Init Debug
$debug = [
  'time' => [
    'ISO8601' => date('c'),
    'total'   => microtime(true),
  ],
  'http' =>
  [
    'total' => 0,
  ],
  'memory' =>
  [
    'start' => memory_get_usage(),
    'total' => 0,
    'peaks' => 0
  ],
];

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
$debug['time']['total']   = microtime(true) - $debug['time']['total'];

$debug['memory']['total'] = memory_get_usage() - $debug['memory']['start'];
$debug['memory']['peaks'] = memory_get_peak_usage();

$debug['db']['total']['select'] = $db->getDebug()->query->select->total;
$debug['db']['total']['insert'] = $db->getDebug()->query->insert->total;
$debug['db']['total']['update'] = $db->getDebug()->query->update->total;
$debug['db']['total']['delete'] = $db->getDebug()->query->delete->total;

print_r($debug);

// Debug log
if (LOG_CRONTAB_SITEMAP_ENABLED)
{
  @mkdir(LOG_DIRECTORY, 0774, true);

  if ($handle = fopen(LOG_DIRECTORY . '/' . LOG_CRONTAB_SITEMAP_FILENAME, 'a+'))
  {
    fwrite($handle, print_r($debug, true));
    fclose($handle);

    chmod(LOG_DIRECTORY . '/' . LOG_CRONTAB_SITEMAP_FILENAME, 0774);
  }
}