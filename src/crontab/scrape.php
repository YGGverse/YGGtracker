<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.scrape'), 1);

if (false === sem_acquire($semaphore, true)) {

  exit (PHP_EOL . 'yggtracker.crontab.scrape process locked by another thread.' . PHP_EOL);
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

// Init Scraper
try {

  $scraper = new Scrapeer\Scraper();

} catch(Exception $e) {

  var_dump($e);

  exit;
}

// Begin
try {

  $db->beginTransaction();

  // Reset time offline by timeout
  $db->resetMagnetToAddressTrackerTimeOfflineByTimeout(
    CRAWLER_SCRAPE_TIME_OFFLINE_TIMEOUT
  );

  foreach ($db->getMagnetToAddressTrackerScrapeQueue(CRAWLER_SCRAPE_QUEUE_LIMIT) as $queue)
  {
    $hashes = [];
    foreach ($db->findMagnetToInfoHashByMagnetId($queue->magnetId) as $result)
    {
      $hashes[] = $db->getInfoHash($result->infoHashId)->value;
    }

    if ($addressTracker = $db->getAddressTracker($queue->addressTrackerId))
    {
      // Build url
      $scheme = $db->getScheme($addressTracker->schemeId);
      $host   = $db->getHost($addressTracker->hostId);
      $port   = $db->getPort($addressTracker->portId);
      $uri    = $db->getUri($addressTracker->uriId);

      $url = $port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                    $host->value,
                                                    $port->value,
                                                    $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $uri->value);

      foreach ($hashes as $hash)
      {
        if ($scrape = $scraper->scrape([$hash], [$url], null, 1))
        {
          $db->updateMagnetToAddressTrackerTimeOffline(
            $queue->magnetToAddressTrackerId,
            null
          );

          if (isset($scrape[$hash]['seeders']))
          {
            $db->updateMagnetToAddressTrackerSeeders(
              $queue->magnetToAddressTrackerId,
              (int) $scrape[$hash]['seeders'],
              time()
            );
          }

          if (isset($scrape[$hash]['completed']))
          {
            $db->updateMagnetToAddressTrackerCompleted(
              $queue->magnetToAddressTrackerId,
              (int) $scrape[$hash]['completed'],
              time()
            );
          }

          if (isset($scrape[$hash]['leechers']))
          {
            $db->updateMagnetToAddressTrackerLeechers(
              $queue->magnetToAddressTrackerId,
              (int) $scrape[$hash]['leechers'],
              time()
            );
          }
        }
        else
        {
          $db->updateMagnetToAddressTrackerTimeOffline(
            $queue->magnetToAddressTrackerId,
            time()
          );
        }
      }
    }
  }

  $db->commit();

} catch (EXception $e) {

  $db->rollback();

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
if (LOG_CRONTAB_SCRAPE_ENABLED)
{
  @mkdir(LOG_DIRECTORY, 0774, true);

  if ($handle = fopen(LOG_DIRECTORY . '/' . LOG_CRONTAB_SCRAPE_FILENAME, 'a+'))
  {
    fwrite($handle, print_r($debug, true));
    fclose($handle);

    chmod(LOG_DIRECTORY . '/' . LOG_CRONTAB_SCRAPE_FILENAME, 0774);
  }
}