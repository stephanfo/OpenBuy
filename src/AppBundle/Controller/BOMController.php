<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class BomController extends Controller
{
    /**
     * @Route("/bom/", name="bom")
     */
    public function indexAction()
    {
        return $this->render('bom/index.html.twig');
    }
}
