<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

use APIDigikeyBundle\Classes\ApiDigikey;
use AppBundle\Entity\Supplier;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class InterfaceDigikeyController extends Controller
{

    /**
     * @Route("/interface/digikey/edit/{id}", requirements={"id": "\d+"}, name="interface_digikey_edit")
     */
    public function editAction(Supplier $supplier, Request $request)
    {
        if (is_null($supplier->getParameters())) {
            $api = new ApiDigikey();
            $config = $api->getDefaultConfig();
        } else {
            $config = $supplier->getParameters();
        }

        $form = $this->getForm();
        $form->setData($config);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $supplier->setParameters($form->getData());
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.edit.success", array('%supplier%' => $supplier->getName())), "interface");

            return $this->redirectToRoute('srm_suppliers_index');
        }

        return $this->render('interface/digikey/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/interface/digikey/console/{id}", requirements={"id": "\d+"}, name="interface_digikey_console")
     */
    public function consoleAction(Supplier $supplier, Request $request)
    {
        $form = $this->getConsoleForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $api = new ApiDigikey($supplier->getParameters());
            $data = $form->getData();
            switch ($data['path']) {
                case 'keywordSearch':
                    $response = $api->keywordSearch($request->headers->get('User-Agent'), $data['keyword']);
                    break;
                case 'partDetails':
                    $response = $api->partDetailSearch($request->headers->get('User-Agent'), $data['keyword']);
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
    public function revokeAction(Supplier $supplier, Request $request)
    {
        $api = new ApiDigikey($supplier->getParameters());
        $supplier->setParameters($api->revoke());

        $this->getDoctrine()->getManager()->flush();

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.revoke.success", array('%supplier%' => $supplier->getName())), "interface");

        return $this->redirectToRoute('interface_digikey_console', array('id' => $supplier->getId()));
    }

    /**
     * @Route("/interface/digikey/redirect/{id}", requirements={"id": "\d+"}, name="interface_digikey_redirect")
     */
    public function redirectAction(Supplier $supplier, Request $request)
    {
        $api = new ApiDigikey($supplier->getParameters());

        $request->getSession()->set("interface_digikey_code_id", array(
            'id' => $supplier->getId(),
            'local' => $request->getLocale(),
        ));

        return $this->redirect($api->linkLoginPage());
    }

    /**
     * @Route("/interface/digikey/token/{id}", requirements={"id": "\d+"}, name="interface_digikey_token")
     */
    public function tokenAction(Supplier $supplier, Request $request)
    {
        $api = new ApiDigikey($supplier->getParameters());

        $response = $api->retrieveToken($request->headers->get('User-Agent'));

        if (!is_array($response)) {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.token.error", array('%supplier%' => $supplier->getName(), 'error' => $response), "interface"));

            return $this->redirectToRoute('interface_digikey_console', array('id' => $supplier->getId()));
        }

        $supplier->setParameters($response);

        $this->getDoctrine()->getManager()->flush();

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.token.success", array('%supplier%' => $supplier->getName())), "interface");

        return $this->redirectToRoute('interface_digikey_console', array('id' => $supplier->getId()));
    }

    /**
     * @Route("/interface/digikey/refresh/{id}", requirements={"id": "\d+"}, name="interface_digikey_refresh")
     */
    public function refreshAction(Supplier $supplier, Request $request)
    {
        $api = new ApiDigikey($supplier->getParameters());

        $response = $api->refreshToken($request->headers->get('User-Agent'));

        if (!is_array($response)) {
            $request->getSession()->getFlashBag()->add('danger', $this->get('translator')->trans("flash.refresh.error", array('%supplier%' => $supplier->getName(), 'error' => $response), "interface"));

            return $this->redirectToRoute('interface_digikey_console', array('id' => $supplier->getId()));
        }

        $supplier->setParameters($response);

        $this->getDoctrine()->getManager()->flush();

        $request->getSession()->getFlashBag()->add('success', $this->get('translator')->trans("flash.refresh.success", array('%supplier%' => $supplier->getName())), "interface");

        return $this->redirectToRoute('interface_digikey_console', array('id' => $supplier->getId()));
    }

    private function getForm()
    {
        return $this->createFormBuilder()
            ->add('loginPage', TextType::class, array(
                'label' => 'digikey.label.login_page',
                'translation_domain' => 'interface',
            ))
            ->add('redirectUri', TextType::class, array(
                'label' => 'digikey.label.redirect_uri',
                'translation_domain' => 'interface',
            ))
            ->add('clientId', TextType::class, array(
                'label' => 'digikey.label.client_id',
                'translation_domain' => 'interface',
            ))
            ->add('clientSecret', TextType::class, array(
                'label' => 'digikey.label.client_secret',
                'translation_domain' => 'interface',
            ))
            ->add('customerId', TextType::class, array(
                'label' => 'digikey.label.customer_id',
                'translation_domain' => 'interface',
                'required' => false,
            ))
            ->add('tokenPage', TextType::class, array(
                'label' => 'digikey.label.token_page',
                'translation_domain' => 'interface',
            ))
            ->add('keywordSearchUri', TextType::class, array(
                'label' => 'digikey.label.keywordsearch_uri',
                'translation_domain' => 'interface',
            ))
            ->add('partDetailsUri', TextType::class, array(
                'label' => 'digikey.label.partdetails_uri',
                'translation_domain' => 'interface',
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
