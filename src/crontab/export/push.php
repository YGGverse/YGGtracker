<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.export.push'), 1);

if (false === sem_acquire($semaphore, true))
{
  exit (_('yggtracker.crontab.export.push process locked by another thread.'));
}

// Bootstrap
require_once __DIR__ . '/../../config/bootstrap.php';

// Init Debug
$debug =
[
  'dump' => [],
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

// Define public registry
$public = [
  'user'   => [],
  'magnet' => [],
];

// Push export enabled
if (API_EXPORT_PUSH_ENABLED)
{
  // Init request
  $request = [];

  // Get push queue from memory pool
  foreach((array) $memoryApiExportPush = $memory->get('api.export.push') as $id => $push)
  {
    // User request
    if (!empty($push->userId) && API_EXPORT_USERS_ENABLED)
    {
      // Get user info
      if ($user = $db->getUser($push->userId))
      {
        // Dump public data only
        if ($user->public)
        {
          $request['user'] = (object)
          [
            'userId'      => (int) $user->userId,
            'address'     => (string) $user->address,
            'timeAdded'   => (int) $user->timeAdded,
            'timeUpdated' => (int) $user->timeUpdated,
            'approved'    => (bool) $user->approved,
          ];

          // Cache public status
          $public['user'][$user->userId] = (bool) $user->public;
        }
      }
    }

    // Magnet request
    if (!empty($push->magnetId) && API_EXPORT_MAGNETS_ENABLED)
    {
      // Get magnet info
      if ($magnet = $db->getMagnet($push->magnetId))
      {
        if ($magnet->public &&
            $public['user'][$magnet->userId]) // After upgrade, some users have not updated their public status.
                                              // Remote node have warning on import, because user info still hidden to init new profile there.
                                              // Stop magnets export without public profile available, even magnet is public.
        {
          // Info Hash
          $xt = [];
          foreach ($db->findMagnetToInfoHashByMagnetId($magnet->magnetId) as $result)
          {
            if ($infoHash = $db->getInfoHash($result->infoHashId))
            {
              $xt[] = (object) [
                'version' => (float) $infoHash->version,
                'value'   => (string) $infoHash->value,
              ];
            }
          }

          // Keyword Topic
          $kt = [];

          foreach ($db->findKeywordTopicByMagnetId($magnet->magnetId) as $result)
          {
            $kt[] = $db->getKeywordTopic($result->keywordTopicId)->value;
          }

          // Address Tracker
          $tr = [];
          foreach ($db->findAddressTrackerByMagnetId($magnet->magnetId) as $result)
          {
            $addressTracker = $db->getAddressTracker($result->addressTrackerId);

            $scheme = $db->getScheme($addressTracker->schemeId);
            $host   = $db->getHost($addressTracker->hostId);
            $port   = $db->getPort($addressTracker->portId);
            $uri    = $db->getUri($addressTracker->uriId);

            // Yggdrasil host only
            if (!Valid::host($host->value))
            {
              continue;
            }

            $tr[] = $port->value ? sprintf('%s://%s:%s%s',  $scheme->value,
                                                            $host->value,
                                                            $port->value,
                                                            $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                $host->value,
                                                                                                $uri->value);
          }

          // Acceptable Source
          $as = [];

          foreach ($db->findAcceptableSourceByMagnetId($magnet->magnetId) as $result)
          {
            $acceptableSource = $db->getAcceptableSource($result->acceptableSourceId);

            $scheme = $db->getScheme($acceptableSource->schemeId);
            $host   = $db->getHost($acceptableSource->hostId);
            $port   = $db->getPort($acceptableSource->portId);
            $uri    = $db->getUri($acceptableSource->uriId);

            // Yggdrasil host only
            if (!Valid::host($host->value))
            {
              continue;
            }

            $as[] = $port->value ? sprintf('%s://%s:%s%s',  $scheme->value,
                                                            $host->value,
                                                            $port->value,
                                                            $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                $host->value,
                                                                                                $uri->value);
          }

          // Exact Source
          $xs = [];

          foreach ($db->findExactSourceByMagnetId($magnet->magnetId) as $result)
          {
            $eXactSource = $db->getExactSource($result->eXactSourceId);

            $scheme = $db->getScheme($eXactSource->schemeId);
            $host   = $db->getHost($eXactSource->hostId);
            $port   = $db->getPort($eXactSource->portId);
            $uri    = $db->getUri($eXactSource->uriId);

            // Yggdrasil host only
            if (!Valid::host($host->value))
            {
              continue;
            }

            $xs[] = $port->value ? sprintf('%s://%s:%s%s',  $scheme->value,
                                                            $host->value,
                                                            $port->value,
                                                            $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                $host->value,
                                                                                                $uri->value);
          }

          $request['magnet'] = (object)
          [
            'magnetId'        => (int) $magnet->magnetId,
            'userId'          => (int) $magnet->userId,
            'title'           => (string) $magnet->title,
            'preview'         => (string) $magnet->preview,
            'description'     => (string) $magnet->description,
            'comments'        => (bool) $magnet->comments,
            'sensitive'       => (bool) $magnet->sensitive,
            'approved'        => (bool) $magnet->approved,
            'timeAdded'       => (int) $magnet->timeAdded,
            'timeUpdated'     => (int) $magnet->timeUpdated,
            'dn'              => (string) $magnet->dn,
            'xl'              => (float) $magnet->xl,
            'xt'              => (object) $xt,
            'kt'              => (object) $kt,
            'tr'              => (object) $tr,
            'as'              => (object) $as,
            'xs'              => (object) $xs,
          ];
        }

        // Cache public status
        if (!empty($public['user'][$magnet->userId]))
        {
          $public['magnet'][$magnet->magnetId] = (bool) $magnet->public;
        } else {
          $public['magnet'][$magnet->magnetId] = false;
        }
      }
    }

    // Magnet download request
    if (!empty($push->magnetDownloadId) && API_EXPORT_MAGNET_DOWNLOADS_ENABLED)
    {
      // Get magnet download info
      if ($magnetDownload = $db->getMagnetDownload($push->magnetDownloadId))
      {
        // Dump public data only
        if (!empty($public['magnet'][$magnetDownload->magnetId]) &&
            !empty($public['user'][$magnetDownload->userId]))
        {
          $request['magnetDownload'] = (object)
          [
            'magnetDownloadId' => (int) $magnetDownload->magnetDownloadId,
            'userId'           => (int) $magnetDownload->userId,
            'magnetId'         => (int) $magnetDownload->magnetId,
            'timeAdded'        => (int) $magnetDownload->timeAdded,
          ];
        }
      }
    }

    // Magnet comment request
    if (!empty($push->magnetCommentId) && API_EXPORT_MAGNET_COMMENTS_ENABLED)
    {
      // Get magnet comment info
      if ($magnetComment = $db->getMagnetComment($push->magnetCommentId))
      {
        // Dump public data only
        if (!empty($public['magnet'][$magnetComment->magnetId]) &&
            !empty($public['user'][$magnetComment->userId]))
        {
          $request['magnetComment'] = (object)
          [
            'magnetCommentId'       => (int)    $magnetComment->magnetCommentId,
            'magnetCommentIdParent' => (int)    $magnetComment->magnetCommentIdParent,
            'userId'                => (int)    $magnetComment->userId,
            'magnetId'              => (int)    $magnetComment->magnetId,
            'timeAdded'             => (int)    $magnetComment->timeAdded,
            'approved'              => (bool)   $magnetComment->approved,
            'value'                 => (string) $magnetComment->value
          ];
        }
      }
    }

    // Magnet star request
    if (!empty($push->magnetStarId) && API_EXPORT_MAGNET_STARS_ENABLED)
    {
      // Get magnet star info
      if ($magnetStar = $db->getMagnetStar($push->magnetStarId))
      {
        // Dump public data only
        if (!empty($public['magnet'][$magnetStar->magnetId]) &&
            !empty($public['user'][$magnetStar->userId]))
        {
          $request['magnetStar'] = (object)
          [
            'magnetStarId' => (int) $magnetStar->magnetStarId,
            'userId'       => (int) $magnetStar->userId,
            'magnetId'     => (int) $magnetStar->magnetId,
            'value'        => (bool) $magnetStar->value,
            'timeAdded'    => (int) $magnetStar->timeAdded,
          ];
        }
      }
    }

    // Magnet view request
    if (!empty($push->magnetViewId) && API_EXPORT_MAGNET_VIEWS_ENABLED)
    {
      // Get magnet view info
      if ($magnetView = $db->getMagnetView($push->magnetViewId))
      {
        // Dump public data only
        if (!empty($public['magnet'][$magnetView->magnetId]) &&
            !empty($public['user'][$magnetView->userId]))
        {
          $request['magnetView'] = (object)
          [
            'magnetViewId' => (int) $magnetView->magnetViewId,
            'userId'       => (int) $magnetView->userId,
            'magnetId'     => (int) $magnetView->magnetId,
            'timeAdded'    => (int) $magnetView->timeAdded,
          ];
        }
      }
    }

    // Check request
    if (empty($request))
    {
      // Amy request data match conditions, skip

      continue;
    }

    // Send push data
    foreach (json_decode(
      file_get_contents(__DIR__ . '/../../config/nodes.json')
    ) as $node)
    {
      // Manifest exists
      if (empty($node->manifest))
      {
        $debug['dump']['warning'][] = sprintf(
          _('Manifest URL not provided: %s'),
          $node
        );

        continue;
      }

      // Skip non-condition addresses
      $error = [];

      if (!Valid::url($node->manifest, $error))
      {
        $debug['dump'][$node->manifest]['warning'][] = sprintf(
          _('Manifest URL invalid: %s'),
          print_r(
            $error,
            true
          )
        );

        continue;
      }

      // Skip current host
      $thisUrl     = Yggverse\Parser\Url::parse(WEBSITE_URL);
      $manifestUrl = Yggverse\Parser\Url::parse($node->manifest);

      if (empty($thisUrl->host->name) ||
          empty($manifestUrl->host->name) ||
          $manifestUrl->host->name == $thisUrl->host->name) // @TODO some mirrors could be available, improve condition
      {
        // No debug warnings in this case, continue next item

        continue;
      }

      // Get node manifest
      // @TODO cache to prevent extra-queries as usually this script running every minute
      $curl = new Curl($node->manifest, API_USER_AGENT);

      $debug['http']['total']++;

      if (200 != $code = $curl->getCode())
      {
        $debug['dump'][$node->manifest]['warning'][] = sprintf(
          _('Manifest unreachable with code: "%s"'),
          $code
        );

        continue;
      }

      if (!$manifest = $curl->getResponse())
      {
        $debug['dump'][$node->manifest]['warning'][] = sprintf(
          _('Manifest URL "%s" has broken response'),
          $node->manifest
        );

        continue;
      }

      // API channel not exists
      if (empty($manifest->import))
      {
        $debug['dump'][$node->manifest]['warning'][] = sprintf(
          _('Manifest import URL not provided: %s'),
          $node
        );

        continue;
      }

      // Push API channel not exists
      if (empty($manifest->import->push))
      {
        $debug['dump'][$manifest->import->push]['warning'][] = sprintf(
          _('Manifest import push URL not provided: %s'),
          $node
        );

        continue;
      }

      // Skip sending to non-condition addresses
      $error = [];

      if (!Valid::url($manifest->import->push, $error))
      {
        $debug['dump'][$manifest->import->push]['warning'][] = sprintf(
          _('Manifest import push URL invalid: %s'),
          print_r(
            $error,
            true
          )
        );

        continue;
      }

      // Skip current host
      $thisUrl = Yggverse\Parser\Url::parse(WEBSITE_URL);
      $pushUrl = Yggverse\Parser\Url::parse($manifest->import->push);

      if (empty($thisUrl->host->name) ||
          empty($pushUrl->host->name) ||
          $pushUrl->host->name == $thisUrl->host->name) // @TODO some mirrors could be available, improve condition
      {
        // No debug warnings in this case, continue next item

        continue;
      }

      // @TODO add recipient manifest conditions check to not disturb it API without needs

      // Send push request
      $debug['dump'][$manifest->import->push]['request'] = $request;

      $curl = new Curl(
        $manifest->import->push,
        API_USER_AGENT,
        [
          'data' => json_encode($request)
        ]
      );

      $debug['http']['total']++;

      if (200 != $code = $curl->getCode())
      {
        $debug['dump'][$manifest->import->push]['warning'][] = sprintf(
          _('Server returned code "%s"'),
          $code
        );

        continue;
      }

      if (!$response = $curl->getResponse())
      {
        $debug['dump'][$manifest->import->push]['warning'][] = _('Could not receive server response');

        continue;
      }

      $debug['dump'][$manifest->import->push]['response'] = $response;
    }

    // Drop processed item from queue
    //unset($memoryApiExportPush[$id]);
  }

  // Update memory pool
  $memory->set('api.export.push', $memoryApiExportPush, 3600);
}

// Export push disabled, free api.export.push pool
else
{
  $memory->delete('api.export.push');
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
if (LOG_CRONTAB_EXPORT_PUSH_ENABLED)
{
  @mkdir(LOG_DIRECTORY, 0770, true);

  if ($handle = fopen(LOG_DIRECTORY . '/' . LOG_CRONTAB_EXPORT_PUSH_FILENAME, 'a+'))
  {
    fwrite($handle, print_r($debug, true));
    fclose($handle);

    chmod(LOG_DIRECTORY . '/' . LOG_CRONTAB_EXPORT_PUSH_FILENAME, 0770);
  }
}