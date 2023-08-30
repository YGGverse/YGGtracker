<?php

// Load dependencies
require_once (__DIR__ . '/../config/app.php');
require_once (__DIR__ . '/../library/database.php');
require_once (__DIR__ . '/../../vendor/autoload.php');

// Connect database
try {

  $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USERNAME, DB_PASSWORD);

} catch (Exception $e) {

  var_dump($e);

  exit;
}

// Define variables
$response = (object)
[
  'success' => true,
  'message' => false,
  'form' => (object)
  [
    'metaTitle' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'metaDescription' => (object)
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
    'kt' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'tr' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'as' => (object)
    [
      'value' => false,
      'valid' => (object)
      [
        'success' => true,
        'message' => false,
      ]
    ],
    'xs' => (object)
    [
      'value' => false,
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
if (!preg_match(YGGDRASIL_URL_REGEX, $_SERVER['REMOTE_ADDR']))
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

    // Approve by approved user
    if ($user->approved)
    {
      $db->updateMagnetApproved($magnet->magnetId, true, time());
    }

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

    // Set default approve status
    else
    {
      $db->updateMagnetApproved($magnet->magnetId, MAGNET_DEFAULT_APPROVED, time());
    }

    // Meta
    if (MAGNET_META_TITLE_MIN_LENGTH <= mb_strlen($_POST['metaTitle']) && MAGNET_META_TITLE_MAX_LENGTH >= mb_strlen($_POST['metaTitle']))
    {
      $db->updateMagnetMetaTitle($magnet->magnetId, trim(strip_tags(html_entity_decode($_POST['metaTitle']))), time());

      $response->form->metaTitle->valid->success = true;
      $response->form->metaTitle->valid->message = false;
    }
    else
    {
      $response->form->metaTitle->valid->success = false;
      $response->form->metaTitle->valid->message = sprintf(_('* required, %s-%s chars'), MAGNET_META_TITLE_MIN_LENGTH, MAGNET_META_TITLE_MAX_LENGTH);
    }

    if (mb_strlen($_POST['metaDescription']) < MAGNET_META_DESCRIPTION_MIN_LENGTH || mb_strlen($_POST['metaDescription']) > MAGNET_META_DESCRIPTION_MAX_LENGTH)
    {
      $response->form->metaDescription->valid->success = false;
      $response->form->metaDescription->valid->message = sprintf(_('* required, %s-%s chars, %s provided'), MAGNET_META_DESCRIPTION_MIN_LENGTH, MAGNET_META_DESCRIPTION_MAX_LENGTH, mb_strlen($_POST['metaDescription']));
    }
    else
    {
      $db->updateMagnetMetaDescription($magnet->magnetId, trim(strip_tags(html_entity_decode($_POST['metaDescription']))), time());
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
    $db->updateMagnetPublic($magnet->magnetId, isset($_POST['public']) ? true : false, time());
    $db->updateMagnetComments($magnet->magnetId, isset($_POST['comments']) ? true : false, time());
    $db->updateMagnetSensitive($magnet->magnetId, isset($_POST['sensitive']) ? true : false, time());

    // Display Name
    if (isset($_POST['dn']))
    {
      $db->updateMagnetDn($magnet->magnetId, trim(strip_tags(html_entity_decode($_POST['dn']))), time());
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
            $db->initMagnetToAddressTrackerId(
              $magnet->magnetId,
              $db->initAddressTrackerId(
                $db->initSchemeId($url->host->scheme),
                $db->initHostId($url->host->name),
                $db->initPortId($url->host->port),
                $db->initUriId($url->page->uri)
              )
            );

            if (preg_match(YGGDRASIL_URL_REGEX, str_replace(['[',']'], false, $url->host->name)))
            {
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
            $db->initMagnetToAcceptableSourceId(
              $magnet->magnetId,
              $db->initAcceptableSourceId(
                $db->initSchemeId($url->host->scheme),
                $db->initHostId($url->host->name),
                $db->initPortId($url->host->port),
                $db->initUriId($url->page->uri)
              )
            );

            if (preg_match(YGGDRASIL_URL_REGEX, str_replace(['[',']'], false, $url->host->name)))
            {
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
            $db->initMagnetToExactSourceId(
              $magnet->magnetId,
              $db->initExactSourceId(
                $db->initSchemeId($url->host->scheme),
                $db->initHostId($url->host->name),
                $db->initPortId($url->host->port),
                $db->initUriId($url->page->uri)
              )
            );

            if (preg_match(YGGDRASIL_URL_REGEX, str_replace(['[',']'], false, $url->host->name)))
            {
              $response->form->xs->valid->success = true;
              $response->form->xs->valid->message = false;
            }
          }
        }
      }
    }

    // Is valid
    if ($response->success &&
        $response->form->metaTitle->valid->success &&
        $response->form->metaDescription->valid->success &&
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
  $response->form->metaTitle->value = $magnet->metaTitle ? $magnet->metaTitle : $magnet->dn;

  // Meta Description
  $response->form->metaDescription->value = $magnet->metaDescription;

  // Description
  $response->form->description->value = $magnet->description;

  // Magnet settings
  $response->form->public->value    = (bool) $magnet->public;
  $response->form->comments->value  = (bool) $magnet->comments;
  $response->form->sensitive->value = (bool) $magnet->sensitive;
  $response->form->approved->value  = (bool) $magnet->approved;

  // Display Name
  $response->form->dn->value = $magnet->dn;

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
    <meta name="description" content="<?php echo _('BitTorrent Catalog for Yggdrasil') ?>" />
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
                    <label class="display-block margin-y-8"><?php echo _('Title') ?></label>
                    <?php if ($response->form->metaTitle->valid->message) { ?>
                      <div class="margin-b-8"><?php echo $response->form->metaTitle->valid->message ?></div>
                    <?php } ?>
                    <input class="width-100 <?php echo ($response->form->metaTitle->valid->success ? false : 'background-color-red') ?>" type="text" name="metaTitle" value="<?php echo $response->form->metaTitle->value ?>" placeholder="<?php echo _('Main title') ?>" maxlength="255" />
                    <label class="display-block margin-y-8"><?php echo _('Short description') ?></label>
                    <?php if ($response->form->metaDescription->valid->message) { ?>
                      <div class="margin-b-8"><?php echo $response->form->metaDescription->valid->message ?></div>
                    <?php } ?>
                    <textarea class="width-100 <?php echo ($response->form->metaDescription->valid->success ? false : 'background-color-red') ?>" name="metaDescription" placeholder="<?php echo _('Shows in listing and meta tags') ?>"><?php echo $response->form->metaDescription->value ?></textarea>
                    <label class="display-block margin-y-8"><?php echo _('Long description') ?></label>
                    <?php if ($response->form->description->valid->message) { ?>
                      <div class="margin-b-8"><?php echo $response->form->description->valid->message ?></div>
                    <?php } ?>
                    <textarea class="width-100 <?php echo ($response->form->description->valid->success ? false : 'background-color-red') ?>" name="description" placeholder="<?php echo _('Shows on magnet page') ?>"><?php echo $response->form->description->value ?></textarea>
                  </fieldset>
                  <fieldset class="display-block margin-b-16">
                    <legend class="text-right width-100 padding-y-8 margin-b-8 border-bottom-default"><?php echo _('BitTorrent') ?></legend>
                    <label class="display-block margin-y-8"><?php echo _('Display Name (dn)') ?></label>
                    <input class="width-100" type="text" name="dn" value="<?php echo $response->form->dn->value ?>" placeholder="<?php echo _('A filename to display to the user, for convenience') ?>" maxlength="255" />
                    <label class="display-block margin-y-8"><?php echo _('Keyword Topic (kt)') ?></label>
                    <textarea class="width-100" name="kt" placeholder="<?php echo _('Hash tag, comma separated, or one per line') ?>"><?php echo $response->form->kt->value ?></textarea>
                    <label class="display-block margin-y-8"><?php echo _('Address Tracker (tr)') ?></label>
                    <?php if ($response->form->tr->valid->message) { ?>
                      <div class="margin-b-8"><?php echo $response->form->tr->valid->message ?></div>
                    <?php } ?>
                    <textarea class="width-100 <?php echo ($response->form->tr->valid->success ? false : 'background-color-red') ?>" name="tr" placeholder="<?php echo _('BitTorrent trackers list - comma separated, or one per line') ?>"><?php echo $response->form->tr->value ?></textarea>
                    <label class="display-block margin-y-8"><?php echo _('Acceptable Source (as)') ?></label>
                    <?php if ($response->form->as->valid->message) { ?>
                      <div class="margin-b-8"><?php echo $response->form->as->valid->message ?></div>
                    <?php } ?>
                    <textarea class="width-100 <?php echo ($response->form->as->valid->success ? false : 'background-color-red') ?>" name="as" placeholder="<?php echo _('Web servers to a direct download - comma separated, or one per line') ?>"><?php echo $response->form->as->value ?></textarea>
                    <label class="display-block margin-y-8"><?php echo _('eXact Source (xs)') ?></label>
                    <?php if ($response->form->xs->valid->message) { ?>
                      <div class="margin-b-8"><?php echo $response->form->xs->valid->message ?></div>
                    <?php } ?>
                    <textarea class="width-100 <?php echo ($response->form->xs->valid->success ? false : 'background-color-red') ?>" name="xs" placeholder="<?php echo _('URL of a P2P source for the file or the address of a hub - comma separated, or one per line') ?>"><?php echo $response->form->xs->value ?></textarea>
                  </fieldset>
                  <fieldset class="display-block">
                    <legend class="text-right width-100 padding-y-8 margin-b-16 border-bottom-default"><?php echo _('Social') ?></legend>
                    <div class="margin-b-8">
                      <?php if ($response->form->public->value) { ?>
                        <input type="checkbox" id="public" name="public" value="1" checked="checked" />
                      <?php } else { ?>
                        <input type="checkbox" id="public" name="public" value="1" />
                      <?php } ?>
                      <label class="margin-y-8" for="public"><?php echo _('Public') ?></label>
                    </div>
                    <div class="margin-b-8">
                      <?php if ($response->form->comments->value) { ?>
                        <input type="checkbox" id="comments" name="comments" value="1" checked="checked" />
                      <?php } else { ?>
                        <input type="checkbox" id="comments" name="comments" value="1" />
                      <?php } ?>
                      <label class="margin-y-8" for="comments"><?php echo _('Comments') ?></label>
                    </div>
                    <div class="margin-b-8">
                      <?php if ($response->form->sensitive->value) { ?>
                        <input type="checkbox" id="sensitive" name="sensitive" value="1" checked="checked" />
                      <?php } else { ?>
                        <input type="checkbox" id="sensitive" name="sensitive" value="1" />
                      <?php } ?>
                      <label class="margin-y-8" for="sensitive"><?php echo _('Sensitive') ?></label>
                    </div>
                    <?php if (in_array($user->address, MODERATOR_IP_LIST)) { ?>
                      <div class="margin-b-8">
                        <?php if ($response->form->approved->value) { ?>
                          <input type="checkbox" id="approved" name="approved" value="1" checked="checked" />
                        <?php } else { ?>
                          <input type="checkbox" id="approved" name="approved" value="1" />
                        <?php } ?>
                        <label class="margin-y-8" for="approved"><?php echo _('Approved') ?></label>
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
            <?php foreach (TRACKER_LINKS as $name => $value) { ?>
              <a href="<?php echo $value->announce ?>"><?php echo $name ?></a>
              /
              <a href="<?php echo $value->stats ?>"><?php echo _('Stats') ?></a>
              |
            <?php } ?>
            <a href="<?php echo WEBSITE_URL ?>/node.php"><?php echo _('Node') ?></a>
            |
            <a href="<?php echo WEBSITE_URL ?>/index.php?rss"><?php echo _('RSS') ?></a>
            |
            <a href="https://github.com/YGGverse/YGGtracker"><?php echo _('GitHub') ?></a>
          </div>
        </div>
      </div>
    </footer>
  </body>
</html>