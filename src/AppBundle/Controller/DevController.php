<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Article;

class DevController extends Controller
{

    /**
     * @Route("/dev/", name="dev")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $supplier = $em->getRepository("AppBundle:Supplier")->find(14);

        $article = new Article();

        $article->setSupplier($supplier);

        $article->setMfrName("machin");
        $article->setSku("1236-456-ND");
        $article->setMfrPn("1236-456");
        $article->setPackage("Cut Tape");
        $article->createVariable(1,25, 2, new \DateTime("now + 1 day"), null, array(1 => 0.9, 10 => 0.8));

        $em->persist($article);
        $em->flush();

        return $this->render('dev/index.html.twig', [
            'data' => $article,
            'link' => null,
        ]);
    }
}
