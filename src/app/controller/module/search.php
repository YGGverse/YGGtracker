<?php

class AppControllerModuleSearch
{
  public function render()
  {
    $query = empty($_GET['query']) ? false : urldecode($_GET['query']);

    include __DIR__ . '../../../view/theme/default/module/search.phtml';
  }
}