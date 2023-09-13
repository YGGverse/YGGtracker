<?php

// Bootstrap
require_once __DIR__ . '/../config/bootstrap.php';

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
if (!preg_match(YGGDRASIL_HOST_REGEX, $_SERVER['REMOTE_ADDR']))
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

// Get user
else if (!$user = $db->getUser($userId))
{
  $response->success = false;
  $response->message = _('Could not init user info');
}

// On first visit, redirect user to the welcome page with access level question
else if (is_null($user->public))
{
  header(
    sprintf('Location: %s/welcome.php', WEBSITE_URL)
  );
}

// Request valid
else
{
  // Update download stats
  $db->addMagnetDownload($magnet->magnetId, $userId, time());

  // Build magnet link
  $link = (object)
  [
    'magnet' => [],
    'direct' => [],
  ];

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

  $link->magnet[] = sprintf('magnet:?%s', implode('&', $xt));

  /// Display Name
  $link->magnet[] = sprintf('dn=%s', urlencode($magnet->dn));

  // Keyword Topic
  $kt = [];

  foreach ($db->findKeywordTopicByMagnetId($magnet->magnetId) as $result)
  {
    $kt[] = urlencode($db->getKeywordTopic($result->keywordTopicId)->value);
  }

  $link->magnet[] = sprintf('kt=%s', implode('+', $kt));

  /// Address Tracker
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

    $link->magnet[] = sprintf('tr=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $port->value,
                                                                                        $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                            $host->value,
                                                                                                                            $uri->value)));
  }

  // Append trackers.json
  foreach (json_decode(file_get_contents(__DIR__ . '/../config/trackers.json')) as $tracker)
  {
    $link->magnet[] = sprintf('tr=%s', urlencode($tracker->announce));
  }

  /// Acceptable Source
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

    $link->magnet[] = sprintf('as=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $port->value,
                                                                                        $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                            $host->value,
                                                                                                                            $uri->value)));
    $link->direct[] = $port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                              $host->value,
                                                              $port->value,
                                                              $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                  $host->value,
                                                                                                  $uri->value);
  }

  /// Exact Source
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

    $link->magnet[] = sprintf('xs=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $port->value,
                                                                                        $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                            $host->value,
                                                                                                                            $uri->value)));
  }

  // Return html
  $response->html->title = sprintf(
    _('%s - Download - %s'),
    htmlentities($magnet->title),
    WEBSITE_NAME
  );

  $response->html->h1 = htmlentities($magnet->title);

  // @TODO implement .bittorrent, separated v1/v2 magnet links
  $response->html->link->magnet = implode('&', array_unique($link->magnet));
  $response->html->link->direct = $link->direct;
}

?>

<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <title>
      <?php echo $response->html->title ?>
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
                  <a class="padding-x-4" href="<?php echo $response->html->link->magnet ?>" title="<?php echo _('Magnet') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-magnet" viewBox="0 0 16 16">
                      <path d="M8 1a7 7 0 0 0-7 7v3h4V8a3 3 0 0 1 6 0v3h4V8a7 7 0 0 0-7-7Zm7 11h-4v3h4v-3ZM5 12H1v3h4v-3ZM0 8a8 8 0 1 1 16 0v8h-6V8a2 2 0 1 0-4 0v8H0V8Z"/>
                    </svg>
                  </a>
                  <?php foreach ($response->html->link->direct as $direct) { ?>
                    <a class="padding-x-4" href="<?php echo $direct ?>" title="<?php echo _('Direct') ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-database" viewBox="0 0 16 16">
                        <path d="M4.318 2.687C5.234 2.271 6.536 2 8 2s2.766.27 3.682.687C12.644 3.125 13 3.627 13 4c0 .374-.356.875-1.318 1.313C10.766 5.729 9.464 6 8 6s-2.766-.27-3.682-.687C3.356 4.875 3 4.373 3 4c0-.374.356-.875 1.318-1.313ZM13 5.698V7c0 .374-.356.875-1.318 1.313C10.766 8.729 9.464 9 8 9s-2.766-.27-3.682-.687C3.356 7.875 3 7.373 3 7V5.698c.271.202.58.378.904.525C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777A4.92 4.92 0 0 0 13 5.698ZM14 4c0-1.007-.875-1.755-1.904-2.223C11.022 1.289 9.573 1 8 1s-3.022.289-4.096.777C2.875 2.245 2 2.993 2 4v9c0 1.007.875 1.755 1.904 2.223C4.978 15.71 6.427 16 8 16s3.022-.289 4.096-.777C13.125 14.755 14 14.007 14 13V4Zm-1 4.698V10c0 .374-.356.875-1.318 1.313C10.766 11.729 9.464 12 8 12s-2.766-.27-3.682-.687C3.356 10.875 3 10.373 3 10V8.698c.271.202.58.378.904.525C4.978 9.71 6.427 10 8 10s3.022-.289 4.096-.777A4.92 4.92 0 0 0 13 8.698Zm0 3V13c0 .374-.356.875-1.318 1.313C10.766 14.729 9.464 15 8 15s-2.766-.27-3.682-.687C3.356 13.875 3 13.373 3 13v-1.302c.271.202.58.378.904.525C4.978 12.71 6.427 13 8 13s3.022-.289 4.096-.777c.324-.147.633-.323.904-.525Z"/>
                      </svg>
                    </a>
                  <?php } ?>
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
            <a href="<?php echo WEBSITE_URL ?>/faq.php"><?php echo _('F.A.Q') ?></a>
            |
            <a href="<?php echo WEBSITE_URL ?>/node.php"><?php echo _('Node') ?></a>
            |
            <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/index.php?rss"><?php echo _('RSS') ?></a>
            <?php if (API_ENABLED) { ?>
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