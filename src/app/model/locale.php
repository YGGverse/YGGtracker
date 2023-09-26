<?php

class AppModelLocale {

  private $_locales = [];

  public function __construct(object $locales)
  {
    foreach ($locales as $key => $value)
    {
      $this->_locales[] = (object)
      [
        'key'    => $key,
        'value'  => $value[0],
        'active' => false !== stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'], $key) ? true : false,
      ];
    }
  }

  public function getLocales() : object
  {
    return (object) $this->_locales;
  }

  public function localeKeyExists(string $key) : bool
  {
    foreach ($this->_locales as $locale)
    {
      if ($locale->key === $key)
      {
        return true;
      }
    }

    return false;
  }
}
