<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use APIDigikeyBundle\Service\InterfaceDigikey;
use AppBundle\Entity\Supplier;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class InterfaceDigikeyController extends Controller
{

    /**
     * @Route("/interface/digikey/edit/{id}", requirements={"id": "\d+"}, name="interface_digikey_edit")
     */
    public function editAction(Supplier $supplier, InterfaceDigikey $interface, Request $request)
    {
        $parameters = $interface->getParameters($supplier->getId());

        $form = $this->getForm();
        $form->setData($parameters);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $interface->setParameters($form->getData());

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.edit.success", array('%supplier%' => $supplier->getName()), "interface"));

            return $this->redirectToRoute('srm_suppliers_index');
        }

        return $this->render('interface/digikey/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/interface/digikey/console/{id}", requirements={"id": "\d+"}, name="interface_digikey_console")
     */
    public function consoleAction(Supplier $supplier, InterfaceDigikey $interface, Request $request)
    {
        if (is_null($supplier->getParameters()))
        {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.console.empty_config", array('%supplier%' => $supplier->getName()), "interface"));
            return $this->redirectToRoute('srm_suppliers_index');
        }

        $form = $this->getConsoleForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            switch ($data['path']) {
                case 'keywordSearch':
                    $response = $interface->keywordSearch($request->headers->get('User-Agent'), $data['keyword'], $supplier->getId());
                    break;
                case 'partDetails':
                    $response = $interface->partDetails($request->headers->get('User-Agent'), $data['keyword'], $supplier->getId());
                    break;
                default:
                    $response = null;
            }
        } else {
            $response = null;
        }

        return $this->render('interface/digikey/console.html.twig', array(
            'supplier' => $supplier,
            'response' => print_r($response, true),
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/interface/digikey/revoke/{id}", requirements={"id": "\d+"}, name="interface_digikey_revoke")
     */
    public function revokeAction(Supplier $supplier, InterfaceDigikey $interface, Request $request)
    {
        $interface->revoke($supplier->getId());

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.revoke.success", array('%supplier%' => $supplier->getName()), "interface"));

        return $this->redirectToRoute('interface_digikey_console', array('id' => $supplier->getId()));
    }

    /**
     * @Route("/interface/digikey/redirect/{id}", requirements={"id": "\d+"}, name="interface_digikey_redirect")
     */
    public function redirectAction(Supplier $supplier, InterfaceDigikey $interface, Request $request)
    {
        $request->getSession()->set("interface_digikey_code_id", array(
            'id' => $supplier->getId(),
            'redirect' => array(
                'route' => "interface_digikey_console",
                'params' => array(
                    'id' => $supplier->getId(),
                    'local' => $request->getLocale(),
                )
            )
        ));

        return $this->redirect($interface->linkLoginPage($supplier->getId()));
    }

    /**
     * @Route("/interface/digikey/token/{id}", requirements={"id": "\d+"}, name="interface_digikey_token")
     */
    public function tokenAction(Supplier $supplier, InterfaceDigikey $interface, Request $request)
    {
        $valid = $interface->retrieveToken($request->headers->get('User-Agent'), $supplier->getId());

        if ($valid === true) {
            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.token.success", array('%supplier%' => $supplier->getName()), "interface"));
        } else {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.token.error", array('%supplier%' => $supplier->getName(), 'error' => $valid), "interface"));
        }

        return $this->redirectToRoute('interface_digikey_console', array('id' => $supplier->getId()));
    }

    /**
     * @Route("/interface/digikey/refresh/{id}", requirements={"id": "\d+"}, name="interface_digikey_refresh")
     */
    public function refreshAction(Supplier $supplier, InterfaceDigikey $interface, Request $request)
    {
        $valid = $interface->refreshToken($request->headers->get('User-Agent'), $supplier->getId());

        if ($valid === true) {
            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.token.success", array('%supplier%' => $supplier->getName()), "interface"));
        } else {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.token.error", array('%supplier%' => $supplier->getName(), 'error' => $valid), "interface"));
        }

        return $this->redirectToRoute('interface_digikey_console', array('id' => $supplier->getId()));
    }

    /**
     * @Route("/interface/digikey/signing/{id}", requirements={"id": "\d+"}, name="interface_digikey_signin")
     */
    public function signinAction(Supplier $supplier, InterfaceDigikey $interface, Request $request)
    {
        $request->getSession()->set("interface_digikey_code_id", array(
            'id' => $supplier->getId(),
            'redirect' => array(
                'route' => "srm_suppliers_index",
                'params' => array(
                    'local' => $request->getLocale(),
                )
            )
        ));

        return $this->redirect($interface->linkLoginPage($supplier->getId()));
    }

    private function getForm()
    {
        return $this->createFormBuilder()
            ->add('customerId', TextType::class, array(
                'label' => 'digikey.label.customer_id',
                'translation_domain' => 'interface',
                'required' => false,
            ))
            ->add('localSite', TextType::class, array(
                'label' => 'digikey.label.local_site',
                'translation_domain' => 'interface',
            ))
            ->add('localLanguage', TextType::class, array(
                'label' => 'digikey.label.local_language',
                'translation_domain' => 'interface',
            ))
            ->add('localCurrency', TextType::class, array(
                'label' => 'digikey.label.local_currency',
                'translation_domain' => 'interface',
            ))
            ->add('localShipToCountry', TextType::class, array(
                'label' => 'digikey.label.local_ship_to_country',
                'translation_domain' => 'interface',
            ))
            ->add('partnerId', TextType::class, array(
                'label' => 'digikey.label.partner_id',
                'translation_domain' => 'interface',
                'required' => false,
            ))
            ->getForm();
    }

    private function getConsoleForm()
    {
        return $this->createFormBuilder()
            ->add('path', ChoiceType::class, array(
                'label' => 'form.path.label',
                'translation_domain' => 'interface',
                'required' => true,
                'choices' => array(
                    'form.keywordsearch.label' => 'keywordSearch',
                    'form.partdetails.label' => 'partDetails',
                ),
                'data' => 'keywordSearch',
                'expanded' => true,
                'multiple' => false,
            ))
            ->add('keyword', TextType::class, array(
                'label' => 'form.search.label',
                'translation_domain' => 'interface',
                'required' => true,
            ))
            ->getForm();
    }
}
