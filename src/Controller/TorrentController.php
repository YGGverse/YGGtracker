<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\TorrentService;
use App\Service\TimeService;

class TorrentController extends AbstractController
{
    #[Route(
        '/{_locale}/torrent/{id}',
        name: 'torrent_info',
        requirements:
        [
            'id' => '\d+'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function info(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        TorrentService $torrentService,
        TimeService $timeService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        if (!$torrent = $torrentService->getTorrent($request->get('id')))
        {
            throw $this->createNotFoundException();
        }

        /*
        if (!$torrent = $torrentService->getTorrentLocales($request->get('id')))
        {
            throw $this->createNotFoundException();
        }
        */

        return $this->render('default/torrent/info.html.twig', [
            'torrent' =>
            [
                'id'      => $torrent->getId(),
                'locales' => [], //$torrent->getLocales(),
                'pages'   => []
            ],
            'file'    => $torrentService->decodeTorrentById(
                $torrent->getId()
            ),
            'trackers' => explode('|', $this->getParameter('app.trackers')),
        ]);
    }

    #[Route(
        '/{_locale}/submit/torrent',
        name: 'torrent_submit',
        methods:
        [
            'GET',
            'POST'
        ]
    )]
    public function submit(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        TorrentService $torrentService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        if (!$user->isStatus())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Init form
        $form =
        [
            'locales' =>
            [
                'error'       => [],
                'attribute' =>
                [
                    'value'       => $request->get('locales') ? $request->get('locales') : [$request->get('_locale')],
                    'placeholder' => $translator->trans('Content language')
                ]
            ],
            'torrent' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => null, // is local file, there is no values passed
                    'placeholder' => $translator->trans('Select torrent file')
                ]
            ],
            'sensitive' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => $request->get('sensitive'),
                    'placeholder' => $translator->trans('Apply sensitive filters for this publication'),
                ]
            ]
        ];

        // Process request
        if ($request->isMethod('post'))
        {
            /// Locales
            $locales = [];
            if ($request->get('locales'))
            {
                foreach ((array) $request->get('locales') as $locale)
                {
                    if (in_array($locale, explode('|', $this->getParameter('app.locales'))))
                    {
                        $locales[] = $locale;
                    }
                }
            }

            //// At least one valid locale required
            if (!$locales)
            {
                $form['locales']['error'][] = $translator->trans('At least one locale required');
            }

            /// Torrent
            if ($file = $request->files->get('torrent'))
            {
                //// Validate torrent file
                if (filesize($file->getPathName()) > $this->getParameter('app.torrent.size.max'))
                {
                    $form['torrent']['error'][] = $translator->trans('Torrent file out of size limit');
                }

                if (empty($torrentService->getTorrentFilenameByFilepath($file->getPathName())))
                {
                    $form['torrent']['error'][] = $translator->trans('Could not parse torrent file');
                }
            }

            else
            {
                $form['torrent']['error'][] = $translator->trans('Torrent file required');
            }

            // Request is valid
            if (empty($form['torrent']['error']) && empty($form['locales']['error']))
            {
                // Save data
                $torrent = $torrentService->submit(
                    $file->getPathName(),
                    $user->getId(),
                    time(),
                    (array) $locales,
                    (bool) $request->get('sensitive'),
                    $user->isApproved()
                );

                // Redirect to info page created
                return $this->redirectToRoute(
                    'torrent_info',
                    [
                        '_locale' => $request->get('_locale'),
                        'id'      => $torrent->getId()
                    ]
                );
            }
        }

        // Render form template
        return $this->render(
            'default/torrent/submit.html.twig',
            [
                'locales' => explode('|', $this->getParameter('app.locales')),
                'form'    => $form,
            ]
        );
    }
}
