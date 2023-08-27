<?php

class Time
{
  public static function ago(int $time)
  {
    $diff = time() - $time;

    if ($diff < 1)
    {
      return _('now');
    }

    $values =
    [
      365 * 24 * 60 * 60  =>  _('year'),
       30 * 24 * 60 * 60  =>  _('month'),
            24 * 60 * 60  =>  _('day'),
                 60 * 60  =>  _('hour'),
                      60  =>  _('minute'),
                       1  =>  _('second')
    ];

    $plural = [
      _('year')   => _('years'),
      _('month')  => _('months'),
      _('day')    => _('days'),
      _('hour')   => _('hours'),
      _('minute') => _('minutes'),
      _('second') => _('seconds')
    ];

    foreach ($values as $key => $value)
    {
      $result = $diff / $key;

      if ($result >= 1)
      {
        $round = round($result);

        return sprintf('%s %s ago', $round, $round > 1 ? $plural[$value] : $value);
      }
    }
  }
}
