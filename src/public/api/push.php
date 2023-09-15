<?php

// Bootstrap
require_once __DIR__ . '/../config/bootstrap.php';

// Define response
$response =
[
  'status'  => false,
  'message' => _('Request failed')
];

// Init connections whitelist
$connectionWhiteList = [];

foreach (json_decode(file_get_contents(__DIR__ . '/../../config/nodes.json')) as $node)
{
  // Skip non-condition addresses
  if (!Valid::url($node->manifest))
  {
    $response =
    [
      'status'  => false,
      'message' => Valid::getError()
    ];

    continue;
  }

  // Skip current host
  $thisUrl     = Yggverse\Parser\Url::parse(WEBSITE_URL);
  $manifestUrl = Yggverse\Parser\Url::parse($node->manifest);

  if (empty($manifestUrl->host->name) ||
      empty($manifestUrl->host->name) ||
      $manifestUrl->host->name == $thisUrl->host->name) // @TODO some mirrors could be available, improve condition
  {
    continue;
  }

  $connectionWhiteList[] = $manifestUrl->host->name;
}

// API import enabled
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
    'message' => _('Push API disabled')
  ];
}

// Yggdrasil connections only
else if (!Valid::host($_SERVER['REMOTE_ADDR']))
{
  $response =
  [
    'status'  => false,
    'message' => _('Yggdrasil connection required for this action')
  ];
}

// Init session
else if (!in_array($_SERVER['REMOTE_ADDR'], $connectionWhiteList))
{
  $response =
  [
    'status'  => false,
    'message' => _('Access denied for this host')
  ];
}

// Init session
else if (!$userId = $db->initUserId($_SERVER['REMOTE_ADDR'], USER_DEFAULT_APPROVED, time()))
{
  $response =
  [
    'status'  => false,
    'message' => _('Could not init user session')
  ];
}

// Get user
else if (!$user = $db->getUser($userId))
{
  $response =
  [
    'status'  => false,
    'message' => _('Could not init user info')
  ];
}

// Validate required fields
else if (empty($_POST))
{
  $response =
  [
    'status'  => false,
    'message' => _('Import data required')
  ];
}

// Import begin
else
{
  // Init alias registry
  $aliasUserId = [];
  $aliasMagnetId = [];

  // Process request
  foreach ((object) $_POST as $field => $remote)
  {
    try
    {
      // Transaction begin
      $db->beginTransaction();

      // Process alias fields
      switch ($field)
      {
        case 'user':

          if (!API_IMPORT_USERS_ENABLED)
          {
            $response = [
              'status'  => false,
              'message' => _('Users import disabled on this node. Related content skipped.')
            ];

            continue 2;
          }

          // Validate remote fields
          if (!Valid::user($remote))
          {
            $response = [
              'status'  => false,
              'message' => Valid::getError()
            ];

            continue 2;
          }

          // Skip import on user approved required
          if (API_IMPORT_USERS_APPROVED_ONLY && !$remote->approved)
          {
            $response = [
              'status'  => false,
              'message' => _('This host not accept approved users only')
            ];

            continue 2;
          }

          // Init local user by remote address
          if (!$local = $db->getUser($db->initUserId($remote->address,
                                                     USER_AUTO_APPROVE_ON_IMPORT_APPROVED ? $remote->approved : USER_DEFAULT_APPROVED,
                                                     $remote->timeAdded)))
          {
            $response = [
              'status'  => false,
              'message' => _('Could not init user')
            ];

            continue 2;
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
          }

          // Update user info if newer
          if ($local->timeUpdated < $remote->timeUpdated)
          {
            // Update time updated
            $db->updateUserTimeUpdated(
              $local->userId,
              $remote->timeUpdated
            );

            // Update approved for existing user
            if (USER_AUTO_APPROVE_ON_IMPORT_APPROVED && $local->approved !== $remote->approved && $remote->approved)
            {
              $db->updateUserApproved(
                $local->userId,
                $remote->approved,
                $remote->timeUpdated
              );
            }

            // Set public as received remotely
            if (!$local->public)
            {
              $db->updateUserPublic(
                $local->userId,
                true,
                $remote->timeUpdated
              );
            }
          }

          $response = [
            'status'  => true,
            'message' => _('User registered')
          ];

        break;
        case 'magnet':

          if (!API_IMPORT_MAGNETS_ENABLED)
          {
            $response = [
              'status'  => false,
              'message' => _('Magnets import disabled on this node. Related content skipped.')
            ];

            continue 2;
          }

          // Validate remote fields
          if (!Valid::magnet($remote))
          {
            $response = [
              'status'  => false,
              'message' => Valid::getError()
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]))
          {
            $response = [
              'status'  => false,
              'message' => _('User data required for this action')
            ];

            continue 2;
          }

          // Skip import on magnet approved required
          if (API_IMPORT_MAGNETS_APPROVED_ONLY && !$remote->approved)
          {
            $response = [
              'status'  => false,
              'message' => _('Node accept approved magnets only')
            ];

            continue 2;
          }

          /// Add new magnet if not exist by timestamp added for this user
          if (!$local = $db->findMagnet($aliasUserId[$remote->userId], $remote->timeAdded))
          {
               $local = $db->getMagnet(
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
               );
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

                  if (Yggverse\Parser\Magnet::isXTv1($xt->value))
                  {
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
                          Yggverse\Parser\Magnet::filterInfoHash($xt->value), 1
                        )
                      );
                    }
                  }

                break;

                case 2:

                  if (Yggverse\Parser\Magnet::isXTv2($xt->value))
                  {
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
                          Yggverse\Parser\Magnet::filterInfoHash($xt->value), 2
                        )
                      );
                    }
                  }

                break;
              }
            }

            // kt
            foreach ($remote->kt as $kt)
            {
              $db->initMagnetToKeywordTopicId(
                $local->magnetId,
                $db->initKeywordTopicId(trim(mb_strtolower(strip_tags(html_entity_decode($kt)))))
              );
            }

            // tr
            foreach ($remote->tr as $tr)
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

            // as
            foreach ($remote->as as $as)
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

            // xs
            foreach ($remote->xs as $xs)
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

          $response = [
            'status'  => true,
            'message' => _('Magnet registered')
          ];

        break;
        case 'magnetComment':

          if (!API_IMPORT_MAGNET_COMMENTS_ENABLED)
          {
            $response = [
              'status'  => false,
              'message' => _('Magnet comments import disabled on this node')
            ];

            continue 2;
          }

          // Validate remote fields
          if (!Valid::magnetComment($remote))
          {
            $response = [
              'status'  => false,
              'message' => Valid::getError()
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]) || !isset($aliasMagnetId[$remote->magnetId]))
          {
            $response = [
              'status'  => false,
              'message' => _('User and magnet data required for magnet comments import')
            ];

            continue 2;
          }

          // Skip import on magnet approved required
          if (API_IMPORT_MAGNET_COMMENTS_APPROVED_ONLY && !$remote->approved)
          {
            $response = [
              'status'  => false,
              'message' => _('Node accept approved magnet comments only')
            ];

            continue 2;
          }

          // Add new magnet comment if not exist by timestamp added for this user
          if (!$db->findMagnetComment($aliasMagnetId[$remote->magnetId],
                                      $aliasUserId[$remote->userId],
                                      $remote->timeAdded))
          {
            // Parent comment provided
            if (is_int($remote->magnetCommentIdParent))
            {
              $localMagnetCommentIdParent = null; // @TODO feature not in use yet
            }

            else
            {
              $localMagnetCommentIdParent = null;
            }

            $db->addMagnetComment(
              $aliasMagnetId[$remoteMagnetComment->magnetId],
              $aliasUserId[$remoteMagnetComment->userId],
              $localMagnetCommentIdParent,
              $remote->value,
              $remote->approved,
              true,
              $remote->timeAdded
            );
          }

          $response = [
            'status'  => true,
            'message' => _('Magnet comment registered')
          ];

        break;
        case 'magnetDownload':

          // Magnet downloads
          if (!API_IMPORT_MAGNET_DOWNLOADS_ENABLED)
          {
            $response = [
              'status'  => false,
              'message' => _('Magnet downloads import disabled on this node')
            ];

            continue 2;
          }

          // Validate
          if (!Valid::magnetDownload($remote))
          {
            $response = [
              'status'  => false,
              'message' => Valid::getError()
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]) || !isset($aliasMagnetId[$remote->magnetId]))
          {
            $response = [
              'status'  => false,
              'message' => _('User and magnet data required for magnet downloads import')
            ];

            continue 2;
          }

          // Add new magnet download if not exist by timestamp added for this user
          if (!$db->findMagnetDownload($aliasMagnetId[$remote->magnetId],
                                       $aliasUserId[$remote->userId],
                                       $remote->timeAdded))
          {
            $db->addMagnetDownload(
              $aliasMagnetId[$remote->magnetId],
              $aliasUserId[$remote->userId],
              $remote->timeAdded
            );
          }

          $response = [
            'status'  => true,
            'message' => _('Magnet download registered')
          ];

        break;
        case 'magnetStar':

          if (!API_IMPORT_MAGNET_STARS_ENABLED)
          {
            $response = [
              'status'  => false,
              'message' => _('Magnet stars import disabled on this node')
            ];

            continue 2;
          }

          // Validate
          if (!Valid::magnetStar($remote))
          {
            $response = [
              'status'  => false,
              'message' => Valid::getError()
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]) || !isset($aliasMagnetId[$remote->magnetId]))
          {
            $response = [
              'status'  => false,
              'message' => _('User and magnet data required for magnet stars import')
            ];

            continue 2;
          }

          // Add new magnet star if not exist by timestamp added for this user
          if (!$db->findMagnetStar($aliasMagnetId[$remote->magnetId],
                                   $aliasUserId[$remote->userId],
                                   $remote->timeAdded))
          {
            $db->addMagnetStar(
              $aliasMagnetId[$remote->magnetId],
              $aliasUserId[$remote->userId],
              $remote->value,
              $remote->timeAdded
            );
          }

          $response = [
            'status'  => true,
            'message' => _('Magnet star registered')
          ];

        break;
        case 'magnetView':

          if (!API_IMPORT_MAGNET_VIEWS_ENABLED)
          {
            $response = [
              'status'  => false,
              'message' => _('Magnet views import disabled on this node')
            ];

            continue 2;
          }

          // Validate
          if (!Valid::magnetView($remote))
          {
            $response = [
              'status'  => false,
              'message' => Valid::getError()
            ];

            continue 2;
          }

          // User local alias required
          if (!isset($aliasUserId[$remote->userId]) || !isset($aliasMagnetId[$remote->magnetId]))
          {
            $response = [
              'status'  => false,
              'message' => _('User and magnet data required for magnet views import')
            ];

            continue 2;
          }

          // Add new magnet view if not exist by timestamp added for this user
          if (!$db->findMagnetView($aliasMagnetId[$remote->magnetId],
                                   $aliasUserId[$remote->userId],
                                   $remote->timeAdded))
          {
            $db->addMagnetView(
              $aliasMagnetId[$remote->magnetId],
              $aliasUserId[$remote->userId],
              $remote->timeAdded
            );
          }

          $response = [
            'status'  => true,
            'message' => _('Magnet view registered')
          ];

        break;
        default:
          $response =
          [
            'status'  => false,
            'message' => _('Data type not supported')
          ];
      }

      $db->commit();
    }

    catch (EXception $error)
    {
      $db->rollBack();

      $response =
      [
        'status'  => false,
        'message' => $error
      ];
    }
  }
}

// Output
header('Content-Type: application/json; charset=utf-8');

echo json_encode($response);