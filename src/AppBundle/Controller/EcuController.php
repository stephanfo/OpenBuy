<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Ecu;
use AppBundle\Form\EcuType;

class EcuController extends Controller
{
   /**
     * @Route("/crm/ecu/{page}", requirements={"page": "\d*"}, defaults={"page": "1"}, name="crm_ecus_index")
     */
    public function indexAction($page, $nbPerPage = Ecu::NB_PER_PAGE)
    {
        $list = $this->getDoctrine()
            ->getManager()
            ->getRepository('AppBundle:Ecu')
            ->getEcusPerPage($page, $nbPerPage);

        $nbPages = ceil(count($list) / $nbPerPage);

        if ($page > $nbPages && $page > 1 || $page == 0) {
            throw $this->createNotFoundException($this->get('translator')->trans("controler.page.unknow", array('%page%' => $page)));
        }

        return $this->render('crm/ecu/index.html.twig', [
            'list' => $list->getIterator(),
            'total' => count($list),
            'nbPages' => $nbPages,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/crm/ecu/new", name="crm_ecus_new")
     */
    public function newAction(Request $request)
    {
        $ecu = new Ecu();

        $form = $this->createForm(EcuType::class, $ecu);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($ecu);
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.ecu.new.success", array('%ecu%' => $ecu->getName())));

            return $this->redirectToRoute('crm_ecus_index');
        }

        return $this->render('crm/ecu/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/crm/ecu/edit/{id}", requirements={"id": "\d+"}, name="crm_ecus_edit")
     */
    public function editAction(Ecu $ecu, Request $request)
    {
        $form = $this->createForm(EcuType::class, $ecu);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.ecu.edit.success", array('%ecu%' => $ecu->getName())));

            return $this->redirectToRoute('crm_ecus_index');
        }

        return $this->render('crm/ecu/edit.html.twig', array(
            'form' => $form->createView(),
            '$ecu' => $ecu,
        ));
    }

    /**
     * @Route("/crm/ecu/delete/{id}", requirements={"id": "\d+"}, name="crm_ecus_delete")
     */
    public function deleteAction(Ecu $ecu, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.ecu.delete.success", array('%ecu%' => $ecu->getName())));

        $em = $this->getDoctrine()->getManager();
        $em->remove($ecu);
        $em->flush();

        return $this->redirectToRoute('crm_ecus_index');
    }

}
