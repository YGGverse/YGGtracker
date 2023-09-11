
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

// Init user
else if (!$user = $db->getUser($userId))
{
  $response->success = false;
  $response->message = _('Could not get user session');
}

// User can change public level once, because by agreement data could be already sent
// Otherwise, local access level could be changed to public on settings page later
// Redirect to website features
else if (!is_null($user->public))
{
  header(
    sprintf('Location: %s', WEBSITE_URL)
  );
}

// Apply answer on form submit
else if (isset($_POST['public']))
{
  if ($db->updateUserPublic($user->userId, (bool) $_POST['public'], time()))
  {
    header(
      sprintf('Location: %s', WEBSITE_URL)
    );
  }
}

?>
<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <title>
      <?php echo sprintf(_('Welcome to %s'), WEBSITE_NAME) ?>
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
                  <div class="margin-b-24 padding-b-16 border-bottom-default">
                    <h1 class=""><?php echo _('Welcome, stranger!') ?></h1>
                  </div>
                  <p class="margin-b-8"><?php echo _('YGGtracker use Yggdrasil address to identify users without registration') ?></p>
                  <p class="margin-b-16"><?php echo _('following address could be shared with independent nodes to allow you manage own content everywhere') ?></p>
                  <h2 class="margin-b-16"><?php echo $user->address ?></h2>
                  <form name="public" action="<?php echo sprintf('%s/welcome.php', WEBSITE_URL) ?>" method="post">
                    <div class="margin-b-16">
                      <label class="text-color-green margin-y-8 margin-x-4" for="public-1">
                        <input type="radio" id="public-1" name="public" value="1" checked="checked" />
                        <?php echo _('Allow data distribution') ?>
                      </label>
                      <label class="text-color-pink margin-y-8 margin-x-4" for="public-0">
                        <input type="radio" id="public-0" name="public" value="0" />
                        <?php echo _('Keep activity local') ?>
                      </label>
                    </div>
                    <div class="text-center">
                      <input type="submit" value="<?php echo _('confirm') ?>" />
                    </div>
                  </form>
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