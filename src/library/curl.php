<?php

class Curl
{
  private $_connection;
  private $_response;

  public function __construct(string $url,
                              string $userAgent = 'YGGtracker',
                              array  $post = [],
                              int    $connectTimeout = 10,
                              bool   $header = false,
                              bool   $followLocation = false,
                              int    $maxRedirects = 10,
                              bool   $sslVerifyHost = false,
                              bool   $sslVerifyPeer = false)
  {
    $this->_connection = curl_init($url);

    if ($userAgent)
    {
      curl_setopt($this->_connection, CURLOPT_USERAGENT, $userAgent);
    }

    if (!empty($post))
    {
      curl_setopt($this->_connection, CURLOPT_POST, true);
      curl_setopt($this->_connection, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    if ($header) {
      curl_setopt($this->_connection, CURLOPT_HEADER, true);
    }

    if ($followLocation) {
      curl_setopt($this->_connection, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($this->_connection, CURLOPT_MAXREDIRS, $maxRedirects);
    }

    curl_setopt($this->_connection, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($this->_connection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->_connection, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
    curl_setopt($this->_connection, CURLOPT_TIMEOUT, $connectTimeout);
    curl_setopt($this->_connection, CURLOPT_SSL_VERIFYHOST, $sslVerifyHost);
    curl_setopt($this->_connection, CURLOPT_SSL_VERIFYPEER, $sslVerifyPeer);

    $this->_response = curl_exec($this->_connection);
  }

  public function __destruct()
  {
    curl_close($this->_connection);
  }

  public function getError()
  {
    if (curl_errno($this->_connection))
    {
      return curl_errno($this->_connection);
    }

    else
    {
      return false;
    }
  }

  public function getCode()
  {
    return curl_getinfo($this->_connection, CURLINFO_HTTP_CODE);
  }

  public function getContentType()
  {
    return curl_getinfo($this->_connection, CURLINFO_CONTENT_TYPE);
  }

  public function getSizeDownload()
  {
    return curl_getinfo($this->_connection, CURLINFO_SIZE_DOWNLOAD);
  }

  public function getSizeRequest()
  {
    return curl_getinfo($this->_connection, CURLINFO_REQUEST_SIZE);
  }

  public function getTotalTime()
  {
    return curl_getinfo($this->_connection, CURLINFO_TOTAL_TIME_T);
  }

  public function getResponse(bool $json = true)
  {
    return $json ? json_decode($this->_response) : $this->_response;
  }
}