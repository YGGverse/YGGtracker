<?php

class AppControllerIndex
{
  private $_user;

  public function __construct()
  {
    require_once __DIR__ . '/user.php';

    $this->_user = new AppControllerUser(
      $_SERVER['REMOTE_ADDR']
    );
  }

  public function render()
  {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

    $pages = [];

    require_once __DIR__ . '/module/pagination.php';

    $appControllerModulePagination = new appControllerModulePagination();

    require_once __DIR__ . '/module/head.php';

    $appControllerModuleHead = new AppControllerModuleHead(
      Environment::config('website')->url,
      $page > 1 ?
      sprintf(
        _('Page %s - BitTorrent Registry for Yggdrasil - %s'),
        $page,
        Environment::config('website')->name
      ) :
      sprintf(
        _('%s - BitTorrent Registry for Yggdrasil'),
        Environment::config('website')->name
      ),
      [
        [
          'rel'  => 'stylesheet',
          'type' => 'text/css',
          'href' => sprintf(
            'assets/theme/default/css/common.css?%s',
            CSS_VERSION
          ),
        ],
        [
          'rel'  => 'stylesheet',
          'type' => 'text/css',
          'href' => sprintf(
            'assets/theme/default/css/framework.css?%s',
            CSS_VERSION
          ),
        ],
      ]
    );

    require_once __DIR__ . '/module/profile.php';

    $appControllerModuleProfile = new AppControllerModuleProfile(
      $this->_user
    );

    require_once __DIR__ . '/module/header.php';

    $appControllerModuleHeader = new AppControllerModuleHeader();

    require_once __DIR__ . '/module/footer.php';

    $appControllerModuleFooter = new AppControllerModuleFooter();

    include __DIR__ . '/../view/theme/default/index.phtml';
  }
}