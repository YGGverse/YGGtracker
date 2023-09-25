<?php

class AppControllerModuleSearch
{
  public function render()
  {
    $query  = empty($_GET['query']) ? false : urldecode($_GET['query']);
    $locale = empty($_GET['locale']) ? 'all' : urldecode($_GET['locale']);

    $locales = [];

    foreach (Environment::config('locales') as $key => $value)
    {
      $locales[$key] = (object)
      [
        'key'    => $key,
        'value'  => $value[0],
        'active' => $key === $locale // false !== stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'], $key) ? true : false,
      ];
    }

    include __DIR__ . '../../../view/theme/default/module/search.phtml';
  }
}