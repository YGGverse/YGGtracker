<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.import.feed'), 1);

if (false === sem_acquire($semaphore, true))
{
  exit (PHP_EOL . 'yggtracker.crontab.import.feed process locked by another thread.' . PHP_EOL);
}

// Bootstrap
require_once __DIR__ . '/../../config/bootstrap.php';

if (empty(API_IMPORT_ENABLED))
{
  exit;
}

// Init Debug
$debug =
[
  'time' => [
    'ISO8601' => date('c'),
    'total'   => microtime(true),
  ],
];

// Begin export
try
{
  // Transaction begin
  $db->beginTransaction();

  foreach (json_decode(
    file_get_contents(__DIR__ . '/../../config/nodes.json')
  ) as $node)
  {
    // Skip non-condition addresses
    if (!Valid::url($node->manifest))
    {
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

    // Manifest
    if ($manifest = @json_decode(@file_get_contents($node->manifest)))
    {
      // Feed channel exists
      if (empty($manifest->export))
      {
        continue;
      }

      // Users
      if (API_IMPORT_USERS_ENABLED)
      {
        if (empty($manifest->export->users))
        {
          continue;
        }

        // Init alias registry for this host
        $aliasUserId = [];

        foreach (@json_decode(@file_get_contents($manifest->export->users)) as $remoteUser)
        {
          // Validate required fields
          if (!Valid::user($remoteUser))
          {
            continue;
          }

          // Skip import on user approved required
          if (API_IMPORT_USERS_APPROVED_ONLY && !$remoteUser->approved)
          {
            continue;
          }

          // Init session
          else if (!$localUser = $db->getUser(
            $db->initUserId($remoteUser->address,
                            USER_AUTO_APPROVE_ON_IMPORT_APPROVED ? $remoteUser->approved : USER_DEFAULT_APPROVED,
                            $remoteUser->timeAdded)))
          {
            continue;
          }

          // Remember user ID for this host
          $aliasUserId[$remoteUser->userId] = $localUser->userId;

          // Update time added if newer
          if ($localUser->timeAdded < $remoteUser->timeAdded)
          {
            $db->updateUserTimeAdded(
              $localUser->userId,
              $remoteUser->timeAdded
            );
          }

          // Update user info if newer
          if ($localUser->timeUpdated < $remoteUser->timeUpdated)
          {
            // Update time updated
            $db->updateUserTimeUpdated(
              $localUser->userId,
              $remoteUser->timeUpdated
            );

            // Update approved for existing user
            if (USER_AUTO_APPROVE_ON_IMPORT_APPROVED && $localUser->approved !== $remoteUser->approved && $remoteUser->approved)
            {
              $db->updateUserApproved(
                $localUser->userId,
                $remoteUser->approved,
                $remoteUser->timeUpdated
              );
            }

            // Set public as received remotely
            if (!$localUser->public)
            {
              $db->updateUserPublic(
                $localUser->userId,
                true,
                $remoteUser->timeUpdated
              );
            }
          }
        }

        // Magnets
        if (API_IMPORT_MAGNETS_ENABLED)
        {
          if (empty($manifest->export->magnets))
          {
            continue;
          }

          // Init alias registry for this host
          $aliasMagnetId = [];

          foreach (@json_decode(@file_get_contents($manifest->export->magnets)) as $remoteMagnet)
          {
            // Validate required fields by protocol
            if (!Valid::magnet($remoteMagnet))
            {
              continue;
            }

            // Aliases check
            if (!isset($aliasUserId[$remoteMagnet->userId]))
            {
              continue;
            }

            // Skip import on magnet approved required
            if (API_IMPORT_MAGNETS_APPROVED_ONLY && !$remoteMagnet->approved)
            {
              continue;
            }

            // Add new magnet if not exist by timestamp added for this user
            if (!$localMagnet = $db->findMagnet($aliasUserId[$remoteMagnet->userId], $remoteMagnet->timeAdded))
            {
              $localMagnet = $db->getMagnet(
                $db->addMagnet(
                  $aliasUserId[$remoteMagnet->userId],
                  $remoteMagnet->xl,
                  $remoteMagnet->dn,
                  '', // @TODO linkSource used for debug only, will be deleted later
                  true,
                  $remoteMagnet->comments,
                  $remoteMagnet->sensitive,
                  MAGNET_AUTO_APPROVE_ON_IMPORT_APPROVED ? $remoteMagnet->approved : MAGNET_DEFAULT_APPROVED,
                  $remoteMagnet->timeAdded
                )
              );
            }

            // Add magnet alias for this host
            $aliasMagnetId[$remoteMagnet->magnetId] = $localMagnet->magnetId;

            // Update time added if newer
            if ($localMagnet->timeAdded < $remoteMagnet->timeAdded)
            {
              $db->updateMagnetTimeAdded(
                $localMagnet->magnetId,
                $remoteMagnet->timeAdded
              );
            }

            // Update info if remote newer
            if ($localMagnet->timeUpdated < $remoteMagnet->timeUpdated)
            {
              // Magnet fields
              $db->updateMagnetXl($localMagnet->magnetId, $remoteMagnet->xl, $remoteMagnet->timeUpdated);
              $db->updateMagnetDn($localMagnet->magnetId, $remoteMagnet->dn, $remoteMagnet->timeUpdated);
              $db->updateMagnetTitle($localMagnet->magnetId, $remoteMagnet->title, $remoteMagnet->timeUpdated);
              $db->updateMagnetPreview($localMagnet->magnetId, $remoteMagnet->preview, $remoteMagnet->timeUpdated);
              $db->updateMagnetDescription($localMagnet->magnetId, $remoteMagnet->description, $remoteMagnet->timeUpdated);
              $db->updateMagnetComments($localMagnet->magnetId, $remoteMagnet->comments, $remoteMagnet->timeUpdated);
              $db->updateMagnetSensitive($localMagnet->magnetId, $remoteMagnet->sensitive, $remoteMagnet->timeUpdated);

              if (MAGNET_AUTO_APPROVE_ON_IMPORT_APPROVED && $localMagnet->approved !== $remoteMagnet->approved && $remoteMagnet->approved)
              {
                $db->updateMagnetApproved($localMagnet->magnetId, $remoteMagnet->approved, $remoteMagnet->timeUpdated);
              }

              // xt
              foreach ((array) $remoteMagnet->xt as $xt)
              {
                switch ($xt->version)
                {
                  case 1:

                    if (Yggverse\Parser\Magnet::isXTv1($xt->value))
                    {
                      $exist = false;

                      foreach ($db->findMagnetToInfoHashByMagnetId($localMagnet->magnetId) as $result)
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
                          $localMagnet->magnetId,
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

                      foreach ($db->findMagnetToInfoHashByMagnetId($localMagnet->magnetId) as $result)
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
                          $localMagnet->magnetId,
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
              foreach ($remoteMagnet->kt as $kt)
              {
                $db->initMagnetToKeywordTopicId(
                  $localMagnet->magnetId,
                  $db->initKeywordTopicId(trim(mb_strtolower(strip_tags(html_entity_decode($kt)))))
                );
              }

              // tr
              foreach ($remoteMagnet->tr as $tr)
              {
                $db->initMagnetToAddressTrackerId(
                  $localMagnet->magnetId,
                  $db->initAddressTrackerId(
                    $db->initSchemeId($url->host->scheme),
                    $db->initHostId($url->host->name),
                    $db->initPortId($url->host->port),
                    $db->initUriId($url->page->uri)
                  )
                );
              }

              // as
              foreach ($remoteMagnet->as as $as)
              {
                $db->initMagnetToAcceptableSourceId(
                  $localMagnet->magnetId,
                  $db->initAcceptableSourceId(
                    $db->initSchemeId($url->host->scheme),
                    $db->initHostId($url->host->name),
                    $db->initPortId($url->host->port),
                    $db->initUriId($url->page->uri)
                  )
                );
              }

              // xs
              foreach ($remoteMagnet->xs as $xs)
              {
                $db->initMagnetToExactSourceId(
                  $localMagnet->magnetId,
                  $db->initExactSourceId(
                    $db->initSchemeId($url->host->scheme),
                    $db->initHostId($url->host->name),
                    $db->initPortId($url->host->port),
                    $db->initUriId($url->page->uri)
                  )
                );
              }
            }
          }

          // Magnet comments
          if (API_IMPORT_MAGNET_COMMENTS_ENABLED)
          {
            if (empty($manifest->export->magnetComments))
            {
              continue;
            }

            foreach (@json_decode(@file_get_contents($manifest->export->magnetComments)) as $remoteMagnetComment)
            {
              // Validate
              if (!Valid::magnetComment($remoteMagnetComment))
              {
                continue;
              }

              // Aliases check
              if (!isset($aliasMagnetId[$remoteMagnetComment->magnetId]) || !isset($aliasUserId[$remoteMagnetComment->userId]))
              {
                continue;
              }

              // Skip import on magnet comment approved required
              if (API_IMPORT_MAGNET_COMMENTS_APPROVED_ONLY && !$remoteMagnetComment->approved)
              {
                continue;
              }

              // Add new magnet comment if not exist by timestamp added for this user
              if (!$db->findMagnetComment($aliasMagnetId[$remoteMagnetComment->magnetId],
                                          $aliasUserId[$remoteMagnetComment->userId],
                                          $remoteMagnetComment->timeAdded))
              {
                // Parent comment provided
                if (is_int($remoteMagnetComment->magnetCommentIdParent))
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
                  $remoteMagnetComment->value,
                  $remoteMagnetComment->approved,
                  true,
                  $remoteMagnetComment->timeAdded
                );
              }
            }
          }

          // Magnet downloads
          if (API_IMPORT_MAGNET_DOWNLOADS_ENABLED)
          {
            if (empty($manifest->export->magnetDownloads))
            {
              continue;
            }

            foreach (@json_decode(@file_get_contents($manifest->export->magnetDownloads)) as $remoteMagnetDownload)
            {
              // Validate
              if (!Valid::magnetDownload($remoteMagnetDownload))
              {
                continue;
              }

              // Add new magnet download if not exist by timestamp added for this user
              if (!$db->findMagnetDownload($aliasMagnetId[$remoteMagnetDownload->magnetId],
                                           $aliasUserId[$remoteMagnetDownload->userId],
                                           $remoteMagnetDownload->timeAdded))
              {
                $db->addMagnetDownload(
                  $aliasMagnetId[$remoteMagnetDownload->magnetId],
                  $aliasUserId[$remoteMagnetDownload->userId],
                  $remoteMagnetDownload->timeAdded
                );
              }
            }
          }

          // Magnet views
          if (API_IMPORT_MAGNET_VIEWS_ENABLED)
          {
            if (empty($manifest->export->magnetViews))
            {
              continue;
            }

            foreach (@json_decode(@file_get_contents($manifest->export->magnetViews)) as $remoteMagnetView)
            {
              // Validate
              if (!Valid::magnetView($remoteMagnetView))
              {
                continue;
              }

              // Add new magnet view if not exist by timestamp added for this user
              if (!$db->findMagnetView($aliasMagnetId[$remoteMagnetView->magnetId],
                                       $aliasUserId[$remoteMagnetView->userId],
                                       $remoteMagnetView->timeAdded))
              {
                $db->addMagnetView(
                  $aliasMagnetId[$remoteMagnetView->magnetId],
                  $aliasUserId[$remoteMagnetView->userId],
                  $remoteMagnetView->timeAdded
                );
              }
            }
          }

          // Magnet stars
          if (API_IMPORT_MAGNET_STARS_ENABLED)
          {
            if (empty($manifest->export->magnetStars))
            {
              continue;
            }

            foreach (@json_decode(@file_get_contents($manifest->export->magnetStars)) as $remoteMagnetStar)
            {
              // Validate
              if (!Valid::magnetStar($remoteMagnetStar))
              {
                continue;
              }

              // Add new magnet star if not exist by timestamp added for this user
              if (!$db->findMagnetStar($aliasMagnetId[$remoteMagnetStar->magnetId],
                                       $aliasUserId[$remoteMagnetStar->userId],
                                       $remoteMagnetStar->timeAdded))
              {
                $db->addMagnetStar(
                  $aliasMagnetId[$remoteMagnetStar->magnetId],
                  $aliasUserId[$remoteMagnetStar->userId],
                  $remoteMagnetStar->value,
                  $remoteMagnetStar->timeAdded
                );
              }
            }
          }
        }
      }
    }
  }

  $db->commit();

} catch (EXception $e) {

  $db->rollBack();

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