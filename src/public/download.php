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

// Define response
$response = (object)
[
  'success' => true,
  'message' => _('Internal server error'),
  'html'    => (object)
  [
    'title' => sprintf(_('Oops - %s'), WEBSITE_NAME),
    'h1'    => false,
    'link'  => (object) [],
  ]
];

  // Yggdrasil connections only
  if (!preg_match(YGGDRASIL_URL_REGEX, $_SERVER['REMOTE_ADDR']))
  {
    $response->success = false;
    $response->message = _('Yggdrasil connection required for this action');
  }

  // Init session
  else if (!$userId = $db->initUserId($_SERVER['REMOTE_ADDR'], USER_DEFAULT_APPROVED, time()))
  {
    $response->success = false;
    $response->message = _('Could not init user session');
  }

  // Magnet exists
  else if (!$magnet = $db->getMagnet(isset($_GET['magnetId']) && $_GET['magnetId'] > 0 ? (int) $_GET['magnetId'] : 0))
  {
    $response->success = false;
    $response->message = _('Requested magnet not found');
  }

  // Access allowed
  else if (!($_SERVER['REMOTE_ADDR'] == $db->getUser($magnet->userId)->address || in_array($_SERVER['REMOTE_ADDR'], MODERATOR_IP_LIST) || ($magnet->public && $magnet->approved))) {

    $response->success = false;
    $response->message = _('Magnet not available for this action');
  }

  // Request valid
  else
  {
    // Update download stats
    $db->addMagnetDownload($magnet->magnetId, $userId, time());

    // Build magnet link
    $link = [];

    /// Exact Topic
    $xt = [];

    foreach ($db->findMagnetToInfoHashByMagnetId($magnet->magnetId) as $result)
    {
      if ($infoHash = $db->getInfoHash($result->infoHashId))
      {
        switch ($infoHash->version)
        {
          case 1:

            $xt[] = sprintf('xt=urn:btih:%s', $infoHash->value);

          break;

          case 2:

            $xt[] = sprintf('xt=urn:btmh:1220%s', $infoHash->value);

          break;
        }
      }
    }

    $link[] = sprintf('magnet:?%s', implode('&', $xt));

    /// Display Name
    $link[] = sprintf('dn=%s', urlencode($magnet->dn));

    // Keyword Topic
    $kt = [];

    foreach ($db->findKeywordTopicByMagnetId($magnet->magnetId) as $result)
    {
      $kt[] = urlencode($db->getKeywordTopic($result->keywordTopicId)->value);
    }

    $link[] = sprintf('kt=%s', implode('+', $kt));

    /// Address Tracker
    foreach ($db->findAddressTrackerByMagnetId($magnet->magnetId) as $result)
    {
      $addressTracker = $db->getAddressTracker($result->addressTrackerId);

      $scheme = $db->getScheme($addressTracker->schemeId);
      $host   = $db->getHost($addressTracker->hostId);
      $port   = $db->getPort($addressTracker->portId);
      $uri    = $db->getUri($addressTracker->uriId);

      $url    = sprintf('tr=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                  $host->value,
                                                                                  $port->value,
                                                                                  $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                      $host->value,
                                                                                                                      $uri->value)));

      // Yggdrasil url only
      if (!preg_match(YGGDRASIL_URL_REGEX, $url))
      {
        continue;
      }

      $link[] = $url;
    }

    // Append trackers.json
    foreach (json_decode(file_get_contents(__DIR__ . '/../config/trackers.json')) as $tracker)
    {
      $link[] = sprintf('tr=%s', urlencode($tracker->announce));
    }

    /// Acceptable Source
    foreach ($db->findAcceptableSourceByMagnetId($magnet->magnetId) as $result)
    {
      $acceptableSource = $db->getAcceptableSource($result->acceptableSourceId);

      $scheme = $db->getScheme($acceptableSource->schemeId);
      $host   = $db->getHost($acceptableSource->hostId);
      $port   = $db->getPort($acceptableSource->portId);
      $uri    = $db->getUri($acceptableSource->uriId);

      $url    = sprintf('as=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                  $host->value,
                                                                                  $port->value,
                                                                                  $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                      $host->value,
                                                                                                                      $uri->value)));

      // Yggdrasil url only
      if (!preg_match(YGGDRASIL_URL_REGEX, $url))
      {
        continue;
      }

      $link[] = $url;
    }

    /// Exact Source
    foreach ($db->findExactSourceByMagnetId($magnet->magnetId) as $result)
    {
      $eXactSource = $db->getExactSource($result->eXactSourceId);

      $scheme = $db->getScheme($eXactSource->schemeId);
      $host   = $db->getHost($eXactSource->hostId);
      $port   = $db->getPort($eXactSource->portId);
      $uri    = $db->getUri($eXactSource->uriId);

      $url    = sprintf('xs=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                  $host->value,
                                                                                  $port->value,
                                                                                  $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                      $host->value,
                                                                                                                      $uri->value)));

      // Yggdrasil url only
      if (!preg_match(YGGDRASIL_URL_REGEX, $url))
      {
        continue;
      }

      $link[] = $url;
    }

    // Return html
    $response->html->title = sprintf(
      _('%s - Download - %s'),
      htmlentities($magnet->metaTitle),
      WEBSITE_NAME
    );

    $response->html->h1 = htmlentities($magnet->metaTitle);
    $response->html->link->magnet = implode('&', array_unique($link)); // @TODO implement .bittorrent and separated v1/v2 magnet links
  }

?>

<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <title>
      <?php echo $response->data->title ?>
    </title>
    <meta name="robots" content="noindex,nofollow"/>
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
            <div class="padding-16 margin-y-8 border-radius-3 background-color-night">
              <?php if ($response->success) { ?>
                <div class="text-center">
                <h1 class="display-block margin-b-16 font-size-16"><?php echo $response->html->h1 ?></h1>
                  <div class="margin-b-16 text-color-night">
                    <?php echo _('* make sure BitTorrent client listen Yggdrasil interface!') ?>
                  </div>
                  <a href="<?php echo $response->html->link->magnet ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-magnet" viewBox="0 0 16 16">
                      <path d="M8 1a7 7 0 0 0-7 7v3h4V8a3 3 0 0 1 6 0v3h4V8a7 7 0 0 0-7-7Zm7 11h-4v3h4v-3ZM5 12H1v3h4v-3ZM0 8a8 8 0 1 1 16 0v8h-6V8a2 2 0 1 0-4 0v8H0V8Z"/>
                    </svg>
                  </a>
                </div>
              <?php } else { ?>
                <div class="text-center">
                  <?php echo $response->message ?>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
        <?php if (!empty($_SERVER['HTTP_REFERER']) && false !== strpos($_SERVER['HTTP_REFERER'], WEBSITE_URL)) { ?>
          <div class="row">
            <div class="column width-100 text-right">
              <a class="button margin-l-8"
                  rel="nofollow"
                  href="<?php echo $_SERVER['HTTP_REFERER'] ?>">
                <?php echo _('back') ?>
              </a>
            </div>
          </div>
        <?php } ?>
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
            <a href="<?php echo WEBSITE_URL ?>/node.php"><?php echo _('Node') ?></a>
            |
            <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/index.php?rss"><?php echo _('RSS') ?></a>
            |
            <a href="https://github.com/YGGverse/YGGtracker"><?php echo _('GitHub') ?></a>
          </div>
        </div>
      </div>
    </footer>
  </body>
</html>