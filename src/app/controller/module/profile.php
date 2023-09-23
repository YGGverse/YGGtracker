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

    $stars     = $this->_user->getUserPageStarsTotal();
    $views     = $this->_user->getUserPageViewsTotal();
    $downloads = $this->_user->getUserPageDownloadsTotal();
    $comments  = $this->_user->getUserPageCommentsTotal();

    include __DIR__ . '../../../view/theme/default/module/profile.phtml';
  }
}