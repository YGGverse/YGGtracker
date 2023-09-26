<?php

class AppControllerModuleProfile
{
  private $_database;
  private $_website;
  private $_session;

  public function __construct(
    AppModelDatabase $database,
    AppModelWebsite  $website,
    AppModelSession  $session)
  {
    $this->_database = $database;
    $this->_website = $website;
    $this->_session  = $session;
  }

  public function render()
  {
    $route = isset($_GET['_route_']) ? (string) $_GET['_route_'] : '';

    $user = $this->_database->getUser(
      $this->_database->initUserId(
        $this->_session->getAddress(),
        $this->_website->getDefaultUserStatus(),
        $this->_website->getDefaultUserApproved(),
        time()
      )
    );

    $stars     = $this->_database->findUserPageStarsDistinctTotalByValue($user->userId, true);
    $views     = $this->_database->findUserPageViewsDistinctTotal($user->userId);
    $downloads = 0; // @TODO $this->_database->findUserPageDownloadsDistinctTotal($user->userId);
    $comments  = $this->_database->findUserPageCommentsDistinctTotal($user->userId);
    $editions  = 0; // @TODO $this->_database->findUserPageEditionsDistinctTotal($user->userId);

    $identicon = false; // @TODO $this->_database->getIdenticon(24);

    $public    = $user->public;
    $address   = $user->address;

    include __DIR__ . '../../../view/theme/default/module/profile.phtml';
  }
}