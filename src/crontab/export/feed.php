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
  // Connect DB
  $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USERNAME, DB_PASSWORD);

  // Init API folder if not exists
  @mkdir(__DIR__ . '/../public/api');

  // Delete cached feeds
  @unlink(__DIR__ . '/../public/api/manifest.json');

  @unlink(__DIR__ . '/../public/api/users.json');
  @unlink(__DIR__ . '/../public/api/magnets.json');
  @unlink(__DIR__ . '/../public/api/comments.json');
  @unlink(__DIR__ . '/../public/api/downloads.json');
  @unlink(__DIR__ . '/../public/api/stars.json');
  @unlink(__DIR__ . '/../public/api/views.json');

  if (API_EXPORT_ENABLED)
  {
    // Manifest
    $manifest =
    [
      'version'  => API_VERSION,
      'updated'  => time(),

      'settings' =>
      [
        'YGGDRASIL_HOST_REGEX'                 => YGGDRASIL_HOST_REGEX,

        'NODE_RULE_SUBJECT'                    => NODE_RULE_SUBJECT,
        'NODE_RULE_LANGUAGES'                  => NODE_RULE_LANGUAGES,

        'USER_DEFAULT_APPROVED'                => USER_DEFAULT_APPROVED,
        'USER_AUTO_APPROVE_ON_MAGNET_APPROVE'  => USER_AUTO_APPROVE_ON_MAGNET_APPROVE,
        'USER_AUTO_APPROVE_ON_COMMENT_APPROVE' => USER_AUTO_APPROVE_ON_COMMENT_APPROVE,
        'USER_DEFAULT_IDENTICON'               => USER_DEFAULT_IDENTICON,
        'USER_IDENTICON_FIELD'                 => USER_IDENTICON_FIELD,

        'MAGNET_DEFAULT_APPROVED'              => MAGNET_DEFAULT_APPROVED,
        'MAGNET_DEFAULT_PUBLIC'                => MAGNET_DEFAULT_PUBLIC,
        'MAGNET_DEFAULT_COMMENTS'              => MAGNET_DEFAULT_COMMENTS,
        'MAGNET_DEFAULT_SENSITIVE'             => MAGNET_DEFAULT_SENSITIVE,

        'MAGNET_EDITOR_LOCK_TIMEOUT'           => MAGNET_EDITOR_LOCK_TIMEOUT,

        'MAGNET_TITLE_MIN_LENGTH'              => MAGNET_TITLE_MIN_LENGTH,
        'MAGNET_TITLE_MAX_LENGTH'              => MAGNET_TITLE_MAX_LENGTH,

        'MAGNET_PREVIEW_MIN_LENGTH'            => MAGNET_PREVIEW_MIN_LENGTH,
        'MAGNET_PREVIEW_MAX_LENGTH'            => MAGNET_PREVIEW_MAX_LENGTH,

        'MAGNET_DESCRIPTION_MIN_LENGTH'        => MAGNET_DESCRIPTION_MIN_LENGTH,
        'MAGNET_DESCRIPTION_MAX_LENGTH'        => MAGNET_DESCRIPTION_MAX_LENGTH,

        'MAGNET_COMMENT_DEFAULT_APPROVED'      => MAGNET_COMMENT_DEFAULT_APPROVED,
        'MAGNET_COMMENT_DEFAULT_PUBLIC'        => MAGNET_COMMENT_DEFAULT_PUBLIC,
        'MAGNET_COMMENT_DEFAULT_PUBLIC'        => MAGNET_COMMENT_DEFAULT_PUBLIC,
        'MAGNET_COMMENT_MIN_LENGTH'            => MAGNET_COMMENT_MIN_LENGTH,
        'MAGNET_COMMENT_MAX_LENGTH'            => MAGNET_COMMENT_MAX_LENGTH,

        'MAGNET_STOP_WORDS_SIMILAR'            => MAGNET_STOP_WORDS_SIMILAR,
      ],

      'users'     => API_EXPORT_USERS_ENABLED            ? sprintf('%s/api/users.json', WEBSITE_URL)     : false,
      'magnets'   => API_EXPORT_MAGNETS_ENABLED          ? sprintf('%s/api/magnets.json', WEBSITE_URL)   : false,
      'downloads' => API_EXPORT_MAGNET_DOWNLOADS_ENABLED ? sprintf('%s/api/downloads.json', WEBSITE_URL) : false,
      'comments'  => API_EXPORT_MAGNET_COMMENTS_ENABLED  ? sprintf('%s/api/comments.json', WEBSITE_URL)  : false,
      'stars'     => API_EXPORT_MAGNET_STARS_ENABLED     ? sprintf('%s/api/stars.json', WEBSITE_URL)     : false,
      'views'     => API_EXPORT_MAGNET_VIEWS_ENABLED     ? sprintf('%s/api/views.json', WEBSITE_URL)     : false,

      'totals'    =>
      [
        'magnets'   =>
        [
          'total'       => $db->getMagnetsTotal(),
          'distributed' => $db->getMagnetsTotalByUsersPublic(true),
          'local'       => $db->getMagnetsTotalByUsersPublic(false),
        ],
        'downloads' =>
        [
          'total'       => $db->getMagnetDownloadsTotal(),
          'distributed' => $db->findMagnetDownloadsTotalByUsersPublic(true),
          'local'       => $db->findMagnetDownloadsTotalByUsersPublic(false),
        ],
        'comments'  =>
        [
          'total'       => $db->getMagnetCommentsTotal(),
          'distributed' => $db->findMagnetCommentsTotalByUsersPublic(true),
          'local'       => $db->findMagnetCommentsTotalByUsersPublic(false),
        ],
        'stars'     =>
        [
          'total'       => $db->getMagnetStarsTotal(),
          'distributed' => $db->findMagnetStarsTotalByUsersPublic(true),
          'local'       => $db->findMagnetStarsTotalByUsersPublic(false),
        ],
        'views'     =>
        [
          'total'       => $db->getMagnetViewsTotal(),
          'distributed' => $db->findMagnetViewsTotalByUsersPublic(true),
          'local'       => $db->findMagnetViewsTotalByUsersPublic(false),
        ],
      ],

      'trackers'  => json_decode(file_get_contents(__DIR__ . '/../../config/trackers.json')),
      'nodes'     => json_decode(file_get_contents(__DIR__ . '/../../config/nodes.json')),
    ];

    /// Dump manifest manifest
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
            'userId'      => $user->userId,
            'address'     => $user->address,
            'timeAdded'   => $user->timeAdded,
            'timeUpdated' => $user->timeUpdated,
            'approved'    => (bool) $user->approved,
            'magnets'     => $db->findMagnetsTotalByUserId($user->userId),
            'downloads'   => $db->findMagnetDownloadsTotalByUserId($user->userId),
            'comments'    => $db->findMagnetCommentsTotalByUserId($user->userId),
            'stars'       => $db->findMagnetStarsTotalByUserId($user->userId),
            'views'       => $db->findMagnetViewsTotalByUserId($user->userId),
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
                'version' => $infoHash->version,
                'value'   => $infoHash->value,
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

          $magnets[] = (object)
          [
            'magnetId'        => $magnet->magnetId,
            'userId'          => $magnet->userId,
            'title'           => $magnet->title,
            'preview'         => $magnet->preview,
            'description'     => $magnet->description,
            'comments'        => (bool) $magnet->comments,
            'sensitive'       => (bool) $magnet->sensitive,
            'approved'        => (bool) $magnet->approved,
            'timeAdded'       => $magnet->timeAdded,
            'timeUpdated'     => $magnet->timeUpdated,
            'dn'              => $magnet->dn,
            'xl'              => $magnet->xl,
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

    // Downloads
    if (API_EXPORT_MAGNET_DOWNLOADS_ENABLED)
    {
      $downloads = [];

      foreach ($db->getMagnetDownloads() as $download)
      {
        // Dump public data only
        if (isset($public['magnet'][$download->magnetId]) && $public['magnet'][$download->magnetId] === '1' &&
            isset($public['user'][$download->userId]) && $public['user'][$download->userId] === '1')
        {
          $downloads[] = (object)
          [
            'magnetDownloadId' => $download->magnetDownloadId,
            'userId'           => $download->userId,
            'magnetId'         => $download->magnetId,
            'timeAdded'        => $download->timeAdded,
          ];
        }
      }

      /// Dump downloads feed
      if ($handle = fopen(__DIR__ . '/../../public/api/downloads.json', 'w+'))
      {
        fwrite($handle, json_encode($downloads));
        fclose($handle);
      }
    }

    // Comments
    if (API_EXPORT_MAGNET_COMMENTS_ENABLED)
    {
      $comments = [];

      foreach ($db->getMagnetComments() as $comment)
      {
        // Dump public data only
        if (isset($public['magnet'][$comment->magnetId]) && $public['magnet'][$comment->magnetId] === '1' &&
            isset($public['user'][$comment->userId]) && $public['user'][$comment->userId] === '1')
        {
          $comments[] = (object)
          [
            'magnetCommentId' => $comment->magnetCommentId,
            'userId'           => $comment->userId,
            'magnetId'         => $comment->magnetId,
            'timeAdded'        => $comment->timeAdded,
          ];
        }
      }

      /// Dump comments feed
      if ($handle = fopen(__DIR__ . '/../../public/api/comments.json', 'w+'))
      {
        fwrite($handle, json_encode($comments));
        fclose($handle);
      }
    }

    // Stars
    if (API_EXPORT_MAGNET_STARS_ENABLED)
    {
      $stars = [];

      foreach ($db->getMagnetStars() as $star)
      {
        // Dump public data only
        if (isset($public['magnet'][$star->magnetId]) && $public['magnet'][$star->magnetId] === '1' &&
            isset($public['user'][$star->userId]) && $public['user'][$star->userId] === '1')
        {
          $stars[] = (object)
          [
            'magnetStarId' => $star->magnetStarId,
            'userId'       => $star->userId,
            'magnetId'     => $star->magnetId,
            'value'        => (bool) $star->value,
            'timeAdded'    => $star->timeAdded,
          ];
        }
      }

      /// Dump stars feed
      if ($handle = fopen(__DIR__ . '/../../public/api/stars.json', 'w+'))
      {
        fwrite($handle, json_encode($stars));
        fclose($handle);
      }
    }
    // Views
    if (API_EXPORT_MAGNET_VIEWS_ENABLED)
    {
      $views = [];

      foreach ($db->getMagnetViews() as $view)
      {
        // Dump public data only
        if (isset($public['magnet'][$view->magnetId]) && $public['magnet'][$view->magnetId] === '1' &&
            isset($public['user'][$view->userId]) && $public['user'][$view->userId] === '1')
        {
          $views[] = (object)
          [
            'magnetViewId' => $view->magnetViewId,
            'userId'       => $view->userId,
            'magnetId'     => $view->magnetId,
            'timeAdded'    => $view->timeAdded,
          ];
        }
      }

      /// Dump views feed
      if ($handle = fopen(__DIR__ . '/../../public/api/views.json', 'w+'))
      {
        fwrite($handle, json_encode($views));
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