<?php

class AppControllerIndex
{
  private $_database;
  private $_validator;
  private $_website;
  private $_session;

  public function __construct(
    AppModelDatabase  $database,
    AppModelValidator $validator,
    AppModelWebsite   $website,
    AppModelSession   $session
  )
  {
    $this->_database  = $database;
    $this->_validator = $validator;
    $this->_website   = $website;
    $this->_session   = $session;
  }

  private function _initUser(string $address)
  {
    $error = [];
    if (!$this->_validator->host($address, $error))
    {
      $this->_response(
        sprintf(
          _('Error - %s'),
          $this->_website->getName()
        ),
        _('406'),
        $error,
        406
      );
    }

    try
    {
      $this->_database->beginTransaction();

      $user = $this->_database->getUser(
        $this->_database->initUserId(
          $address,
          $this->_website->getDefaultUserStatus(),
          $this->_website->getDefaultUserApproved(),
          time()
        )
      );

      $this->_database->commit();
    }

    catch (Exception $error)
    {
      $this->_database->rollback();

      $this->_response(
        sprintf(
          _('Error - %s'),
          $this->_website->getName()
        ),
        _('500'),
        $error,
        500
      );
    }

    // Access denied
    if (!$user->status)
    {
      $this->_response(
        sprintf(
          _('Error - %s'),
          $this->_website->getName()
        ),
        _('403'),
        _('Access denied'),
        403
      );
    }

    // Require account type selection
    if (is_null($user->public))
    {
      header(
        sprintf(
          'Location: %s/welcome',
          trim($this->_website->getUrl(), '/')
        )
      );
    }
  }

  public function render()
  {
    $user = $this->_initUser(
      $this->_session->getAddress()
    );

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

    $pages = [];

    require_once __DIR__ . '/module/pagination.php';

    $appControllerModulePagination = new appControllerModulePagination();

    require_once __DIR__ . '/module/head.php';

    $appControllerModuleHead = new AppControllerModuleHead(
      $this->_website->getUrl(),
      $page > 1 ?
      sprintf(
        _('Page %s - BitTorrent Registry for Yggdrasil - %s'),
        $page,
        $this->_website->getName()
      ) :
      sprintf(
        _('%s - BitTorrent Registry for Yggdrasil'),
        $this->_website->getName()
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
      $this->_database,
      $this->_website,
      $this->_session
    );

    require_once __DIR__ . '/module/header.php';

    $appControllerModuleHeader = new AppControllerModuleHeader();

    require_once __DIR__ . '/module/footer.php';

    $appControllerModuleFooter = new AppControllerModuleFooter();

    include __DIR__ . '/../view/theme/default/index.phtml';
  }
}