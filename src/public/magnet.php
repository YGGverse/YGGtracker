<?php

// Load dependencies
require_once (__DIR__ . '/../config/app.php');
require_once (__DIR__ . '/../library/sphinx.php');
require_once (__DIR__ . '/../library/database.php');
require_once (__DIR__ . '/../library/time.php');
require_once (__DIR__ . '/../../vendor/autoload.php');

// Connect Sphinx
try {

  $sphinx = new Sphinx(SPHINX_HOST, SPHINX_PORT);

} catch(Exception $e) {

  var_dump($e);

  exit;
}

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
  'success'  => true,
  'message'  => false,
  'magnet'   => [],
  'comments' => [],
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
else if (!$magnet = $db->getMagnet(isset($_GET['magnetId']) ? (int) $_GET['magnetId'] : 0))
{
  $response->success = false;
  $response->message = _('Magnet not found! Submit new magnet link by sending address to the search field.');
}

// Request valid
else
{
  // Get access info
  $accessRead = ($user->address == $db->getUser($magnet->userId)->address || in_array($user->address, MODERATOR_IP_LIST) || ($magnet->public && $magnet->approved));
  $accessEdit = ($user->address == $db->getUser($magnet->userId)->address || in_array($user->address, MODERATOR_IP_LIST));

  // Update magnet viwed
  if ($accessRead)
  {
    $db->addMagnetView($magnet->magnetId, $userId, time());
  }

  // Keywords
  $keywords = [];

  foreach ($db->findKeywordTopicByMagnetId($magnet->magnetId) as $keyword)
  {
    $keywords[] = $db->getKeywordTopic($keyword->keywordTopicId)->value;
  }

  $response->user   = $user;
  $response->magnet = (object)
  [
    'magnetId'        => $magnet->magnetId,
    'metaTitle'       => $magnet->metaTitle ? htmlentities($magnet->metaTitle) : ($magnet->dn ? htmlentities($magnet->dn): false),
    'metaDescription' => $magnet->metaDescription ? nl2br(
                                                      htmlentities(
                                                        $magnet->metaDescription
                                                      )
                                                    ) : false,
    'description'     => $magnet->description ? nl2br(
                                                  htmlentities(
                                                    $magnet->description
                                                  )
                                                ) : false,
    'approved'        => (bool) $magnet->approved,
    'public'          => (bool) $magnet->public,
    'sensitive'       => (bool) $magnet->sensitive,
    'comments'        => (bool) $magnet->comments,
    'timeAdded'       => $magnet->timeAdded   ? Time::ago((int) $magnet->timeAdded)   : false,
    'timeUpdated'     => $magnet->timeUpdated ? Time::ago((int) $magnet->timeUpdated) : false,
    'keywords'        => $keywords,
    'comment'         => (object)
    [
      'total'  => $db->getMagnetCommentsTotal($magnet->magnetId),
      'status' => $db->findMagnetCommentsTotalByUserId($magnet->magnetId, $userId),
    ],
    'download' => (object)
    [
      'total'  => $db->getMagnetDownloadsTotalByUserId($magnet->magnetId),
      'status' => $db->findMagnetDownloadsTotalByUserId($magnet->magnetId, $userId),
    ],
    'star' => (object)
    [
      'total'  => $db->getMagnetStarsTotal($magnet->magnetId),
      'status' => $db->findMagnetStarsTotalByUserId($magnet->magnetId, $userId),
    ],
    'access' => (object)
    [
      'read' => $accessRead,
      'edit' => $accessEdit,
    ],
    'seeders'   => $db->getMagnetToAddressTrackerSeedersSumByMagnetId($magnet->magnetId),
    'completed' => $db->getMagnetToAddressTrackerCompletedSumByMagnetId($magnet->magnetId),
    'leechers'  => $db->getMagnetToAddressTrackerLeechersSumByMagnetId($magnet->magnetId)
  ];
}

if (isset($_GET['rss']) && isset($_GET['target']) && $_GET['target'] == 'comment' && $response->success) { ?><?php
header('Content-type: text/xml;charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
      <atom:link href="<?php echo sprintf('%s/magnet.php?magnetId=%s#comment', WEBSITE_URL, $response->magnet->magnetId) ?>" rel="self" type="application/rss+xml"></atom:link>
      <link><?php echo sprintf('%s/magnet.php?magnetId=%s#comment', WEBSITE_URL, $response->magnet->magnetId) ?></link>
      <title><?php echo sprintf(_('%s - Comments - %s'), htmlentities($response->magnet->metaTitle), WEBSITE_NAME) ?></title>
      <description><?php echo _('BitTorrent Catalog for Yggdrasil') ?></description>
      <?php foreach ($db->getMagnetComments($response->magnet->magnetId) as $magnetComment) { ?>
        <?php if ($response->user->address == $db->getUser($magnetComment->userId)->address || in_array($response->user->address, MODERATOR_IP_LIST)) { ?>
          <item>
            <title><?php echo sprintf('%s - comment #%s', htmlspecialchars($magnet->metaTitle, ENT_QUOTES, 'UTF-8'), $magnetComment->magnetCommentId) ?></title>
            <description><?php echo htmlspecialchars($magnetComment->value, ENT_QUOTES, 'UTF-8') ?></description>
            <guid><?php echo sprintf('%s/magnet.php?magnetId=%s#comment-%s', WEBSITE_URL, $response->magnet->magnetId, $magnetComment->magnetCommentId) ?></guid>
            <link><?php echo sprintf('%s/magnet.php?magnetId=%s#comment-%s', WEBSITE_URL, $response->magnet->magnetId, $magnetComment->magnetCommentId) ?></link>
          </item>
        <?php } ?>
    <?php } ?>
  </channel>
</rss>
<?php } else { ?>
<!DOCTYPE html>
<html lang="en-US">
  <head>
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/common.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo WEBSITE_URL ?>/assets/theme/default/css/framework.css?<?php echo WEBSITE_CSS_VERSION ?>" />
    <?php if ($response->success) { ?>
      <title><?php echo sprintf(_('%s - %s'), htmlentities($response->magnet->metaTitle), WEBSITE_NAME) ?></title>
      <meta name="description" content="<?php echo htmlentities($response->magnet->metaDescription) ?>" />
      <meta name="keywords" content="<?php echo  htmlentities(implode(',',$response->magnet->keywords)) ?>" />
    <?php } else { ?>
      <title><?php echo $response->message ?></title>
    <?php } ?>
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
              <?php if ($response->magnet->access->read) { ?>
                <div class="margin-y-8
                            border-radius-3
                            background-color-night
                            <?php echo !$response->magnet->public || !$response->magnet->approved ? 'opacity-06 opacity-hover-1' : false ?>">
                  <div class="padding-16 <?php echo $response->magnet->sensitive ? 'blur-2 blur-hover-0' : false ?>">
                    <a name="magnet-<?php echo $response->magnet->magnetId ?>"></a>
                    <h1 class="margin-b-8"><?php echo $response->magnet->metaTitle ?></h1>
                    <div class="float-right opacity-0 parent-hover-opacity-09">
                    <?php if (!$response->magnet->public) { ?>
                      <span class="margin-l-8" title="<?php echo _('Private') ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash-fill" viewBox="0 0 16 16">
                          <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/>
                          <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/>
                        </svg>
                      </span>
                    <?php } ?>
                    <?php if (!$response->magnet->approved) { ?>
                      <span class="margin-l-8" title="<?php echo _('Waiting for approve') ?>">
                        <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hourglass-split" viewBox="0 0 16 16">
                          <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1h-11zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2h-7zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48V8.35zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
                        </svg>
                      </span>
                    <?php } ?>
                      <?php if ($response->magnet->access->edit) { ?>
                        <a class="text-color-green margin-l-12" href="<?php echo WEBSITE_URL ?>/edit.php?magnetId=<?php echo $response->magnet->magnetId ?>" title="<?php echo _('Edit') ?>">
                          <svg class="text-color-green" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                          </svg>
                        </a>
                      <?php } else { ?>
                        <!-- TODO
                        <a class="text-color-pink margin-l-12" rel="nofollow" href="<?php echo WEBSITE_URL ?>/action.php?target=magnet&toggle=report&magnetId=<?php echo $response->magnet->magnetId ?>&callback=<?php echo base64_encode(sprintf('%s/index.php?query=%s#magnet-%s', WEBSITE_URL, urlencode($request->query), $magnet->magnetId)) ?>" title="<?php echo _('Report') ?>">
                          <svg class="text-color-pink" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-flag" viewBox="0 0 16 16">
                            <path d="M14.778.085A.5.5 0 0 1 15 .5V8a.5.5 0 0 1-.314.464L14.5 8l.186.464-.003.001-.006.003-.023.009a12.435 12.435 0 0 1-.397.15c-.264.095-.631.223-1.047.35-.816.252-1.879.523-2.71.523-.847 0-1.548-.28-2.158-.525l-.028-.01C7.68 8.71 7.14 8.5 6.5 8.5c-.7 0-1.638.23-2.437.477A19.626 19.626 0 0 0 3 9.342V15.5a.5.5 0 0 1-1 0V.5a.5.5 0 0 1 1 0v.282c.226-.079.496-.17.79-.26C4.606.272 5.67 0 6.5 0c.84 0 1.524.277 2.121.519l.043.018C9.286.788 9.828 1 10.5 1c.7 0 1.638-.23 2.437-.477a19.587 19.587 0 0 0 1.349-.476l.019-.007.004-.002h.001M14 1.221c-.22.078-.48.167-.766.255-.81.252-1.872.523-2.734.523-.886 0-1.592-.286-2.203-.534l-.008-.003C7.662 1.21 7.139 1 6.5 1c-.669 0-1.606.229-2.415.478A21.294 21.294 0 0 0 3 1.845v6.433c.22-.078.48-.167.766-.255C4.576 7.77 5.638 7.5 6.5 7.5c.847 0 1.548.28 2.158.525l.028.01C9.32 8.29 9.86 8.5 10.5 8.5c.668 0 1.606-.229 2.415-.478A21.317 21.317 0 0 0 14 7.655V1.222z"/>
                          </svg>
                        </a>
                        -->
                      <?php } ?>
                    </div>
                    <?php if ($response->magnet->metaDescription) { ?>
                      <div class="margin-y-8"><?php echo $response->magnet->metaDescription ?></div>
                    <?php } ?>
                    <?php if ($response->magnet->description) { ?>
                      <div class="margin-t-16 margin-b-8 padding-t-16  border-top-default"><?php echo $response->magnet->description ?></div>
                    <?php } ?>
                    <?php if ($response->magnet->keywords) { ?>
                      <div class="margin-y-8">
                        <?php foreach ($response->magnet->keywords as $keyword) { ?>
                          <small>
                            <a href="<?php echo WEBSITE_URL ?>/index.php?query=<?php echo urlencode($keyword) ?>">#<?php echo htmlentities($keyword) ?></a>
                          </small>
                        <?php } ?>
                      </div>
                    <?php } ?>
                    <div class="width-100 padding-y-4"></div>
                    <!-- DOUBTS
                    <span class="margin-t-8 margin-r-8 cursor-default" title="<?php echo $response->magnet->timeUpdated ? _('Updated') : _('Added') ?>">
                      <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                      </svg>
                      <sup><?php echo $response->magnet->timeUpdated ? $response->magnet->timeUpdated : $response->magnet->timeAdded ?></sup>
                    </span>
                    -->
                    <span class="margin-t-8 margin-r-8 cursor-default">
                      <sup>
                        <?php echo $response->magnet->timeUpdated ? _('Updated') : _('Added') ?>
                        <?php echo $response->magnet->timeUpdated ? $response->magnet->timeUpdated : $response->magnet->timeAdded ?>
                      </sup>
                    </span>
                    <span class="margin-t-8 margin-r-8 cursor-default opacity-0 parent-hover-opacity-09" title="<?php echo _('Seeds') ?>">
                      <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-up" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z"/>
                      </svg>
                      <sup><?php echo $response->magnet->seeders ?></sup>
                    </span>
                    <span class="margin-t-8 margin-r-8 cursor-default opacity-0 parent-hover-opacity-09" title="<?php echo _('Peers') ?>">
                      <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/>
                      </svg>
                      <sup><?php echo $response->magnet->completed ?></sup>
                    </span>
                    <span class="margin-t-8 margin-r-8 cursor-default opacity-0 parent-hover-opacity-09" title="<?php echo _('Leechers') ?>">
                      <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cup-hot" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M.5 6a.5.5 0 0 0-.488.608l1.652 7.434A2.5 2.5 0 0 0 4.104 16h5.792a2.5 2.5 0 0 0 2.44-1.958l.131-.59a3 3 0 0 0 1.3-5.854l.221-.99A.5.5 0 0 0 13.5 6H.5ZM13 12.5a2.01 2.01 0 0 1-.316-.025l.867-3.898A2.001 2.001 0 0 1 13 12.5ZM2.64 13.825 1.123 7h11.754l-1.517 6.825A1.5 1.5 0 0 1 9.896 15H4.104a1.5 1.5 0 0 1-1.464-1.175Z"/>
                        <path d="m4.4.8-.003.004-.014.019a4.167 4.167 0 0 0-.204.31 2.327 2.327 0 0 0-.141.267c-.026.06-.034.092-.037.103v.004a.593.593 0 0 0 .091.248c.075.133.178.272.308.445l.01.012c.118.158.26.347.37.543.112.2.22.455.22.745 0 .188-.065.368-.119.494a3.31 3.31 0 0 1-.202.388 5.444 5.444 0 0 1-.253.382l-.018.025-.005.008-.002.002A.5.5 0 0 1 3.6 4.2l.003-.004.014-.019a4.149 4.149 0 0 0 .204-.31 2.06 2.06 0 0 0 .141-.267c.026-.06.034-.092.037-.103a.593.593 0 0 0-.09-.252A4.334 4.334 0 0 0 3.6 2.8l-.01-.012a5.099 5.099 0 0 1-.37-.543A1.53 1.53 0 0 1 3 1.5c0-.188.065-.368.119-.494.059-.138.134-.274.202-.388a5.446 5.446 0 0 1 .253-.382l.025-.035A.5.5 0 0 1 4.4.8Zm3 0-.003.004-.014.019a4.167 4.167 0 0 0-.204.31 2.327 2.327 0 0 0-.141.267c-.026.06-.034.092-.037.103v.004a.593.593 0 0 0 .091.248c.075.133.178.272.308.445l.01.012c.118.158.26.347.37.543.112.2.22.455.22.745 0 .188-.065.368-.119.494a3.31 3.31 0 0 1-.202.388 5.444 5.444 0 0 1-.253.382l-.018.025-.005.008-.002.002A.5.5 0 0 1 6.6 4.2l.003-.004.014-.019a4.149 4.149 0 0 0 .204-.31 2.06 2.06 0 0 0 .141-.267c.026-.06.034-.092.037-.103a.593.593 0 0 0-.09-.252A4.334 4.334 0 0 0 6.6 2.8l-.01-.012a5.099 5.099 0 0 1-.37-.543A1.53 1.53 0 0 1 6 1.5c0-.188.065-.368.119-.494.059-.138.134-.274.202-.388a5.446 5.446 0 0 1 .253-.382l.025-.035A.5.5 0 0 1 7.4.8Zm3 0-.003.004-.014.019a4.077 4.077 0 0 0-.204.31 2.337 2.337 0 0 0-.141.267c-.026.06-.034.092-.037.103v.004a.593.593 0 0 0 .091.248c.075.133.178.272.308.445l.01.012c.118.158.26.347.37.543.112.2.22.455.22.745 0 .188-.065.368-.119.494a3.198 3.198 0 0 1-.202.388 5.385 5.385 0 0 1-.252.382l-.019.025-.005.008-.002.002A.5.5 0 0 1 9.6 4.2l.003-.004.014-.019a4.149 4.149 0 0 0 .204-.31 2.06 2.06 0 0 0 .141-.267c.026-.06.034-.092.037-.103a.593.593 0 0 0-.09-.252A4.334 4.334 0 0 0 9.6 2.8l-.01-.012a5.099 5.099 0 0 1-.37-.543A1.53 1.53 0 0 1 9 1.5c0-.188.065-.368.119-.494.059-.138.134-.274.202-.388a5.446 5.446 0 0 1 .253-.382l.025-.035A.5.5 0 0 1 10.4.8Z"/>
                      </svg>
                      <sup><?php echo $response->magnet->leechers ?></sup>
                    </span>
                    <span class="float-right margin-l-12">
                      <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/action.php?target=magnet&toggle=star&magnetId=<?php echo $response->magnet->magnetId ?>&callback=<?php echo base64_encode(sprintf('%s/magnet.php?magnetId=%s', WEBSITE_URL, $response->magnet->magnetId)) ?>" title="<?php echo _('Star') ?>">
                        <?php if ($response->magnet->star->status) { ?>
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
                            <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                          </svg>
                        <?php } else { ?>
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star" viewBox="0 0 16 16">
                            <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                          </svg>
                        <?php } ?>
                      </a>
                      <sup><?php echo $response->magnet->star->total ?></sup>
                    </span>
                    <span class="float-right margin-l-12">
                      <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/magnet.php?magnetId=<?php echo $response->magnet->magnetId ?>#comment" title="<?php echo _('Comment') ?>">
                        <?php if ($response->magnet->comment->status) { ?>
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-fill" viewBox="0 0 16 16">
                            <path d="M8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6-.097 1.016-.417 2.13-.771 2.966-.079.186.074.394.273.362 2.256-.37 3.597-.938 4.18-1.234A9.06 9.06 0 0 0 8 15z"/>
                          </svg>
                        <?php } else { ?>
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
                            <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/>
                          </svg>
                        <?php } ?>
                      </a>
                      <sup><?php echo $response->magnet->comment->total ?></sup>
                    </span>
                    <span class="float-right margin-l-12">
                      <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/action.php?target=magnet&toggle=download&magnetId=<?php echo $response->magnet->magnetId ?>" title="<?php echo _('Download') ?>">
                        <?php if ($response->magnet->download->status) { ?>
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v5.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V4.5z"/>
                          </svg>
                        <?php } else { ?>
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down-circle" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v5.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V4.5z"/>
                          </svg>
                        <?php } ?>
                      </a>
                      <sup><?php echo $response->magnet->download->total ?></sup>
                    </span>
                  </div>
                </div>
                <?php if ($similarMagnetsTotal = $sphinx->searchMagnetsTotal($magnet->metaTitle ? $magnet->metaTitle : $magnet->dn, 'similar', MAGNET_STOP_WORDS_SIMILAR)) { ?>
                  <?php if ($similarMagnetsTotal > 1) { // skip current magnet ?>
                    <div class="padding-y-8 padding-x-16">
                      <a name="similar"></a>
                      <h3><?php echo _('Similar') ?></h3>
                    </div>
                    <div class="padding-x-16 margin-b-8">
                      <div class="padding-16 margin-t-8 border-radius-3 background-color-night">
                        <?php foreach ( $sphinx->searchMagnets(
                                        $magnet->metaTitle ? $magnet->metaTitle : $magnet->dn,
                                        0,
                                        10,
                                        $similarMagnetsTotal,
                                        'similar',
                                        MAGNET_STOP_WORDS_SIMILAR
                                      ) as $result) { ?>
                          <?php if ($magnet = $db->getMagnet($result->magnetid)) { ?>
                            <?php if ($result->magnetid != $response->magnet->magnetId && // skip current magnet
                                      ($response->user->address == $db->getUser($magnet->userId)->address ||
                                       in_array($response->user->address, MODERATOR_IP_LIST) || ($magnet->approved && $magnet->public))) { ?>
                              <div class="margin-y-8">
                                <a href="<?php echo sprintf('%s/magnet.php?magnetId=%s', WEBSITE_URL, $magnet->magnetId) ?>" class="margin-b-16">
                                  <?php echo nl2br(htmlentities($magnet->metaTitle ? $magnet->metaTitle : $magnet->dn)) ?>
                                </a>
                              </div>
                            <?php } ?>
                          <?php } ?>
                        <?php } ?>
                      </div>
                    </div>
                  <?php } ?>
                <?php } ?>
                <?php if ($response->magnet->comments) { ?>
                  <div class="padding-y-8 padding-x-16">
                    <a name="comment"></a>
                    <h3><?php echo _('Comments') ?></h3>
                    <sup><small><a href="<?php echo sprintf('%s/magnet.php?rss&magnetId=%s&target=comment', WEBSITE_URL, $response->magnet->magnetId) ?>"><?php echo _('RSS') ?></a></small></sup>
                  </div>
                  <div class="padding-x-16">
                    <?php foreach ($db->getMagnetComments($response->magnet->magnetId) as $magnetComment) { ?>
                      <div class="padding-x-16 padding-t-16 padding-b-8 margin-t-8 border-radius-3 background-color-night <?php echo !$magnetComment->approved || !$magnetComment->public ? 'opacity-06 opacity-hover-1' : false ?>">
                        <a name="comment-<?php echo $magnetComment->magnetCommentId ?>"></a>
                        <?php if ($response->user->address == $db->getUser($magnetComment->userId)->address ||
                                  in_array($response->user->address, MODERATOR_IP_LIST) ||
                                  ($magnetComment->approved && $magnetComment->public)) { ?>
                          <div class="margin-b-16">
                            <?php echo nl2br(htmlentities($magnetComment->value)) ?>
                          </div>
                          <?php if (USER_DEFAULT_IDENTICON) { ?>
                            <img class="float-left margin-r-4"
                                alt=""
                                src="<?php echo sprintf('%s/action.php?target=profile&toggle=%s&userId=%s&size=16',
                                                        WEBSITE_URL,
                                                        USER_DEFAULT_IDENTICON,
                                                        $magnetComment->userId) ?>" />
                          <?php } ?>
                          <sup>
                            <?php echo Time::ago((int) $magnetComment->timeAdded) ?>
                          </sup>
                          <span class="opacity-0 parent-hover-opacity-09">
                            <?php if (!$magnetComment->public) { ?>
                              <span class="margin-l-8" title="<?php echo _('Private') ?>">
                                <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash-fill" viewBox="0 0 16 16">
                                  <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/>
                                  <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/>
                                </svg>
                              </span>
                            <?php } ?>
                            <?php if (!$magnetComment->approved) { ?>
                              <span class="margin-l-8" title="<?php echo _('Waiting for approve') ?>">
                                <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hourglass-split" viewBox="0 0 16 16">
                                  <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1h-11zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2h-7zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48V8.35zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
                                </svg>
                              </span>
                            <?php } ?>
                            <small>
                              <a class="float-right margin-l-12"
                                 href="<?php echo WEBSITE_URL ?>/action.php?target=comment&toggle=public&magnetCommentId=<?php echo $magnetComment->magnetCommentId ?>&callback=<?php echo base64_encode(sprintf('%s/magnet.php?magnetId=%s#comment-%s', WEBSITE_URL, $magnetComment->magnetId, $magnetComment->magnetCommentId)) ?>">
                                <?php if ($magnetComment->public) { ?>
                                  <?php echo _('hide') ?>
                                <?php } else { ?>
                                  <?php echo _('show') ?>
                                <?php } ?>
                              </a>
                              <?php if (in_array($response->user->address, MODERATOR_IP_LIST)) { ?>
                                <a class="float-right margin-l-12"
                                  href="<?php echo WEBSITE_URL ?>/action.php?target=comment&toggle=approved&magnetCommentId=<?php echo $magnetComment->magnetCommentId ?>&callback=<?php echo base64_encode(sprintf('%s/magnet.php?magnetId=%s#comment-%s', WEBSITE_URL, $magnetComment->magnetId, $magnetComment->magnetCommentId)) ?>">
                                  <?php if ($magnetComment->approved) { ?>
                                    <?php echo _('disapprove') ?>
                                  <?php } else { ?>
                                    <?php echo _('approve') ?>
                                  <?php } ?>
                                </a>
                              <?php } ?>
                            </small>
                          </span>
                        <?php } else { ?>
                          <div class="margin-b-8"><?php echo _('hidden content') ?></div>
                        <?php } ?>
                      </div>
                    <?php } ?>
                    <form name="comment"
                          method="post"
                          action="<?php echo sprintf('%s/action.php?target=comment&toggle=new&magnetId=%s&callback=%s',
                                                      WEBSITE_URL,
                                                      $response->magnet->magnetId,
                                                      base64_encode(sprintf('%s/magnet.php?magnetId=%s',
                                                                            WEBSITE_URL,
                                                                            $response->magnet->magnetId))) ?>">
                      <div class="padding-y-8">
                        <textarea class="width-100 padding-16"
                                  name="comment"
                                  value=""
                                  placeholder="<?php echo _('Enter your comment') ?>"
                                  minlength="<?php echo COMMENT_MIN_LENGTH ?>"
                                  maxlength="<?php echo COMMENT_MAX_LENGTH ?>"></textarea>
                      </div>
                      <div class="padding-b-8 text-right">
                        <input type="submit" value="<?php echo _('send') ?>" />
                      </div>
                    </form>
                  </div>
                <?php } ?>
              <?php } else { ?>
                <div class="padding-16 margin-y-8 border-radius-3 background-color-night">
                  <div><?php echo _('Magnet not public') ?></div>
                </div>
              <?php } ?>
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
<?php } ?>