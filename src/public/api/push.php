<?php

// Bootstrap
require_once __DIR__ . '/../../config/bootstrap.php';

// Init Debug
$debug =
[
  'time' => [
    'ISO8601' => date('c'),
    'total'   => microtime(true),
  ],
  'memory' =>
  [
    'start' => memory_get_usage(),
    'total' => 0,
    'peaks' => 0
  ],
  'exception' => []
];

// Define response
$response =
[
  'status'  => false,
  'message' => _('Internal server error'),
  'data'    => [
    'user'           => [],
    'magnet'         => [],
    'magnetDownload' => [],
    'magnetComment'  => [],
    'magnetView'     => [],
    'magnetStar'     => [],
  ]
];

// Init connections whitelist
$connectionWhiteList = [];

foreach (json_decode(file_get_contents(__DIR__ . '/../../config/nodes.json')) as $node)
{
  // Skip non-condition addresses
  if (!Valid::url($node->manifest))
  {
    continue;
  }

  // Skip current host
  $thisUrl     = Yggverse\Parser\Url::parse(WEBSITE_URL);
  $manifestUrl = Yggverse\Parser\Url::parse($node->manifest);

  if (empty($thisUrl->host->name) ||
      empty($manifestUrl->host->name) ||
      $manifestUrl->host->name == $thisUrl->host->name) // @TODO some mirrors could be available on same host sub-folders, improve condition
  {
    continue;
  }

  $connectionWhiteList[] = str_replace(['[',']'], false, $manifestUrl->host->name);
}

// API import enabled
$error = [];

if (!API_IMPORT_ENABLED)
{
  $response =
  [
    'status'  => false,
    'message' => _('Import API disabled')
  ];
}

// Push API import enabled
else if (!API_IMPORT_PUSH_ENABLED)
{
  $response =
  [
    'status'  => false,
    'message' => _('Push API import disabled')
  ];
}

// Yggdrasil connections only
else if (!Valid::host($_SERVER['REMOTE_ADDR'], $error))
{
  $response =
  [
    'status'  => false,
    'message' => $error
  ];
}

// Init session
else if (!in_array($_SERVER['REMOTE_ADDR'], $connectionWhiteList))
{
  $response =
  [
    'status'  => false,
    'message' => sprintf(
      _('Push API access denied for host "%s"'),
      $_SERVER['REMOTE_ADDR']
    )
  ];
}

// Init session
else if (!$userId = $db->initUserId($_SERVER['REMOTE_ADDR'], USER_DEFAULT_APPROVED, time()))
{
  $response =
  [
    'status'  => false,
    'message' => _('Could not init user session for this connection')
  ];
}

// Validate required fields
else if (empty($_POST['data']))
{
  $response =
  [
    'status'  => false,
    'message' => _('Request protocol invalid')
  ];
}

// Validate required fields
else if (false === $data = json_decode($_POST['data']))
{
  $response =
  [
    'status'  => false,
    'message' => _('Could not decode data requested')
  ];
}

// Import begin
else
{
  $response =
  [
    'status'  => true,
    'message' => sprintf(
      _('Connection for "%s" established'),
      $_SERVER['REMOTE_ADDR']
    )
  ];

  // Init alias registry
  $aliasUserId = [];
  $aliasMagnetId = [];

  try {

    // Transaction begin
    $db->beginTransaction();

    // Process request
    foreach ((object) $data as $field => $remote)
    {
      // Process alias fields
      switch ($field)
      {
        case 'user':

          if (!API_IMPORT_USERS_ENABLED)
          {
            $response['user'][] = [
              'status'  => false,
              'message' => _('Users import disabled on this node. Related content skipped.')
            ];

            continue 2;
          }

          // Validate remote fields
          $error = [];

          if (!Valid::user($remote, $error))
          {
            $response['user'][] = [
              'status'  => false,
              'message' => sprintf(
                _('User data mismatch protocol with error: %s'),
                print_r($error, true)
              ),
            ];

            continue 2;
          }

          // Skip import on user approved required
          if (API_IMPORT_USERS_APPROVED_ONLY && !$remote->approved)
          {
            $response['user'][] = [
              'status'  => false,
              'message' => _('Node accepting approved users only')
            ];

            continue 2;
          }

          // Init local user by remote address
          if (!$local = $db->getUser($db->initUserId($remote->address,
                                                      USER_AUTO_APPROVE_ON_IMPORT_APPROVED ? $remote->approved : USER_DEFAULT_APPROVED,
                                                      $remote->timeAdded)))
          {
            $response['user'][] = [
              'status'  => false,
              'message' => _('Could not init user profile')
            ];

            continue 2;
          }

          else
          {
            $response['user'][] = [
              'status'  => true,
              'message' => sprintf(
                _('User profile successfully associated with ID "%s"'),
                $local->userId
              )
            ];
          }

          // Register user alias
          $aliasUserId[$remote->userId] = $local->userId;

          // Update time added if newer
          if ($local->timeAdded < $remote->timeAdded)
          {
            $db->updateUserTimeAdded(
              $local->userId,
              $remote->timeAdded
            );

            $response['user'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Field "timeAdded" changed to newer value for user ID "%s"'),
                $local->userId
              )
            ];
          }

          // Update user info if newer
          if ($local->timeUpdated < $remote->timeUpdated)
          {
            // Update time updated
            $db->updateUserTimeUpdated(
              $local->userId,
              $remote->timeUpdated
            );

            $response['user'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Field "timeUpdated" changed to newer value for user ID "%s"'),
                $local->userId
              )
            ];

            // Update approved for existing user
            if (USER_AUTO_APPROVE_ON_IMPORT_APPROVED && $local->approved !== $remote->approved && $remote->approved)
            {
              $db->updateUserApproved(
                $local->userId,
                $remote->approved,
                $remote->timeUpdated
              );

              $response['user'][] = [
                'status'  => true,
                'message' => sprintf(
                  _('Field "approved" changed to newer value for user ID "%s"'),
                  $local->userId
                )
              ];
            }

            // Set public as received remotely
            if (!$local->public)
            {
              $db->updateUserPublic(
                $local->userId,
                true,
                $remote->timeUpdated
              );

              $response['user'][] = [
                'status'  => true,
                'message' => sprintf(
                  _('Field "public" changed to newer value for user ID "%s"'),
                  $local->userId
                )
              ];
            }
          }

        break;
        case 'magnet':

          if (!API_IMPORT_MAGNETS_ENABLED)
          {
            $response['magnet'][] = [
              'status'  => false,
              'message' => _('Magnets import disabled on this node. Related content skipped.')
            ];

            continue 2;
          }

          // Validate remote fields
          $error = [];

          if (!Valid::magnet($remote, $error))
          {
            $response['magnet'][] = [
              'status'  => false,
              'message' => sprintf(
                _('Magnet data mismatch protocol with error: %s'),
                print_r($error, true)
              ),
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]))
          {
            $response['magnet'][] = [
              'status'  => false,
              'message' => _('User data relation not found for magnet'),
            ];

            continue 2;
          }

          // Skip import on magnet approved required
          if (API_IMPORT_MAGNETS_APPROVED_ONLY && !$remote->approved)
          {
            $response['magnet'][] = [
              'status'  => false,
              'message' => _('Node accepting approved magnets only')
            ];

            continue 2;
          }

          /// Add new magnet if not exist by timestamp added for this user
          if ($local = $db->findMagnet($aliasUserId[$remote->userId], $remote->timeAdded))
          {
            $response['magnet'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet successfully associated with ID "%s"'),
                $local->magnetId
              )
            ];
          }

          /// Add and init new magnet if not exist
          else if ($local = $db->getMagnet(
                  $db->addMagnet(
                    $aliasUserId[$remote->userId],
                    $remote->xl,
                    $remote->dn,
                    '', // @TODO linkSource used for debug only, will be deleted later
                    true,
                    $remote->comments,
                    $remote->sensitive,
                    MAGNET_AUTO_APPROVE_ON_IMPORT_APPROVED ? $remote->approved : MAGNET_DEFAULT_APPROVED,
                    $remote->timeAdded
                  )
                )
              )
            {
              $response['magnet'][] = [
                'status'  => true,
                'message' => sprintf(
                  _('Magnet successfully synced with ID "%s"'),
                  $local->magnetId
                )
              ];
            }

          else
          {
            $response['magnet'][] = [
              'status'  => false,
              'message' => sprintf(
                _('Could not init magnet: %s'),
                $remote
              )
            ];

            continue 2;
          }

          /// Add magnet alias for this host
          $aliasMagnetId[$remote->magnetId] = $local->magnetId;

          /// Update time added if newer
          if ($local->timeAdded < $remote->timeAdded)
          {
            $db->updateMagnetTimeAdded(
              $local->magnetId,
              $remote->timeAdded
            );

            $response['magnet'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Field "timeAdded" changed to newer value for magnet ID "%s"'),
                $local->magnetId
              )
            ];
          }

          /// Update info if remote newer
          if ($local->timeUpdated < $remote->timeUpdated)
          {
            // Magnet fields
            $db->updateMagnetXl($local->magnetId, $remote->xl, $remote->timeUpdated);
            $db->updateMagnetDn($local->magnetId, $remote->dn, $remote->timeUpdated);
            $db->updateMagnetTitle($local->magnetId, $remote->title, $remote->timeUpdated);
            $db->updateMagnetPreview($local->magnetId, $remote->preview, $remote->timeUpdated);
            $db->updateMagnetDescription($local->magnetId, $remote->description, $remote->timeUpdated);
            $db->updateMagnetComments($local->magnetId, $remote->comments, $remote->timeUpdated);
            $db->updateMagnetSensitive($local->magnetId, $remote->sensitive, $remote->timeUpdated);

            if (MAGNET_AUTO_APPROVE_ON_IMPORT_APPROVED && $local->approved !== $remote->approved && $remote->approved)
            {
              $db->updateMagnetApproved($local->magnetId, $remote->approved, $remote->timeUpdated);
            }

            // xt
            foreach ((array) $remote->xt as $xt)
            {
              switch ($xt->version)
              {
                case 1:

                  $exist = false;

                  foreach ($db->findMagnetToInfoHashByMagnetId($local->magnetId) as $result)
                  {
                    if ($infoHash = $db->getInfoHash($result->infoHashId))
                    {
                      if ($infoHash->version == 1)
                      {
                        $exist = true;
                      }
                    }
                  }

                  if (!$exist)
                  {
                    $db->addMagnetToInfoHash(
                      $local->magnetId,
                      $db->initInfoHashId(
                        $xt->value, 1
                      )
                    );
                  }

                break;

                case 2:

                  $exist = false;

                  foreach ($db->findMagnetToInfoHashByMagnetId($local->magnetId) as $result)
                  {
                    if ($infoHash = $db->getInfoHash($result->infoHashId))
                    {
                      if ($infoHash->version == 2)
                      {
                        $exist = true;
                      }
                    }
                  }

                  if (!$exist)
                  {
                    $db->addMagnetToInfoHash(
                      $local->magnetId,
                      $db->initInfoHashId(
                        $xt->value, 2
                      )
                    );
                  }

                break;
              }
            }

            // kt
            $db->deleteMagnetToKeywordTopicByMagnetId($local->magnetId);

            foreach ($remote->kt as $kt)
            {
              $db->initMagnetToKeywordTopicId(
                $local->magnetId,
                $db->initKeywordTopicId(trim(mb_strtolower($kt)))
              );
            }

            // tr
            $db->deleteMagnetToAddressTrackerByMagnetId($local->magnetId);

            foreach ($remote->tr as $tr)
            {
              if ($url = Yggverse\Parser\Url::parse($tr))
              {
                $db->initMagnetToAddressTrackerId(
                  $local->magnetId,
                  $db->initAddressTrackerId(
                    $db->initSchemeId($url->host->scheme),
                    $db->initHostId($url->host->name),
                    $db->initPortId($url->host->port),
                    $db->initUriId($url->page->uri)
                  )
                );
              }
            }

            // as
            $db->deleteMagnetToAcceptableSourceByMagnetId($local->magnetId);

            foreach ($remote->as as $as)
            {
              if ($url = Yggverse\Parser\Url::parse($as))
              {
                $db->initMagnetToAcceptableSourceId(
                  $local->magnetId,
                  $db->initAcceptableSourceId(
                    $db->initSchemeId($url->host->scheme),
                    $db->initHostId($url->host->name),
                    $db->initPortId($url->host->port),
                    $db->initUriId($url->page->uri)
                  )
                );
              }
            }

            // xs
            $db->deleteMagnetToExactSourceByMagnetId($local->magnetId);

            foreach ($remote->xs as $xs)
            {
              if ($url = Yggverse\Parser\Url::parse($xs))
              {
                $db->initMagnetToExactSourceId(
                  $local->magnetId,
                  $db->initExactSourceId(
                    $db->initSchemeId($url->host->scheme),
                    $db->initHostId($url->host->name),
                    $db->initPortId($url->host->port),
                    $db->initUriId($url->page->uri)
                  )
                );
              }
            }

            $response['magnet'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet fields updated to newer version for magnet ID "%s"'),
                $local->magnetId
              )
            ];
          }

        break;
        case 'magnetComment':

          if (!API_IMPORT_MAGNET_COMMENTS_ENABLED)
          {
            $response['magnetComment'][] = [
              'status'  => false,
              'message' => _('Magnet comments import disabled on this node')
            ];

            continue 2;
          }

          // Validate
          $error = [];

          if (!Valid::magnetComment($remote, $error))
          {
            $response['magnetComment'][] = [
              'status'  => false,
              'message' => sprintf(
                _('Magnet comment data mismatch protocol with error: %s'),
                print_r($error, true)
              ),
            ];

            continue 2;
          }

          // Skip import on magnet approved required
          if (API_IMPORT_MAGNET_COMMENTS_APPROVED_ONLY && !$remote->approved)
          {
            $response['magnetComment'][] = [
              'status'  => false,
              'message' => _('Node accepting approved magnet comments only: %s')
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]) || !isset($aliasMagnetId[$remote->magnetId]))
          {
            $response['magnetComment'][] = [
              'status'  => false,
              'message' => _('Magnet comment data relation not found for: %s')
            ];

            continue 2;
          }

          // Parent comment provided
          if (is_int($remote->magnetCommentIdParent))
          {
            $localMagnetCommentIdParent = null; // @TODO feature not in use yet
          }

          else
          {
            $localMagnetCommentIdParent = null;
          }

          // Magnet comment exists by timestamp added for this user
          if ($local = $db->findMagnetComment($aliasMagnetId[$remote->magnetId],
                                              $aliasUserId[$remote->userId],
                                              $remote->timeAdded))
          {
            $response['magnetComment'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet comment successfully associated with ID "%s"'),
                $local->magnetCommentId
              )
            ];
          }

          // Magnet comment exists by timestamp added for this user, register new one
          else if ($magnetCommentId = $db->addMagnetComment($aliasMagnetId[$remote->magnetId],
                                                            $aliasUserId[$remote->userId],
                                                            $localMagnetCommentIdParent,
                                                            $remote->value,
                                                            $remote->approved,
                                                            true,
                                                            $remote->timeAdded))
          {
            $response['magnetComment'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet comment successfully synced with ID "%s"'),
                $magnetCommentId
              )
            ];
          }

        break;
        case 'magnetDownload':

          // Magnet downloads
          if (!API_IMPORT_MAGNET_DOWNLOADS_ENABLED)
          {
            $response['magnetDownload'][] = [
              'status'  => false,
              'message' => _('Magnet downloads import disabled on this node')
            ];

            continue 2;
          }

          // Validate
          $error = [];

          if (!Valid::magnetDownload($remote, $error))
          {
            $response['magnetDownload'][] = [
              'status'  => false,
              'message' => sprintf(
                _('Magnet download data mismatch protocol with error: %s'),
                print_r($error, true)
              ),
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]) || !isset($aliasMagnetId[$remote->magnetId]))
          {
            $response['magnetDownload'][] = [
              'status'  => false,
              'message' => _('Magnet download data relation not found')
            ];

            continue 2;
          }

          // Magnet download exists by timestamp added for this user
          if ($local = $db->findMagnetDownload($aliasMagnetId[$remote->magnetId],
                                                $aliasUserId[$remote->userId],
                                                $remote->timeAdded))
          {
            $response['magnetDownload'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet download successfully associated with ID "%s"'),
                $local->magnetDownloadId
              )
            ];
          }

          // Magnet download exists by timestamp added for this user, register new one
          else if ($magnetDownloadId = $db->addMagnetDownload($aliasMagnetId[$remote->magnetId],
                                                              $aliasUserId[$remote->userId],
                                                              $remote->timeAdded))
          {
            $response['magnetDownload'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet download successfully synced with ID "%s"'),
                $magnetDownloadId
              )
            ];
          }

        break;
        case 'magnetStar':

          if (!API_IMPORT_MAGNET_STARS_ENABLED)
          {
            $response['magnetStar'][] = [
              'status'  => false,
              'message' => _('Magnet stars import disabled on this node')
            ];

            continue 2;
          }

          // Validate
          $error = [];

          if (!Valid::magnetStar($remote, $error))
          {
            $response['magnetStar'][] = [
              'status'  => false,
              'message' => sprintf(
                _('Magnet star data mismatch protocol with error: %s'),
                print_r($error, true)
              ),
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]) || !isset($aliasMagnetId[$remote->magnetId]))
          {
            $response['magnetStar'][] = [
              'status'  => false,
              'message' => _('Magnet star data relation not found')
            ];

            continue 2;
          }

          // Magnet star exists by timestamp added for this user
          if ($local = $db->findMagnetStar($aliasMagnetId[$remote->magnetId],
                                           $aliasUserId[$remote->userId],
                                           $remote->timeAdded))
          {
            $response['magnetStar'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet star successfully associated with ID "%s"'),
                $local->magnetStarId
              )
            ];
          }

          // Magnet star exists by timestamp added for this user, register new one
          else if ($magnetStarId = $db->addMagnetStar($aliasMagnetId[$remote->magnetId],
                                                      $aliasUserId[$remote->userId],
                                                      $remote->value,
                                                      $remote->timeAdded))
          {
            $response['magnetStar'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet star successfully synced with ID "%s"'),
                $magnetStarId
              )
            ];
          }

        break;
        case 'magnetView':

          if (!API_IMPORT_MAGNET_VIEWS_ENABLED)
          {
            $response['magnetView'][] = [
              'status'  => false,
              'message' => _('Magnet views import disabled on this node')
            ];

            continue 2;
          }

          // Validate
          $error = [];

          if (!Valid::magnetView($remote, $error))
          {
            $response['magnetView'][] = [
              'status'  => false,
              'message' => sprintf(
                _('Magnet view data mismatch protocol with error: %s'),
                print_r($error, true)
              ),
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]) || !isset($aliasMagnetId[$remote->magnetId]))
          {
            $response['magnetView'][] = [
              'status'  => false,
              'message' => _('Magnet view data relation not found for: %s')
            ];

            continue 2;
          }

          // Magnet view exists by timestamp added for this user
          if ($local = $db->findMagnetView($aliasMagnetId[$remote->magnetId],
                                            $aliasUserId[$remote->userId],
                                            $remote->timeAdded))
          {
            $response['magnetView'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet view successfully associated with ID "%s"'),
                $local->magnetViewId
              )
            ];
          }

          // Magnet view exists by timestamp added for this user, register new one
          else if ($magnetViewId = $db->addMagnetView($aliasMagnetId[$remote->magnetId],
                                                      $aliasUserId[$remote->userId],
                                                      $remote->timeAdded))
          {
            $response['magnetView'][] = [
              'status'  => true,
              'message' => sprintf(
                _('Magnet view successfully synced with ID "%s"'),
                $magnetViewId
              )
            ];
          }

        break;
        default:

          $response[$field][] =
          [
            'status'  => false,
            'message' => _('Field "%s" not supported by protocol')
          ];

          continue 2;
      }
    }

    $db->commit();
  }

  catch (Exception $error)
  {
    $debug['exception'][] = print_r($error, true);

    $db->rollBack();
  }
}

// Debug log
if (LOG_API_PUSH_ENABLED)
{
  @mkdir(LOG_DIRECTORY, 0770, true);

  if ($handle = fopen(LOG_DIRECTORY . '/' . LOG_API_PUSH_FILENAME, 'a+'))
  {
    $debug['time']['total']   = microtime(true) - $debug['time']['total'];

    $debug['memory']['total'] = memory_get_usage() - $debug['memory']['start'];
    $debug['memory']['peaks'] = memory_get_peak_usage();

    $debug['db']['total']['select'] = $db->getDebug()->query->select->total;
    $debug['db']['total']['insert'] = $db->getDebug()->query->insert->total;
    $debug['db']['total']['update'] = $db->getDebug()->query->update->total;
    $debug['db']['total']['delete'] = $db->getDebug()->query->delete->total;

    fwrite(
      $handle,
      print_r(
        [
          'response' => $response,
          'debug'    => $debug
        ],
        true
      )
    );

    fclose($handle);

    chmod(LOG_DIRECTORY . '/' . LOG_API_PUSH_FILENAME, 0770);
  }
}

// Output
header('Content-Type: application/json; charset=utf-8');

echo json_encode($response);