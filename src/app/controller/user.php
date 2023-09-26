<?php

class AppControllerUser
{
  private $_database;
  private $_validator;
  private $_website;

  private $_user;

  public function __construct(
    AppModelDatabase  $database,
    AppModelValidator $validator,
    AppModelWebsite   $website
  )
  {
    $this->_database  = $database;
    $this->_validator = $validator;
    $this->_website   = $website;
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