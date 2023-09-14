<?php

// Bootstrap dependencies
require_once __DIR__ . '/../config/bootstrap.php';

// Define variables
$request = (object)
[
  'query' => false,
  'page'  => 1,
];

// Prepare request
$request->query = isset($_GET['query']) ? urldecode((string) $_GET['query']) : '';
$request->page  = isset($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;

// Define response
$response = (object)
[
  'success' => true,
  'message' => false,
  'magnets' => [],
];

// Yggdrasil connections only
if (!preg_match(YGGDRASIL_HOST_REGEX, $_SERVER['REMOTE_ADDR']))
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

// On first visit, redirect user to the welcome page with access level question
else if (is_null($user->public) && !isset($_GET['rss']))
{
  header(
    sprintf('Location: %s/welcome.php', WEBSITE_URL)
  );
}

// Request valid
else
{
  // Query is magnet link
  if ($magnet = Yggverse\Parser\Magnet::is($request->query))
  {
    header(
      sprintf('Location: %s/action.php?target=magnet&toggle=new&magnet=%s', WEBSITE_URL, base64_encode($request->query))
    );
  }

  // Get index
  $response->total = $sphinx->searchMagnetsTotal($request->query);
  $results = $sphinx->searchMagnets(
    $request->query,
    $request->page * WEBSITE_PAGINATION_LIMIT - WEBSITE_PAGINATION_LIMIT,
    WEBSITE_PAGINATION_LIMIT,
    $response->total
  );

  foreach ($results as $result)
  {
    if ($magnet = $db->getMagnet($result->magnetid))
    {
      // Get access info
      $accessRead = ($user->address == $db->getUser($magnet->userId)->address || in_array($user->address, MODERATOR_IP_LIST) || ($magnet->public && $magnet->approved));
      $accessEdit = ($user->address == $db->getUser($magnet->userId)->address || in_array($user->address, MODERATOR_IP_LIST));

      // Keywords
      $keywords = [];

      foreach ($db->findKeywordTopicByMagnetId($magnet->magnetId) as $keyword)
      {
        $keywords[] = $db->getKeywordTopic($keyword->keywordTopicId)->value;
      }

      $response->magnets[] = (object)
      [
        'magnetId'        => $magnet->magnetId,
        'title'           => $magnet->title ? htmlentities($magnet->title) : ($magnet->dn ? htmlentities($magnet->dn): false),
        'preview'         => $magnet->preview ? nl2br(
                                                        htmlentities(
                                                          $magnet->preview
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
          'total'  => $db->findMagnetCommentsTotalByMagnetId($magnet->magnetId),
          'status' => $db->findMagnetCommentsTotal($magnet->magnetId, $userId),
        ],
        'download'        => (object)
        [
          'total'  => $db->findMagnetDownloadsTotalByMagnetId($magnet->magnetId),
          'status' => $db->findMagnetDownloadsTotal($magnet->magnetId, $userId),
        ],
        'star'            => (object)
        [
          'total'  => $db->findMagnetStarsTotalByMagnetId($magnet->magnetId, true),
          'status' => $db->findLastMagnetStarValue($magnet->magnetId, $userId),
        ],
        'access'          => (object)
        [
          'read' => $accessRead,
          'edit' => $accessEdit,
        ],
        'seeders'   => $db->getMagnetToAddressTrackerSeedersSumByMagnetId($magnet->magnetId),
        'completed' => $db->getMagnetToAddressTrackerCompletedSumByMagnetId($magnet->magnetId),
        'leechers'  => $db->getMagnetToAddressTrackerLeechersSumByMagnetId($magnet->magnetId),
        'directs'   => $db->getMagnetToAcceptableSourceTotalByMagnetId($magnet->magnetId)
      ];
    }
  }
}

if (isset($_GET['rss']) && $response->success) { ?><?php
header('Content-type: text/xml;charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
      <atom:link href="<?php echo WEBSITE_URL ?>/index.php<?php echo $request->query ? sprintf('?query=%s', urlencode($request->query)) : false ?>" rel="self" type="application/rss+xml"></atom:link>
      <title><?php echo WEBSITE_NAME ?></title>
      <description><?php echo _('BitTorrent Registry for Yggdrasil') ?></description>
      <link><?php echo sprintf('%s/index.php%s', WEBSITE_URL, $request->query ? sprintf('?query=%s', urlencode($request->query)) : false) ?></link>
      <?php foreach ($response->magnets as $magnet) { ?>
        <?php if ($magnet->access->read) { ?>
          <item>
            <title><?php echo htmlspecialchars($magnet->title, ENT_QUOTES, 'UTF-8') ?></title>
            <description><?php echo htmlspecialchars(strip_tags($magnet->preview), ENT_QUOTES, 'UTF-8') ?></description>
            <guid><?php echo sprintf('%s/magnet.php?magnetId=%s', WEBSITE_URL, $magnet->magnetId) ?></guid>
            <link><?php echo sprintf('%s/magnet.php?magnetId=%s', WEBSITE_URL, $magnet->magnetId) ?></link>
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
    <title>
      <?php echo sprintf(_('%s - BitTorrent Registry for Yggdrasil'), WEBSITE_NAME) ?>
    </title>
    <meta name="description" content="<?php echo _('BitTorrent Registry for Yggdrasil') ?>" />
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
            <input type="text" name="query" value="<?php echo htmlentities($request->query) ?>" placeholder="<?php echo _('search or submit magnet link') ?>" />
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
              <?php if ($response->magnets) { ?>
                <?php foreach ($response->magnets as $magnet) { ?>
                  <?php if ($magnet->access->read) { ?>
                    <a name="magnet-<?php echo $magnet->magnetId ?>"></a>
                    <div class="margin-y-8
                                border-radius-3
                                background-color-night
                                <?php echo !$magnet->public || !$magnet->approved ? 'opacity-06 opacity-hover-1' : false ?>">
                      <div class="padding-16 <?php echo $magnet->sensitive ? 'blur-2 blur-hover-0' : false ?>">
                        <a href="<?php echo sprintf('%s/magnet.php?magnetId=%s', WEBSITE_URL, $magnet->magnetId) ?>">
                          <h2 class="margin-b-8"><?php echo $magnet->title ?></h2>
                          <?php if ($magnet->leechers && !$magnet->seeders) { ?>
                            <span class="label label-green margin-x-4 font-size-10 position-relative top--2 cursor-default"
                                  title="<?php echo _('Active leechers waiting for seeds') ?>">
                              <?php echo _('wanted') ?>
                            </span>
                          <?php } ?>
                        </a>
                        <div class="float-right opacity-0 parent-hover-opacity-09">
                        <?php if (!$magnet->public) { ?>
                          <span class="margin-l-8" title="<?php echo _('Private') ?>">
                            <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash-fill" viewBox="0 0 16 16">
                              <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/>
                              <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/>
                            </svg>
                          </span>
                        <?php } ?>
                        <?php if (!$magnet->approved) { ?>
                          <span class="margin-l-8" title="<?php echo _('Waiting for approve') ?>">
                            <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hourglass-split" viewBox="0 0 16 16">
                              <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1h-11zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2h-7zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48V8.35zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
                            </svg>
                          </span>
                        <?php } ?>
                          <?php if ($magnet->access->edit) { ?>
                            <a class="text-color-green margin-l-12" href="<?php echo WEBSITE_URL ?>/edit.php?magnetId=<?php echo $magnet->magnetId ?>" title="<?php echo _('Edit') ?>">
                              <svg class="text-color-green" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                              </svg>
                            </a>
                          <?php } ?>
                        </div>
                        <?php if ($magnet->preview) { ?>
                          <div class="margin-y-8"><?php echo $magnet->preview ?></div>
                        <?php } ?>
                        <?php if ($magnet->keywords) { ?>
                          <div class="margin-y-8">
                            <?php foreach ($magnet->keywords as $keyword) { ?>
                              <small>
                                <a href="<?php echo WEBSITE_URL ?>/index.php?query=<?php echo urlencode($keyword) ?>">#<?php echo htmlentities($keyword) ?></a>
                              </small>
                            <?php } ?>
                          </div>
                        <?php } ?>
                        <div class="width-100 padding-y-4"></div>
                        <!-- DOUBTS
                        <span class="margin-t-8 margin-r-8 cursor-default" title="<?php echo $magnet->timeUpdated ? _('Updated') : _('Added') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                          </svg>
                          <sup><?php echo $magnet->timeUpdated ? $magnet->timeUpdated : $magnet->timeAdded ?></sup>
                        </span>
                        -->
                        <span class="margin-t-8 margin-r-8 cursor-default">
                          <sup>
                            <?php echo $magnet->timeUpdated ? _('Updated') : _('Added') ?>
                            <?php echo $magnet->timeUpdated ? $magnet->timeUpdated : $magnet->timeAdded ?>
                          </sup>
                        </span>
                        <span class="margin-t-8 margin-r-8 cursor-default opacity-0 parent-hover-opacity-09" title="<?php echo _('Seeds') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-up" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z"/>
                          </svg>
                          <sup><?php echo $magnet->seeders ?></sup>
                        </span>
                        <span class="margin-t-8 margin-r-8 cursor-default opacity-0 parent-hover-opacity-09" title="<?php echo _('Peers') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/>
                          </svg>
                          <sup><?php echo $magnet->completed ?></sup>
                        </span>
                        <span class="margin-t-8 margin-r-8 cursor-default opacity-0 parent-hover-opacity-09" title="<?php echo _('Leechers') ?>">
                          <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cup-hot" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M.5 6a.5.5 0 0 0-.488.608l1.652 7.434A2.5 2.5 0 0 0 4.104 16h5.792a2.5 2.5 0 0 0 2.44-1.958l.131-.59a3 3 0 0 0 1.3-5.854l.221-.99A.5.5 0 0 0 13.5 6H.5ZM13 12.5a2.01 2.01 0 0 1-.316-.025l.867-3.898A2.001 2.001 0 0 1 13 12.5ZM2.64 13.825 1.123 7h11.754l-1.517 6.825A1.5 1.5 0 0 1 9.896 15H4.104a1.5 1.5 0 0 1-1.464-1.175Z"/>
                            <path d="m4.4.8-.003.004-.014.019a4.167 4.167 0 0 0-.204.31 2.327 2.327 0 0 0-.141.267c-.026.06-.034.092-.037.103v.004a.593.593 0 0 0 .091.248c.075.133.178.272.308.445l.01.012c.118.158.26.347.37.543.112.2.22.455.22.745 0 .188-.065.368-.119.494a3.31 3.31 0 0 1-.202.388 5.444 5.444 0 0 1-.253.382l-.018.025-.005.008-.002.002A.5.5 0 0 1 3.6 4.2l.003-.004.014-.019a4.149 4.149 0 0 0 .204-.31 2.06 2.06 0 0 0 .141-.267c.026-.06.034-.092.037-.103a.593.593 0 0 0-.09-.252A4.334 4.334 0 0 0 3.6 2.8l-.01-.012a5.099 5.099 0 0 1-.37-.543A1.53 1.53 0 0 1 3 1.5c0-.188.065-.368.119-.494.059-.138.134-.274.202-.388a5.446 5.446 0 0 1 .253-.382l.025-.035A.5.5 0 0 1 4.4.8Zm3 0-.003.004-.014.019a4.167 4.167 0 0 0-.204.31 2.327 2.327 0 0 0-.141.267c-.026.06-.034.092-.037.103v.004a.593.593 0 0 0 .091.248c.075.133.178.272.308.445l.01.012c.118.158.26.347.37.543.112.2.22.455.22.745 0 .188-.065.368-.119.494a3.31 3.31 0 0 1-.202.388 5.444 5.444 0 0 1-.253.382l-.018.025-.005.008-.002.002A.5.5 0 0 1 6.6 4.2l.003-.004.014-.019a4.149 4.149 0 0 0 .204-.31 2.06 2.06 0 0 0 .141-.267c.026-.06.034-.092.037-.103a.593.593 0 0 0-.09-.252A4.334 4.334 0 0 0 6.6 2.8l-.01-.012a5.099 5.099 0 0 1-.37-.543A1.53 1.53 0 0 1 6 1.5c0-.188.065-.368.119-.494.059-.138.134-.274.202-.388a5.446 5.446 0 0 1 .253-.382l.025-.035A.5.5 0 0 1 7.4.8Zm3 0-.003.004-.014.019a4.077 4.077 0 0 0-.204.31 2.337 2.337 0 0 0-.141.267c-.026.06-.034.092-.037.103v.004a.593.593 0 0 0 .091.248c.075.133.178.272.308.445l.01.012c.118.158.26.347.37.543.112.2.22.455.22.745 0 .188-.065.368-.119.494a3.198 3.198 0 0 1-.202.388 5.385 5.385 0 0 1-.252.382l-.019.025-.005.008-.002.002A.5.5 0 0 1 9.6 4.2l.003-.004.014-.019a4.149 4.149 0 0 0 .204-.31 2.06 2.06 0 0 0 .141-.267c.026-.06.034-.092.037-.103a.593.593 0 0 0-.09-.252A4.334 4.334 0 0 0 9.6 2.8l-.01-.012a5.099 5.099 0 0 1-.37-.543A1.53 1.53 0 0 1 9 1.5c0-.188.065-.368.119-.494.059-.138.134-.274.202-.388a5.446 5.446 0 0 1 .253-.382l.025-.035A.5.5 0 0 1 10.4.8Z"/>
                          </svg>
                          <sup><?php echo $magnet->leechers ?></sup>
                        </span>
                        <?php if ($magnet->directs) { ?>
                          <span class="margin-t-8 margin-r-8 cursor-default opacity-0 parent-hover-opacity-09" title="<?php echo _('Direct') ?>">
                            <svg class="width-13px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-database" viewBox="0 0 16 16">
                              <path d="M4.318 2.687C5.234 2.271 6.536 2 8 2s2.766.27 3.682.687C12.644 3.125 13 3.627 13 4c0 .374-.356.875-1.318 1.313C10.766 5.729 9.464 6 8 6s-2.766-.27-3.682-.687C3.356 4.875 3 4.373 3 4c0-.374.356-.875 1.318-1.313ZM13 5.698V7c0 .374-.356.875-1.318 1.313C10.766 8.729 9.464 9 8 9s-2.766-.27-3.682-.687C3.356 7.875 3 7.373 3 7V5.698c.271.202.58.378.904.525C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777A4.92 4.92 0 0 0 13 5.698ZM14 4c0-1.007-.875-1.755-1.904-2.223C11.022 1.289 9.573 1 8 1s-3.022.289-4.096.777C2.875 2.245 2 2.993 2 4v9c0 1.007.875 1.755 1.904 2.223C4.978 15.71 6.427 16 8 16s3.022-.289 4.096-.777C13.125 14.755 14 14.007 14 13V4Zm-1 4.698V10c0 .374-.356.875-1.318 1.313C10.766 11.729 9.464 12 8 12s-2.766-.27-3.682-.687C3.356 10.875 3 10.373 3 10V8.698c.271.202.58.378.904.525C4.978 9.71 6.427 10 8 10s3.022-.289 4.096-.777A4.92 4.92 0 0 0 13 8.698Zm0 3V13c0 .374-.356.875-1.318 1.313C10.766 14.729 9.464 15 8 15s-2.766-.27-3.682-.687C3.356 13.875 3 13.373 3 13v-1.302c.271.202.58.378.904.525C4.978 12.71 6.427 13 8 13s3.022-.289 4.096-.777c.324-.147.633-.323.904-.525Z"/>
                            </svg>
                            <sup><?php echo $magnet->directs ?></sup>
                          </span>
                        <?php } ?>
                        <span class="float-right margin-l-12">
                        <a rel="nofollow" href="<?php echo sprintf('%s/action.php?target=magnet&toggle=star&magnetId=%s&callback=%s',
                                                                      WEBSITE_URL,
                                                                      $magnet->magnetId,
                                                                      base64_encode(sprintf('%s/index.php?%s#magnet-%s',
                                                                                             WEBSITE_URL,
                                                                                            ($request->query ? sprintf('&query=%s', urlencode($request->query)) : false).
                                                                                            ($request->page ? sprintf('&page=%s', urlencode($request->page)) : false),
                                                                                             $magnet->magnetId))) ?>" title="<?php echo _('Star') ?>">
                            <?php if ($magnet->star->status) { ?>
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
                                <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                              </svg>
                            <?php } else { ?>
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star" viewBox="0 0 16 16">
                                <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                              </svg>
                            <?php } ?>
                          </a>
                          <sup><?php echo $magnet->star->total ?></sup>
                        </span>
                        <span class="float-right margin-l-12">
                          <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/magnet.php?magnetId=<?php echo $magnet->magnetId ?>#comment" title="<?php echo _('Comment') ?>">
                            <?php if ($magnet->comment->status) { ?>
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-fill" viewBox="0 0 16 16">
                                <path d="M8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6-.097 1.016-.417 2.13-.771 2.966-.079.186.074.394.273.362 2.256-.37 3.597-.938 4.18-1.234A9.06 9.06 0 0 0 8 15z"/>
                              </svg>
                            <?php } else { ?>
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
                                <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/>
                              </svg>
                            <?php } ?>
                          </a>
                          <sup><?php echo $magnet->comment->total ?></sup>
                        </span>
                        <span class="float-right margin-l-12">
                          <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/download.php?magnetId=<?php echo $magnet->magnetId ?>" title="<?php echo _('Download') ?>">
                            <?php if ($magnet->download->status) { ?>
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down-circle-fill" viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v5.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V4.5z"/>
                              </svg>
                            <?php } else { ?>
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down-circle" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v5.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V4.5z"/>
                              </svg>
                            <?php } ?>
                          </a>
                          <sup><?php echo $magnet->download->total ?></sup>
                        </span>
                      </div>
                    </div>
                  <?php } else { ?>
                    <!-- DOUBTS
                    <div class="padding-16 margin-y-8 border-radius-3 background-color-night">
                      <div><?php echo _('Hidden content') ?></div>
                    </div>
                    -->
                  <?php } ?>
                <?php } ?>
              <?php } else { ?>
                <div class="padding-16 margin-y-8 border-radius-3 background-color-night text-center">
                  <h2 class="margin-b-8">
                    <?php echo _('Nothing found') ?>
                  </h2>
                  <div class="text-color-night"><?php echo _('* share your magnet links above to change it') ?></div>
                </div>
              <?php } ?>
            <?php } else { ?>
              <div class="padding-16 margin-y-8 border-radius-3 background-color-night">
                <div class="text-center"><?php echo $response->message ?></div>
              </div>
            <?php } ?>
          </div>
        </div>
        <?php if ($response->total > WEBSITE_PAGINATION_LIMIT) { ?>
          <div class="row">
            <div class="column width-100 text-right">
              <?php echo sprintf(_('page %s / %s'), $request->page, ceil($response->total / WEBSITE_PAGINATION_LIMIT)) ?>
              <?php if ($request->page > 1) { ?>
                <a class="button margin-l-8"
                   rel="nofollow"
                   href="<?php echo sprintf('%s/index.php?page=%s', WEBSITE_URL,
                                                                   $request->page - 1,
                                                                   $request->query ? sprintf('&query=%s', urlencode($request->query)) : false) ?>">
                  <?php echo _('back') ?>
                </a>
              <?php } ?>
              <?php if ($request->page < ceil($response->total / WEBSITE_PAGINATION_LIMIT)) { ?>
                <a class="button margin-l-4"
                   rel="nofollow"
                   href="<?php echo sprintf('%s/index.php?page=%s', WEBSITE_URL,
                                                                   $request->page + 1,
                                                                   $request->query ? sprintf('&query=%s', urlencode($request->query)) : false) ?>">
                  <?php echo _('next') ?>
                </a>
              <?php } ?>
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
            <a rel="nofollow" href="<?php echo WEBSITE_URL ?>/index.php?rss<?php echo $request->query ? sprintf('&query=%s', urlencode($request->query)) : false ?>"><?php echo _('RSS') ?></a>
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
<?php } ?>