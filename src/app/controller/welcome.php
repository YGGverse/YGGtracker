<?php

class AppControllerWelcome
{
  private $_user;

  public function __construct()
  {
    require_once __DIR__ . '/../model/user.php';

    $this->_user = new AppModelUser(
      $_SERVER['REMOTE_ADDR']
    );
  }

  private function _response(string $title, string $h1, string $text, int $code = 200)
  {
    require_once __DIR__ . '/response.php';

    $appControllerResponse = new AppControllerResponse(
      $title,
      $h1,
      $text,
      $code
    );

    $appControllerResponse->render();

    exit;
  }

  public function render()
  {
    if (!$user = $this->_user->get())
    {
      $this->_response(
        sprintf(
          _('Error - %s'),
          WEBSITE_NAME
        ),
        _('500'),
        _('Could not init user'),
        500
      );
    }

    if (!is_null($user->public))
    {
      $this->_response(
        sprintf(
          _('Welcome back - %s'),
          WEBSITE_NAME
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
      if ($this->_user->updateUserPublic((bool) $_POST['public'], time()))
      {
        $this->_response(
          sprintf(
            _('Success - %s'),
            WEBSITE_NAME
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
      WEBSITE_URL,
      sprintf(
        _('Welcome to %s'),
        WEBSITE_NAME
      ),
      [
        [
          'rel'  => 'stylesheet',
          'type' => 'text/css',
          'href' => sprintf(
            'assets/theme/default/css/common.css?%s',
            WEBSITE_CSS_VERSION
          ),
        ],
        [
          'rel'  => 'stylesheet',
          'type' => 'text/css',
          'href' => sprintf(
            'assets/theme/default/css/framework.css?%s',
            WEBSITE_CSS_VERSION
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