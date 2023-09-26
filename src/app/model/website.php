<?php

class AppModelWebsite
{
  private $_config;

  public function __construct(object $config)
  {
    $this->_config = $config;
  }

  public function getConfig() : object
  {
    return $this->_config->name;
  }

  public function getName() : string
  {
    return $this->_config->name;
  }

  public function getUrl() : string
  {
    return $this->_config->url;
  }

  public function getDefaultUserApproved() : bool
  {
    return $this->_config->default->user->approved;
  }

  public function getApiExportEnabled() : bool
  {
    return $this->_config->api->export->enabled;
  }
}