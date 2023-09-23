<?php

class AppControllerCommonPage
{
  public function render(int $pageId)
  {
    include __DIR__ . '../../../view/theme/default/common/page.phtml';
  }
}