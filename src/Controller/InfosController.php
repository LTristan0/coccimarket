<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InfosController extends AbstractController
{
    #[Route('/infos', name: 'infos')]
    public function index(): Response
    {
        return $this->render('infos/base.html.twig');
    }
}
