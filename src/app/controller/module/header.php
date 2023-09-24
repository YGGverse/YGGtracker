<?php

class AppControllerModuleHeader
{
  public function render()
  {
    $name = str_replace(
      'YGG',
      '<span>YGG</span>',
      Environment::config('website')->name
    );

    require_once __DIR__ . '/search.php';

    $appControllerModuleSearch = new AppControllerModuleSearch();

    include __DIR__ . '../../../view/theme/default/module/header.phtml';
  }
}