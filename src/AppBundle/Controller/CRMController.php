<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class CrmController extends Controller
{
    /**
     * @Route("/crm/", name="crm")
     */
    public function indexAction()
    {
        return $this->render('crm/index.html.twig');
    }
}
