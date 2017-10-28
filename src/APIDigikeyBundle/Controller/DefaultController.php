<?php

namespace APIDigikeyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use APIDigikeyBundle\Service\ApiDigikey;

class DefaultController extends Controller
{
    /**
     * @Route("/code", name="api_digikey_code")
     */
    public function codeAction(Request $request)
    {
        $session = $request->getSession();

        if (!$session->has("interface_digikey_code_id")) {
            return new Response($this->get('translator')->trans("error.bad_id", array(), "interface"), Response::HTTP_BAD_REQUEST);
        }

        $code = $request->query->get('code');

        if (!is_string($code)) {
            return new Response($this->get('translator')->trans("error.bad_code", array(), "interface"), Response::HTTP_BAD_REQUEST);
        }

        $params = $session->get("interface_digikey_code_id");
        $id = $params['id'];
        $locale = $params['local'];
        $session->remove("interface_digikey_code_id");

        $supplier = $this->getDoctrine()->getRepository('AppBundle:Supplier')->find($id);

        $api = new ApiDigikey($supplier->getParameters());
        $supplier->setParameters($api->setCode($code));

        $this->getDoctrine()->getManager()->flush();

        $session->getFlashBag()->add('success', $this->get('translator')->trans("flash.interface.code.success", array('%supplier%' => $supplier->getName())), "api_digikey");

        return $this->redirectToRoute('interface_digikey_console', array(
            'id' => $supplier->getId(),
            '_locale' => $locale
        ));
    }
}
