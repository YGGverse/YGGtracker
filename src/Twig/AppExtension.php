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
            new TwigFilter(
                'url_to_markdown',
                [
                    $this,
                    'urlToMarkdown'
                ]
            ),
            new TwigFilter(
                'trans_category',
                [
                    $this,
                    'transCategory'
                ]
            ),
        ];
    }

    public function formatBytes(
        int $bytes,
        int $precision = 2
    ): string
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
    ): string
    {
        $diff = time() - $time;

        if ($diff < 1)
        {
            return $this->translator->trans('now');
        }

        $values =
        [
            365 * 24 * 60 * 60 =>
            [
                $this->translator->trans('year ago'),
                $this->translator->trans('years ago'),
                $this->translator->trans(' years ago')
            ],
            30  * 24 * 60 * 60 =>
            [
                $this->translator->trans('month ago'),
                $this->translator->trans('months ago'),
                $this->translator->trans(' months ago')
            ],
            24 * 60 * 60 =>
            [
                $this->translator->trans('day ago'),
                $this->translator->trans('days ago'),
                $this->translator->trans(' days ago')
            ],
            60 * 60 =>
            [
                $this->translator->trans('hour ago'),
                $this->translator->trans('hours ago'),
                $this->translator->trans(' hours ago')
            ],
            60 =>
            [
                $this->translator->trans('minute ago'),
                $this->translator->trans('minutes ago'),
                $this->translator->trans(' minutes ago')
            ],
            1 =>
            [
                $this->translator->trans('second ago'),
                $this->translator->trans('seconds ago'),
                $this->translator->trans(' seconds ago')
            ]
        ];

        foreach ($values as $key => $value)
        {
            $result = $diff / $key;

            if ($result >= 1)
            {
                $round = round($result);

                return sprintf(
                    '%s %s',
                    $round,
                    $this->plural(
                        $round,
                        $value
                    )
                );
            }
        }
    }

    public function urlToMarkdown(
        string $text
    ): string
    {
        return preg_replace(
            '~(https?://(?:www\.)?[^\s]+)~i',
            '[$1]($1)',
            $text
        );
    }

    public function transCategory(
        string $name
    ): string
    {
        switch ($name)
        {
            case 'movie':     return $this->translator->trans('movie');
            case 'series':    return $this->translator->trans('series');
            case 'tv':        return $this->translator->trans('tv');
            case 'animation': return $this->translator->trans('animation');
            case 'music':     return $this->translator->trans('music');
            case 'game':      return $this->translator->trans('game');
            case 'audiobook': return $this->translator->trans('audiobook');
            case 'podcast':   return $this->translator->trans('podcast');
            case 'book':      return $this->translator->trans('book');
            case 'archive':   return $this->translator->trans('archive');
            case 'picture':   return $this->translator->trans('picture');
            case 'software':  return $this->translator->trans('software');
            case 'other':     return $this->translator->trans('other');
            default:          return $name;
        }
    }

    private function plural(int $number, array $texts)
    {
        $cases = [2, 0, 1, 1, 1, 2];

        return $texts[(($number % 100) > 4 && ($number % 100) < 20) ? 2 : $cases[min($number % 10, 5)]];
    }
}