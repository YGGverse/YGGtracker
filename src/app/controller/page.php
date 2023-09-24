<?php

class AppControllerPage
{
  private $_database;
  private $_validator;

  private $_user;

  public function __construct()
  {
    require_once __DIR__ . '/../model/database.php';

    $this->_database = new AppModelDatabase(
      Environment::config('database')
    );

    require_once __DIR__ . '/../model/validator.php';

    $this->_validator = new AppModelValidator(
      Environment::config('validator')
    );

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

  public function renderFormDescription()
  {
    // Init form
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
      'sensitive' => (object)
      [
        'error' => [],
        'attribute' => (object)
        [
          'value'       => null,
          'placeholder' => _('Apply NSFW filters for this publication'),
        ]
      ]
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

      if (isset($_POST['sensitive']))
      {
        $form->sensitive->attribute->value = (bool) $_POST['sensitive'];
      }

      // Request valid
      if (empty($error))
      {
        // @TODO redirect
      }
    }

    // Render template
    require_once __DIR__ . '/module/head.php';

    $appControllerModuleHead = new AppControllerModuleHead(
      Environment::config('website')->url,
      sprintf(
        _('Submit - %s'),
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

    include __DIR__ . '../../view/theme/default/page/form/description.phtml';
  }
}