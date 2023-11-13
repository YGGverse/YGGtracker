<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\TorrentService;
use App\Service\ActivityService;

class SearchController extends AbstractController
{
    public function module(
        Request $request,
        UserService $userService,
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Defaults
        $locales    = [];
        $categories = [];

        // Request
        $query = $request->get('query') ? urldecode($request->get('query')) : '';
        $filter = $request->get('filter') ? true : false;

        // Extended search
        if ($filter)
        {
            // Init user
            $user = $this->initUser(
                $request,
                $userService,
                $activityService
            );

            // Keywords
            $keywords = explode(' ', $query);

            // Locales
            foreach (explode('|', $this->getParameter('app.locales')) as $locale)
            {

                if ($request->get('locales'))
                {
                    $checked = in_array($locale, (array) $request->get('locales'));
                }

                else
                {
                    $checked = in_array($locale, $user->getLocales());
                }

                $locales[] =
                [
                    'value'   => $locale,
                    'checked' => $checked,
                    'total'   => $torrentService->findTorrentsTotal(
                        0,
                        $keywords,
                        [$locale],
                        $request->get('categories') ? $request->get('categories') : $user->getCategories(),
                        $sensitive,
                        !$user->isModerator() ? true : null,
                        !$user->isModerator() ? true : null,
                    )
                ];
            }

            // Categories
            foreach (explode('|', $this->getParameter('app.categories')) as $category)
            {
                if ($request->get('categories'))
                {
                    $checked = in_array($category, (array) $request->get('categories'));
                }

                else
                {
                    $checked = in_array($category, $user->getCategories());
                }

                $categories[] =
                [
                    'value'   => $category,
                    'checked' => $checked,
                    'total'   => $torrentService->findTorrentsTotal(
                        0,
                        $keywords,
                        $request->get('locales') ? $request->get('locales') : $user->getLocales(),
                        [$category],
                        $sensitive,
                        !$user->isModerator() ? true : null,
                        !$user->isModerator() ? true : null,
                    )
                ];
            }

            // Sensitive
            $sensitive =
            [
                'checked' => $request->get('sensitive'),
                'total'   => $torrentService->findTorrentsTotal(
                    0,
                    $keywords,
                    $request->get('locales') ? $request->get('locales') : $user->getLocales(),
                    $request->get('categories') ? $request->get('categories') : $user->getCategories(),
                    true,
                    !$user->isModerator() ? true : null,
                    !$user->isModerator() ? true : null,
                )
            ];
        }

        return $this->render(
            'default/search/module.html.twig',
            [
                'query'      => $query,
                'filter'     => $filter,
                'sensitive'  => $sensitive,
                'locales'    => $locales,
                'categories' => $categories,
            ]
        );
    }

    private function initUser(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): ?\App\Entity\User
    {
        // Init user
        if (!$user = $userService->findUserByAddress($request->getClientIp()))
        {
            $user = $userService->addUser(
                $request->getClientIp(),
                time(),
                $this->getParameter('app.locale'),
                explode('|', $this->getParameter('app.locales')),
                $activityService->getEventCodes(),
                $this->getParameter('app.theme'),
                $this->getParameter('app.sensitive'),
                $this->getParameter('app.yggdrasil'),
                $this->getParameter('app.posters'),
                $this->getParameter('app.approved')
            );

            // Add user join event
            $activityService->addEventUserAdd(
                $user->getId(),
                time()
            );
        }

        return $user;
    }
}