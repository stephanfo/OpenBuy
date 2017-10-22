<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Request;

class DevController extends Controller
{

    /**
     * @Route("/dev/", name="dev")
     */
    public function indexAction(Request $request)
    {
        return $this->render('dev/index.html.twig', [
            'data' => null,
            'link' => null,
        ]);
    }
}
