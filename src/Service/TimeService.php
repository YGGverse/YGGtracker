<?php

namespace App\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

class TimeService
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function ago(int $time): string
    {
        $diff = time() - $time;

        if ($diff < 1)
        {
            return $this->translator->trans('now');
        }

        $values =
        [
            365 * 24 * 60 * 60 => $this->translator->trans('year'),
            30  * 24 * 60 * 60 => $this->translator->trans('month'),
                  24 * 60 * 60 => $this->translator->trans('day'),
                       60 * 60 => $this->translator->trans('hour'),
                            60 => $this->translator->trans('minute'),
                             1 => $this->translator->trans('second')
        ];

        $plural = [
            $this->translator->trans('year')   => $this->translator->trans('years'),
            $this->translator->trans('month')  => $this->translator->trans('months'),
            $this->translator->trans('day')    => $this->translator->trans('days'),
            $this->translator->trans('hour')   => $this->translator->trans('hours'),
            $this->translator->trans('minute') => $this->translator->trans('minutes'),
            $this->translator->trans('second') => $this->translator->trans('seconds')
        ];

        foreach ($values as $key => $value)
        {
            $result = $diff / $key;

            if ($result >= 1)
            {
                $round = round($result);

                return sprintf(
                    '%s %s %s',
                    $round,
                    $round > 1 ? $plural[$value] : $value,
                    $this->translator->trans('ago')
                );
            }
        }
    }
}