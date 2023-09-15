<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.export.feed'), 1);

if (false === sem_acquire($semaphore, true))
{
  exit (PHP_EOL . 'yggtracker.crontab.export.feed process locked by another thread.' . PHP_EOL);
}

// Bootstrap
require_once __DIR__ . '/../../config/bootstrap.php';

// Init Debug
$debug =
[
  'time' => [
    'ISO8601' => date('c'),
    'total'   => microtime(true),
  ],
];

// Define public registry
$public = [
  'user'   => [],
  'magnet' => [],
];

// Begin export
try
{
  // Init API folder if not exists
  @mkdir(__DIR__ . '/../public/api');

  // Delete cached feeds
  @unlink(__DIR__ . '/../public/api/manifest.json');

  @unlink(__DIR__ . '/../public/api/users.json');
  @unlink(__DIR__ . '/../public/api/magnets.json');
  @unlink(__DIR__ . '/../public/api/magnetComments.json');
  @unlink(__DIR__ . '/../public/api/magnetDownloads.json');
  @unlink(__DIR__ . '/../public/api/magnetStars.json');
  @unlink(__DIR__ . '/../public/api/magnetViews.json');

  if (API_EXPORT_ENABLED)
  {
    // Manifest
    $manifest =
    [
      'version'  => API_VERSION,
      'updated'  => time(),

      'settings' => (object)
      [
        'YGGDRASIL_HOST_REGEX'                     => (string) YGGDRASIL_HOST_REGEX,

        'NODE_RULE_SUBJECT'                        => (string) NODE_RULE_SUBJECT,
        'NODE_RULE_LANGUAGES'                      => (string) NODE_RULE_LANGUAGES,

        'USER_DEFAULT_APPROVED'                    => (bool)   USER_DEFAULT_APPROVED,
        'USER_AUTO_APPROVE_ON_MAGNET_APPROVE'      => (bool)   USER_AUTO_APPROVE_ON_MAGNET_APPROVE,
        'USER_AUTO_APPROVE_ON_COMMENT_APPROVE'     => (bool)   USER_AUTO_APPROVE_ON_COMMENT_APPROVE,
        'USER_DEFAULT_IDENTICON'                   => (string) USER_DEFAULT_IDENTICON,
        'USER_IDENTICON_FIELD'                     => (string) USER_IDENTICON_FIELD,

        'MAGNET_DEFAULT_APPROVED'                  => (bool) MAGNET_DEFAULT_APPROVED,
        'MAGNET_DEFAULT_PUBLIC'                    => (bool) MAGNET_DEFAULT_PUBLIC,
        'MAGNET_DEFAULT_COMMENTS'                  => (bool) MAGNET_DEFAULT_COMMENTS,
        'MAGNET_DEFAULT_SENSITIVE'                 => (bool) MAGNET_DEFAULT_SENSITIVE,

        'MAGNET_EDITOR_LOCK_TIMEOUT'               => (int) MAGNET_EDITOR_LOCK_TIMEOUT,

        'MAGNET_TITLE_MIN_LENGTH'                  => (int) MAGNET_TITLE_MIN_LENGTH,
        'MAGNET_TITLE_MAX_LENGTH'                  => (int) MAGNET_TITLE_MAX_LENGTH,
        'MAGNET_TITLE_REGEX'                       => (string) MAGNET_TITLE_REGEX,

        'MAGNET_PREVIEW_MIN_LENGTH'                => (int) MAGNET_PREVIEW_MIN_LENGTH,
        'MAGNET_PREVIEW_MAX_LENGTH'                => (int) MAGNET_PREVIEW_MAX_LENGTH,
        'MAGNET_PREVIEW_REGEX'                     => (string) MAGNET_PREVIEW_REGEX,

        'MAGNET_DESCRIPTION_MIN_LENGTH'            => (int) MAGNET_DESCRIPTION_MIN_LENGTH,
        'MAGNET_DESCRIPTION_MAX_LENGTH'            => (int) MAGNET_DESCRIPTION_MAX_LENGTH,
        'MAGNET_DESCRIPTION_REGEX'                 => (string) MAGNET_DESCRIPTION_REGEX,

        'MAGNET_DN_MIN_LENGTH'                     => (int) MAGNET_DN_MIN_LENGTH,
        'MAGNET_DN_MAX_LENGTH'                     => (int) MAGNET_DN_MAX_LENGTH,
        'MAGNET_DN_REGEX'                          => (string) MAGNET_DN_REGEX,

        'MAGNET_KT_MIN_LENGTH'                     => (int) MAGNET_KT_MIN_LENGTH,
        'MAGNET_KT_MAX_LENGTH'                     => (int) MAGNET_KT_MAX_LENGTH,
        'MAGNET_KT_MIN_QUANTITY'                   => (int) MAGNET_KT_MIN_QUANTITY,
        'MAGNET_KT_MAX_QUANTITY'                   => (int) MAGNET_KT_MAX_QUANTITY,
        'MAGNET_KT_REGEX'                          => (string) MAGNET_KT_REGEX,

        'MAGNET_TR_MIN_QUANTITY'                   => (int) MAGNET_TR_MIN_QUANTITY,
        'MAGNET_TR_MAX_QUANTITY'                   => (int) MAGNET_TR_MAX_QUANTITY,

        'MAGNET_AS_MIN_QUANTITY'                   => (int) MAGNET_AS_MIN_QUANTITY,
        'MAGNET_AS_MAX_QUANTITY'                   => (int) MAGNET_AS_MAX_QUANTITY,

        'MAGNET_WS_MIN_QUANTITY'                   => (int) MAGNET_WS_MIN_QUANTITY,
        'MAGNET_WS_MAX_QUANTITY'                   => (int) MAGNET_WS_MAX_QUANTITY,

        'MAGNET_COMMENT_DEFAULT_APPROVED'          => (bool) MAGNET_COMMENT_DEFAULT_APPROVED,
        'MAGNET_COMMENT_DEFAULT_PUBLIC'            => (bool) MAGNET_COMMENT_DEFAULT_PUBLIC,
        'MAGNET_COMMENT_DEFAULT_PUBLIC'            => (bool) MAGNET_COMMENT_DEFAULT_PUBLIC,
        'MAGNET_COMMENT_MIN_LENGTH'                => (int)  MAGNET_COMMENT_MIN_LENGTH,
        'MAGNET_COMMENT_MAX_LENGTH'                => (int)  MAGNET_COMMENT_MAX_LENGTH,

        'MAGNET_STOP_WORDS_SIMILAR'                => (object) MAGNET_STOP_WORDS_SIMILAR,

        'API_EXPORT_ENABLED'                       => (bool) API_EXPORT_ENABLED,
        'API_EXPORT_PUSH_ENABLED'                  => (bool) API_EXPORT_PUSH_ENABLED,
        'API_EXPORT_USERS_ENABLED'                 => (bool) API_EXPORT_USERS_ENABLED,
        'API_EXPORT_MAGNETS_ENABLED'               => (bool) API_EXPORT_MAGNETS_ENABLED,
        'API_EXPORT_MAGNET_DOWNLOADS_ENABLED'      => (bool) API_EXPORT_MAGNET_DOWNLOADS_ENABLED,
        'API_EXPORT_MAGNET_COMMENTS_ENABLED'       => (bool) API_EXPORT_MAGNET_COMMENTS_ENABLED,
        'API_EXPORT_MAGNET_STARS_ENABLED'          => (bool) API_EXPORT_MAGNET_STARS_ENABLED,
        'API_EXPORT_MAGNET_STARS_ENABLED'          => (bool) API_EXPORT_MAGNET_STARS_ENABLED,
        'API_EXPORT_MAGNET_VIEWS_ENABLED'          => (bool) API_EXPORT_MAGNET_VIEWS_ENABLED,

        'API_IMPORT_ENABLED'                       => (bool) API_IMPORT_ENABLED,
        'API_IMPORT_PUSH_ENABLED'                  => (bool) API_IMPORT_PUSH_ENABLED,
        'API_IMPORT_USERS_ENABLED'                 => (bool) API_IMPORT_USERS_ENABLED,

        'API_IMPORT_USERS_APPROVED_ONLY'           => (bool) API_IMPORT_USERS_APPROVED_ONLY,
        'API_IMPORT_MAGNETS_ENABLED'               => (bool) API_IMPORT_MAGNETS_ENABLED,
        'API_IMPORT_MAGNETS_APPROVED_ONLY'         => (bool) API_IMPORT_MAGNETS_APPROVED_ONLY,
        'API_IMPORT_MAGNET_DOWNLOADS_ENABLED'      => (bool) API_IMPORT_MAGNET_DOWNLOADS_ENABLED,
        'API_IMPORT_MAGNET_COMMENTS_ENABLED'       => (bool) API_IMPORT_MAGNET_COMMENTS_ENABLED,
        'API_IMPORT_MAGNET_COMMENTS_APPROVED_ONLY' => (bool) API_IMPORT_MAGNET_COMMENTS_APPROVED_ONLY,
        'API_IMPORT_MAGNET_STARS_ENABLED'          => (bool) API_IMPORT_MAGNET_STARS_ENABLED,
        'API_IMPORT_MAGNET_VIEWS_ENABLED'          => (bool) API_IMPORT_MAGNET_VIEWS_ENABLED,
      ],
      'totals' => (object)
      [
        'magnets'   => (object)
        [
          'total'       => $db->getMagnetsTotal(),
          'distributed' => $db->getMagnetsTotalByUsersPublic(true),
          'local'       => $db->getMagnetsTotalByUsersPublic(false),
        ],
        'downloads' => (object)
        [
          'total'       => $db->getMagnetDownloadsTotal(),
          'distributed' => $db->findMagnetDownloadsTotalByUsersPublic(true),
          'local'       => $db->findMagnetDownloadsTotalByUsersPublic(false),
        ],
        'comments'  => (object)
        [
          'total'       => $db->getMagnetCommentsTotal(),
          'distributed' => $db->findMagnetCommentsTotalByUsersPublic(true),
          'local'       => $db->findMagnetCommentsTotalByUsersPublic(false),
        ],
        'stars'     => (object)
        [
          'total'       => $db->getMagnetStarsTotal(),
          'distributed' => $db->findMagnetStarsTotalByUsersPublic(true),
          'local'       => $db->findMagnetStarsTotalByUsersPublic(false),
        ],
        'views'     => (object)
        [
          'total'       => $db->getMagnetViewsTotal(),
          'distributed' => $db->findMagnetViewsTotalByUsersPublic(true),
          'local'       => $db->findMagnetViewsTotalByUsersPublic(false),
        ],
      ],
      'import' => (object)
      [
        'push' => API_IMPORT_PUSH_ENABLED ? sprintf('%s/api/push.php', WEBSITE_URL) : false,
      ],
      'export' => (object)
      [
        'users'           => API_EXPORT_USERS_ENABLED            ? sprintf('%s/api/users.json', WEBSITE_URL)     : false,
        'magnets'         => API_EXPORT_MAGNETS_ENABLED          ? sprintf('%s/api/magnets.json', WEBSITE_URL)   : false,
        'magnetDownloads' => API_EXPORT_MAGNET_DOWNLOADS_ENABLED ? sprintf('%s/api/magnetDownloads.json', WEBSITE_URL) : false,
        'magnetComments'  => API_EXPORT_MAGNET_COMMENTS_ENABLED  ? sprintf('%s/api/magnetComments.json', WEBSITE_URL)  : false,
        'magnetStars'     => API_EXPORT_MAGNET_STARS_ENABLED     ? sprintf('%s/api/magnetStars.json', WEBSITE_URL)     : false,
        'magnetViews'     => API_EXPORT_MAGNET_VIEWS_ENABLED     ? sprintf('%s/api/magnetViews.json', WEBSITE_URL)     : false,
      ],
      'trackers' => (object) json_decode(file_get_contents(__DIR__ . '/../../config/trackers.json')),
      'nodes'    => (object) json_decode(file_get_contents(__DIR__ . '/../../config/nodes.json')),
    ];

    /// Dump feed
    if ($handle = fopen(__DIR__ . '/../../public/api/manifest.json', 'w+'))
    {
      fwrite($handle, json_encode($manifest));
      fclose($handle);
    }

    // Users
    if (API_EXPORT_USERS_ENABLED)
    {
      $users = [];

      foreach ($db->getUsers() as $user)
      {
        // Dump public data only
        if ($user->public === '1')
        {
          $users[] = (object)
          [
            'userId'      => (int) $user->userId,
            'address'     => (string) $user->address,
            'timeAdded'   => (int) $user->timeAdded,
            'timeUpdated' => (int) $user->timeUpdated,
            'approved'    => (bool) $user->approved,
            'magnets'     => (int) $db->findMagnetsTotalByUserId($user->userId),
            'downloads'   => (int) $db->findMagnetDownloadsTotalByUserId($user->userId),
            'comments'    => (int) $db->findMagnetCommentsTotalByUserId($user->userId),
            'stars'       => (int) $db->findMagnetStarsTotalByUserId($user->userId),
            'views'       => (int) $db->findMagnetViewsTotalByUserId($user->userId),
          ];
        }

        // Cache public status
        $public['user'][$user->userId] = $user->public;
      }

      /// Dump users feed
      if ($handle = fopen(__DIR__ . '/../../public/api/users.json', 'w+'))
      {
        fwrite($handle, json_encode($users));
        fclose($handle);
      }
    }

    // Magnets
    if (API_EXPORT_MAGNETS_ENABLED)
    {
      $magnets = [];

      foreach ($db->getMagnets($user->userId) as $magnet)
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

          $magnets[] = (object)
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

      /// Dump magnets feed
      if ($handle = fopen(__DIR__ . '/../../public/api/magnets.json', 'w+'))
      {
        fwrite($handle, json_encode($magnets));
        fclose($handle);
      }
    }

    // Magnet downloads
    if (API_EXPORT_MAGNET_DOWNLOADS_ENABLED)
    {
      $magnetDownloads = [];

      foreach ($db->getMagnetDownloads() as $magnetDownload)
      {
        // Dump public data only
        if (isset($public['magnet'][$magnetDownload->magnetId]) && $public['magnet'][$magnetDownload->magnetId] === '1' &&
            isset($public['user'][$magnetDownload->userId])     && $public['user'][$magnetDownload->userId] === '1')
        {
          $magnetDownloads[] = (object)
          [
            'magnetDownloadId' => (int) $magnetDownload->magnetDownloadId,
            'userId'           => (int) $magnetDownload->userId,
            'magnetId'         => (int) $magnetDownload->magnetId,
            'timeAdded'        => (int) $magnetDownload->timeAdded,
          ];
        }
      }

      /// Dump feed
      if ($handle = fopen(__DIR__ . '/../../public/api/magnetDownloads.json', 'w+'))
      {
        fwrite($handle, json_encode($magnetDownloads));
        fclose($handle);
      }
    }

    // Magnet comments
    if (API_EXPORT_MAGNET_COMMENTS_ENABLED)
    {
      $magnetComments = [];

      foreach ($db->getMagnetComments() as $magnetComment)
      {
        // Dump public data only
        if (isset($public['magnet'][$magnetComment->magnetId]) && $public['magnet'][$magnetComment->magnetId] === '1' &&
            isset($public['user'][$magnetComment->userId])     && $public['user'][$magnetComment->userId] === '1')
        {
          $magnetComments[] = (object)
          [
            'magnetCommentId'       => (int) $magnetComment->magnetCommentId,
            'magnetCommentIdParent' => (int) $magnetComment->magnetCommentIdParent,
            'userId'                => (int) $magnetComment->userId,
            'magnetId'              => (int) $magnetComment->magnetId,
            'timeAdded'             => (int) $magnetComment->timeAdded,
            'approved'              => (bool) $magnetComment->approved,
            'value'                 => (string) $magnetComment->value
          ];
        }
      }

      /// Dump feed
      if ($handle = fopen(__DIR__ . '/../../public/api/magnetComments.json', 'w+'))
      {
        fwrite($handle, json_encode($magnetComments));
        fclose($handle);
      }
    }

    // Magnet stars
    if (API_EXPORT_MAGNET_STARS_ENABLED)
    {
      $magnetStars = [];

      foreach ($db->getMagnetStars() as $magnetStar)
      {
        // Dump public data only
        if (isset($public['magnet'][$magnetStar->magnetId]) && $public['magnet'][$magnetStar->magnetId] === '1' &&
            isset($public['user'][$magnetStar->userId])     && $public['user'][$magnetStar->userId] === '1')
        {
          $magnetStars[] = (object)
          [
            'magnetStarId' => (int) $magnetStar->magnetStarId,
            'userId'       => (int) $magnetStar->userId,
            'magnetId'     => (int) $magnetStar->magnetId,
            'value'        => (bool) $magnetStar->value,
            'timeAdded'    => (int) $magnetStar->timeAdded,
          ];
        }
      }

      /// Dump feed
      if ($handle = fopen(__DIR__ . '/../../public/api/magnetStars.json', 'w+'))
      {
        fwrite($handle, json_encode($magnetStars));
        fclose($handle);
      }
    }

    // Magnet views
    if (API_EXPORT_MAGNET_VIEWS_ENABLED)
    {
      $magnetViews = [];

      foreach ($db->getMagnetViews() as $magnetView)
      {
        // Dump public data only
        if (isset($public['magnet'][$magnetView->magnetId]) && $public['magnet'][$magnetView->magnetId] === '1' &&
            isset($public['user'][$magnetView->userId]    ) && $public['user'][$magnetView->userId] === '1')
        {
          $magnetViews[] = (object)
          [
            'magnetViewId' => (int) $magnetView->magnetViewId,
            'userId'       => (int) $magnetView->userId,
            'magnetId'     => (int) $magnetView->magnetId,
            'timeAdded'    => (int) $magnetView->timeAdded,
          ];
        }
      }

      /// Dump feed
      if ($handle = fopen(__DIR__ . '/../../public/api/magnetViews.json', 'w+'))
      {
        fwrite($handle, json_encode($magnetViews));
        fclose($handle);
      }
    }
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