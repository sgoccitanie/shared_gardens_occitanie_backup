<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/rgpd', name: 'app_rgpd', priority: 10)]
    public function rgpd(): Response
    {
        return $this->render('page/rgpd.html.twig');
    }

    #[Route('/mentions-legales', name: 'app_legal', priority: 10)]
    public function legal(): Response
    {
        return $this->render('page/legal.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'app_privacy', priority: 10)]
    public function privacy(): Response
    {
        return $this->render('page/privacy.html.twig');
    }

    #[Route('/cookies', name: 'app_cookies', priority: 10)]
    public function cookies(): Response
    {
        return $this->render('page/cookies.html.twig');
    }

    #[Route('/qui-sommes-nous', name: 'app_home', priority: 10)]
    public function about(): Response
    {
        return $this->render('page/home.html.twig');
    }
}
