
<?php

// Bootstrap
require_once __DIR__ . '/../config/bootstrap.php';

// Define response
$response = (object)
[
  'success' => true,
  'message' => _('Internal server error'),
];

// Yggdrasil connections only
if (!Valid::host($_SERVER['REMOTE_ADDR']))
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

// Init user
else if (!$user = $db->getUser($userId))
{
  $response->success = false;
  $response->message = _('Could not get user session');
}

// On first visit, redirect user to the welcome page with access level question
else if (is_null($user->public))
{
  header(
    sprintf('Location: %s/welcome.php', WEBSITE_URL)
  );
}

// Import form magnet link request
else if (!empty($_POST['magnet']))
{
  // Validate magnet
  if (!$magnet = Yggverse\Parser\Magnet::parse($_POST['magnet']))
  {
    $response->success = false;
    $response->message = _('Could not parse magnet link');
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
                                      '', // @TODO deprecated, remove
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
                if (Valid::url($tr))
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
                if (Valid::url($as))
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
              }
            break;
            case 'xs':
              foreach ($value as $xs)
              {
                if (Valid::url($xs))
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
      }
    }

    catch (Exception $error)
    {
      $response->success = false;
      $response->message = sprintf(
        _('Internal server error: %s'),
        print_r($error, true)
      );

      $db->rollBack();
    }
  }

  // Redirect to edit page on success
  if ($response->success)
  {
    header(sprintf('Location: %s/edit.php?magnetId=%s', trim(WEBSITE_URL, '/'), $magnetId));
  }
}

// Import form torrent file request
else if (!empty($_FILE['torrent']))
{
  // @TODO
}

?>
<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <title>
      <?php echo sprintf(_('Add - %s'), WEBSITE_NAME) ?>
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
                <div class="margin-b-24 padding-b-16 border-bottom-default">
                  <h2><?php echo _('Import') ?></h2>
                </div>
                <form class="margin-t-8" name="search" method="post" action="<?php echo WEBSITE_URL ?>/import.php">
                  <div class="margin-b-16">
                    <label class="margin-b-16 display-block" for="magnet"><?php echo _('Magnet URL') ?></label>
                    <textarea class="width-100" name="magnet" id="magnet" placeholder="<?php echo _('magnet: ...') ?>"></textarea>
                  </div>
                  <div class="margin-b-16">
                    <label class="margin-b-16 display-block" for="torrent"><?php echo _('Torrent file') ?></label>
                    <input class="width-100" type="file" name="torrent" id="torrent" value="" />
                  </div>
                  <div class="text-right">
                    <input type="submit" value="<?php echo _('import') ?>" />
                  </div>
                </form>
              <?php } else { ?>
                <div class="text-center">
                  <?php echo $response->message ?>
                </div>
              <?php } ?>
            </div>
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