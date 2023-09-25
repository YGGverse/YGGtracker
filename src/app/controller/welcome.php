<?php

class AppControllerWelcome
{
  private $_user;

  public function __construct()
  {
    require_once __DIR__ . '/user.php';

    $this->_user = new AppControllerUser(
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
    if (!$address = $this->_user->getAddress())
    {
      $this->_response(
        sprintf(
          _('Error - %s'),
          Environment::config('website')->name
        ),
        _('500'),
        _('Could not init user'),
        500
      );
    }

    if (!is_null($this->_user->getPublic()))
    {
      $this->_response(
        sprintf(
          _('Welcome back - %s'),
          Environment::config('website')->name
        ),
        _('Welcome back!'),
        sprintf(
          _('You already have selected account type to %s'),
          $this->_user->getPublic() ? _('Distributed') : _('Local')
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
            Environment::config('website')->name
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
      Environment::config('website')->url,
      sprintf(
        _('Welcome to %s'),
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

    require_once __DIR__ . '/module/header.php';

    $appControllerModuleHeader = new AppControllerModuleHeader();

    require_once __DIR__ . '/module/footer.php';

    $appControllerModuleFooter = new AppControllerModuleFooter();

    include __DIR__ . '../../view/theme/default/welcome.phtml';
  }
}