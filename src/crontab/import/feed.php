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
  // Connect DB
  $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USERNAME, DB_PASSWORD);

  // Transaction begin
  $db->beginTransaction();

  foreach (json_decode(
    file_get_contents(__DIR__ . '/../../config/nodes.json')
  ) as $node)
  {
    // Manifest
    if ($manifest = @json_decode(@file_get_contents($node->manifest)))
    {
      // Users
      if (API_IMPORT_USERS_ENABLED)
      {
        if (empty($manifest->users))
        {
          continue;
        }

        // Init alias registry for this host
        $aliasUserId = [];

        foreach (@json_decode(@file_get_contents($manifest->users)) as $remoteUser)
        {
          // Validate required fields
          if (!isset($remoteUser->address)     || !preg_match(YGGDRASIL_HOST_REGEX, $remoteUser->address) ||
              !isset($remoteUser->timeAdded)   || !is_int($remoteUser->timeAdded)                         ||
              !isset($remoteUser->timeUpdated) || !is_int($remoteUser->timeUpdated)                       ||
              !isset($remoteUser->approved)    || !is_bool($remoteUser->approved))
          {
            continue;
          }

          // Yggdrasil connections only
          else if (!preg_match(YGGDRASIL_HOST_REGEX, $remoteUser->address))
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
          if (empty($manifest->magnets))
          {
            continue;
          }

          // Init alias registry for this host
          $aliasMagnetId = [];

          foreach (@json_decode(@file_get_contents($manifest->magnets)) as $remoteMagnet)
          {
            // Validate required fields
            if (!isset($remoteMagnet->userId)      || !is_int($remoteMagnet->userId)                                        ||
                                                      !isset($aliasUserId[$remoteMagnet->userId])                           ||
                                                      !$db->getUser($aliasUserId[$remoteMagnet->userId])                    ||

                !isset($remoteMagnet->title)       || mb_strlen($remoteMagnet->title) < MAGNET_TITLE_MIN_LENGTH             ||
                                                      mb_strlen($remoteMagnet->title) > MAGNET_TITLE_MAX_LENGTH             ||

                !isset($remoteMagnet->preview)     || mb_strlen($remoteMagnet->preview) < MAGNET_PREVIEW_MIN_LENGTH         ||
                                                      mb_strlen($remoteMagnet->preview) > MAGNET_PREVIEW_MAX_LENGTH         ||

                !isset($remoteMagnet->description) || mb_strlen($remoteMagnet->description) < MAGNET_DESCRIPTION_MIN_LENGTH ||
                                                      mb_strlen($remoteMagnet->description) > MAGNET_DESCRIPTION_MAX_LENGTH ||

                !isset($remoteMagnet->comments)    || !is_bool($remoteMagnet->comments)                                     ||
                !isset($remoteMagnet->sensitive)   || !is_bool($remoteMagnet->sensitive)                                    ||
                !isset($remoteMagnet->approved)    || !is_bool($remoteMagnet->approved)                                     ||

                !isset($remoteMagnet->timeAdded)   || !is_int($remoteMagnet->timeAdded)                                     ||
                !isset($remoteMagnet->timeUpdated) || !is_int($remoteMagnet->timeUpdated)                                   ||

                !isset($remoteMagnet->dn)          || mb_strlen($remoteMagnet->dn) < MAGNET_TITLE_MIN_LENGTH                ||
                                                      mb_strlen($remoteMagnet->dn) > MAGNET_TITLE_MAX_LENGTH                ||

                !isset($remoteMagnet->xl)          || !is_int($remoteMagnet->xl)                                            ||

                !isset($remoteMagnet->xt)          || !is_object($remoteMagnet->xt)                                         ||
                !isset($remoteMagnet->kt)          || !is_object($remoteMagnet->kt)                                         ||
                !isset($remoteMagnet->tr)          || !is_object($remoteMagnet->tr)                                         ||
                !isset($remoteMagnet->as)          || !is_object($remoteMagnet->as)                                         ||
                !isset($remoteMagnet->xs)          || !is_object($remoteMagnet->xs))
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
                if ($url = Yggverse\Parser\Url::parse($tr))
                {
                  if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
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
              }

              // as
              foreach ($remoteMagnet->as as $as)
              {
                if ($url = Yggverse\Parser\Url::parse($as))
                {
                  if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
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
              }

              // xs
              foreach ($remoteMagnet->xs as $xs)
              {
                if ($url = Yggverse\Parser\Url::parse($xs))
                {
                  if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
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