<?php

class AppControllerPage
{
  private $_database;
  private $_validator;
  private $_locale;
  private $_website;
  private $_session;
  private $_request;

  public function __construct(
    AppModelDatabase  $database,
    AppModelValidator $validator,
    AppModelLocale    $locale,
    AppModelWebsite   $website,
    AppModelSession   $session,
    AppModelRequest   $request
  )
  {
    $this->_database  = $database;
    $this->_validator = $validator;
    $this->_locale    = $locale;
    $this->_website   = $website;
    $this->_session   = $session;
    $this->_request   = $request;
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

  private function _initLocale(string $value)
  {
    if (!$locale = $this->_database->findLocale($value))
    {
      $locale = $this->_database->getLocale(
        $this->_database->addLocale(
          $value
        )
      );
    }

    return $locale;
  }

  private function _initPage(int $pageId = 0)
  {
    if (!$page = $this->_database->getPage($pageId))
    {
      $page = $this->_database->getPage(
        $this->_database->addPage(
          time()
        )
      );
    }

    return $page;
  }

  private function _initText(string $value, string $mime = 'text/plain')
  {
    if (!$text = $this->_database->findText($mime, md5($value)))
    {
      $text = $this->_database->getText(
        $this->_database->addText(
          $mime,
          md5($value),
          $value,
          time()
        )
      );
    }

    return $text;
  }

  private function _commitPageTitle(int $pageId, int $userId, int $localeId, string $text, string $mime = 'text/plain')
  {
    $textId = $this->_initText(
      $text,
      $mime
    )->textId;

    if (!$this->_database->findPageTitleLatest($pageId,
                                               $userId,
                                               $localeId,
                                               $textId))
    {
      $this->_database->addPageTitle(
        $pageId,
        $userId,
        $localeId,
        $textId,
        time()
      );
    }
  }

  public function renderFormSubmit()
  {
    // Init user
    $user = $this->_initUser(
      $this->_session->getAddress()
    );

    // Init page
    if ($this->_request->get('pageId'))
    {
      $page = $this->_initPage(
        (int) $this->_request->get('pageId')
      );
    }

    else if ($this->_request->post('pageId'))
    {
      $page = $this->_initPage(
        (int) $this->_request->post('pageId')
      );
    }

    else
    {
      $page = $this->_initPage();
    }

    // Init locale
    if ($this->_locale->codeExists($this->_request->get('locale')))
    {
      $localeCode = (int) $this->_request->get('locale');
    }

    else if ($this->_locale->codeExists($this->_request->post('locale')))
    {
      $localeCode = (int) $this->_request->post('locale');
    }

    else
    {
      $localeCode = $this->_website->getDefaultLocale();

      if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) // @TODO environment
      {
        foreach ($this->_locale->getList() as $value)
        {
          if (false !== stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'], $value->code))
          {
            $localeCode = $value->code;
            break;
          }
        }
      }
    }

    $locale = $this->_initLocale($localeCode);

    // Init form
    $form = (object)
    [
      'pageId' => (object)
      [
        'error' => [],
        'type'  => 'hidden',
        'attribute' => (object)
        [
          'value' => $page->pageId,
        ]
      ],
      'locale' => (object)
      [
        'error'        => [],
        'type'        => 'select',
        'options'     => $this->_locale->getList(),
        'value'       => $locale->value,
        'placeholder' => _('Page content language'),
      ],
      'title' => (object)
      [
        'error' => [],
        'type'      => 'text',
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
        'type'      => 'textarea',
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
        'type'      => 'textarea',
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
        'type'      => 'checkbox',
        'attribute' => (object)
        [
          'value'       => null,
          'placeholder' => _('Apply NSFW filters for this publication'),
        ]
      ]
    ];

    // Submit request
    if ($this->_request->hasPost())
    {
      /// Title
      if ($title = $this->_request->post('title'))
      {
        $error = [];

        if (!$this->_validator->pageTitle($title, $error))
        {
          $form->title->error[] = $error;
        }

        else
        {
          $this->_commitPageTitle(
            $page->pageId,
            $user->userId,
            $locale->localeId,
            $title
          );
        }

        $form->title->attribute->value = htmlentities($title);
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
      $this->_database,
      $this->_website,
      $this->_session
    );

    require_once __DIR__ . '/module/header.php';

    $appControllerModuleHeader = new AppControllerModuleHeader();

    require_once __DIR__ . '/module/footer.php';

    $appControllerModuleFooter = new AppControllerModuleFooter();

    include __DIR__ . '../../view/theme/default/page/form/submit.phtml';
  }
}