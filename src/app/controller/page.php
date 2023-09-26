<?php

class AppControllerPage
{
  private $_database;
  private $_validator;
  private $_locale;
  private $_website;
  private $_session;

  public function __construct(
    AppModelDatabase  $database,
    AppModelValidator $validator,
    AppModelLocale    $locale,
    AppModelWebsite   $website,
    AppModelSession   $session,
  )
  {
    $this->_database  = $database;
    $this->_validator = $validator;
    $this->_locale    = $locale;
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

  public function get(int $pageId)
  {
    return $this->_database->getPage($pageId);
  }

  public function add(int $timeAdded)
  {
    return $this->_database->addPage($timeAdded);
  }

  public function commitTitle(int $localeId, string $value)
  {

  }


  public function renderFormSubmit()
  {

    $user = $this->_initUser(
      $this->_session->getAddress()
    );

    // Init form
    $form = (object)
    [
      'locale' => (object)
      [
        'error'  => [],
        'values' => $this->_locale->getLocales(),
        'attribute' => (object)
        [
          'value'       => null,
          'placeholder' => _('Page content language'),
        ]
      ],
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
      if (isset($_POST['locale']))
      {
        if (!$this->_locale->localeKeyExists($_POST['locale']))
        {
          $form->locale->error[] = [
            _('Locale not supported')
          ];
        }

        $form->locale->attribute->value = htmlentities($_POST['locale']);
      }

      if (isset($_POST['title']))
      {
        $error = [];

        if (!$this->_validator->pageTitle($_POST['title'], $error))
        {
          $form->title->error[] = $error;

          $form->title->attribute->value = htmlentities($_POST['title']);
        }

        else
        {
          $this->commitTitle($_POST['locale'], $_POST['title']);

          $form->title->attribute->value = $this->getTitle();
        }
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
        // Init page
        if (isset($_GET['pageId']))
        {
          $page = $this->_database->getPage((int) $_GET['pageId']);
        }

        else if (isset($_POST['pageId']))
        {
          $page = $this->_database->getPage((int) $_POST['pageId']);
        }

        else
        {
          $page = $this->_database->getPage(
            $this->_database->addPage(
              time()
            )
          );
        }

        // @TODO redirect
      }
    }

    // Render template
    require_once __DIR__ . '/module/head.php';

    $appControllerModuleHead = new AppControllerModuleHead(
      $this->_website->getUrl(),
      sprintf(
        _('Submit - %s'),
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
      $user
    );

    require_once __DIR__ . '/module/header.php';

    $appControllerModuleHeader = new AppControllerModuleHeader();

    require_once __DIR__ . '/module/footer.php';

    $appControllerModuleFooter = new AppControllerModuleFooter();

    include __DIR__ . '../../view/theme/default/page/form/submit.phtml';
  }
}