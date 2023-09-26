<?php

class AppControllerWelcome
{
  private $_database;
  private $_validator;
  private $_website;
  private $_session;

  public function __construct(
    AppModelDatabase  $database,
    AppModelValidator $validator,
    AppModelWebsite   $website,
    AppModelSession   $session,
  )
  {
    $this->_database  = $database;
    $this->_validator = $validator;
    $this->_website   = $website;
    $this->_session   = $session;
  }

  private function _response(string $title, string $h1, mixed $data, int $code = 200)
  {
    require_once __DIR__ . '/response.php';

    if (is_array($data))
    {
      $data = implode('<br />', $data);
    }

    $appControllerResponse = new AppControllerResponse(
      $title,
      $h1,
      $data,
      $code
    );

    $appControllerResponse->render();

    exit;
  }

  public function render()
  {
    $error = [];
    if (!$this->_validator->host($this->_session->getAddress(), $error))
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
          $this->_session->getAddress(),
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

    if (!is_null($user->public))
    {
      $this->_response(
        sprintf(
          _('Welcome back - %s'),
          $this->_website->getName()
        ),
        _('Welcome back!'),
        sprintf(
          _('You already have selected account type to %s'),
          $user->public ? _('Distributed') : _('Local')
        ),
        405
      );
    }

    if (isset($_POST['public']))
    {
      if ($this->_database->updateUserPublic($user->userId, (bool) $_POST['public'], time()))
      {
        $this->_response(
          sprintf(
            _('Success - %s'),
            $this->_website->getName()
          ),
          _('Success!'),
          sprintf(
            _('Account type successfully changed to %s'),
            $_POST['public'] ? _('Distributed') : _('Local')
          ),
        );
      }
    }

    require_once __DIR__ . '/module/head.php';

    $appControllerModuleHead = new AppControllerModuleHead(
      $this->_website->getUrl(),
      sprintf(
        _('Welcome to %s'),
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

    require_once __DIR__ . '/module/header.php';

    $appControllerModuleHeader = new AppControllerModuleHeader();

    require_once __DIR__ . '/module/footer.php';

    $appControllerModuleFooter = new AppControllerModuleFooter();

    include __DIR__ . '../../view/theme/default/welcome.phtml';
  }
}