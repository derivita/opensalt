<?php

namespace App\Controller\Site;

use App\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DevController extends AbstractController
{
    #[Route(path: '/dev/cookie', name: 'dev_cookie')]
    #[IsGranted(Permission::FEATURE_DEV_ENV_CHECK)]
    public function devCookie(Request $request): Response
    {
        if (empty($cookie = $request->server->get('DEV_COOKIE'))) {
            $this->addFlash('error', 'Could not add development cookie as no DEV_COOKIE set.');

            return $this->redirectToRoute('salt_index');
        }

        $response = $this->redirectToRoute('salt_index');
        $response->headers->setCookie(Cookie::create('dev', $cookie, 'now + 1 year'));
        $this->addFlash('success', 'Development cookie set.');

        return $response;
    }
}
