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
  'message' => _('Internal server error')
];

// Begin action request
switch (isset($_GET['target']) ? urldecode($_GET['target']) : false)
{
  case 'profile':

    switch (isset($_GET['toggle']) ? $_GET['toggle'] : false)
    {
      case 'identicon':

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

        // Get user
        else if (!$user = $db->getUser($userId))
        {
          $response->success = false;
          $response->message = _('Could not init user info');
        }

        // Render icon
        else
        {
          header('Cache-Control: max-age=604800');


          $icon = new Jdenticon\Identicon();

          $icon->setValue($user->address);
          $icon->setSize(empty($_GET['size']) ? 100 : (int) $_GET['size']);
          $icon->setStyle(
            [
              'backgroundColor' => 'rgba(255, 255, 255, 0)',
            ]
          );
          $icon->displayImage('webp');
        }

      break;
    }

  break;

  case 'comment':

    switch (isset($_GET['toggle']) ? $_GET['toggle'] : false)
    {
      case 'approved':

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

        // Get user
        else if (!$user = $db->getUser($userId))
        {
          $response->success = false;
          $response->message = _('Could not init user info');
        }

        // Magnet comment exists
        else if (!$magnetComment = $db->getMagnetComment(isset($_GET['magnetCommentId']) && $_GET['magnetCommentId'] > 0 ? (int) $_GET['magnetCommentId'] : 0))
        {
          $response->success = false;
          $response->message = _('Requested magnet comment not found');
        }

        // Access allowed
        else if (!in_array($user->address, MODERATOR_IP_LIST)) {

          $response->success = false;
          $response->message = _('Access denied');
        }

        // Validate callback
        else if (empty($_GET['callback']))
        {
          $response->success = false;
          $response->message = _('Callback required');
        }

        // Validate base64
        else if (!$callback = (string) @base64_decode($_GET['callback']))
        {
          $response->success = false;
          $response->message = _('Invalid callback encoding');
        }

        // Request valid
        else
        {
          if ($magnetComment->approved)
          {
            $db->updateMagnetCommentApproved($magnetComment->magnetCommentId, false);

            if (USER_AUTO_APPROVE_ON_COMMENT_APPROVE)
            {
              $db->updateUserApproved($magnetComment->userId, false, time());
            }
          }
          else
          {
            $db->updateMagnetCommentApproved($magnetComment->magnetCommentId, true);

            if (USER_AUTO_APPROVE_ON_COMMENT_APPROVE)
            {
              $db->updateUserApproved($magnetComment->userId, true, time());
            }
          }

          // Redirect to edit page
          header(
            sprintf('Location: %s', $callback)
          );
        }

      break;

      case 'public':

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

        // Get user
        else if (!$user = $db->getUser($userId))
        {
          $response->success = false;
          $response->message = _('Could not init user info');
        }

        // Magnet comment exists
        else if (!$magnetComment = $db->getMagnetComment(isset($_GET['magnetCommentId']) && $_GET['magnetCommentId'] > 0 ? (int) $_GET['magnetCommentId'] : 0))
        {
          $response->success = false;
          $response->message = _('Requested magnet comment not found');
        }

        // Access allowed
        else if (!($user->address == $db->getUser($magnetComment->userId)->address || in_array($user->address, MODERATOR_IP_LIST))) {

          $response->success = false;
          $response->message = _('Access denied');
        }

        // Validate callback
        else if (empty($_GET['callback']))
        {
          $response->success = false;
          $response->message = _('Callback required');
        }

        // Validate base64
        else if (!$callback = (string) @base64_decode($_GET['callback']))
        {
          $response->success = false;
          $response->message = _('Invalid callback encoding');
        }

        // Request valid
        else
        {
          if ($magnetComment->public)
          {
            $db->updateMagnetCommentPublic($magnetComment->magnetCommentId, false);
          }
          else{
            $db->updateMagnetCommentPublic($magnetComment->magnetCommentId, true);
          }

          // Redirect to edit page
          header(
            sprintf('Location: %s', $callback)
          );
        }

      break;

      case 'new':

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

        // Get user
        else if (!$user = $db->getUser($userId))
        {
          $response->success = false;
          $response->message = _('Could not init user info');
        }

        // Magnet exists
        else if (!$magnet = $db->getMagnet(isset($_GET['magnetId']) && $_GET['magnetId'] > 0 ? (int) $_GET['magnetId'] : 0))
        {
          $response->success = false;
          $response->message = _('Requested magnet not found');
        }

        // Access allowed
        else if (!($user->address == $db->getUser($magnet->userId)->address || in_array($user->address, MODERATOR_IP_LIST) || ($magnet->public && $magnet->approved))) {

          $response->success = false;
          $response->message = _('Magnet not available for this action');
        }

        // Validate callback
        else if (empty($_GET['callback']))
        {
          $response->success = false;
          $response->message = _('Callback required');
        }

        // Validate base64
        else if (!$callback = (string) @base64_decode($_GET['callback']))
        {
          $response->success = false;
          $response->message = _('Invalid callback encoding');
        }

        // Validate comment value
        else if (empty($_POST['comment']) ||
                mb_strlen($_POST['comment']) < COMMENT_MIN_LENGTH ||
                mb_strlen($_POST['comment']) > COMMENT_MAX_LENGTH)
        {
          $response->success = false;
          $response->message = sprintf(_('Valid comment value required, %s-%s chars allowed'), COMMENT_MIN_LENGTH, COMMENT_MAX_LENGTH);
        }

        // Request valid
        else
        {
          if ($magnetCommentId = $db->addMagnetComment($magnet->magnetId,
                                                      $user->userId,
                                                      null, // @TODO implement threads
                                                      trim($_POST['comment']),
                                                      $user->approved || in_array($user->address, MODERATOR_IP_LIST) ? true : COMMENT_DEFAULT_APPROVED,
                                                      COMMENT_DEFAULT_PUBLIC,
                                                      time()))
          {
            // Redirect to referrer page
            header(
              sprintf('Location: %s#comment-%s', $callback, $magnetCommentId)
            );
          }
        }

      break;

      default:

        header(
          sprintf('Location: %s', WEBSITE_URL)
        );
    }

  break;

  case 'magnet':

    switch (isset($_GET['toggle']) ? $_GET['toggle'] : false)
    {
      case 'star':

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

        // Validate callback
        else if (empty($_GET['callback']))
        {
          $response->success = false;
          $response->message = _('Callback required');
        }

        // Validate base64
        else if (!$callback = (string) @base64_decode($_GET['callback']))
        {
          $response->success = false;
          $response->message = _('Invalid callback encoding');
        }

        // Request valid
        else
        {
          // Star exists, trigger delete
          if ($db->findMagnetStarsTotalByUserId($magnet->magnetId, $userId))
          {
            $db->deleteMagnetStarByUserId($magnet->magnetId, $userId);
          }
          else
          {
            // Star not exists, trigger add
            $db->addMagnetStar($magnet->magnetId, $userId, time());
          }

          // Redirect to edit page
          header(
            sprintf('Location: %s', $callback)
          );
        }

      break;

      case 'download':

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
          $link[] = sprintf('magnet:?xt=%s', $magnet->xt);

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

            $link[] = sprintf('tr=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $port->value,
                                                                                        $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                            $host->value,
                                                                                                                            $uri->value)));
          }

          foreach (TRACKER_LINKS as $tracker => $value)
          {
            $link[] = sprintf('tr=%s', urlencode($value->announce));
          }

          /// Acceptable Source
          foreach ($db->findAcceptableSourceByMagnetId($magnet->magnetId) as $result)
          {
            $acceptableSource = $db->getAcceptableSource($result->acceptableSourceId);

            $scheme = $db->getScheme($acceptableSource->schemeId);
            $host   = $db->getHost($acceptableSource->hostId);
            $port   = $db->getPort($acceptableSource->portId);
            $uri    = $db->getUri($acceptableSource->uriId);

            $link[] = sprintf('as=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $port->value,
                                                                                        $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                            $host->value,
                                                                                                                            $uri->value)));
          }

          /// Exact Source
          foreach ($db->findExactSourceByMagnetId($magnet->magnetId) as $result)
          {
            $eXactSource = $db->getExactSource($result->eXactSourceId);

            $scheme = $db->getScheme($eXactSource->schemeId);
            $host   = $db->getHost($eXactSource->hostId);
            $port   = $db->getPort($eXactSource->portId);
            $uri    = $db->getUri($eXactSource->uriId);

            $link[] = sprintf('xs=%s', urlencode($port->value ? sprintf('%s://%s:%s%s', $scheme->value,
                                                                                        $host->value,
                                                                                        $port->value,
                                                                                        $uri->value) : sprintf('%s://%s%s', $scheme->value,
                                                                                                                            $host->value,
                                                                                                                            $uri->value)));
          }

          // Return download link
          header(
            sprintf('Location: %s', implode('&', array_unique($link)))
          );
        }

      break;

      case 'new':

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

        // Get user
        else if (!$user = $db->getUser($userId))
        {
          $response->success = false;
          $response->message = _('Could not init user info');
        }

        // Validate link
        if (empty($_GET['magnet']))
        {
          $response->success = false;
          $response->message = _('Link required');
        }

        // Validate base64
        else if (!$link = (string) @base64_decode($_GET['magnet']))
        {
          $response->success = false;
          $response->message = _('Invalid link encoding');
        }

        // Validate magnet
        else if (!$magnet = Yggverse\Parser\Magnet::parse($link))
        {
          $response->success = false;
          $response->message = _('Invalid magnet link');
        }

        // Request valid
        else
        {
          // Begin magnet registration
          try
          {
            $db->beginTransaction();

            // Init magnet
            if (Yggverse\Parser\Urn::parse($magnet->xt))
            {
              if ($magnetId = $db->initMagnetId($user->userId,
                                                strip_tags($magnet->xt),
                                                strip_tags($magnet->xl),
                                                strip_tags($magnet->dn),
                                                $link,
                                                MAGNET_DEFAULT_PUBLIC,
                                                MAGNET_DEFAULT_COMMENTS,
                                                MAGNET_DEFAULT_SENSITIVE,
                                                $user->approved ? true : MAGNET_DEFAULT_APPROVED,
                                                time()))
              {
                foreach ($magnet as $key => $value)
                {
                  switch ($key)
                  {
                    case 'tr':
                      foreach ($value as $tr)
                      {
                        if ($url = Yggverse\Parser\Url::parse($tr))
                        {
                          $db->initMagnetToAddressTrackerId(
                            $magnetId,
                            $db->initAddressTrackerId(
                              $db->initSchemeId($url->host->scheme),
                              $db->initHostId($url->host->name),
                              $db->initPortId($url->host->port),
                              $db->initUriId($url->page->uri)
                            )
                          );
                        }
                      }
                    break;
                    case 'ws':
                      foreach ($value as $ws)
                      {
                        // @TODO
                      }
                    break;
                    case 'as':
                      foreach ($value as $as)
                      {
                        if ($url = Yggverse\Parser\Url::parse($as))
                        {
                          $db->initMagnetToAcceptableSourceId(
                            $magnetId,
                            $db->initAcceptableSourceId(
                              $db->initSchemeId($url->host->scheme),
                              $db->initHostId($url->host->name),
                              $db->initPortId($url->host->port),
                              $db->initUriId($url->page->uri)
                            )
                          );
                        }
                      }
                    break;
                    case 'xs':
                      foreach ($value as $xs)
                      {
                        if ($url = Yggverse\Parser\Url::parse($xs))
                        {
                          $db->initMagnetToExactSourceId(
                            $magnetId,
                            $db->initExactSourceId(
                              $db->initSchemeId($url->host->scheme),
                              $db->initHostId($url->host->name),
                              $db->initPortId($url->host->port),
                              $db->initUriId($url->page->uri)
                            )
                          );
                        }
                      }
                    break;
                    case 'mt':
                      foreach ($value as $mt)
                      {
                        // @TODO
                      }
                    break;
                    case 'x.pe':
                      foreach ($value as $xPe)
                      {
                        // @TODO
                      }
                    break;
                    case 'kt':
                      foreach ($value as $kt)
                      {
                        $db->initMagnetToKeywordTopicId(
                          $magnetId,
                          $db->initKeywordTopicId(trim(mb_strtolower(strip_tags(html_entity_decode($kt)))))
                        );
                      }
                    break;
                  }
                }

                $db->commit();

                // Redirect to edit page
                header(sprintf('Location: %s/edit.php?magnetId=%s', trim(WEBSITE_URL, '/'), $magnetId));
              }
            }

          } catch (Exception $e) {

            var_dump($e);

            $db->rollBack();
          }
        }

      break;
    }

  break;
}

?>

<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <title>
      <?php echo sprintf(_('Oops - %s'), WEBSITE_NAME) ?>
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
              <div class="text-center"><?php echo $response->message ?></div>
            </div>
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
            <a href="<?php echo WEBSITE_URL ?>/index.php?rss"><?php echo _('RSS') ?></a>
            |
            <a href="https://github.com/YGGverse/YGGtracker"><?php echo _('GitHub') ?></a>
          </div>
        </div>
      </div>
    </footer>
  </body>
</html>