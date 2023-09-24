<?php

class AppControllerUser
{
  private $_database;
  private $_validator;

  private $_user;

  public function __construct(string $address)
  {
    require_once __DIR__ . '/../model/database.php';

    $this->_database = new AppModelDatabase(
      Environment::config('database')
    );

    require_once __DIR__ . '/../model/validator.php';

    $this->_validator = new AppModelValidator(
      Environment::config('validator')
    );

    // Validate address
    $error = [];

    if (!$this->_validator->host($address, $error))
    {
      $this->_response(
        sprintf(
          _('Error - %s'),
          Environment::config('website')->name
        ),
        _('406'),
        print_r($error, true),
        406
      );
    }

    // Init user session
    try
    {
      $this->_database->beginTransaction();

      $this->_user = $this->_database->getUser(
        $this->_database->initUserId(
          $address,
          Environment::config('website')->default->user->approved,
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
          Environment::config('website')->name
        ),
        _('500'),
        print_r($error, true),
        500
      );
    }

    // Require account type selection
    if (is_null($this->getPublic()))
    {
      header(
        sprintf(
          'Location: %s/welcome',
          trim($this->_config->url, '/')
        )
      );
    }
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

  public function getIdenticon(int $size)
  {
    $icon = new Jdenticon\Identicon();

    $icon->setValue($this->_user->public ? $this->_user->address : $this->_user->userId);
    $icon->setSize($size);
    $icon->setStyle(
      [
        'backgroundColor' => 'rgba(255, 255, 255, 0)',
      ]
    );

    return $icon->getImageDataUri('webp');
  }

  public function getUser()
  {
    return $this->_user;
  }

  public function getPublic()
  {
    return $this->_user->public;
  }

  public function getAddress()
  {
    return $this->_user->address;
  }

  public function findUserPageStarsDistinctTotalByValue(bool $value) : int
  {
    return $this->_database->findUserPageStarsDistinctTotal(
      $this->_user->userId,
      $value
    );
  }

  public function findUserPageViewsDistinctTotal() : int
  {
    return $this->_database->findUserPageViewsDistinctTotal(
      $this->_user->userId
    );
  }

  public function findUserPageDownloadsDistinctTotal() : int
  {
    return $this->_database->findUserPageDownloadsDistinctTotal(
      $this->_user->userId
    );
  }

  public function findUserPageCommentsDistinctTotal() : int
  {
    return $this->_database->findUserPageCommentsDistinctTotal(
      $this->_user->userId
    );
  }

  public function findUserPageEditionsDistinctTotal() : int
  {
    return $this->_database->findUserPageEditionsDistinctTotal(
      $this->_user->userId
    );
  }

  public function updateUserPublic(bool $public, int $time) : int
  {
    return $this->_database->updateUserPublic(
      $this->_user->userId,
      $public,
      $time
    );
  }
}