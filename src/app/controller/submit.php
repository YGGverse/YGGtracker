<?php

class AppControllerSubmit
{
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
    require_once __DIR__ . '/user.php';

    $appControllerUser = new AppControllerUser(
      $_SERVER['REMOTE_ADDR']
    );

    // Get user info
    if (!$user = $appControllerUser->getUser())
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

    // Require account type selection
    if (is_null($user->public))
    {
      header(
        sprintf('Location: %s/welcome', trim(WEBSITE_URL, '/'))
      );
    }

    // Render
    require_once __DIR__ . '/module/head.php';

    $appControllerModuleHead = new AppControllerModuleHead(
      WEBSITE_URL,
      sprintf(
        _('Submit - %s'),
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

    require_once __DIR__ . '/module/profile.php';

    $appControllerModuleProfile = new AppControllerModuleProfile(
      $appControllerUser
    );

    require_once __DIR__ . '/module/header.php';

    $appControllerModuleHeader = new AppControllerModuleHeader();

    require_once __DIR__ . '/module/footer.php';

    $appControllerModuleFooter = new AppControllerModuleFooter();

    include __DIR__ . '../../view/theme/default/submit.phtml';
  }
}