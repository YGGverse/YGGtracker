<?php

class AppModelSession {

  private $_address;

  public function __construct(string $address)
  {
    $this->_address = $address;
  }

  public function getAddress() : string
  {
    return $this->_address;
  }
}
