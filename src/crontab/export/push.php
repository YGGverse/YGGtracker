<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.export.push'), 1);

if (false === sem_acquire($semaphore, true))
{
  exit (PHP_EOL . 'yggtracker.crontab.export.push process locked by another thread.' . PHP_EOL);
}

// Bootstrap
require_once __DIR__ . '/../../config/bootstrap.php';

// Init Debug
$debug =
[
  'time' =>
  [
    'ISO8601' => date('c'),
    'total'   => microtime(true),
  ],
  'result' => []
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
        if ($user->public === '1')
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
          $public['user'][$user->userId] = $user->public;
        }
      }
    }

    // Magnet request
    if (!empty($push->magnetId) && API_EXPORT_MAGNETS_ENABLED)
    {
      // Get magnet info
      if ($magnet = $db->getMagnet($push->magnetId))
      {
        // Dump public data only
        if ($magnet->public === '1')
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
            if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $host->value)))
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
            if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $host->value)))
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
            if (!preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $host->value)))
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
        $public['magnet'][$magnet->magnetId] = $magnet->public;
      }
    }

    // Magnet download request
    if (!empty($push->magnetDownloadId) && API_EXPORT_MAGNET_DOWNLOADS_ENABLED)
    {
      // Get magnet download info
      if ($magnetDownload = $db->getMagnetDownload($push->magnetDownloadId))
      {
        // Dump public data only
        if (isset($public['magnet'][$magnetDownload->magnetId]) && $public['magnet'][$magnetDownload->magnetId] === '1' &&
            isset($public['user'][$magnetDownload->userId]    ) && $public['user'][$magnetDownload->userId] === '1')
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
        if (isset($public['magnet'][$magnetComment->magnetId]) && $public['magnet'][$magnetComment->magnetId] === '1' &&
            isset($public['user'][$magnetComment->userId]    ) && $public['user'][$magnetComment->userId] === '1')
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
        if (isset($public['magnet'][$magnetStar->magnetId]) && $public['magnet'][$magnetStar->magnetId] === '1' &&
            isset($public['user'][$magnetStar->userId]    ) && $public['user'][$magnetStar->userId] === '1')
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
        if (isset($public['magnet'][$magnetView->magnetId]) && $public['magnet'][$magnetView->magnetId] === '1' &&
            isset($public['user'][$magnetView->userId]    ) && $public['user'][$magnetView->userId] === '1')
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
      continue;
    }

    // Send push data
    foreach (json_decode(
      file_get_contents(__DIR__ . '/../../config/nodes.json')
    ) as $node)
    {
      // Manifest
      if ($manifest = @json_decode(@file_get_contents($node->manifest)))
      {
        // API channel not exists
        if (empty($manifest->api))
        {
          continue;
        }

        // Push API channel not exists
        if (empty($manifest->api->push))
        {
          continue;
        }

        // Send push request
        $debug['result'][$manifest->api->push]['request'] = $request;

        $curl = new Curl($manifest->api->push, $request);

        if ($response = $curl->getResponse())
        {
          $debug['result'][$manifest->api->push]['response'] = $response;
        }

        $debug['result'][$manifest->api->push]['code'] = $curl->getCode();
      }
    }

    // Drop processed item from queue
    // unset($memoryApiExportPush[$id]);
  }

  // Update memory pool
  // $memory->set('api.export.push', $memoryApiExportPush);
}

// Export push disabled, free api.export.push pool
else
{
  $memory->delete('api.export.push');
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