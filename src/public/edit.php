<?php

// Bootstrap
require_once __DIR__ . '/../config/bootstrap.php';

// Define variables
$response = (object)
[
  'success' => true,
  'message' => false,
  'form' => (object)
  [
    'title' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'preview' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'description' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'dn' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'xt' => (object)
    [
      'value' => [],
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'kt' => (object)
    [
      'value' => [],
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'tr' => (object)
    [
      'value' => [],
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'as' => (object)
    [
      'value' => [],
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'xs' => (object)
    [
      'value' => [],
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'public' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'comments' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'sensitive' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'approved' => (object)
    [
      'value' => false,
    ],
  ]
];

// Yggdrasil connections only
if (!preg_match(YGGDRASIL_HOST_REGEX, $_SERVER['REMOTE_ADDR']))
{
  $response->success = false;
  $response->message = _('Yggdrasil connection required to enable resource features');
}

// Init session
else if (!$userId = $db->initUserId($_SERVER['REMOTE_ADDR'], USER_DEFAULT_APPROVED, time()))
{
  $response->success = false;
  $response->message = _('Could not init user session');
}

// Get user
else if (!$user = $db->getUser($userId))
{
  $response->success = false;
  $response->message = _('Could not init user info');
}

// Init magnet
else if (!$magnet = $db->getMagnet(isset($_GET['magnetId']) ? (int) $_GET['magnetId'] : 0)) {

  $response->success = false;
  $response->message = _('Magnet not found!');
}

// Validate access
else if (!($user->address == $db->getUser($magnet->userId)->address || in_array($user->address, MODERATOR_IP_LIST))) {

  $response->success = false;
  $response->message = _('You have no permissions to edit this magnet!');
}

// On first visit, redirect user to the welcome page with access level question
else if (is_null($user->public))
{
  header(
    sprintf('Location: %s/welcome.php', WEBSITE_URL)
  );
}

// Process form
else {

  // Validate magnet lock
  if ($lastMagnetLock = $db->findLastMagnetLock($magnet->magnetId))
  {
    if ($lastMagnetLock->userId != $user->userId &&
        $lastMagnetLock->timeAdded > time() - MAGNET_EDITOR_LOCK_TIMEOUT)
    {
      $response->success = false;
      $response->message = _('This form have opened by owner or moderator, to prevent overwriting, try attempt later!');
    }
  }

  // Lock form for moderators
  $db->addMagnetLock($magnet->magnetId, $user->userId, time());

  // Update form
  if (!empty($_POST)) {

    // Approve by moderation request
    if (in_array($user->address, MODERATOR_IP_LIST))
    {
      $db->updateMagnetApproved($magnet->magnetId, isset($_POST['approved']), time());

      // Auto-approve user on magnet approve
      if (USER_AUTO_APPROVE_ON_MAGNET_APPROVE)
      {
        $db->updateUserApproved($magnet->userId, isset($_POST['approved']), time());
      }
    }

    // Approve by user approved status
    else
    {
      $db->updateMagnetApproved($magnet->magnetId, (bool) $user->approved, time());
    }

    // Meta
    if (MAGNET_TITLE_MIN_LENGTH <= mb_strlen($_POST['title']) && MAGNET_TITLE_MAX_LENGTH >= mb_strlen($_POST['title']))
    {
      $db->updateMagnetTitle($magnet->magnetId, trim(strip_tags(html_entity_decode($_POST['title']))), time());

      $response->form->title->valid->success = true;
      $response->form->title->valid->message = false;
    }
    else
    {
      $response->form->title->valid->success = false;
      $response->form->title->valid->message = sprintf(_('* required, %s-%s chars'), MAGNET_TITLE_MIN_LENGTH, MAGNET_TITLE_MAX_LENGTH);
    }

    if (mb_strlen($_POST['preview']) < MAGNET_PREVIEW_MIN_LENGTH || mb_strlen($_POST['preview']) > MAGNET_PREVIEW_MAX_LENGTH)
    {
      $response->form->preview->valid->success = false;
      $response->form->preview->valid->message = sprintf(_('* required, %s-%s chars, %s provided'), MAGNET_PREVIEW_MIN_LENGTH, MAGNET_PREVIEW_MAX_LENGTH, mb_strlen($_POST['preview']));
    }
    else
    {
      $db->updateMagnetPreview($magnet->magnetId, trim(strip_tags(html_entity_decode($_POST['preview']))), time());
    }

    if (mb_strlen($_POST['description']) < MAGNET_DESCRIPTION_MIN_LENGTH || mb_strlen($_POST['description']) > MAGNET_DESCRIPTION_MAX_LENGTH)
    {
      $response->form->description->valid->success = false;
      $response->form->description->valid->message = sprintf(_('* required, %s-%s chars, %s provided'), MAGNET_DESCRIPTION_MIN_LENGTH, MAGNET_DESCRIPTION_MAX_LENGTH, mb_strlen($_POST['description']));
    }
    else
    {
      $db->updateMagnetDescription($magnet->magnetId, trim(strip_tags(html_entity_decode($_POST['description']))), time());
    }

    // Social
    $db->updateMagnetComments($magnet->magnetId, isset($_POST['comments']) ? true : false, time());
    $db->updateMagnetSensitive($magnet->magnetId, isset($_POST['sensitive']) ? true : false, time());

    if (isset($_POST['public'])) // could be enabled once only because of distributed database model #1
    {
      $db->updateMagnetPublic($magnet->magnetId, true, time());
    }

    // Display Name
    if (isset($_POST['dn']))
    {
      $db->updateMagnetDn($magnet->magnetId, trim(strip_tags(html_entity_decode($_POST['dn']))), time());
    }

    // Exact Topic
    if (isset($_POST['xt']))
    {
      foreach ((array) $_POST['xt'] as $version => $value)
      {
        switch ($version)
        {
          case 1:

            if (!empty($value) && Yggverse\Parser\Magnet::isXTv1($value))
            {
              $exist = false;

              foreach ($db->findMagnetToInfoHashByMagnetId($magnet->magnetId) as $result)
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
                  $magnet->magnetId,
                  $db->initInfoHashId(
                    Yggverse\Parser\Magnet::filterInfoHash($value), 1
                  )
                );
              }
            }

          break;

          case 2:

            if (!empty($value) && Yggverse\Parser\Magnet::isXTv2($value))
            {
              $exist = false;

              foreach ($db->findMagnetToInfoHashByMagnetId($magnet->magnetId) as $result)
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
                  $magnet->magnetId,
                  $db->initInfoHashId(
                    Yggverse\Parser\Magnet::filterInfoHash($value), 2
                  )
                );
              }
            }

          break;
        }
      }
    }

    // Keyword Topic
    $db->deleteMagnetToKeywordTopicByMagnetId($magnet->magnetId);

    if (!empty($_POST['kt']))
    {
      foreach (explode(PHP_EOL, str_replace(['#', ',', ' '], PHP_EOL, $_POST['kt'])) as $kt)
      {
        if (!empty(trim($kt)))
        {
          $db->initMagnetToKeywordTopicId(
            $magnet->magnetId,
            $db->initKeywordTopicId(trim(mb_strtolower(strip_tags(html_entity_decode($kt)))))
          );
        }
      }
    }

    // Address Tracker
    $db->deleteMagnetToAddressTrackerByMagnetId($magnet->magnetId);

    if (!empty($_POST['tr']))
    {
      $response->form->tr->valid->success = false;
      $response->form->tr->valid->message = _('* please, provide at least one Yggdrasil address');

      foreach (explode(PHP_EOL, str_replace(['#', ',', ' '], PHP_EOL, $_POST['tr'])) as $tr)
      {
        $tr = trim($tr);

        if (!empty($tr))
        {
          if ($url = Yggverse\Parser\Url::parse($tr))
          {
            if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
            {
              $db->initMagnetToAddressTrackerId(
                $magnet->magnetId,
                $db->initAddressTrackerId(
                  $db->initSchemeId($url->host->scheme),
                  $db->initHostId($url->host->name),
                  $db->initPortId($url->host->port),
                  $db->initUriId($url->page->uri)
                )
              );

              $response->form->tr->valid->success = true;
              $response->form->tr->valid->message = false;
            }
          }
        }
      }
    }

    // Acceptable Source
    $db->deleteMagnetToAcceptableSourceByMagnetId($magnet->magnetId);

    if (!empty($_POST['as']))
    {
      $response->form->as->valid->success = false;
      $response->form->as->valid->message = _('* please, provide at least one Yggdrasil address');

      foreach (explode(PHP_EOL, str_replace(['#', ',', ' '], PHP_EOL, $_POST['as'])) as $as)
      {
        $as = trim($as);

        if (!empty($as))
        {
          if ($url = Yggverse\Parser\Url::parse($as))
          {
            if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
            {
              $db->initMagnetToAcceptableSourceId(
                $magnet->magnetId,
                $db->initAcceptableSourceId(
                  $db->initSchemeId($url->host->scheme),
                  $db->initHostId($url->host->name),
                  $db->initPortId($url->host->port),
                  $db->initUriId($url->page->uri)
                )
              );

              $response->form->as->valid->success = true;
              $response->form->as->valid->message = false;
            }
          }
        }
      }
    }

    // Exact Source
    $db->deleteMagnetToExactSourceByMagnetId($magnet->magnetId);

    if (!empty($_POST['xs']))
    {
      $response->form->xs->valid->success = false;
      $response->form->xs->valid->message = _('* please, provide at least one Yggdrasil address');

      foreach (explode(PHP_EOL, str_replace(['#', ',', ' '], PHP_EOL, $_POST['xs'])) as $xs)
      {
        $xs = trim($xs);

        if (!empty($xs))
        {
          if ($url = Yggverse\Parser\Url::parse($xs))
          {
            if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
            {
              $db->initMagnetToExactSourceId(
                $magnet->magnetId,
                $db->initExactSourceId(
                  $db->initSchemeId($url->host->scheme),
                  $db->initHostId($url->host->name),
                  $db->initPortId($url->host->port),
                  $db->initUriId($url->page->uri)
                )
              );

              $response->form->xs->valid->success = true;
              $response->form->xs->valid->message = false;
            }
          }
        }
      }
    }

    // Is valid
    if ($response->success &&
        $response->form->title->valid->success &&
        $response->form->preview->valid->success &&
        $response->form->description->valid->success &&
        $response->form->tr->valid->success &&
        $response->form->as->valid->success &&
        $response->form->xs->valid->success)
    {
      // Unlock form
      $db->flushMagnetLock($magnet->magnetId);

      // Return redirect to the magnet page
      header(
        sprintf('Location: %s/magnet.php?magnetId=%s', WEBSITE_URL, $magnet->magnetId)
      );
    }
    else
    {
      // Refresh magnet data
      $magnet = $db->getMagnet($magnet->magnetId);

      // Replace fields by last POST data
      foreach ($_POST as $key => $value)
      {
        $magnet->{$key} = $value;
      }
    }
  }

  // Meta Title, auto-replace with Display Name on empty value
  $response->form->title->value = $magnet->title ? $magnet->title : $magnet->dn;

  // Meta Description
  $response->form->preview->value = $magnet->preview;

  // Description
  $response->form->description->value = $magnet->description;

  // Magnet settings
  $response->form->public->value    = (bool) $magnet->public;
  $response->form->comments->value  = (bool) $magnet->comments;
  $response->form->sensitive->value = (bool) $magnet->sensitive;
  $response->form->approved->value  = (bool) $magnet->approved;

  // Display Name
  $response->form->dn->value = $magnet->dn;

  // Exact Topic
  foreach ($db->findMagnetToInfoHashByMagnetId($magnet->magnetId) as $result)
  {
    if ($infoHash = $db->getInfoHash($result->infoHashId))
    {
      $response->form->xt->value[$infoHash->version] = $infoHash->value;
    }
  }

  // Keyword Topic
  $kt = [];
  foreach ($db->findKeywordTopicByMagnetId($magnet->magnetId) as $result)
  {
    $kt[] = $db->getKeywordTopic($result->keywordTopicId)->value;
  }

  $response->form->kt->value = implode(', ', $kt);

  // Address Tracker
  $tr = [];
  foreach ($db->findAddressTrackerByMagnetId($magnet->magnetId) as $result)
  {
    $addressTracker = $db->getAddressTracker($result->addressTrackerId);

    $scheme = $db->getScheme($addressTracker->schemeId);
    $host   = $db->getHost($addressTracker->hostId);
    $port   = $db->getPort($addressTracker->portId);
    $uri    = $db->getUri($addressTracker->uriId);

    $tr[] = $port->value ? sprintf('%s://%s:%s%s',  $scheme->value,
                                                    $host->value,
                                                    $port->value,
                                                    $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $uri->value);
  }

  $response->form->tr->value = implode(PHP_EOL, $tr);

  // Acceptable Source
  $as = [];
  foreach ($db->findAcceptableSourceByMagnetId($magnet->magnetId) as $result)
  {
    $acceptableSource = $db->getAcceptableSource($result->acceptableSourceId);

    $scheme = $db->getScheme($acceptableSource->schemeId);
    $host   = $db->getHost($acceptableSource->hostId);
    $port   = $db->getPort($acceptableSource->portId);
    $uri    = $db->getUri($acceptableSource->uriId);

    $as[] = $port->value ? sprintf('%s://%s:%s%s',  $scheme->value,
                                                    $host->value,
                                                    $port->value,
                                                    $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $uri->value);
  }

  $response->form->as->value = implode(PHP_EOL, $as);

  // Exact Source
  $xs = [];
  foreach ($db->findExactSourceByMagnetId($magnet->magnetId) as $result)
  {
    $eXactSource = $db->getExactSource($result->eXactSourceId);

    $scheme = $db->getScheme($eXactSource->schemeId);
    $host   = $db->getHost($eXactSource->hostId);
    $port   = $db->getPort($eXactSource->portId);
    $uri    = $db->getUri($eXactSource->uriId);

    $xs[] = $port->value ? sprintf('%s://%s:%s%s',  $scheme->value,
                                                    $host->value,
                                                    $port->value,
                                                    $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $uri->value);
  }

  $response->form->xs->value = implode(PHP_EOL, $xs);
}

?>

<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <title>
      <?php echo sprintf(_('Edit - %s'), WEBSITE_NAME) ?>
    </title>
    <meta name="description" content="<?php echo _('BitTorrent Registry for Yggdrasil') ?>" />
    <meta name="keywords" content="yggdrasil, yggverse, yggtracker, bittorrent, magnet, catalog" />
    <meta name="author" content="YGGtracker" />
    <meta charset="UTF-8" />
  </head>
  <body>
    <header>
      <div class="container">
        <div class="row margin-t-8 text-center">
          <a class="logo" href="<?php echo WEBSITE_URL ?>"><?php echo str_replace('YGG', '<span>YGG</span>', WEBSITE_NAME) ?></a>
          <form class="margin-t-8" name="search" method="get" action="<?php echo WEBSITE_URL ?>/index.php">
            <input type="text" name="query" value="" placeholder="<?php echo _('search or submit magnet link') ?>" />
            <input type="submit" value="<?php echo _('submit') ?>" />
          </form>
        </div>
      </div>
    </header>
    <main>
      <div class="container">
        <div class="row">
          <div class="column width-100">
            <?php if ($response->success) { ?>
              <div class="padding-x-16 text-right">
                <a href="<?php echo WEBSITE_URL ?>" target="_blank"><?php echo _('catalog') ?></a>
                <!-- <a href="<?php echo WEBSITE_URL ?>/magnet.php?magnetId=<?php echo $magnet->magnetId ?>" target="_blank"><?php echo _('magnet page') ?></a> -->
              </div>
              <div class="padding-16 margin-y-8 border-radius-3 background-color-night">
                <h2 class="margin-b-8"><?php echo _('Edit magnet') ?></h2>
                <form name="search" method="post" action="<?php echo WEBSITE_URL ?>/edit.php?magnetId=<?php echo $magnet->magnetId ?>">
                  <fieldset class="display-block margin-b-16">
                    <legend class="text-right width-100 padding-y-8 margin-b-8 border-bottom-default"><?php echo _('Meta') ?></legend>
                    <label class="display-block margin-y-8 padding-t-4">
                      <?php echo _('Title') ?>
                      <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo sprintf(_('Subject and meta title (%s-%s chars)'), MAGNET_TITLE_MIN_LENGTH, MAGNET_TITLE_MAX_LENGTH) ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                      </sub>
                      <?php if ($response->form->title->valid->message) { ?>
                        <div class="margin-b-8"><?php echo $response->form->title->valid->message ?></div>
                      <?php } ?>
                      <input class="width-100 margin-t-8 <?php echo ($response->form->title->valid->success ? false : 'background-color-red') ?>" type="text" name="title" value="<?php echo $response->form->title->value ?>" placeholder="<?php echo _('Main title') ?>" maxlength="255" />
                    </label>
                    <label class="display-block margin-y-8 padding-t-4">
                      <?php echo _('Preview') ?>
                      <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo sprintf(_('Visible in listings, magnet web page description and meta description (%s-%s chars)'), MAGNET_PREVIEW_MIN_LENGTH, MAGNET_PREVIEW_MAX_LENGTH) ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                      </sub>
                      <?php if ($response->form->preview->valid->message) { ?>
                        <div class="margin-b-8"><?php echo $response->form->preview->valid->message ?></div>
                      <?php } ?>
                      <textarea class="width-100 margin-t-8 <?php echo ($response->form->preview->valid->success ? false : 'background-color-red') ?>" name="preview" placeholder="<?php echo _('Shows in listing and meta tags') ?>"><?php echo $response->form->preview->value ?></textarea>
                    </label>
                    <label class="display-block margin-y-8 padding-t-4">
                      <?php echo _('Description') ?>
                      <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo sprintf(_('Visible on magnet web page (%s-%s chars)'), MAGNET_DESCRIPTION_MIN_LENGTH, MAGNET_DESCRIPTION_MAX_LENGTH) ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                      </sub>
                      <?php if ($response->form->description->valid->message) { ?>
                        <div class="margin-b-8"><?php echo $response->form->description->valid->message ?></div>
                      <?php } ?>
                      <textarea class="width-100 margin-t-8 <?php echo ($response->form->description->valid->success ? false : 'background-color-red') ?>" name="description" placeholder="<?php echo _('Shows on magnet page') ?>"><?php echo $response->form->description->value ?></textarea>
                    </label>
                  </fieldset>
                  <fieldset class="display-block margin-b-16">
                    <legend class="text-right width-100 padding-y-8 margin-b-8 border-bottom-default"><?php echo _('BitTorrent') ?></legend>
                    <label class="display-block margin-y-8 padding-t-4" for="xt-1">
                      <?php echo _('Info Hash v1 (xt)') ?>
                      <?php if (empty($response->form->xt->value[1])) { ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Info info hash (btih) not provided and could be changed once') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                        <input class="width-100 margin-t-8 <?php echo (empty($response->form->xt->value[2]) ? 'background-color-red' : false) ?>" type="text" name="xt[1]" id="xt-1" value="" />
                      <?php } else { ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Unique info hash (btih)') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                        <input class="width-100 margin-t-8" type="text" name="xt[1]" id="xt-1" value="<?php echo $response->form->xt->value[1] ?>" readonly="readonly" disabled="disabled" />
                      <?php } ?>
                    </label>
                    <label class="display-block margin-y-8 padding-t-4" for="xt-2">
                      <?php echo _('Info Hash v2 (xt)') ?>
                      <?php if (empty($response->form->xt->value[2])) { ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Info info hash (btmh) not provided and could be changed once') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                        <input class="width-100 margin-t-8 <?php echo (empty($response->form->xt->value[1]) ? 'background-color-red' : false) ?>" type="text" name="xt[2]" id="xt-2" value="" />
                      <?php } else { ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Unique info hash (btmh)') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                        <input class="width-100 margin-t-8" type="text" name="xt[2]" id="xt-2" value="<?php echo $response->form->xt->value[2] ?>" readonly="readonly" disabled="disabled" />
                      <?php } ?>
                    </label>
                    <?php if (empty($response->form->xt->value[1]) && empty($response->form->xt->value[2])) { ?>
                      <div class="margin-b-8"><?php echo _('At least v1 or v2 info hash required for download') ?></div>
                    <?php } ?>
                    <label class="display-block margin-y-8 padding-t-4" for="dn">
                      <?php echo _('Display Name (dn)') ?>
                      <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Filename display to the user in BitTorrent client') ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                      </sub>
                      <input class="width-100 margin-t-8" type="text" name="dn" id="dn" value="<?php echo $response->form->dn->value ?>" placeholder="<?php echo _('A filename to display to the user, for convenience') ?>" maxlength="255" />
                    </label>
                    <label class="display-block margin-y-8 padding-t-4" for="kt">
                      <?php echo _('Keyword Topic (kt)') ?>
                      <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Search keywords for YGGtracker and P2P networks over DHT') ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                      </sub>
                      <textarea class="width-100 margin-t-8" name="kt" id="kt" placeholder="<?php echo _('Hash tag, comma separated, or one per line') ?>"><?php echo $response->form->kt->value ?></textarea>
                    </label>
                    <label class="display-block margin-y-8 padding-t-4" for="tr">
                      <?php echo _('Address Tracker (tr)') ?>
                      <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Yggdrasil only trackers URL to obtain peers without DHT') ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                      </sub>
                      <?php if ($response->form->tr->valid->message) { ?>
                        <div class="margin-b-8"><?php echo $response->form->tr->valid->message ?></div>
                      <?php } ?>
                      <textarea class="width-100 margin-t-8 <?php echo ($response->form->tr->valid->success ? false : 'background-color-red') ?>" name="tr" id="tr" placeholder="<?php echo _('BitTorrent trackers list - comma separated, or one per line') ?>"><?php echo $response->form->tr->value ?></textarea>
                    </label>
                    <label class="display-block margin-y-8 padding-t-4" for="as">
                      <?php echo _('Acceptable Source (as)') ?>
                      <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Yggdrasil only URL to a direct download from a web server') ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                      </sub>
                      <?php if ($response->form->as->valid->message) { ?>
                        <div class="margin-b-8"><?php echo $response->form->as->valid->message ?></div>
                      <?php } ?>
                      <textarea class="width-100 margin-t-8 <?php echo ($response->form->as->valid->success ? false : 'background-color-red') ?>" name="as" id="as" placeholder="<?php echo _('Web servers to a direct download - comma separated, or one per line') ?>"><?php echo $response->form->as->value ?></textarea>
                    </label>
                    <label class="display-block margin-y-8 padding-t-4" for="xs">
                      <?php echo _('eXact Source (xs)') ?>
                      <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Yggdrasil only URL to download source for the file pointed to by the Magnet link') ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                      </sub>
                      <?php if ($response->form->xs->valid->message) { ?>
                        <div class="margin-b-8"><?php echo $response->form->xs->valid->message ?></div>
                      <?php } ?>
                      <textarea class="width-100 margin-t-8 <?php echo ($response->form->xs->valid->success ? false : 'background-color-red') ?>" name="xs" id="xs" placeholder="<?php echo _('URL of a P2P source for the file or the address of a hub - comma separated, or one per line') ?>"><?php echo $response->form->xs->value ?></textarea>
                    </label>
                 </fieldset>
                  <fieldset class="display-block">
                    <legend class="text-right width-100 padding-y-8 margin-b-16 border-bottom-default"><?php echo _('Social') ?></legend>
                    <div class="margin-b-8">
                      <label class="margin-y-8" for="public">
                        <?php if ($response->form->public->value) { ?>
                          <input type="checkbox" id="public" name="public" value="1" checked="checked" disabled="disabled" />
                        <?php } else { ?>
                          <input type="checkbox" id="public" name="public" value="1" />
                        <?php } ?>
                        <?php echo _('Public') ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo $response->form->public->value ? _('Magnet already transmitted to this website, RSS feeds and YGGtracker nodes') : _('Make permanently visible on this website, RSS feeds and other YGGtracker nodes') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                      </label>
                    </div>
                    <div class="margin-b-8">
                      <label class="margin-y-8" for="comments">
                        <?php if ($response->form->comments->value) { ?>
                          <input type="checkbox" id="comments" name="comments" value="1" checked="checked" />
                        <?php } else { ?>
                          <input type="checkbox" id="comments" name="comments" value="1" />
                        <?php } ?>
                        <?php echo _('Comments') ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Allow comments for this publication') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                      </label>
                    </div>
                    <div class="margin-b-8">
                      <label class="margin-y-8" for="sensitive">
                        <?php if ($response->form->sensitive->value) { ?>
                          <input type="checkbox" id="sensitive" name="sensitive" value="1" checked="checked" />
                        <?php } else { ?>
                          <input type="checkbox" id="sensitive" name="sensitive" value="1" />
                        <?php } ?>
                        <?php echo _('Sensitive') ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('Apply NSFW filters for this publication') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                      </label>
                    </div>
                    <?php if (in_array($user->address, MODERATOR_IP_LIST)) { ?>
                      <div class="margin-b-8">
                        <label class="margin-y-8" for="approved">
                          <?php if ($response->form->approved->value) { ?>
                            <input type="checkbox" id="approved" name="approved" value="1" checked="checked" />
                          <?php } else { ?>
                            <input type="checkbox" id="approved" name="approved" value="1" />
                          <?php } ?>
                          <?php echo _('Approved') ?>
                          <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo USER_AUTO_APPROVE_ON_MAGNET_APPROVE ? _('Approve this post and user') : _('Approve this post as moderator') ?>">
                            <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                              <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                            </svg>
                          </sub>
                        </label>
                      </div>
                    <?php } ?>
                  </fieldset>
                  <fieldset class="display-block text-right">
                    <input type="submit" value="<?php echo _('save') ?>" />
                  </fieldset>
                </form>
              </div>
            <?php } else { ?>
              <div class="padding-16 margin-y-8 border-radius-3 background-color-night">
                <div class="text-center"><?php echo $response->message ?></div>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </main>
    <footer>
      <div class="container">
        <div class="row">
          <div class="column width-100 text-center margin-y-8">
            <?php foreach (json_decode(file_get_contents(__DIR__ . '/../config/trackers.json')) as $i => $tracker) { ?>
              <?php if (!empty($tracker->announce) && !empty($tracker->stats)) { ?>
                <a href="<?php echo $tracker->announce ?>"><?php echo sprintf('Tracker %s', $i + 1) ?></a>
                /
                <a href="<?php echo $tracker->stats ?>"><?php echo _('Stats') ?></a>
                |
              <?php } ?>
            <?php } ?>
            <a href="<?php echo WEBSITE_URL ?>/faq.php"><?php echo _('F.A.Q') ?></a>
            |
            <a href="<?php echo WEBSITE_URL ?>/node.php"><?php echo _('Node') ?></a>
            |
            <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/index.php?rss"><?php echo _('RSS') ?></a>
            <?php if (API_EXPORT_ENABLED) { ?>
              |
              <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/api/manifest.json"><?php echo _('API') ?></a>
            <?php } ?>
            |
            <a href="https://github.com/YGGverse/YGGtracker"><?php echo _('GitHub') ?></a>
          </div>
        </div>
      </div>
    </footer>
  </body>
</html>