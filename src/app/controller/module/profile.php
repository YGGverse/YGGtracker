<?php

class AppControllerModuleProfile
{
  private $_user;

  public function __construct(AppControllerUser $user)
  {
    $this->_user = $user;
  }

  public function render()
  {
    $route = isset($_GET['_route_']) ? (string) $_GET['_route_'] : '';

    $stars     = $this->_user->findUserPageStarsDistinctTotalByValue(true);
    $views     = $this->_user->findUserPageViewsDistinctTotal();
    $downloads = $this->_user->findUserPageDownloadsDistinctTotal();
    $comments  = $this->_user->findUserPageCommentsDistinctTotal();
    $editions  = $this->_user->findUserPageEditionsDistinctTotal();

    $identicon = $this->_user->getIdenticon(24);

    $public    = $this->_user->getPublic();
    $address   = $this->_user->getAddress();

    include __DIR__ . '../../../view/theme/default/module/profile.phtml';
  }
}