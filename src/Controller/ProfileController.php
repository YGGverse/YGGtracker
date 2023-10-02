<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\User;

class ProfileController extends AbstractController
{
    #[Route(
        '/{_locale}/profile',
        name: 'profile_index'
    )]
    public function index(Request $request, User $user): Response
    {
        return $this->render(
            'default/profile/index.html.twig',
            [
                'user' => $user->init($request->getClientIp())
            ]
        );
    }

    #[Route(
        '/{_locale}/profile/setting',
        name: 'profile_setting'
    )]
    public function setting(): Response
    {
        // @TODO
        return $this->render(
            'default/profile/setting.html.twig'
        );
    }

    public function module(string $route = ''): Response
    {
        return $this->render(
            'default/profile/module.html.twig',
            [
                'route'     => $route,
                'stars'     => 0,
                'views'     => 0,
                'comments'  => 0,
                'downloads' => 0,
                'editions'  => 0,
                'identicon' => $this->_getIdenticon(
                    '@TODO',
                    17,
                    [
                        'backgroundColor' => 'rgba(255, 255, 255, 0)',
                    ]
                )
            ]
        );
    }

    private function _getIdenticon(
        mixed $id,
        int $size,
        array $style,
        string $format = 'webp') : string
    {
        $identicon = new \Jdenticon\Identicon();

        $identicon->setValue($id);
        $identicon->setSize($size);
        $identicon->setStyle($style);

        return $identicon->getImageDataUri($format);
    }
}