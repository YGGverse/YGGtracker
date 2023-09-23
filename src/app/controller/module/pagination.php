<?php

class AppControllerModulePagination
{
  public function render(string $url, int $total, int $limit)
  {
    if ($total > $limit)
    {
      parse_str($url, $query);

      $pagination->page  = isset($query['total']) ? (int) $query['total'] : 1;
      $pagination->pages = ceil($total / $limit);

      // Previous
      if ($page > 1)
      {
        $query['page'] = $page - 1;

        $pagination->back = sprintf('%s', WEBSITE_URL, http_build_query($query));
      }

      else
      {
        $pagination->back = false;
      }

      // Next
      if ($page < ceil($total / $limit))
      {
        $query['page'] = $page + 1;

        $pagination->next = sprintf('%s', WEBSITE_URL, http_build_query($query));
      }

      else
      {
        $pagination->next = false;
      }

      // Render
    }
  }
}