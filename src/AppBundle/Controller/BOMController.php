<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Bom;
use AppBundle\Form\BomType;

class BomController extends Controller
{
    /**
     * @Route("/bom", defaults={"page": "1"}, name="bom")
     * @Route("/bom/{page}", requirements={"page": "\d*"}, defaults={"page": "1"}, name="bom_index")
     */
    public function indexAction($page, $nbPerPage = Bom::NB_PER_PAGE)
    {
        $list = $this->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Bom')
            ->getBomsPerPage($page, $nbPerPage);

        $nbPages = ceil(count($list) / $nbPerPage);

        if ($page > $nbPages && $page > 1 || $page == 0) {
            throw $this->createNotFoundException($this->get('translator')->trans("controler.page.unknow", array('%page%' => $page)));
        }

        return $this->render('bom/index.html.twig', [
            'list' => $list->getIterator(),
            'total' => count($list),
            'nbPages' => $nbPages,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/bom/new", name="bom_new")
     */
    public function newAction(Request $request)
    {
        $bom = new Bom();

        $form = $this->createForm(BomType::class, $bom);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($bom);
            $em->flush();

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.bom.new.success", array('%bom%' => $bom->getName())));

        return $this->redirectToRoute('bom_manage', array('id' => $bom->getId()));
    }

        return $this->render('bom/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/bom/edit/{id}", requirements={"id": "\d+"}, name="bom_edit")
     */
    public function editAction(Bom $bom, Request $request)
    {
        $form = $this->createForm(BomType::class, $bom);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.bom.edit.success", array('%bom%' => $bom->getName())));

            return $this->redirectToRoute('bom_index');
        }

        return $this->render('bom/edit.html.twig', array(
            'form' => $form->createView(),
            '$bom' => $bom,
        ));
    }

    /**
     * @Route("/bom/delete/{id}", requirements={"id": "\d+"}, name="bom_delete")
     */
    public function deleteAction(Bom $bom, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.bom.delete.success", array('%bom%' => $bom->getName())));

        $em = $this->getDoctrine()->getManager();
        $em->remove($bom);
        $em->flush();

        return $this->redirectToRoute('bom_index');
    }

    /**
     * @Route("/bom/manage/{id}", requirements={"id": "\d+"}, name="bom_manage")
     */
    public function manageAction($id)
    {
        $bom = $this->getDoctrine()->getRepository('AppBundle:Bom')->getBomLinesAlternatives($id);

        return $this->render('bom/manage.html.twig', array(
            'bom' => $bom,
        ));
    }
}
