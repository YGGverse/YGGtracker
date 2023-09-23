<?php

class AppControllerUser
{
  private $_database;

  private $_user;

  public function __construct(string $address)
  {
    // Connect DB
    require_once __DIR__ . '/../model/database.php';

    try
    {
      $this->_database = new AppModelDatabase(
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_USERNAME,
        DB_PASSWORD
      );
    }

    catch (Exception $error)
    {
      $this->_response(
        sprintf(
          _('Error - %s'),
          WEBSITE_NAME
        ),
        _('500'),
        print_r($error, true),
        500
      );
    }

    // Validate user address
    require_once __DIR__ . '/../../library/valid.php';

    $error = [];
    if (!Valid::host($address, $error))
    {
      $this->_response(
        sprintf(
          _('Error - %s'),
          WEBSITE_NAME
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
          USER_DEFAULT_APPROVED,
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
          WEBSITE_NAME
        ),
        _('500'),
        print_r($error, true),
        500
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

  public function getUser()
  {
    return $this->_user;
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