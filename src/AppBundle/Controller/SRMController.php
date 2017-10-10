<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class SrmController extends Controller
{
    /**
     * @Route("/srm/", name="srm")
     */
    public function indexAction()
    {
        return $this->render('srm/index.html.twig');
    }
}
