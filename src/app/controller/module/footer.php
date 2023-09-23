<?php

class AppControllerModuleFooter
{
  public function render()
  {
    $response['trackers'] = [];

    if ($trackers = json_decode(file_get_contents(__DIR__ . '/../../../config/trackers.json')))
    {
      foreach ($trackers as $tracker)
      {
        if (!empty($tracker->announce) && !empty($tracker->stats))
        {
          $response['trackers'][] = [
            'announce' => $tracker->announce,
            'stats'    => $tracker->stats,
          ];
        }
      }
    }

    include __DIR__ . '../../../view/theme/default/module/footer.phtml';
  }
}