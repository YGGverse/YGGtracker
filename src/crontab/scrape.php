<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.scrape'), 1);

if (false === sem_acquire($semaphore, true)) {

  exit (PHP_EOL . 'yggtracker.crontab.scrape process locked by another thread.' . PHP_EOL);
}

// Load system dependencies
require_once(__DIR__ . '/../config/app.php');
require_once(__DIR__ . '/../library/database.php');
require_once(__DIR__ . '/../library/scrapeer.php');

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

      $hash = str_replace('urn:btih:', false, $db->getMagnet($queue->magnetId)->xt);

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

  $db->commit();

} catch (EXception $e) {

  $db->rollback();

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