<?php

class AppControllerModuleFooter
{
  public function render()
  {
    $trackers = Environment::config('trackers');

    $api = Environment::config('website')->api->export;

    include __DIR__ . '../../../view/theme/default/module/footer.phtml';
  }
}