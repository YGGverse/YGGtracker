<?php

class AppModelRequest {

  private array $_get;
  private array $_post;
  private array $_files;
  private array $_server;

  public function __construct(array $get, array $post, array $files, array $server)
  {
    $this->_get    = $get;
    $this->_post   = $post;
    $this->_files  = $files;
    $this->_server = $server;
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

  public function server(string $key) : mixed
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

  public function hasPost() : bool
  {
    return !empty($this->_post);
  }

  public function hasGet() : bool
  {
    return !empty($this->_post);
  }

  public function hasFiles() : bool
  {
    return !empty($this->_post);
  }
}
