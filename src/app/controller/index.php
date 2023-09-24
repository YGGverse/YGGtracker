<?php

class AppControllerIndex
{
  private $_db;
  private $_sphinx;
  private $_memory;

  public function __construct()
  {
    require_once __DIR__ . '/../../library/database.php';
    require_once __DIR__ . '/../../library/sphinx.php';
    require_once __DIR__ . '/../../library/scrapeer.php';
    require_once __DIR__ . '/../../library/time.php';
    require_once __DIR__ . '/../../library/curl.php';
    require_once __DIR__ . '/../../library/valid.php';
    require_once __DIR__ . '/../../library/filter.php';

    require_once __DIR__ . '/../../../vendor/autoload.php';

    try
    {
      $this->_db = new Database(
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_USERNAME,
        DB_PASSWORD
      );

      $this->_sphinx = new Sphinx(
        SPHINX_HOST,
        SPHINX_PORT
      );

      $this->_memory = new \Yggverse\Cache\Memory(
        MEMCACHED_HOST,
        MEMCACHED_PORT,
        MEMCACHED_NAMESPACE,
        MEMCACHED_TIMEOUT + time()
      );
    }

    catch (Exception $error)
    {
      require_once __DIR__ . '/error/500.php';

      $controller = new AppControllerError500(
        print_r($error, true)
      );

      $controller->render();

      exit;
    }
  }

  public function render()
  {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

    $pages = [];

    require_once __DIR__ . '/module/pagination.php';

    $appControllerModulePagination = new appControllerModulePagination();

    require_once __DIR__ . '/module/head.php';

    $appControllerModuleHead = new AppControllerModuleHead(
      WEBSITE_URL,
      $page > 1 ?
      sprintf(
        _('Page %s - BitTorrent Registry for Yggdrasil - %s'),
        $page,
        WEBSITE_NAME
      ) :
      sprintf(
        _('%s - BitTorrent Registry for Yggdrasil'),
        WEBSITE_NAME
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

    $appControllerModuleProfile = new AppControllerModuleProfile($user->userId);

    require_once __DIR__ . '/module/header.php';

    $appControllerModuleHeader = new AppControllerModuleHeader();

    require_once __DIR__ . '/module/footer.php';

    $appControllerModuleFooter = new AppControllerModuleFooter();

    include __DIR__ . '/../view/theme/default/index.phtml';
  }
}