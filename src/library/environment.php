<?php

class Environment
{
  public static function config(string $name) : object
  {
    $config = __DIR__ . '/../config/' . $name . '.json';

    if (file_exists(__DIR__ . '/../config/.env'))
    {
      $environment = file_get_contents(__DIR__ . '/../config/.env');

      $filename = __DIR__ . '/../config/' . $environment . '/' . $name . '.json';

      if (file_exists($filename))
      {
        $config = $filename;
      }
    }

    return json_decode(
      file_get_contents(
        $config
      )
    );
  }
}