<?php

// Lock multi-thread execution
$semaphore = sem_get(crc32('yggtracker.crontab.import.feed'), 1);

if (false === sem_acquire($semaphore, true))
{
  exit(_('yggtracker.crontab.import.feed process locked by another thread.'));
}

// Bootstrap
require_once __DIR__ . '/../../config/bootstrap.php';

if (empty(API_IMPORT_ENABLED))
{
  exit(_('Import disabled in settings'));
}

// Init debug
$debug =
[
  'dump' => [],
  'time' =>
  [
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
  'db' =>
  [
    'total' => [
      'select' => 0,
      'insert' => 0,
      'update' => 0,
      'delete' => 0,
    ]
  ],
];

// Begin import
try
{
  // Transaction begin
  $db->beginTransaction();

  foreach (json_decode(
    file_get_contents(__DIR__ . '/../../config/nodes.json')
  ) as $node)
  {
    // Manifest exists
    if (empty($node->manifest))
    {
      array_push(
        $debug['dump'],
        sprintf(
          _('Manifest URL not provided for this node: %s'),
          $node
        )
      );

      continue;
    }

    // Skip non-condition addresses
    $error = [];

    if (!Valid::url($node->manifest, $error))
    {
      array_push(
        $debug['dump'],
        sprintf(
          _('Manifest URL "%s" invalid: %s'),
          $node->manifest,
          print_r(
            $error,
            true
          )
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
    $curl = new Curl($node->manifest, API_USER_AGENT);

    $debug['http']['total']++;

    if (200 != $code = $curl->getCode())
    {
      array_push(
        $debug['dump'],
        sprintf(
          _('Manifest URL "%s" unreachable with code: "%s"'),
          $node->manifest,
          $code
        )
      );

      continue;
    }

    if (!$manifest = $curl->getResponse())
    {
      array_push(
        $debug['dump'],
        sprintf(
          _('Manifest URL "%s" has broken response'),
          $node->manifest
        )
      );

      continue;
    }

    if (empty($manifest->export))
    {
      array_push(
        $debug['dump'],
        sprintf(
          _('Manifest URL "%s" has broken protocol'),
          $node->manifest
        )
      );

      continue;
    }

    // Users
    if (API_IMPORT_USERS_ENABLED)
    {
      $error = [];

      if (!Valid::url($manifest->export->users, $error))
      {
        array_push(
          $debug['dump'],
          sprintf(
            _('Users feed URL "%s" invalid: %s'),
            $manifest->export->users,
            print_r(
              $error,
              true
            )
          )
        );

        continue;
      }

      // Call feed
      $curl = new Curl($manifest->export->users, API_USER_AGENT);

      $debug['http']['total']++;

      if (200 != $code = $curl->getCode())
      {
        array_push(
          $debug['dump'],
          sprintf(
            _('Users feed URL "%s" unreachable with code: "%s"'),
            $manifest->export->users,
            $code
          )
        );

        continue;
      }

      if (!$remoteUsers = $curl->getResponse())
      {
        array_push(
          $debug['dump'],
          sprintf(
            _('Users feed URL "%s" has broken response'),
            $manifest->export->users
          )
        );

        continue;
      }

      // Init alias registry for this host
      $aliasUserId = [];

      foreach ((object) $remoteUsers as $remoteUser)
      {
        // Validate required fields
        $error = [];

        if (!Valid::user($remoteUser, $error))
        {
          array_push(
            $debug['dump'],
            sprintf(
              _('Users feed URL "%s" has invalid protocol: "%s" error: "%s"'),
              $manifest->export->users,
              print_r(
                $remoteUser,
                true
              ),
              print_r(
                $error,
                true
              )
            )
          );

          continue;
        }

        // Skip import on user approved required
        if (API_IMPORT_USERS_APPROVED_ONLY && !$remoteUser->approved)
        {
          // No debug warnings in this case, continue next item

          continue;
        }

        // Init session
        else if (!$localUser = $db->getUser(
          $db->initUserId(
            $remoteUser->address,
            USER_AUTO_APPROVE_ON_IMPORT_APPROVED ? $remoteUser->approved : USER_DEFAULT_APPROVED,
            $remoteUser->timeAdded
          )
        ))
        {
          array_push(
            $debug['dump'],
            sprintf(
              _('Could not init user with address "%s" using feed URL "%s"'),
              $remoteUser->address,
              $manifest->export->users
            )
          );

          continue;
        }

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

        // Register userId alias
        $aliasUserId[$remoteUser->userId] = $localUser->userId;
      }

      // Magnets
      if (API_IMPORT_MAGNETS_ENABLED)
      {
        $error = [];

        if (!Valid::url($manifest->export->magnets, $error))
        {
          array_push(
            $debug['dump'],
            sprintf(
              _('Magnets feed URL "%s" invalid: %s'),
              $manifest->export->magnets,
              print_r(
                $error,
                true
              )
            )
          );

          continue;
        }

        // Call feed
        $curl = new Curl($manifest->export->magnets, API_USER_AGENT);

        $debug['http']['total']++;

        if (200 != $code = $curl->getCode())
        {
          array_push(
            $debug['dump'],
            sprintf(
              _('Magnets feed URL "%s" unreachable with code: "%s"'),
              $manifest->export->magnets,
              $code
            )
          );

          continue;
        }

        if (!$remoteMagnets = $curl->getResponse())
        {
          array_push(
            $debug['dump'],
            sprintf(
              _('Magnets feed URL "%s" has broken response'),
              $manifest->export->magnets
            )
          );

          continue;
        }

        // Init alias registry for this host
        $aliasMagnetId = [];

        foreach ((object) $remoteMagnets as $remoteMagnet)
        {
          // Validate required fields by protocol
          $error = [];

          if (!Valid::magnet($remoteMagnet, $error))
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnets feed URL "%s" has invalid protocol: "%s" error: "%s"'),
                $manifest->export->magnets,
                print_r(
                  $remoteMagnet,
                  true
                ),
                print_r(
                  $error,
                  true
                )
              )
            );

            continue;
          }

          // Aliases check
          if (!isset($aliasUserId[$remoteMagnet->userId]))
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Local alias for remote userId "%s" not found in URL "%s" %s'),
                $remoteMagnet->userId,
                $manifest->export->magnets,
                print_r(
                  $remoteMagnet,
                  true
                ),
              )
            );

            continue;
          }

          // Skip import on magnet approved required
          if (API_IMPORT_MAGNETS_APPROVED_ONLY && !$remoteMagnet->approved)
          {
            // No debug warnings in this case, continue next item

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
                        $xt->value, 1
                      )
                    );
                  }

                break;

                case 2:

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
                        $xt->value, 2
                      )
                    );
                  }

                break;
              }
            }

            // kt
            foreach ($remoteMagnet->kt as $kt)
            {
              $db->initMagnetToKeywordTopicId(
                $localMagnet->magnetId,
                $db->initKeywordTopicId(trim(mb_strtolower($kt))) // @TODO
              );
            }

            // tr
            foreach ($remoteMagnet->tr as $tr)
            {
              if ($url = Yggverse\Parser\Url::parse($tr))
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
            }

            // as
            foreach ($remoteMagnet->as as $as)
            {
              if ($url = Yggverse\Parser\Url::parse($as))
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
            }

            // xs
            foreach ($remoteMagnet->xs as $xs)
            {
              if ($url = Yggverse\Parser\Url::parse($tr))
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

          // Add magnet alias for this host
          $aliasMagnetId[$remoteMagnet->magnetId] = $localMagnet->magnetId;
        }

        // Magnet comments
        if (API_IMPORT_MAGNET_COMMENTS_ENABLED)
        {
          $error = [];

          if (!Valid::url($manifest->export->magnetComments, $error))
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet comments feed URL "%s" invalid: %s'),
                $manifest->export->magnetComments,
                print_r(
                  $error,
                  true
                )
              )
            );

            continue;
          }

          // Call feed
          $curl = new Curl($manifest->export->magnetComments, API_USER_AGENT);

          $debug['http']['total']++;

          if (200 != $code = $curl->getCode())
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet comments feed URL "%s" unreachable with code: "%s"'),
                $manifest->export->magnetComments,
                $code
              )
            );

            continue;
          }

          if (!$remoteMagnetComments = $curl->getResponse())
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet comments feed URL "%s" has broken response'),
                $manifest->export->magnetComments
              )
            );

            continue;
          }

          foreach ((object) $remoteMagnetComments as $remoteMagnetComment)
          {
            // Validate
            $error = [];

            if (!Valid::magnetComment($remoteMagnetComment, $error))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Magnet comments feed URL "%s" has invalid protocol: "%s" error: "%s"'),
                  $manifest->export->magnetComments,
                  print_r(
                    $remoteMagnetComment,
                    true
                  ),
                  print_r(
                    $error,
                    true
                  )
                )
              );

              continue;
            }

            // Aliases check
            if (!isset($aliasUserId[$remoteMagnetComment->userId]))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Local alias for remote userId "%s" not found in URL "%s" %s'),
                  $remoteMagnetComment->userId,
                  $manifest->export->magnetComments,
                  print_r(
                    $remoteMagnetComment,
                    true
                  ),
                )
              );

              continue;
            }

            if (!isset($aliasMagnetId[$remoteMagnetComment->magnetId]))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Local alias for remote magnetId "%s" not found in URL "%s" %s'),
                  $remoteMagnetComment->magnetId,
                  $manifest->export->magnetComments,
                  print_r(
                    $remoteMagnetComment,
                    true
                  ),
                )
              );

              continue;
            }

            // Skip import on magnet comment approved required
            if (API_IMPORT_MAGNET_COMMENTS_APPROVED_ONLY && !$remoteMagnetComment->approved)
            {
              // No debug warnings in this case, continue next item

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
          // Skip non-condition addresses
          $error = [];

          if (!Valid::url($manifest->export->magnetDownloads, $error))
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet downloads feed URL "%s" invalid: %s'),
                $manifest->export->magnetDownloads,
                print_r(
                  $error,
                  true
                )
              )
            );

            continue;
          }

          // Call feed
          $curl = new Curl($manifest->export->magnetDownloads, API_USER_AGENT);

          $debug['http']['total']++;

          if (200 != $code = $curl->getCode())
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet downloads feed URL "%s" unreachable with code: "%s"'),
                $manifest->export->magnetDownloads,
                $code
              )
            );

            continue;
          }

          if (!$remoteMagnetDownloads = $curl->getResponse())
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet downloads feed URL "%s" has broken response'),
                $manifest->export->magnetDownloads
              )
            );

            continue;
          }

          foreach ((object) $remoteMagnetDownloads as $remoteMagnetDownload)
          {
            // Validate
            $error = [];

            if (!Valid::magnetDownload($remoteMagnetDownload, $error))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Magnet downloads feed URL "%s" has invalid protocol: "%s" error: "%s"'),
                  $manifest->export->magnetDownloads,
                  print_r(
                    $remoteMagnetDownload,
                    true
                  ),
                  print_r(
                    $error,
                    true
                  )
                )
              );

              continue;
            }

            // Aliases check
            if (!isset($aliasUserId[$remoteMagnetDownload->userId]))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Local alias for remote userId "%s" not found in URL "%s" %s'),
                  $remoteMagnetDownload->userId,
                  $manifest->export->magnetDownloads,
                  print_r(
                    $remoteMagnetDownload,
                    true
                  ),
                )
              );

              continue;
            }

            if (!isset($aliasMagnetId[$remoteMagnetDownload->magnetId]))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Local alias for remote magnetId "%s" not found in URL "%s" %s'),
                  $remoteMagnetDownload->magnetId,
                  $manifest->export->magnetDownloads,
                  print_r(
                    $remoteMagnetDownload,
                    true
                  ),
                )
              );

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
          $error = [];

          if (!Valid::url($manifest->export->magnetViews, $error))
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet views feed URL "%s" invalid: %s'),
                $manifest->export->magnetViews,
                print_r(
                  $error,
                  true
                )
              )
            );

            continue;
          }

          // Call feed
          $curl = new Curl($manifest->export->magnetViews, API_USER_AGENT);

          $debug['http']['total']++;

          if (200 != $code = $curl->getCode())
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet views feed URL "%s" unreachable with code: "%s"'),
                $manifest->export->magnetViews,
                $code
              )
            );

            continue;
          }

          if (!$remoteMagnetViews = $curl->getResponse())
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet views feed URL "%s" has broken response'),
                $manifest->export->magnetViews
              )
            );

            continue;
          }

          foreach ((object) $remoteMagnetViews as $remoteMagnetView)
          {
            // Validate
            $error = [];

            if (!Valid::magnetView($remoteMagnetView, $error))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Magnet views feed URL "%s" has invalid protocol: "%s" error: "%s"'),
                  $manifest->export->magnetViews,
                  print_r(
                    $remoteMagnetView,
                    true
                  ),
                  print_r(
                    $error,
                    true
                  )
                )
              );

              continue;
            }

            // Aliases check
            if (!isset($aliasUserId[$remoteMagnetView->userId]))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Local alias for remote userId "%s" not found in URL "%s" %s'),
                  $remoteMagnetView->userId,
                  $manifest->export->magnetViews,
                  print_r(
                    $remoteMagnetView,
                    true
                  ),
                )
              );

              continue;
            }

            if (!isset($aliasMagnetId[$remoteMagnetView->magnetId]))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Local alias for remote magnetId "%s" not found in URL "%s" %s'),
                  $remoteMagnetView->magnetId,
                  $manifest->export->magnetViews,
                  print_r(
                    $remoteMagnetView,
                    true
                  ),
                )
              );

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
          $error = [];

          if (!Valid::url($manifest->export->magnetStars, $error))
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet stars feed URL "%s" invalid: %s'),
                $manifest->export->magnetStars,
                print_r(
                  $error,
                  true
                )
              )
            );

            continue;
          }

          // Call feed
          $curl = new Curl($manifest->export->magnetStars, API_USER_AGENT);

          $debug['http']['total']++;

          if (200 != $code = $curl->getCode())
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet stars feed URL "%s" unreachable with code: "%s"'),
                $manifest->export->magnetStars,
                $code
              )
            );

            continue;
          }

          if (!$remoteMagnetStars = $curl->getResponse())
          {
            array_push(
              $debug['dump'],
              sprintf(
                _('Magnet stars feed URL "%s" has broken response'),
                $manifest->export->magnetStars
              )
            );

            continue;
          }

          foreach ((object) $remoteMagnetStars as $remoteMagnetStar)
          {
            // Validate
            $error = [];

            if (!Valid::magnetStar($remoteMagnetStar, $error))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Magnet stars feed URL "%s" has invalid protocol: "%s" error: "%s"'),
                  $manifest->export->magnetStars,
                  print_r(
                    $remoteMagnetStar,
                    true
                  ),
                  print_r(
                    $error,
                    true
                  )
                )
              );

              continue;
            }

            // Aliases check
            if (!isset($aliasUserId[$remoteMagnetStar->userId]))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Local alias for remote userId "%s" not found in URL "%s" %s'),
                  $remoteMagnetStar->userId,
                  $manifest->export->magnetStars,
                  print_r(
                    $remoteMagnetStar,
                    true
                  ),
                )
              );

              continue;
            }

            if (!isset($aliasMagnetId[$remoteMagnetStar->magnetId]))
            {
              array_push(
                $debug['dump'],
                sprintf(
                  _('Local alias for remote magnetId "%s" not found in URL "%s" %s'),
                  $remoteMagnetStar->magnetId,
                  $manifest->export->magnetStars,
                  print_r(
                    $remoteMagnetStar,
                    true
                  ),
                )
              );

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

  $db->commit();

} catch (EXception $e) {

  $db->rollBack();

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
if (LOG_CRONTAB_IMPORT_FEED_ENABLED)
{
  @mkdir(LOG_DIRECTORY, 0770, true);

  if ($handle = fopen(LOG_DIRECTORY . '/' . LOG_CRONTAB_IMPORT_FEED_FILENAME, 'a+'))
  {
    fwrite($handle, print_r($debug, true));
    fclose($handle);

    chmod(LOG_DIRECTORY . '/' . LOG_CRONTAB_IMPORT_FEED_FILENAME, 0770);
  }
}