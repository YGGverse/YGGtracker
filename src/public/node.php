
<?php

// Load dependencies
require_once (__DIR__ . '/../config/app.php');
require_once (__DIR__ . '/../library/time.php');
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

?>
<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <title>
      <?php echo sprintf(_('%s instance info'), WEBSITE_NAME) ?>
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
                <h1 class="margin-b-16"><?php echo _('Node info') ?></h1>
                <table class="width-100">
                  <tbody>
                    <tr>
                      <td class="padding-b-8 border-bottom-default text-right" colspan="2">
                        <?php echo _('Profile') ?>
                      </td>
                    </tr>
                    <tr>
                      <td class="padding-t-16"><?php echo _('Access level') ?></td>
                      <td class="padding-t-16 cursor-default <?php echo $user->public ? _('text-color-green') : _('text-color-pink') ?>">
                        <?php echo $user->public ? _('Distributed') : _('Local') ?>
                        <sub class="text-color-default opacity-0 parent-hover-opacity-09" title="<?php echo $user->public ? _('You have access to own data everywhere YGGtracker running') : _('Your profile details stored on this instance only') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                      </td>
                    </tr>
                    <tr>
                      <td><?php echo _('Address') ?></td>
                      <td>
                        <?php echo $user->address ?>
                      </td>
                    </tr>
                    <tr>
                      <td><?php echo _('Updated') ?></td>
                      <td>
                        <?php echo Time::ago((int) $user->timeUpdated) ?>
                      </td>
                    </tr>
                    <!--
                    <tr>
                      <td><?php echo _('Magnets') ?></td>
                      <td>
                        <?php echo $db->findMagnetsTotalByUserId($user->userId) ?>
                      </td>
                    </tr>
                    <tr>
                      <td><?php echo _('Comments') ?></td>
                      <td>
                        <?php echo $db->findMagnetCommentsTotalByUserId($user->userId) ?>
                      </td>
                    </tr>
                    -->
                    <tr>
                      <td class="padding-b-8 border-bottom-default text-right" colspan="2">
                        <?php echo _('Rules') ?>
                      </td>
                    </tr>
                    <tr>
                      <td class="padding-t-16"><?php echo _('Subject') ?></td>
                      <td class="padding-t-16"><?php echo _(RULE_SUBJECT) ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Languages') ?></td>
                      <td><?php echo _(RULE_LANGUAGES) ?></td>
                    </tr>
                    <tr>
                      <td class="padding-y-8 border-bottom-default text-right" colspan="2">
                        <?php echo _('Totals') ?>
                      </td>
                    </tr>
                    <tr>
                      <td class="padding-t-16"><?php echo _('Users') ?></td>
                      <td class="padding-t-16">
                        <?php echo $db->getUsersTotal() ?>
                        <?php if ($usersTotalByPublicTrue = $db->getUsersTotalByPublic(true)) { ?>
                        /
                        <span class="cursor-default text-color-green">
                          <?php echo $usersTotalByPublicTrue ?>
                        </span>
                        <?php } ?>
                        <?php if ($usersTotalByPublicFalse = $db->getUsersTotalByPublic(false)) { ?>
                        /
                        <span class="cursor-default text-color-pink">
                          <?php echo $usersTotalByPublicFalse ?>
                        </span>
                        <?php } ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('total') .
                                                                                        ($usersTotalByPublicTrue  ? sprintf(' / %s', _('distributed')) : false) .
                                                                                        ($usersTotalByPublicFalse ? sprintf(' / %s', _('local')) : false) ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                      </td>
                    </tr>
                    <tr>
                      <td><?php echo _('Magnets') ?></td>
                      <td class="cursor-default">
                        <?php echo $db->getMagnetsTotal() ?>
                        <?php if ($magnetsTotalByUsersPublicTrue = $db->getMagnetsTotalByUsersPublic(true)) { ?>
                          /
                          <span class="text-color-green">
                            <?php echo $magnetsTotalByUsersPublicTrue ?>
                          </span>
                        <?php } ?>
                        <?php if ($magnetsTotalByUsersPublicFalse = $db->getMagnetsTotalByUsersPublic(false)) { ?>
                          /
                          <span class="text-color-pink">
                            <?php echo $magnetsTotalByUsersPublicFalse ?>
                          </span>
                        <?php } ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('total') .
                                                                                        ($magnetsTotalByUsersPublicTrue  ? sprintf(' / %s', _('distributed')) : false) .
                                                                                        ($magnetsTotalByUsersPublicFalse ? sprintf(' / %s', _('local')) : false) ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                      </td>
                    </tr>
                    <tr>
                      <td><?php echo _('Comments') ?></td>
                      <td class="cursor-default">
                        <?php echo $db->getMagnetCommentsTotal() ?>
                        <?php if ($magnetCommentsTotalByUsersPublicTrue = $db->getMagnetCommentsTotalByUsersPublic(true)) { ?>
                          /
                          <span class="text-color-green">
                            <?php echo $magnetCommentsTotalByUsersPublicTrue ?>
                          </span>
                        <?php } ?>
                        <?php if ($magnetCommentsTotalByUsersPublicFalse = $db->getMagnetCommentsTotalByUsersPublic(false)) { ?>
                          /
                          <span class="text-color-pink">
                            <?php echo $magnetCommentsTotalByUsersPublicFalse ?>
                          </span>
                        <?php } ?>
                        <sub class="opacity-0 parent-hover-opacity-09" title="<?php echo _('total') .
                                                                                        ($magnetCommentsTotalByUsersPublicTrue  ? sprintf(' / %s', _('distributed')) : false) .
                                                                                        ($magnetCommentsTotalByUsersPublicFalse ? sprintf(' / %s', _('local')) : false) ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                          </svg>
                        </sub>
                      </td>
                    </tr>
                    <tr>
                      <td><?php echo _('Moderators') ?></td>
                      <td><?php echo count(MODERATOR_IP_LIST) ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Seeds') ?></td>
                      <td><?php echo $db->getMagnetToAddressTrackerSeedersSum() ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Peers') ?></td>
                      <td><?php echo $db->getMagnetToAddressTrackerCompletedSum() ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Leechers') ?></td>
                      <td><?php echo $db->getMagnetToAddressTrackerLeechersSum() ?></td>
                    </tr>
                    <tr>
                      <td class="padding-y-8 border-bottom-default text-right" colspan="2">
                        <?php echo _('Users') ?>
                      </td>
                    </tr>
                    <tr>
                      <td class="padding-t-16"><?php echo _('Identicon') ?></td>
                      <td class="padding-t-16"><?php echo USER_DEFAULT_IDENTICON ? USER_DEFAULT_IDENTICON : _('no') ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Identicon key') ?></td>
                      <td><?php echo USER_IDENTICON_FIELD ? USER_IDENTICON_FIELD : _('undefined') ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Approved by default') ?></td>
                      <td><?php echo USER_DEFAULT_APPROVED ? _('yes') : _('no') ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Auto-approved on magnet approve') ?></td>
                      <td><?php echo USER_AUTO_APPROVE_ON_MAGNET_APPROVE ? _('yes') : _('no') ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Auto-approved on comment approve') ?></td>
                      <td><?php echo USER_AUTO_APPROVE_ON_COMMENT_APPROVE ? _('yes') : _('no') ?></td>
                    </tr>
                    <tr>
                      <td class="padding-y-8 border-bottom-default text-right" colspan="2">
                        <?php echo _('Magnets') ?>
                      </td>
                    </tr>
                    <tr>
                      <td class="padding-t-16"><?php echo _('Approved by default') ?></td>
                      <td class="padding-t-16"><?php echo MAGNET_DEFAULT_APPROVED ? _('yes') : _('no') ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Title, chars') ?></td>
                      <td><?php echo MAGNET_TITLE_MIN_LENGTH ?>-<?php echo MAGNET_TITLE_MAX_LENGTH ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Description short, chars') ?></td>
                      <td><?php echo MAGNET_PREVIEW_MIN_LENGTH ?>-<?php echo MAGNET_PREVIEW_MAX_LENGTH ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Description long, chars') ?></td>
                      <td><?php echo MAGNET_DESCRIPTION_MIN_LENGTH ?>-<?php echo MAGNET_DESCRIPTION_MAX_LENGTH ?></td>
                    </tr>
                    <tr>
                      <td class="padding-y-8 border-bottom-default text-right" colspan="2">
                        <?php echo _('Comments') ?>
                      </td>
                    </tr>
                    <tr>
                      <td class="padding-t-16"><?php echo _('Approved by default') ?></td>
                      <td class="padding-t-16"><?php echo COMMENT_DEFAULT_APPROVED ? _('yes') : _('no') ?></td>
                    </tr>
                    <tr>
                      <td><?php echo _('Length, chars') ?></td>
                      <td><?php echo COMMENT_MIN_LENGTH ?>-<?php echo COMMENT_MAX_LENGTH ?></td>
                    </tr>
                    <tr>
                      <td class="padding-y-8 padding-b-8 border-bottom-default text-right" colspan="2">
                        <?php echo _('Trackers') ?>
                        <a href="https://github.com/YGGverse/YGGtracker/blob/main/src/config/trackers.json" title="<?php echo _('Add') ?>">
                          <sub class="margin-l-4">
                            <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                              <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                            </svg>
                          </sub>
                        </a>
                      </td>
                      <?php foreach (json_decode(file_get_contents(__DIR__ . '/../config/trackers.json')) as $i => $tracker) { ?>
                        <tr>
                          <td class="padding-t-16"><?php echo sprintf('#%s', $i + 1) ?></td>
                        </tr>
                        <?php foreach ($tracker as $key => $value) { ?>
                          <?php if ($value) { ?>
                            <tr>
                              <td>
                                <span class="margin-l-16"><?php echo $key ?></span>
                              </td>
                              <td><?php echo $value ?></td>
                            </tr>
                          <?php } ?>
                        <?php } ?>
                      <?php } ?>
                    </tr>
                  </tbody>
                </table>
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