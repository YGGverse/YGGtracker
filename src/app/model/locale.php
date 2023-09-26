<?php

class AppModelLocale {

  private $_locales = [];

  public function __construct(object $locales)
  {
    foreach ($locales as $code => $value)
    {
      $this->_locales[] = (object)
      [
        'code'   => $code,
        'value'  => $value[0],
        'active' => false,
      ];
    }
  }

  public function getList() : object
  {
    return (object) $this->_locales;
  }

  public function codeExists(string $code) : bool
  {
    foreach ($this->_locales as $locale)
    {
      if ($locale->code === $code)
      {
        return true;
      }
    }

    return false;
  }
}
