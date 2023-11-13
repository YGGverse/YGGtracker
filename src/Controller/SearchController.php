<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\ActivityService;

class SearchController extends AbstractController
{
    public function module(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        // Defaults
        $locales    = [];
        $categories = [];

        // Extended search
        if ($request->get('filter'))
        {
            // Init user
            $user = $this->initUser(
                $request,
                $userService,
                $activityService
            );

            // Locales
            foreach (explode('|', $this->getParameter('app.locales')) as $locale)
            {
                if ($request->get('locales'))
                {
                    $locales[] =
                    [
                        'value'   => $locale,
                        'checked' => in_array($locale, (array) $request->get('locales')) ? true : false,
                    ];
                }

                else
                {
                    $locales[] =
                    [
                        'value'   => $locale,
                        'checked' => in_array($locale, $user->getLocales()) ? true : false,
                    ];
                }
            }

            // Categories
            foreach (explode('|', $this->getParameter('app.categories')) as $category)
            {
                if ($request->get('categories'))
                {
                    $categories[] =
                    [
                        'value'   => $category,
                        'checked' => in_array($category, (array) $request->get('categories')) ? true : false,
                    ];
                }

                else
                {
                    $categories[] =
                    [
                        'value'   => $category,
                        'checked' => in_array($category, $user->getCategories()) ? true : false,
                    ];
                }
            }
        }

        return $this->render(
            'default/search/module.html.twig',
            [
                'query'      => $request->get('query') ? urldecode($request->get('query')) : '',
                'filter'     => $request->get('filter'),
                'sensitive'  => $request->get('sensitive'),
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