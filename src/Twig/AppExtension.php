<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    protected ContainerInterface $container;
    protected TranslatorInterface $translator;

    public function __construct(
        ContainerInterface $container,
        TranslatorInterface $translator
    )
    {
        $this->container = $container;
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return
        [
            new TwigFilter(
                'format_bytes',
                [
                    $this,
                    'formatBytes'
                ]
            ),
            new TwigFilter(
                'format_ago',
                [
                    $this,
                    'formatAgo'
                ]
            ),
        ];
    }

    public function formatBytes(
        int $bytes,
        int $precision = 2
    ) : string
    {
        $size = [
            $this->translator->trans('B'),
            $this->translator->trans('Kb'),
            $this->translator->trans('Mb'),
            $this->translator->trans('Gb'),
            $this->translator->trans('Tb'),
            $this->translator->trans('Pb'),
            $this->translator->trans('Eb'),
            $this->translator->trans('Zb'),
            $this->translator->trans('Yb')
        ];

        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$precision}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }

    public function formatAgo(
        int $time,
    ) : string
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