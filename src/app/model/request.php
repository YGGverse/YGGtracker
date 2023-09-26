<?php

class AppModelRequest {

  private $_get;
  private $_post;
  private $_files;

  public function __construct(array $get, array $post, array $files)
  {
    $this->_get   = $get;
    $this->_post  = $post;
    $this->_files = $files;
  }

  public function get(string $key) : mixed
  {
    if (isset($this->_get[$key]))
    {
      return $this->_get[$key];
    }

    else
    {
      return false;
    }
  }

  public function post(string $key) : mixed
  {
    if (isset($this->_post[$key]))
    {
      return $this->_post[$key];
    }

    else
    {
      return false;
    }
  }

  public function files(string $key) : mixed
  {
    if (isset($this->_files[$key]))
    {
      return $this->_files[$key];
    }

    else
    {
      return false;
    }
  }
}
