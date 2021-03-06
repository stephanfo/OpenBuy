<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Supplier;
use AppBundle\Form\SupplierType;

class SupplierController extends Controller
{
    /**
     * @Route("/srm/suppliers/{page}", requirements={"page": "\d*"}, defaults={"page": "1"}, name="srm_suppliers_index")
     */
    public function indexAction($page, $nbPerPage = Supplier::NB_PER_PAGE)
    {
        $listSuppliers = $this->getDoctrine()
            ->getManager()
            ->getRepository(Supplier::class)
            ->getSuppliersPerPage($page, $nbPerPage);

        $nbPages = ceil(count($listSuppliers) / $nbPerPage);

        if ($page > $nbPages && $page > 1 || $page == 0) {
            throw $this->createNotFoundException($this->get('translator')->trans("controler.page.unknow", array('%page%' => $page)));
        }

        return $this->render('srm/supplier/index.html.twig', [
            'list' => $listSuppliers->getIterator(),
            'total' => count($listSuppliers),
            'nbPages' => $nbPages,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/srm/suppliers/new", name="srm_suppliers_new")
     */
    public function newAction(Request $request)
    {
        $supplier = new Supplier();

        $form = $this->createForm(SupplierType::class, $supplier);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $supplier->setEnabled(true);
            $supplier->setUser($this->getUser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($supplier);
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.supplier.new.success", array('%supplier%' => $supplier->getName())));

            if (is_null($supplier->getInterface()))
                return $this->redirectToRoute('srm_suppliers_index');
            else
                return $this->redirectToRoute('interface_'. $supplier->getInterface() . '_edit', array('id' => $supplier->getId()));
        }

        return $this->render('srm/supplier/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/srm/suppliers/edit/{id}", requirements={"id": "\d+"}, name="srm_suppliers_edit")
     */
    public function editAction(Supplier $supplier, Request $request)
    {
        $previous_interface = $supplier->getInterface();

        $form = $this->createForm(SupplierType::class, $supplier);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (is_null($supplier->getInterface()) || $previous_interface !== $supplier->getInterface())
                $supplier->setParameters(null);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.supplier.edit.success", array('%supplier%' => $supplier->getName())));

            if (is_null($supplier->getInterface()))
                return $this->redirectToRoute('srm_suppliers_index');
            else
                return $this->redirectToRoute('interface_'. $supplier->getInterface() . '_edit', array('id' => $supplier->getId()));
        }

        return $this->render('srm/supplier/edit.html.twig', array(
            'form' => $form->createView(),
            '$supplier' => $supplier,

        ));
    }

    /**
     * @Route("/srm/suppliers/delete/{id}", requirements={"id": "\d+"}, name="srm_suppliers_delete")
     */
    public function deleteAction(Supplier $supplier, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.supplier.delete.success", array('%supplier%' => $supplier->getName())));

        $em = $this->getDoctrine()->getManager();
        $em->remove($supplier);
        $em->flush();

        return $this->redirectToRoute('srm_suppliers_index');
    }

    /**
     * @Route("/srm/suppliers/toggle/enable/{id}", requirements={"id": "\d+"}, name="srm_suppliers_enable")
     */
    public function enableToggleAction(Supplier $supplier, Request $request)
    {
        if($supplier->getEnabled())
        {
            $supplier->setEnabled(false);
            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.supplier.disable.success", array('%supplier%' => $supplier->getName())));
        }
        else
        {
            $supplier->setEnabled(true);
            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.supplier.enable.success", array('%supplier%' => $supplier->getName())));
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('srm_suppliers_index');
    }

    /**
     * @Route("/srm/suppliers/copy/{id}", requirements={"id": "\d+"}, name="srm_suppliers_copy")
     */
    public function copyAction(Supplier $supplier, Request $request)
    {
        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.supplier.copy.success", array('%supplier%' => $supplier->getName())));

        $newSupplier = new Supplier();
        $newSupplier->setName($supplier->getName() . " Copy");
        $newSupplier->setAddressLine1($supplier->getAddressLine1());
        $newSupplier->setAddressLine2($supplier->getAddressLine2());
        $newSupplier->setAddressLine3($supplier->getAddressLine3());
        $newSupplier->setPostcode($supplier->getPostcode());
        $newSupplier->setCity($supplier->getCity());
        $newSupplier->setState($supplier->getState());
        $newSupplier->setCountry($supplier->getCountry());
        $newSupplier->setCurrency($supplier->getCurrency());
        $newSupplier->setExchangeRate($supplier->getExchangeRate());
        $newSupplier->setEnabled($supplier->getEnabled());
        $newSupplier->setInterface($supplier->getInterface());
        $newSupplier->setParameters($supplier->getParameters());
        $newSupplier->setUser($this->getUser());

        $em = $this->getDoctrine()->getManager();
        $em->persist($newSupplier);
        $em->flush();

        return $this->redirectToRoute('srm_suppliers_index');
    }
}
