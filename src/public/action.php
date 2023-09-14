<?php

// Bootstrap
require_once __DIR__ . '/../config/bootstrap.php';

// Define response
$response = (object)
[
  'success' => true,
  'message' => _('Internal server error'),
  'title'   => sprintf(_('Oops - %s'), WEBSITE_NAME)
];

// Begin action request
switch (isset($_GET['target']) ? urldecode($_GET['target']) : false)
{
  case 'profile':

    switch (isset($_GET['toggle']) ? $_GET['toggle'] : false)
    {
      case 'jidenticon':

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

        // Render icon
        else
        {
          header('Cache-Control: max-age=604800');


          $icon = new Jdenticon\Identicon();

          $icon->setValue($user->{USER_IDENTICON_FIELD});
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

      case 'new':

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
                mb_strlen($_POST['comment']) < MAGNET_COMMENT_MIN_LENGTH ||
                mb_strlen($_POST['comment']) > MAGNET_COMMENT_MAX_LENGTH)
        {
          $response->success = false;
          $response->message = sprintf(_('Valid comment value required, %s-%s chars allowed'), MAGNET_COMMENT_MIN_LENGTH, MAGNET_COMMENT_MAX_LENGTH);
        }

        // Request valid
        else
        {
          if ($magnetCommentId = $db->addMagnetComment($magnet->magnetId,
                                                       $user->userId,
                                                       null, // @TODO implement threads
                                                       trim($_POST['comment']),
                                                       $user->approved || in_array($user->address, MODERATOR_IP_LIST) ? true : MAGNET_COMMENT_DEFAULT_APPROVED,
                                                       MAGNET_COMMENT_DEFAULT_PUBLIC,
                                                       time()))
          {

            // Push event to other nodes
            if (API_EXPORT_ENABLED &&
                API_EXPORT_PUSH_ENABLED &&
                API_EXPORT_USERS_ENABLED &&
                API_EXPORT_MAGNETS_ENABLED &&
                API_EXPORT_MAGNET_COMMENTS_ENABLED)
            {
              if (!$memoryApiExportPush = $memory->get('api.export.push'))
              {
                $memoryApiExportPush = [];
              }

              $memoryApiExportPush[] = (object)
              [
                'time'            => time(),
                'userId'          => $user->userId,
                'magnetId'        => $magnet->magnetId,
                'magnetCommentId' => $magnetCommentId
              ];

              $memory->set('api.export.push', $memoryApiExportPush, 3600);
            }

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
          // Save star
          if ($magnetStarId = $db->addMagnetStar( $magnet->magnetId,
                                                  $user->userId,
                                                  !$db->findLastMagnetStarValue($magnet->magnetId, $user->userId),
                                                  time()))
          {
            // Push event to other nodes
            if (API_EXPORT_ENABLED &&
                API_EXPORT_PUSH_ENABLED &&
                API_EXPORT_USERS_ENABLED &&
                API_EXPORT_MAGNETS_ENABLED &&
                API_EXPORT_MAGNET_STARS_ENABLED)
            {
              if (!$memoryApiExportPush = $memory->get('api.export.push'))
              {
                $memoryApiExportPush = [];
              }

              $memoryApiExportPush[] = (object)
              [
                'time'         => time(),
                'userId'       => $user->userId,
                'magnetId'     => $magnet->magnetId,
                'magnetStarId' => $magnetStarId
              ];

              $memory->set('api.export.push', $memoryApiExportPush, 3600);
            }

            // Redirect to edit page
            header(
              sprintf('Location: %s', $callback)
            );
          }
        }

      break;

      case 'new':

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
            if ($magnetId = $db->addMagnet( $user->userId,
                                            $magnet->xl,
                                            $magnet->dn,
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
                  case 'xt':
                    foreach ($value as $xt)
                    {
                      if (Yggverse\Parser\Magnet::isXTv1($xt))
                      {
                        $db->addMagnetToInfoHash(
                          $magnetId,
                          $db->initInfoHashId(
                            Yggverse\Parser\Magnet::filterInfoHash($xt), 1
                          )
                        );
                      }
                      if (Yggverse\Parser\Magnet::isXTv2($xt))
                      {
                        $db->addMagnetToInfoHash(
                          $magnetId,
                          $db->initInfoHashId(
                            Yggverse\Parser\Magnet::filterInfoHash($xt), 2
                          )
                        );
                      }
                    }
                  break;
                  case 'tr':
                    foreach ($value as $tr)
                    {
                      if ($url = Yggverse\Parser\Url::parse($tr))
                      {
                        if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
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
                        if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
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
                    }
                  break;
                  case 'xs':
                    foreach ($value as $xs)
                    {
                      if ($url = Yggverse\Parser\Url::parse($xs))
                      {
                        if (preg_match(YGGDRASIL_HOST_REGEX, str_replace(['[',']'], false, $url->host->name)))
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
      <?php echo $response->title ?>
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