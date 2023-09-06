
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

?>
<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <title>
      <?php echo sprintf(_('F.A.Q. - %s'), WEBSITE_NAME) ?>
    </title>
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
                <h1><?php echo _('F.A.Q.') ?></h1>
                <div class="margin-b-16 text-right padding-y-8 margin-b-8 border-bottom-default">
                  <?php echo _('Project') ?>
                </div>
                <div class="margin-b-16">
                  <h3><?php echo _('About') ?></h3>
                  <a class="opacity-0 parent-hover-opacity-09 position-relative top-2" name="about" href="#about">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link" viewBox="0 0 16 16">
                      <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                      <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                    </svg>
                  </a>
                  <div class="margin-t-16">
                    <p><?php echo _('YGGtracker is an <a href="https://github.com/YGGverse/YGGtracker">open source</a> community-driven BitTorrent registry for <a href="https://yggdrasil-network.github.io/">Yggdrasil</a> ecosystem.') ?></p>
                    <p><?php echo _('Project following free and censorship-resistant data exchange by using Yggdrasil tuns and federated API.') ?></p>
                  </div>
                </div>
                <div class="margin-b-16">
                  <h3><?php echo _('Privacy') ?></h3>
                  <a class="opacity-0 parent-hover-opacity-09 position-relative top-2" name="privacy" href="#privacy">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link" viewBox="0 0 16 16">
                      <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                      <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                    </svg>
                  </a>
                  <div class="margin-t-16">
                    <p class="margin-b-16"><?php echo _('YGGtracker uses IPv6 in 0200::/7 range to auto-identify users and relate theirs activity like downloads, favlists, comments, etc, without manual registration. This model allows to build decentralized BitTorrent registry where users able to manage their own content everywhere YGGtracker instance running.') ?></p>
                    <p><?php echo _('Pay attention, Yggdrasil protocol does not make connection private, just packets. So if your connection sensitive for ISP or local laws, please use Proxy bridge, Tor or I2P software before use YGGtracker.') ?></p>
                  </div>
                </div>
                <div class="margin-b-16">
                  <h3><?php echo _('Rules') ?></h3>
                  <a class="opacity-0 parent-hover-opacity-09 position-relative top-2" name="rules" href="#rules">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link" viewBox="0 0 16 16">
                      <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                      <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                    </svg>
                  </a>
                  <div class="margin-t-16">
                    <p><?php echo sprintf(_('Every node able to provide local rules like region, content subject, etc. Human-readable manifest usually available at <a href="%s/node.php">Node</a> page.'), WEBSITE_URL) ?></p>
                  </div>
                </div>
                <div class="margin-b-16 text-right padding-y-8 margin-b-8 border-bottom-default">
                  <?php echo _('Interface') ?>
                </div>
                <div class="margin-b-16">
                  <h3><?php echo _('Upload') ?></h3>
                  <a class="opacity-0 parent-hover-opacity-09 position-relative top-2" name="download" href="#upload">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link" viewBox="0 0 16 16">
                      <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                      <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                    </svg>
                  </a>
                  <div class="margin-t-16">
                    <p><?php echo _('Just copy magnet link from your BitTorrent client into the search field in header, then enter <strong>Submit</strong>.') ?></p>
                    <p class="margin-b-16"><?php echo _('You will be redirected to edit form, where provide additional options like description or magnet meta.') ?></p>
                    <p class="margin-b-16"><?php echo _('When publication has sensitive content, please mark your post as sensitive, this option allows properly process the data in API as NSFW by applying UI filters.') ?></p>
                  </div>
                </div>
                <div class="margin-b-16">
                  <h3><?php echo _('Download') ?></h3>
                  <a class="opacity-0 parent-hover-opacity-09 position-relative top-2" name="download" href="#download">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link" viewBox="0 0 16 16">
                      <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                      <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                    </svg>
                  </a>
                  <div class="margin-t-16">
                    <p class="margin-b-16"><?php echo _('To use torrents in Yggdrasil network, your client should support IPv6 protocol and listen Yggdrasil interface, instead or with regular internet connection.') ?></p>
                    <p class="margin-b-16"><?php echo _('<a href="https://www.qbittorrent.org/">qBittorrent</a> supports all required features, just check <strong>Preferences - Advanced - Network interface / Optional IP address to bind to</strong> settings and select Yggdrasil instance from list, then reboot client.') ?></p>
                    <p><?php echo sprintf(_('To start download, click %s icon and select available options: magnet or direct download.'), '<svg class="width-13px position-relative top-2" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down-circle" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v5.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V4.5z"/></svg>') ?></p>
                  </div>
                </div>
                <div class="margin-b-16">
                  <h3><?php echo _('Seeding') ?></h3>
                  <a class="opacity-0 parent-hover-opacity-09 position-relative top-2" name="seeding" href="#seeding">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link" viewBox="0 0 16 16">
                      <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                      <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                    </svg>
                  </a>
                  <div class="margin-t-16">
                    <p><?php echo _('To start seeding, make sure you have BitTorrent client configured to listen Yggdrasil interface.') ?></p>
                    <p><?php echo _('Another requirement - port given by client enabled in firewall rules.') ?></p>
                    <p><?php echo _('On changes, re-announce your connection to tracker, alternatively restart BitTorrent client.') ?></p>
                  </div>
                </div>
                <div class="margin-b-16 text-right padding-y-8 margin-b-8 border-bottom-default">
                  <?php echo _('Development') ?>
                </div>
                <div class="margin-b-16">
                  <h3><?php echo _('Deploy') ?></h3>
                  <a class="opacity-0 parent-hover-opacity-09 position-relative top-2" name="deploy" href="#deploy">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link" viewBox="0 0 16 16">
                      <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                      <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                    </svg>
                  </a>
                  <div class="margin-t-16">
                    <p class="margin-b-16"><?php echo _('At this moment, software and communication protocol under development.') ?></p>
                    <p><?php echo _('To deploy YGGtracker instance with features available before release, read <a href="https://github.com/YGGverse/YGGtracker#installation">Install</a> section.') ?></p>
                    <p><?php echo _('Please define your node in <a href="https://github.com/YGGverse/YGGtracker/blob/main/src/config/trackers.json">trackers.json</a> registry to participate shared model testing.') ?></p>
                  </div>
                </div>
                <div class="margin-b-16">
                  <h3><?php echo _('Contribute') ?></h3>
                  <a class="opacity-0 parent-hover-opacity-09 position-relative top-2" name="contribute" href="#contribute">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link" viewBox="0 0 16 16">
                      <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                      <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                    </svg>
                  </a>
                  <div class="margin-t-16">
                    <p class="margin-b-16"><?php echo _('Ideas or bug reports on <a href="https://github.com/YGGverse/YGGtracker/issues">Issues</a> page!') ?></p>
                  </div>
                </div>
              <?php } else { ?>
                <div class="text-center"><?php echo $response->message ?></div>
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
            |
            <a href="https://github.com/YGGverse/YGGtracker"><?php echo _('GitHub') ?></a>
          </div>
        </div>
      </div>
    </footer>
  </body>
</html>