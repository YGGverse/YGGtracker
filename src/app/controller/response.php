<?php

class AppControllerResponse
{
  private $_title;
  private $_h1;
  private $_text;
  private $_code;

  public function __construct(string $title, string $h1, string $text, int $code = 200)
  {
    $this->_title = $title;
    $this->_h1    = $h1;
    $this->_text  = $text;
    $this->_code  = $code;
  }

  public function render()
  {
    header(
      sprintf(
        'HTTP/1.0 %s Not Found',
        $this->_code
      )
    );

    $h1   = $this->_h1;
    $text = $this->_text;

    require_once __DIR__ . '/module/head.php';

    $appControllerModuleHead = new AppControllerModuleHead(
      Environment::config('website')->url,
      $this->_title,
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

    include __DIR__ . '../../view/theme/default/response.phtml';
  }
}