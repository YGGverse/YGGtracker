<?php

class AppControllerSubmit
{
  private $_validator;

  public function __construct(AppModelValidator $validator)
  {
    $this->_validator = $validator;
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

    // Form
    $form = (object)
    [
      'title' => (object)
      [
        'error' => [],
        'attribute' => (object)
        [
          'value'       => null,
          'required'    => $this->_validator->getPageTitleRequired(),
          'minlength'   => $this->_validator->getPageTitleLengthMin(),
          'maxlength'   => $this->_validator->getPageTitleLengthMax(),
          'placeholder' => sprintf(
            _('Page subject (%s-%s chars)'),
            number_format($this->_validator->getPageTitleLengthMin()),
            number_format($this->_validator->getPageTitleLengthMax())
          ),
        ]
      ],
      'description' => (object)
      [
        'error' => [],
        'attribute' => (object)
        [
          'value'       => null,
          'required'    => $this->_validator->getPageDescriptionRequired(),
          'minlength'   => $this->_validator->getPageDescriptionLengthMin(),
          'maxlength'   => $this->_validator->getPageDescriptionLengthMax(),
          'placeholder' => sprintf(
            _('Page description text (%s-%s chars)'),
            number_format($this->_validator->getPageDescriptionLengthMin()),
            number_format($this->_validator->getPageDescriptionLengthMax())
          ),
        ]
      ],
      'keywords' => (object)
      [
        'error' => [],
        'attribute' => (object)
        [
          'value'       => null,
          'required'    => $this->_validator->getPageKeywordsRequired(),
          'placeholder' => sprintf(
            _('Page keywords (%s-%s total / %s-%s chars per item)'),
            number_format($this->_validator->getPageKeywordsQuantityMin()),
            number_format($this->_validator->getPageKeywordsQuantityMax()),
            number_format($this->_validator->getPageKeywordLengthMin()),
            number_format($this->_validator->getPageKeywordLengthMax())
          ),
        ]
      ],
      'torrent' => (object)
      [
        'error' => [],
        'attribute' => (object)
        [
          'value'       => null,
          'required'    => $this->_validator->getPageTorrentRequired(),
          'accept'      => implode(',', $this->_validator->getPageTorrentMimeTypes()),
          'placeholder' => sprintf(
            _('Torrent file (use Ctrl to select multiple files)')
          ),
        ],
      ],
      'magnet' => (object)
      [
        'error' => [],
        'value' => null,
      ],
    ];

    // Submit request
    if (isset($_POST))
    {
      if (isset($_POST['title']))
      {
        $error = [];

        if (!$this->_validator->pageTitle($_POST['title'], $error))
        {
          $form->title->error[] = $error;
        }

        // @TODO check for page duplicates

        $form->title->attribute->value = htmlentities($_POST['title']);
      }

      if (isset($_POST['description']))
      {
        $error = [];

        if (!$this->_validator->pageDescription($_POST['description'], $error))
        {
          $form->description->error[] = $error;
        }

        $form->description->attribute->value = htmlentities($_POST['description']);
      }

      if (isset($_POST['keywords']))
      {
        $error = [];

        if (!$this->_validator->pageKeywords($_POST['keywords'], $error))
        {
          $form->keywords->error[] = $error;
        }

        $form->keywords->attribute->value = htmlentities($_POST['keywords']);
      }

      if (isset($_FILES['torrent']))
      {
        $error = [];

        // @TODO
      }

      // Request valid
      if (empty($error))
      {
        // @TODO redirect
      }
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